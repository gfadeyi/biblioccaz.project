<?php
require_once __DIR__ . '/droits_equipe_lib.php';

const OBJETS_DECISIONS_EQUIPE = [
    'livre' => ['Fiche livre', 'moderation', 'moderation_livres.php'],
    'archive' => ['Archive du catalogue', 'catalogue', 'livres.php'],
    'annonce' => ['Annonce', 'moderation', 'annonces.php'],
    'don' => ['Don', 'moderation', 'dons.php'],
    'avis' => ['Avis', 'moderation', 'moderation_avis.php'],
    'evenement' => ['Événement', 'evenements', 'evenements.php'],
    'candidature_equipe' => ['Candidature équipe', 'admin', 'candidatures.php'],
];
const ACTIONS_DECISIONS_EQUIPE = [
    'valider' => 'Validation', 'refuser' => 'Refus', 'modifier' => 'Modification',
    'archiver' => 'Archivage', 'reactiver' => 'Réactivation', 'retirer' => 'Retrait',
    'annuler' => 'Annulation', 'liberer' => 'Libération', 'masquer' => 'Masquage',
    'afficher' => 'Réaffichage', 'classer' => 'Classement sans suite', 'supprimer' => 'Suppression',
];

function historiqueDecisionsDisponible(PDO $pdo): bool
{
    try {
        $pdo->prepare('SELECT id_decision FROM equipe_decision LIMIT 0')->execute();
        return true;
    } catch (PDOException $erreur) {
        if ((int) ($erreur->errorInfo[1] ?? 0) !== 1146) { throw $erreur; }
        return false;
    }
}

