<?php
if (!function_exists('estEquipe')) { http_response_code(404); exit(); }
require_once __DIR__ . '/../service_client_lib.php';
require_once __DIR__ . '/../avis_spoiler_lib.php';

const TABLEAU_ACTUALISATION = 60;

function compterPriorite(PDO $pdo, string $sql, array $params = []): ?int
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $valeur = $stmt->fetchColumn();
        return $valeur === false || $valeur === null ? null : (int) $valeur;
    } catch (PDOException $e) {
        return null;
    }
}

function sommePriorites(?int ...$valeurs): ?int
{
    if (!$valeurs || in_array(null, $valeurs, true)) { return null; }
    return array_sum($valeurs);
}

function ticketsPolePriorite(PDO $pdo): ?int
{
    try {
        [$condition, $params] = conditionPerimetreService($pdo, (string) ($_SESSION['role'] ?? ''), (int) ($_SESSION['user_id'] ?? 0));
    } catch (PDOException $e) {
        return null;
    }
    return compterPriorite($pdo, "SELECT COUNT(*) FROM demande_client d WHERE (d.statut = 'nouvelle' OR (d.statut <> 'resolue' AND d.non_lu_equipe = 1)) AND $condition", $params);
}

function alertesSystemePriorite(PDO $pdo): array
{
    require_once __DIR__ . '/../sante_site_lib.php';
    try {
        $sante = syntheseSanteSite($pdo);
    } catch (Throwable $e) {
        return [null, 'État du site illisible'];
    }
    $etats = array_map(fn($cle) => $sante[$cle]['etat'] ?? 'inconnu', ['relances', 'courriels', 'php', 'sauvegardes']);
    $attention = count(array_filter($etats, fn($etat) => $etat === 'attention'));
    $inconnus = count(array_filter($etats, fn($etat) => $etat === 'inconnu'));
    if ($inconnus === count($etats)) { return [null, 'Aucun indicateur lisible']; }
    $detail = $attention > 0 ? 'Relances, courriels, erreurs ou sauvegardes à vérifier' : 'Aucun signal récent';
    if ($inconnus > 0) { $detail .= ' · ' . $inconnus . ' indicateur' . ($inconnus > 1 ? 's' : '') . ' indisponible' . ($inconnus > 1 ? 's' : ''); }
    return [$attention, $detail];
}

