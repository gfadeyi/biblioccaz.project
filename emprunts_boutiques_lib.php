<?php
require_once __DIR__ . '/boutiques_lib.php';

function empruntsBoutiquesDisponibles(PDO $pdo): bool
{
    static $cache = null;
    $cache ??= new WeakMap();
    if (!isset($cache[$pdo])) {
        try {
            $r = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'id_boutique' AND TABLE_NAME IN ('exemplaire', 'emprunt', 'emprunt_attente', 'boutique', 'boutique_equipe')");
            $r->execute();
            $cache[$pdo] = (int) $r->fetchColumn() === 5;
        } catch (PDOException $e) { $cache[$pdo] = false; }
    }
    return $cache[$pdo];
}

function boutiquesEmprunt(PDO $pdo, bool $actives = true): array
{
    if (!empruntsBoutiquesDisponibles($pdo)) { return []; }
    $r = $pdo->prepare('SELECT * FROM boutique' . ($actives ? ' WHERE actif = 1' : '') . ' ORDER BY ville, id_boutique');
    $r->execute();
    return $r->fetchAll(PDO::FETCH_ASSOC);
}

function boutiqueEmprunt(PDO $pdo, int $id): ?array
{
    if (!empruntsBoutiquesDisponibles($pdo)) { return null; }
    $r = $pdo->prepare('SELECT * FROM boutique WHERE id_boutique = ?');
    $r->execute([$id]);
    return $r->fetch(PDO::FETCH_ASSOC) ?: null;
}

function comptoirEquipe(PDO $pdo, int $idUser): ?array
{
    if (!empruntsBoutiquesDisponibles($pdo)) { return null; }
    $r = $pdo->prepare('SELECT b.* FROM boutique_equipe be INNER JOIN boutique b ON b.id_boutique = be.id_boutique WHERE be.id_user = ?');
    $r->execute([$idUser]);
    return $r->fetch(PDO::FETCH_ASSOC) ?: null;
}

function perimetreBoutiqueEquipe(PDO $pdo, int $idUser): ?int
{
    $r = $pdo->prepare('SELECT role FROM user WHERE id = ?'); $r->execute([$idUser]);
    if ($r->fetchColumn() === 'admin') { return null; }
    return (int) (comptoirEquipe($pdo, $idUser)['id_boutique'] ?? -1);
}

function erreurComptoirEmprunt(PDO $pdo, array $objet, int $idEquipe): string
{
    if (!empruntsBoutiquesDisponibles($pdo)) { return ''; }
    $r = $pdo->prepare('SELECT role, statut FROM user WHERE id = ?' . ($pdo->inTransaction() ? ' FOR UPDATE' : ''));
    $r->execute([$idEquipe]); $membre = $r->fetch(PDO::FETCH_ASSOC);
    if (!$membre || $membre['statut'] !== 'actif' || !in_array($membre['role'], ['admin', 'gestionnaire', 'moderateur'], true)) { return 'Action réservée à une équipe autorisée.'; }
    if ($membre['role'] === 'admin') { return ''; }
    $r = $pdo->prepare('SELECT id_boutique FROM boutique_equipe WHERE id_user = ?' . ($pdo->inTransaction() ? ' FOR UPDATE' : ''));
    $r->execute([$idEquipe]); $id = (int) $r->fetchColumn();
    if ($id < 1) { return 'Un administrateur doit vous affecter à une boutique avant de traiter les emprunts.'; }
    return $id === (int) ($objet['id_boutique'] ?? 0) ? '' : 'Cet exemplaire ou cet emprunt dépend d’un autre comptoir. Retrait et retour se font dans la même boutique.';
}

function libelleBoutiqueEmprunt(array $emprunt): string
{
    return trim((string) ($emprunt['boutique_nom'] ?? '')) ?: 'Comptoir d’origine à confirmer auprès de l’équipe';
}

function informationsBoutiqueEmprunt(array $emprunt): string
{
    $lignes = [libelleBoutiqueEmprunt($emprunt)];
    if (!empty($emprunt['boutique_adresse'])) { $lignes[] = $emprunt['boutique_adresse']; }
    if (!empty($emprunt['boutique_horaires'])) { $lignes[] = 'Horaires : ' . $emprunt['boutique_horaires']; }
    if (empty($emprunt['boutique_adresse']) || empty($emprunt['boutique_horaires'])) { $lignes[] = 'Modalités pratiques à confirmer auprès de l’équipe avant le déplacement. Réseau de démonstration.'; }
    return implode("\n", $lignes);
}