function enregistrerDecisionEquipe(PDO $pdo, int $idActeur, string $type, int $idObjet, string $action, string $libelle, string $motif = '', ?string $ancienStatut = null, ?string $nouveauStatut = null): bool
{
    if (!isset(OBJETS_DECISIONS_EQUIPE[$type], ACTIONS_DECISIONS_EQUIPE[$action]) || $idObjet < 1 || mb_strlen($motif) > 5000) {
        throw new InvalidArgumentException('Décision invalide.');
    }
    if (!$pdo->inTransaction()) { throw new LogicException('Une décision doit accompagner une transaction.'); }
    $requete = $pdo->prepare('SELECT pseudo, role, statut, email_verifie FROM `user` WHERE id = ? FOR UPDATE');
    $requete->execute([$idActeur]);
    $acteur = $requete->fetch(PDO::FETCH_ASSOC);
    if (!$acteur || !in_array($acteur['role'], ['admin', 'gestionnaire', 'moderateur'], true)
        || !in_array($acteur['statut'], ['actif', 'valide_moderateur'], true) || !(int) $acteur['email_verifie']
        || !rolePossedeDroit($pdo, $acteur['role'], OBJETS_DECISIONS_EQUIPE[$type][1], DROITS_EQUIPE_DEFAUT)) {
        throw new DomainException('Votre compte ne permet pas cette décision.');
    }
    if (!historiqueDecisionsDisponible($pdo)) { return false; }
    $pdo->prepare('INSERT INTO equipe_decision (id_acteur, pseudo_acteur, type_objet, id_objet, libelle_objet, action, motif, ancien_statut, nouveau_statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$idActeur, $acteur['pseudo'], $type, $idObjet, mb_substr($libelle, 0, 255), $action, trim($motif) !== '' ? trim($motif) : null, $ancienStatut, $nouveauStatut]);
    return true;
}

function typesDecisionsAutorises(PDO $pdo, string $role, array $droits): array
{
    return array_keys(array_filter(OBJETS_DECISIONS_EQUIPE, fn($objet) => rolePossedeDroit($pdo, $role, $objet[1], $droits)));
}

function libelleStatutDecision(?string $statut): string
{
    return match ($statut) {
        null => '—', 'en_validation' => 'À valider', 'en_attente' => 'En attente',
        'publie', 'publiee' => 'Publié', 'archivee' => 'Archivé', 'acceptee' => 'Accepté',
        'refuse', 'refusee' => 'Refusé', 'visible' => 'Visible', 'masque' => 'Masqué',
        'supprime' => 'Supprimé', 'retiree' => 'Retiré', 'annule' => 'Annulé',
        'disponible' => 'Disponible', 'reserve' => 'Réservé', default => str_replace('_', ' ', $statut),
    };
}

function historiqueDecisionsEquipe(PDO $pdo, array $typesAutorises, array $filtres = [], int $page = 1): array
{
    $types = array_values(array_intersect(array_keys(OBJETS_DECISIONS_EQUIPE), $typesAutorises));
    if (!$types || !historiqueDecisionsDisponible($pdo)) { return ['total' => 0, 'decisions' => []]; }
    $conditions = ['type_objet IN (' . implode(',', array_fill(0, count($types), '?')) . ')'];
    $parametres = $types;
    if (isset(OBJETS_DECISIONS_EQUIPE[$filtres['type'] ?? ''])) { $conditions[] = 'type_objet = ?'; $parametres[] = $filtres['type']; }
    if (isset(ACTIONS_DECISIONS_EQUIPE[$filtres['action'] ?? ''])) { $conditions[] = 'action = ?'; $parametres[] = $filtres['action']; }
    if (($filtres['acteur'] ?? 0) > 0) { $conditions[] = 'id_acteur = ?'; $parametres[] = (int) $filtres['acteur']; }
    foreach (['depuis' => '>=', 'jusqua' => '<'] as $champ => $operateur) {
        $date = $filtres[$champ] ?? '';
        $jour = is_string($date) ? DateTimeImmutable::createFromFormat('!Y-m-d', $date) : false;
        if ($jour && $jour->format('Y-m-d') === $date) {
            $conditions[] = 'date_decision ' . $operateur . ' ?';
            $parametres[] = ($champ === 'jusqua' ? $jour->modify('+1 day') : $jour)->format('Y-m-d H:i:s');
        }
    }
    $texte = mb_substr(trim(is_string($filtres['q'] ?? null) ? $filtres['q'] : ''), 0, 100);
    if ($texte !== '') {
        $conditions[] = "(libelle_objet LIKE ? ESCAPE '!' OR pseudo_acteur LIKE ? ESCAPE '!' OR motif LIKE ? ESCAPE '!')";
        $motif = '%' . strtr($texte, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        array_push($parametres, $motif, $motif, $motif);
    }
    $where = implode(' AND ', $conditions);
    $compte = $pdo->prepare('SELECT COUNT(*) FROM equipe_decision WHERE ' . $where);
    $compte->execute($parametres);
    $total = (int) $compte->fetchColumn();
    $page = min(max(1, $page), max(1, (int) ceil($total / 30)));
    $requete = $pdo->prepare('SELECT * FROM equipe_decision WHERE ' . $where . ' ORDER BY date_decision DESC, id_decision DESC LIMIT 30 OFFSET ' . (($page - 1) * 30));
    $requete->execute($parametres);
    return ['total' => $total, 'decisions' => $requete->fetchAll(PDO::FETCH_ASSOC), 'page' => $page];
}

function traiterFicheEquipe(PDO $pdo, int $idLivre, int $idActeur, string $action, string $motif = '', string $description = ''): array
{
    require_once __DIR__ . '/archivage_catalogue_lib.php';
    if (!in_array($action, ['valider', 'modifier'], true) || mb_strlen($motif) > 5000 || mb_strlen($description) > 20000) { return ['warning', 'Action ou texte invalide.']; }
    try {
        $pdo->beginTransaction();
        $requete = $pdo->prepare('SELECT * FROM livre WHERE id_livre = ? FOR UPDATE');
        $requete->execute([$idLivre]);
        $livre = $requete->fetch(PDO::FETCH_ASSOC);
        if (!$livre || (int) $livre['is_valide'] !== 0) { throw new DomainException('Cette fiche a déjà été traitée ou n’existe plus.'); }
        $annonce = $pdo->prepare('SELECT 1 FROM exemplaire WHERE id_livre = ? AND id_proprietaire IS NOT NULL LIMIT 1');
        $annonce->execute([$idLivre]);
        if ($annonce->fetchColumn() || livreEstArchive($pdo, $idLivre)) { throw new DomainException('Cette fiche doit être traitée dans sa file dédiée.'); }
        if ($action === 'modifier' && $description === (string) $livre['description']) { throw new DomainException('La description est inchangée.'); }
        $pdo->prepare($action === 'valider' ? 'UPDATE livre SET is_valide = 1 WHERE id_livre = ?' : 'UPDATE livre SET description = ? WHERE id_livre = ?')
            ->execute($action === 'valider' ? [$idLivre] : [$description, $idLivre]);
        enregistrerDecisionEquipe($pdo, $idActeur, 'livre', $idLivre, $action, $livre['titre'], $motif, 'en_validation', $action === 'valider' ? 'publiee' : 'en_validation');
        $pdo->commit();
        insertLog('MODERATION', $action . ' : fiche livre #' . $idLivre);
        return ['success', $action === 'valider' ? 'Fiche validée : elle est maintenant visible dans le catalogue.' : 'Description enregistrée.'];
    } catch (Throwable $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        if (!$erreur instanceof DomainException) { error_log('Décision sur une fiche : ' . $erreur->getMessage()); }
        return [$erreur instanceof DomainException ? 'warning' : 'danger', $erreur instanceof DomainException ? $erreur->getMessage() : 'Aucune modification : la décision n’a pas pu être enregistrée.'];
    }
}

function traiterAvisEquipe(PDO $pdo, int $idAvis, int $idActeur, string $action, string $motif = ''): array
{
    require_once __DIR__ . '/points_lib.php';
    if (!in_array($action, ['valider', 'refuser', 'masquer', 'afficher', 'classer', 'supprimer'], true) || mb_strlen($motif) > 5000) { return ['warning', 'Action ou motif invalide.']; }
    try {
        $pdo->beginTransaction();
        $requete = $pdo->prepare('SELECT a.*, l.titre AS titre_livre FROM avis a JOIN livre l ON l.id_livre = a.id_livre WHERE a.id_avis = ? FOR UPDATE');
        $requete->execute([$idAvis]);
        $avis = $requete->fetch(PDO::FETCH_ASSOC);
        if (!$avis) { throw new DomainException('Cet avis n’existe plus.'); }
        if (in_array($action, ['valider', 'refuser'], true) && ($avis['date_validation'] !== null || $avis['statut'] !== 'visible')) { throw new DomainException('Cet avis a déjà été traité.'); }
        if (($action === 'masquer' && $avis['statut'] === 'masque') || ($action === 'afficher' && $avis['statut'] === 'visible')) { throw new DomainException('Cet avis est déjà dans cet état.'); }
        $statut = match ($action) { 'refuser', 'masquer' => 'masque', 'afficher', 'valider' => 'visible', 'supprimer' => 'supprime', default => $avis['statut'] };
        if ($action === 'supprimer') {
            $acteur = $pdo->prepare('SELECT role FROM `user` WHERE id = ?');
            $acteur->execute([$idActeur]);
            if ($acteur->fetchColumn() !== 'admin') { throw new DomainException('Seul un administrateur peut supprimer un avis.'); }
            $pdo->prepare('DELETE FROM avis WHERE id_avis = ?')->execute([$idAvis]);
            changerStatutMouvement($pdo, (int) $avis['id_user'], 'avis', 'livre:' . $avis['id_livre'], ['en_attente', 'valide'], 'annule');
        } elseif ($action === 'classer') {
            $classement = $pdo->prepare('UPDATE avis_signalement SET traite = 1 WHERE id_avis = ? AND traite = 0');
            $classement->execute([$idAvis]);
            if (!$classement->rowCount()) { throw new DomainException('Aucun signalement à classer pour cet avis.'); }
        } else {
            if (in_array($action, ['valider', 'refuser'], true)) {
                $pdo->prepare('UPDATE avis SET statut = ?, date_validation = NOW(), id_validateur = ? WHERE id_avis = ?')->execute([$statut, $idActeur, $idAvis]);
            } else {
                $pdo->prepare('UPDATE avis SET statut = ? WHERE id_avis = ?')->execute([$statut, $idAvis]);
                $pdo->prepare('UPDATE avis_signalement SET traite = 1 WHERE id_avis = ?')->execute([$idAvis]);
            }
            if ($action === 'valider') {
                validerPointsAvis($pdo, (int) $avis['id_user'], (int) $avis['id_livre']);
                verifierBadges($pdo, (int) $avis['id_user']);
            } elseif (in_array($action, ['refuser', 'masquer'], true)) {
                changerStatutMouvement($pdo, (int) $avis['id_user'], 'avis', 'livre:' . $avis['id_livre'], ['en_attente'], 'refuse');
            }
        }
        enregistrerDecisionEquipe($pdo, $idActeur, 'avis', $idAvis, $action, $avis['titre_livre'], $motif, $avis['statut'], $statut);
        $pdo->commit();
        insertLog('MODERATION', $action . ' : avis #' . $idAvis);
        return ['success', match ($action) {
            'valider' => 'Avis validé et points éventuels crédités.', 'refuser' => 'Avis refusé : il est masqué et ses points ne seront pas crédités.',
            'masquer' => 'Avis masqué (il reste archivé).', 'afficher' => 'Avis de nouveau visible.', 'supprimer' => 'Avis supprimé définitivement.', default => 'Signalements classés sans suite.',
        }];
    } catch (Throwable $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        if (!$erreur instanceof DomainException) { error_log('Décision sur un avis : ' . $erreur->getMessage()); }
        return [$erreur instanceof DomainException ? 'warning' : 'danger', $erreur instanceof DomainException ? $erreur->getMessage() : 'Aucune modification : la décision n’a pas pu être enregistrée.'];
    }
}