function prioritesTableau(PDO $pdo): array
{
    $role = (string) ($_SESSION['role'] ?? '');
    $priorites = [];
    $ajouter = function (string $cle, string $icone, string $libelle, string $detail, ?int $valeur, string $lien, bool $urgence = true) use (&$priorites): void {
        $priorites[] = ['cle' => $cle, 'icone' => $icone, 'libelle' => $libelle, 'detail' => $detail, 'valeur' => $valeur, 'lien' => $lien, 'urgence' => $urgence];
    };
    $donsAValider = fn(): ?int => compterPriorite($pdo, "SELECT COUNT(*) FROM don WHERE statut = 'en_validation'");
    $retraitsEnRetard = fn(): ?int => compterPriorite($pdo, "SELECT COUNT(*) FROM don WHERE statut = 'reserve' AND date_limite < CURDATE()");
    $commandes = fn(): ?int => compterPriorite($pdo, "SELECT COUNT(*) FROM commande WHERE statut <> 'annulee' AND date_commande >= NOW() - INTERVAL 2 DAY");
    $retours = fn(): ?int => compterPriorite($pdo, "SELECT COUNT(*) FROM retour_commande WHERE statut IN ('demande', 'recu') OR (statut = 'accepte' AND type = 'remboursement')");
    $avisSignales = fn(): ?int => compterPriorite($pdo, 'SELECT COUNT(DISTINCT id_avis) FROM avis_signalement WHERE traite = 0');
    $spoilers = fn(): ?int => spoilerAvisDisponible($pdo) ? compterPriorite($pdo, "SELECT COUNT(*) FROM avis WHERE statut = 'visible' AND date_validation IS NULL AND spoiler IS NOT NULL") : null;
    $annonces = fn(): ?int => compterPriorite($pdo, "SELECT COUNT(*) FROM exemplaire WHERE statut_annonce = 'en_validation'");
    $litiges = fn(): ?int => compterPriorite($pdo, 'SELECT COUNT(*) FROM demande_achat WHERE en_litige = 1');
    $conversations = fn(): ?int => compterPriorite($pdo, 'SELECT COUNT(*) FROM conversation WHERE date_dernier_message >= NOW() - INTERVAL 1 DAY');

    if ($role === 'gestionnaire') {
        if (aLeDroit('dons')) {
            $ajouter('dons_a_valider', 'gift', 'Dons à valider', 'Proposés par les membres', $donsAValider(), 'dons?statut=en_validation');
            $ajouter('retraits_en_retard', 'hourglass-split', 'Retraits en retard', 'Dons réservés, date limite dépassée', $retraitsEnRetard(), 'dons?statut=reserve');
        }
        if (aLeDroit('catalogue')) {
            $ajouter('commandes', 'bag-check', 'Commandes à traiter', 'Validées depuis 48 heures', $commandes(), 'commandes');
            $ajouter('retours', 'arrow-return-left', 'Retours à traiter', 'Demandes, colis reçus, remboursements', $retours(), 'retours');
        }
        $ajouter('tickets', 'headset', 'Tickets SAV de mon pôle', 'Nouveaux ou en attente de réponse', ticketsPolePriorite($pdo), 'service_client');
    } elseif ($role === 'moderateur') {
        if (aLeDroit('moderation')) {
            $ajouter('avis_signales', 'flag', 'Avis signalés', 'Signalements des lecteurs', $avisSignales(), 'moderation_avis');
            $ajouter('spoilers', 'eye-slash', 'Spoilers à relire', 'Avis marqués, en attente de relecture', $spoilers(), 'avis_a_valider');
            $ajouter('annonces', 'shop', 'Annonces en attente', 'Ventes entre particuliers', $annonces(), 'annonces');
        }
        if (aLeDroit('ventes')) {
            $ajouter('litiges', 'exclamation-diamond', 'Litiges entre lecteurs', 'Ventes signalées en litige', $litiges(), 'ventes');
        }
        if (aLeDroit('moderation')) {
            $ajouter('conversations', 'chat-dots', 'Conversations récentes', 'Actives depuis 24 heures', $conversations(), 'messages', false);
        }
        $ajouter('tickets', 'headset', 'Tickets SAV de mon pôle', 'Nouveaux ou en attente de réponse', ticketsPolePriorite($pdo), 'service_client');
    } elseif ($role === 'admin') {
        $ajouter('pole_gestion', 'box-seam', 'Pôle gestion', 'Dons à valider, retraits en retard, retours', sommePriorites($donsAValider(), $retraitsEnRetard(), $retours()), 'dons?statut=en_validation');
        $ajouter('pole_moderation', 'shield-check', 'Pôle modération', 'Avis signalés, annonces, litiges', sommePriorites($avisSignales(), $annonces(), $litiges()), 'moderation_avis');
        $ajouter('tickets', 'headset', 'Tickets SAV', 'Tous pôles, nouveaux ou en attente', ticketsPolePriorite($pdo), 'service_client');
        [$alertes, $detailAlertes] = alertesSystemePriorite($pdo);
        $ajouter('alertes_systeme', 'activity', 'Alertes système', $detailAlertes, $alertes, 'sante');
        $sessions = null;
        try {
            $instant = $pdo->query('SELECT NOW()')->fetchColumn();
            $sessions = compterPriorite($pdo, 'SELECT COUNT(*) FROM session_utilisateur s INNER JOIN user u ON u.id = s.id_user WHERE ' . conditionSessionValide(), [$instant]);
        } catch (PDOException $e) {
        }
        $ajouter('sessions_actives', 'broadcast', 'Sessions actives', 'Non révoquées et non expirées', $sessions, 'en_ligne', false);
    }
    return $priorites;
}