function affecterMembreBoutique(PDO $pdo, int $idUser, int $idBoutique): array
{
    $r = $pdo->prepare("SELECT role, statut FROM user WHERE id = ? AND role IN ('gestionnaire', 'moderateur')"); $r->execute([$idUser]);
    $membre = $r->fetch(PDO::FETCH_ASSOC);
    if (!$membre || $membre['statut'] !== 'actif') { return ['warning', 'Membre d’équipe actif introuvable ou compte protégé.']; }
    if ($idBoutique === 0) {
        $pdo->prepare('DELETE FROM boutique_equipe WHERE id_user = ?')->execute([$idUser]);
    } else {
        $b = boutiqueEmprunt($pdo, $idBoutique);
        if (!$b || !(int) $b['actif']) { return ['warning', 'Choisissez une boutique active.']; }
        $pdo->prepare('INSERT INTO boutique_equipe (id_user, id_boutique) VALUES (?, ?) ON DUPLICATE KEY UPDATE id_boutique = VALUES(id_boutique)')->execute([$idUser, $idBoutique]);
    }
    insertLog('ADMIN', 'Affectation du comptoir du membre #' . $idUser);
    return ['success', 'Affectation du comptoir enregistrée. Les droits habituels du membre restent nécessaires.'];
}

function modifierInformationsBoutique(PDO $pdo, int $idBoutique, string $adresse, string $horaires): array
{
    if (mb_strlen($adresse) > 255 || mb_strlen($horaires) > 255 || !boutiqueEmprunt($pdo, $idBoutique)) { return ['warning', 'Boutique inconnue ou informations trop longues (255 caractères maximum).']; }
    $pdo->prepare('UPDATE boutique SET adresse = ?, horaires = ? WHERE id_boutique = ?')->execute([trim($adresse) ?: null, trim($horaires) ?: null, $idBoutique]);
    insertLog('ADMIN', 'Informations du comptoir #' . $idBoutique . ' modifiées');
    return ['success', 'Informations pratiques enregistrées.'];
}