function accesRapidesTableau(): array
{
    $role = (string) ($_SESSION['role'] ?? '');
    $acces = match ($role) {
        'gestionnaire' => [
            ['catalogue', 'emprunts#code', 'qr-code-scan', 'Comptoir'],
            ['catalogue', 'inventaire', 'box-seam', 'Inventaire'],
            ['catalogue', 'commandes', 'bag-check', 'Commandes'],
            ['dons_lecture', 'dons', 'gift', 'Dons'],
            ['evenements', 'evenements', 'calendar-event', 'Événements'],
            ['equipe', 'messagerie_equipe', 'chat-left-text', 'Messagerie'],
        ],
        'moderateur' => [
            ['moderation', 'avis_a_valider', 'patch-check', 'Avis à valider'],
            ['moderation', 'annonces', 'shop', 'Annonces'],
            ['moderation', 'messages', 'chat-dots', 'Conversations'],
            ['ventes', 'ventes', 'people', 'Ventes entre lecteurs'],
            ['equipe', 'historique_decisions', 'clock-history', 'Historique'],
            ['equipe', 'messagerie_equipe', 'chat-left-text', 'Messagerie'],
        ],
        'admin' => [
            ['admin', 'droits_equipe', 'key', 'Gestion des droits'],
            ['admin', 'utilisateurs', 'people', 'Membres'],
            ['admin', 'en_ligne', 'broadcast', 'Sessions'],
            ['admin', 'sante', 'activity', 'Santé du site'],
            ['admin', 'logs', 'journal-text', 'Journal'],
            ['admin', 'parametres_site', 'gear', 'Paramètres'],
        ],
        default => [],
    };
    return array_values(array_filter($acces, fn($a) => aLeDroit($a[0])));
}

function urgencesTableau(array $priorites): array
{
    $urgences = array_filter($priorites, fn($p) => $p['urgence']);
    $connues = array_filter($urgences, fn($p) => $p['valeur'] !== null);
    return ['total' => array_sum(array_column($connues, 'valeur')), 'indisponibles' => count($urgences) - count($connues), 'suivies' => count($urgences)];
}

function libelleUrgencesTableau(array $bilan): string
{
    if ($bilan['suivies'] === 0) { return 'Aucune file suivie'; }
    if ($bilan['total'] > 0) { return $bilan['total'] . ' à traiter'; }
    return $bilan['indisponibles'] > 0 ? 'Données partielles' : 'Rien d’urgent';
}

function afficherPrioritesTableau(PDO $pdo): void
{
    $priorites = prioritesTableau($pdo);
    $acces = accesRapidesTableau();
    $bilan = urgencesTableau($priorites);
    ?>
    <section class="priorites mb-4" aria-labelledby="titrePriorites" data-tableau-bord data-source="tableau_donnees" data-intervalle="<?= TABLEAU_ACTUALISATION ?>">
        <div class="priorites-entete">
            <h2 id="titrePriorites">Priorités</h2>
            <span class="etiquette <?= $bilan['total'] > 0 ? 'etiquette-alerte' : '' ?>" data-tableau-bilan><?= e(libelleUrgencesTableau($bilan)) ?></span>
            <span class="priorites-etat" data-tableau-etat role="status" aria-live="polite">Actualisé à <?= date('H:i') ?></span>
        </div>
        <?php if (!$priorites): ?>
            <div class="etat-vide"><div class="icone-vide"><i class="bi bi-inbox" aria-hidden="true"></i></div><strong>Aucune file à suivre</strong>Vos droits actuels ne donnent accès à aucune file de traitement.</div>
        <?php else: ?>
            <ul class="priorites-grille">
                <?php foreach ($priorites as $p): ?>
                    <?php $classe = $p['valeur'] === null ? 'indisponible' : ($p['urgence'] && $p['valeur'] > 0 ? 'urgente' : ''); ?>
                    <li>
                        <a href="<?= e($p['lien']) ?>" class="priorite <?= $classe ?>" data-priorite="<?= e($p['cle']) ?>" data-urgence="<?= $p['urgence'] ? '1' : '0' ?>">
                            <span class="priorite-icone"><i class="bi bi-<?= e($p['icone']) ?>" aria-hidden="true"></i></span>
                            <span class="priorite-valeur" data-priorite-valeur><?= $p['valeur'] === null ? '—' : (int) $p['valeur'] ?></span>
                            <span class="priorite-libelle"><?= e($p['libelle']) ?></span>
                            <span class="priorite-detail" data-priorite-detail data-detail="<?= e($p['detail']) ?>"><?= $p['valeur'] === null ? 'Donnée indisponible' : e($p['detail']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($acces): ?>
            <nav class="acces-rapides" aria-label="Accès rapides">
                <?php foreach ($acces as [, $lien, $icone, $libelle]): ?>
                    <a href="<?= e($lien) ?>" class="acces-rapide"><i class="bi bi-<?= e($icone) ?>" aria-hidden="true"></i> <?= e($libelle) ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </section>
    <?php
}