function affecterExemplaireBoutique(PDO $pdo, int $idExemplaire, int $idBoutique, int $idEquipe): array
{
    $boutique = boutiqueEmprunt($pdo, $idBoutique);
    if (!$boutique || !(int) $boutique['actif']) { return ['warning', 'Choisissez une boutique active.']; }
    $pdo->beginTransaction();
    try {
        $livre = $pdo->prepare('SELECT id_livre FROM exemplaire WHERE id_exemplaire = ?');
        $livre->execute([$idExemplaire]);
        verrouillerLivreEmprunt($pdo, (int) $livre->fetchColumn());
        $r = $pdo->prepare("SELECT * FROM exemplaire WHERE id_exemplaire = ? AND type_offre = 'emprunt' AND id_proprietaire IS NULL FOR UPDATE");
        $r->execute([$idExemplaire]); $ex = $r->fetch(PDO::FETCH_ASSOC);
        if (!$ex) { return ['warning', 'Exemplaire introuvable.']; }
        if (($erreur = erreurComptoirEmprunt($pdo, $ex, $idEquipe)) !== '') { return ['warning', $erreur]; }
        if ($ex['statut_pret'] !== 'disponible') { return ['warning', 'Un exemplaire réservé ou emprunté ne peut pas changer de boutique.']; }
        $r = $pdo->prepare("SELECT id_emprunt FROM emprunt WHERE id_exemplaire = ? AND statut IN ('reserve', 'en_cours') FOR UPDATE"); $r->execute([$idExemplaire]);
        if ($r->fetchColumn()) { return ['warning', 'Un prêt actif empêche cette affectation.']; }
        if ($ex['id_boutique'] !== null && (int) $ex['id_boutique'] !== $idBoutique) { return ['warning', 'Les transferts entre boutiques ne sont pas proposés.']; }
        $pdo->prepare('UPDATE exemplaire SET id_boutique = ? WHERE id_exemplaire = ?')->execute([$idBoutique, $idExemplaire]);
        insertLog('EMPRUNT', 'Exemplaire #' . $idExemplaire . ' affecté à la boutique #' . $idBoutique);
        return ['success', 'Boutique de l’exemplaire enregistrée.'];
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
    finally { if ($pdo->inTransaction()) { $pdo->commit(); } }
}

function qualifierAncienEmpruntBoutique(PDO $pdo, int $idEmprunt, int $idBoutique, int $idEquipe): array
{
    if (perimetreBoutiqueEquipe($pdo, $idEquipe) !== null || !boutiqueEmprunt($pdo, $idBoutique)) { return ['warning', 'Qualification réservée à l’administrateur avec une boutique valide.']; }
    $pdo->beginTransaction();
    try {
        $em = empruntParId($pdo, $idEmprunt, true);
        if (!$em || !in_array($em['statut'], ['reserve', 'en_cours'], true) || $em['id_boutique'] !== null) { return ['warning', 'Seul un ancien prêt actif sans boutique peut être qualifié.']; }
        $r = $pdo->prepare('SELECT id_boutique FROM exemplaire WHERE id_exemplaire = ? FOR UPDATE'); $r->execute([$em['id_exemplaire']]); $affectation = $r->fetchColumn();
        if ($affectation !== null && (int) $affectation !== $idBoutique) { return ['warning', 'La boutique du prêt doit correspondre à celle de son exemplaire.']; }
        $pdo->prepare('UPDATE emprunt SET id_boutique = ? WHERE id_emprunt = ? AND id_boutique IS NULL')->execute([$idBoutique, $idEmprunt]);
        $pdo->prepare('UPDATE exemplaire SET id_boutique = ? WHERE id_exemplaire = ? AND id_boutique IS NULL')->execute([$idBoutique, $em['id_exemplaire']]);
        insertLog('EMPRUNT', 'Comptoir d’origine qualifié pour le prêt #' . $idEmprunt . ' : boutique #' . $idBoutique);
        return ['success', 'Boutique d’origine renseignée, sans déplacement du livre.'];
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
    finally { if ($pdo->inTransaction()) { $pdo->commit(); } }
}

function affecterStockDisponibleBoutique(PDO $pdo, int $idLivre, int $idBoutique, int $idEquipe): array
{
    require_once __DIR__ . '/emprunts_lib.php';
    $boutique = boutiqueEmprunt($pdo, $idBoutique);
    if (perimetreBoutiqueEquipe($pdo, $idEquipe) !== null || !$boutique || !(int) $boutique['actif']) { return ['warning', 'Affectation initiale réservée à un administrateur et une boutique active.']; }
    $pdo->beginTransaction();
    try {
        verrouillerLivreEmprunt($pdo, $idLivre);
        $r = $pdo->prepare("SELECT e.id_exemplaire FROM exemplaire e WHERE e.id_livre = ? AND e.type_offre = 'emprunt' AND e.id_proprietaire IS NULL AND e.id_boutique IS NULL AND e.statut_pret = 'disponible' AND NOT EXISTS (SELECT 1 FROM emprunt em WHERE em.id_exemplaire = e.id_exemplaire AND em.statut IN ('reserve', 'en_cours')) FOR UPDATE");
        $r->execute([$idLivre]); $ids = $r->fetchAll(PDO::FETCH_COLUMN);
        $attributions = [];
        foreach ($ids as $id) {
            $pdo->prepare('UPDATE exemplaire SET id_boutique = ? WHERE id_exemplaire = ? AND id_boutique IS NULL')->execute([$idBoutique, $id]);
            if ($attribution = attribuerExemplaireLibere($pdo, (int) $id)) { $attributions[] = $attribution; }
        }
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
    foreach ($attributions as $attribution) { prevenirAttribution($pdo, $attribution); }
    insertLog('EMPRUNT', 'Affectation initiale de ' . count($ids) . ' exemplaires du livre #' . $idLivre . ' à la boutique #' . $idBoutique);
    return ['success', count($ids) . ' exemplaire(s) disponible(s) affecté(s), sans modifier les prêts actifs.'];
}
