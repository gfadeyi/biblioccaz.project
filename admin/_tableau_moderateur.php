<?php
if (!function_exists('estEquipe')) { http_response_code(404); exit(); }
require_once __DIR__ . '/../messagerie_equipe_lib.php';

$moi = (int) $_SESSION['user_id'];
$moderation = aLeDroit('moderation');
$compteurs = compteursEquipe($pdo);
$nonLusEquipe = nombreNonLusEquipe($pdo, $moi);

function titresEnAttente(PDO $pdo, string $sql): string
{
    try {
        $titres = $pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        return '';
    }
    return implode(', ', array_map(fn($titre) => '« ' . mb_strimwidth((string) $titre, 0, 28, '…') . ' »', $titres));
}

$membresSignales = [];
$files = [];
if ($moderation) {
    try {
        $membresSignales = $pdo->query("
            SELECT u.id, u.pseudo, u.statut, SUM(x.type = 'avis') AS nb_avis, SUM(x.type = 'message') AS nb_messages, COUNT(*) AS nb_signalements
            FROM (
                SELECT 'avis' AS type, a.id_user AS id_membre FROM avis_signalement s INNER JOIN avis a ON a.id_avis = s.id_avis WHERE s.traite = 0
                UNION ALL
                SELECT 'message', m.id_auteur FROM message_signalement s INNER JOIN conversation_message m ON m.id_message = s.id_message WHERE s.traite = 0 AND m.id_auteur IS NOT NULL
            ) x
            INNER JOIN user u ON u.id = x.id_membre
            GROUP BY u.id, u.pseudo, u.statut
            ORDER BY nb_signalements DESC, u.pseudo
            LIMIT 8")->fetchAll();
    } catch (PDOException $e) {
    }
    $exclureArchives = archivageCatalogueDisponible($pdo) ? ' AND NOT EXISTS (SELECT 1 FROM livre_archive ar WHERE ar.id_livre = l.id_livre AND ar.actif = 1)' : '';
    $files = [
        ['shield-check', 'Fiches livres à valider', titresEnAttente($pdo, "SELECT l.titre FROM livre l WHERE l.is_valide = 0 $exclureArchives AND NOT EXISTS (SELECT 1 FROM exemplaire e WHERE e.id_livre = l.id_livre AND e.id_proprietaire IS NOT NULL) ORDER BY l.id_livre LIMIT 3") ?: 'Proposées par les auteurs', $compteurs['livres'], 'moderation_livres.php'],
        ['shop', 'Annonces à valider', titresEnAttente($pdo, "SELECT l.titre FROM exemplaire e INNER JOIN livre l ON l.id_livre = e.id_livre WHERE e.statut_annonce = 'en_validation' ORDER BY e.id_exemplaire LIMIT 3") ?: 'Ventes entre particuliers', $compteurs['annonces'] ?? 0, 'annonces.php'],
        ['gift', 'Dons à valider', titresEnAttente($pdo, "SELECT titre FROM don WHERE statut = 'en_validation' ORDER BY date_creation LIMIT 3") ?: 'Dons proposés par les membres', $compteurs['dons'] ?? 0, 'dons.php?statut=en_validation'],
        ['patch-check', 'Avis à valider', 'Avis publiés en attente de relecture', $compteurs['avis_a_valider'] ?? 0, 'avis_a_valider.php'],
        ['flag', 'Avis signalés', titresEnAttente($pdo, "SELECT DISTINCT l.titre FROM avis_signalement s INNER JOIN avis a ON a.id_avis = s.id_avis INNER JOIN livre l ON l.id_livre = a.id_livre WHERE s.traite = 0 LIMIT 3") ?: 'Signalements des lecteurs', $compteurs['avis'], 'moderation_avis.php'],
        ['chat-dots', 'Messages signalés', 'Conversations entre membres', $compteurs['messages'] ?? 0, 'messages.php'],
        ['person-exclamation', 'Membres signalés', 'Auteurs d’avis ou de messages signalés', count($membresSignales), '#membres-signales'],
        ['people', 'Ventes à surveiller', 'Litiges et ventes en retard entre lecteurs', $compteurs['ventes'] ?? 0, 'ventes.php'],
    ];
}
$propositionsEvenements = [];
try {
    $propositionsEvenements = $pdo->query("
        SELECT e.id_evenement, e.titre, e.date_debut, u.pseudo
        FROM evenement e LEFT JOIN user u ON u.id = e.id_createur
        WHERE e.statut = 'en_attente' ORDER BY e.date_creation LIMIT 5")->fetchAll();
} catch (PDOException $e) {
}
$files[] = ['calendar-plus', 'Propositions d’événements', $propositionsEvenements
    ? implode(', ', array_map(fn($p) => '« ' . mb_strimwidth($p['titre'], 0, 28, '…') . ' »', array_slice($propositionsEvenements, 0, 3)))
    : 'Proposées par les auteurs', $compteurs['evenements_proposes'] ?? 0, aLeDroit('evenements') ? 'evenements.php?onglet=proposes' : '#propositions-evenements'];
$totalATraiter = array_sum(array_map(fn($f) => $f[4] === '#membres-signales' ? 0 : $f[3], $files));

$mesActions = [];
try {
    $stmt = $pdo->prepare("
        (SELECT 'avis' AS type, a.date_validation AS date_action, l.titre COLLATE utf8mb4_unicode_ci AS titre, 'valide' COLLATE utf8mb4_unicode_ci AS statut, a.id_avis AS id_objet
         FROM avis a INNER JOIN livre l ON l.id_livre = a.id_livre WHERE a.id_validateur = ? AND a.date_validation IS NOT NULL ORDER BY a.date_validation DESC LIMIT 10)
        UNION ALL
        (SELECT 'annonce', e.date_moderation, l.titre COLLATE utf8mb4_unicode_ci, e.statut_annonce COLLATE utf8mb4_unicode_ci, e.id_exemplaire
         FROM exemplaire e INNER JOIN livre l ON l.id_livre = e.id_livre WHERE e.id_moderateur = ? AND e.date_moderation IS NOT NULL ORDER BY e.date_moderation DESC LIMIT 10)
        UNION ALL
        (SELECT 'don', dm.date_creation, d.titre COLLATE utf8mb4_unicode_ci, dm.action COLLATE utf8mb4_unicode_ci, d.id_don
         FROM don_moderation dm INNER JOIN don d ON d.id_don = dm.id_don WHERE dm.id_moderateur = ? ORDER BY dm.date_creation DESC LIMIT 10)
        UNION ALL
        (SELECT 'message', ms.date_traitement, NULL, NULL, cm.id_conversation
         FROM message_signalement ms INNER JOIN conversation_message cm ON cm.id_message = ms.id_message WHERE ms.id_traite_par = ? AND ms.date_traitement IS NOT NULL ORDER BY ms.date_traitement DESC LIMIT 10)
        UNION ALL
        (SELECT 'evenement', ev.date_validation, ev.titre COLLATE utf8mb4_unicode_ci, ev.statut COLLATE utf8mb4_unicode_ci, ev.id_evenement
         FROM evenement ev WHERE ev.id_validateur = ? AND ev.date_validation IS NOT NULL ORDER BY ev.date_validation DESC LIMIT 10)
        ORDER BY date_action DESC LIMIT 10");
    $stmt->execute([$moi, $moi, $moi, $moi, $moi]);
    $mesActions = $stmt->fetchAll();
} catch (PDOException $e) {
}
$actionsSemaine = count(array_filter($mesActions, fn($a) => strtotime($a['date_action']) >= strtotime('-7 days')));
if (historiqueDecisionsDisponible($pdo)) {
    $typesHistorique = typesDecisionsAutorises($pdo, (string) $_SESSION['role'], DROITS);
    $historiquePersonnel = historiqueDecisionsEquipe($pdo, $typesHistorique, ['acteur' => $moi]);
    $mesActions = array_map(fn($d) => ['type' => $d['type_objet'], 'date_action' => $d['date_decision'], 'titre' => $d['libelle_objet'], 'statut' => $d['nouveau_statut'], 'id_objet' => $d['id_objet']], array_slice($historiquePersonnel['decisions'], 0, 10));
    $actionsSemaine = historiqueDecisionsEquipe($pdo, $typesHistorique, ['acteur' => $moi, 'depuis' => date('Y-m-d', strtotime('-7 days'))])['total'];
}

function decrireActionModeration(array $action): array
{
    $titre = $action['titre'] !== null ? ' « ' . mb_strimwidth((string) $action['titre'], 0, 40, '…') . ' »' : '';
    $statut = str_replace('_', ' ', (string) $action['statut']);
    $id = (int) $action['id_objet'];
    return match ($action['type']) {
        'avis' => ['patch-check', 'Avis : ' . $statut . $titre, 'moderation_avis.php', 'moderation'],
        'livre' => ['book', 'Fiche : ' . $statut . $titre, 'moderation_livres.php', 'moderation'],
        'annonce' => ['shop', 'Annonce ' . $statut . $titre, 'annonce.php?id=' . $id, 'moderation'],
        'don' => ['gift', 'Don : ' . $statut . $titre, 'don.php?id=' . $id, 'moderation'],
        'message' => ['chat-dots', 'Signalement de message traité', 'conversation.php?id=' . $id, 'moderation'],
        'evenement' => ['calendar-check', 'Événement ' . $statut . $titre, 'evenement.php?id=' . $id, 'evenements'],
        default => ['dot', 'Action de modération', '', 'moderation'],
    };
}

$cartes = [
    ['shield-check', 'À traiter', $totalATraiter, 'Toutes files confondues', '#a-traiter', $totalATraiter > 0],
    ['person-exclamation', 'Membres signalés', count($membresSignales), 'Contenus signalés non traités', '#membres-signales', count($membresSignales) > 0],
    ['chat-left-text', 'Messages de l’équipe', $nonLusEquipe, $nonLusEquipe > 0 ? 'non lu' . ($nonLusEquipe > 1 ? 's' : '') : 'Tout est lu', 'messagerie_equipe.php', $nonLusEquipe > 0],
    ['check2-all', 'Mes actions', $actionsSemaine, '7 derniers jours', '#mes-actions', false],
];

$titrePage = 'Tableau de bord';
$sousTitrePage = 'Bonjour ' . (($_SESSION['prenom'] ?? '') ?: ($_SESSION['pseudo'] ?? '')) . ', voici les contenus et la communauté à modérer au ' . date('d/m/Y') . '.';
$pageActive = 'index';
$actionsPage = [['messagerie_equipe.php?nouveau=1', 'chat-left-text', 'Écrire à l’équipe', 'btn-outline-secondary']];
include __DIR__ . '/_haut.php';
?>

<div class="grille-indicateurs mb-4">
    <?php foreach ($cartes as [$icone, $libelle, $valeur, $detail, $lien, $alerte]): ?>
        <div>
            <a href="<?= e($lien) ?>" class="stat">
                <span class="stat-haut"><span class="stat-icone <?= $alerte ? 'alerte' : '' ?>"><i class="bi bi-<?= $icone ?>" aria-hidden="true"></i></span></span>
                <span class="stat-valeur"><?= (int) $valeur ?></span>
                <span class="stat-libelle"><?= e($libelle) ?></span>
                <span class="stat-detail"><?= e($detail) ?></span>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <section class="carte h-100" id="a-traiter" aria-labelledby="titreFiles">
            <div class="carte-entete">
                <h2 id="titreFiles">Files de modération</h2>
                <span class="etiquette <?= $totalATraiter > 0 ? 'etiquette-alerte' : '' ?>"><?= $totalATraiter > 0 ? $totalATraiter . ' en attente' : 'Tout est à jour' ?></span>
            </div>
            <?php if (!$moderation): ?>
                <p class="carte-corps text-muted mb-0">Le droit « Modération » ne vous est pas attribué pour le moment.</p>
            <?php endif; ?>
            <ul class="liste-a-traiter">
                <?php foreach ($files as [$icone, $libelle, $detail, $nombre, $lien]): ?>
                    <li>
                        <a href="<?= e($lien) ?>" class="ligne-a-traiter">
                            <span class="compte <?= $nombre > 0 ? '' : 'zero' ?>"><?= $nombre > 0 ? (int) $nombre : '<i class="bi bi-check-lg" aria-label="Aucun"></i>' ?></span>
                            <span><strong><?= e($libelle) ?></strong><small><?= e($detail) ?></small></span>
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
    <div class="col-xl-6 d-flex flex-column gap-4">
        <section class="carte" id="mes-actions" aria-labelledby="titreMesActions">
            <div class="carte-entete"><h2 id="titreMesActions">Mes dernières actions</h2><a class="small" href="historique_decisions.php?acteur=<?= (int) $moi ?>">Historique détaillé</a></div>
            <?php if (!$mesActions): ?>
                <div class="etat-vide"><div class="icone-vide"><i class="bi bi-clock-history"></i></div><strong>Aucune action pour l’instant</strong>Vos validations, refus et traitements de signalements apparaîtront ici.</div>
            <?php else: ?>
                <ul class="fil-activite">
                    <?php foreach ($mesActions as $action): ?>
                        <?php [$iconeAction, $texteAction, $lienAction, $droitAction] = decrireActionModeration($action); ?>
                        <li>
                            <span class="icone-activite"><i class="bi bi-<?= $iconeAction ?>" aria-hidden="true"></i></span>
                            <span>
                                <?php if ($lienAction !== '' && aLeDroit($droitAction)): ?><a href="<?= e($lienAction) ?>"><?= e($texteAction) ?></a><?php else: ?><?= e($texteAction) ?><?php endif; ?>
                                <time datetime="<?= e(date('c', strtotime($action['date_action']))) ?>"><?= depuis($action['date_action']) ?></time>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <?php if ($moderation): ?>
            <section class="carte" id="membres-signales" aria-labelledby="titreMembresSignales">
                <div class="carte-entete"><h2 id="titreMembresSignales">Membres signalés</h2><span class="text-muted small">Avis et messages non traités</span></div>
                <ul class="liste-courte">
                    <?php foreach ($membresSignales as $membre): ?>
                        <li>
                            <span>@<?= e($membre['pseudo']) ?><?= in_array($membre['statut'], STATUTS_COMPTE_SUSPENDU, true) ? ' <span class="badge text-bg-secondary">suspendu</span>' : '' ?></span>
                            <?php if ($membre['nb_avis'] > 0): ?><a href="moderation_avis.php" class="small"><?= (int) $membre['nb_avis'] ?> avis</a><?php endif; ?>
                            <?php if ($membre['nb_messages'] > 0): ?><a href="messages.php" class="small"><?= (int) $membre['nb_messages'] ?> message<?= $membre['nb_messages'] > 1 ? 's' : '' ?></a><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                    <?php if (!$membresSignales): ?><li class="vide">Aucun membre signalé.</li><?php endif; ?>
                </ul>
            </section>
        <?php endif; ?>
        <section class="carte" id="propositions-evenements" aria-labelledby="titrePropositions">
            <div class="carte-entete"><h2 id="titrePropositions">Propositions d’événements d’auteurs</h2></div>
            <ul class="liste-courte">
                <?php foreach ($propositionsEvenements as $proposition): ?>
                    <li>
                        <span><?php if (aLeDroit('evenements')): ?><a href="evenement.php?id=<?= (int) $proposition['id_evenement'] ?>"><?= e($proposition['titre']) ?></a><?php else: ?><?= e($proposition['titre']) ?><?php endif; ?></span>
                        <small><?= $proposition['pseudo'] ? '@' . e($proposition['pseudo']) . ' · ' : '' ?><?= date('d/m/Y', strtotime($proposition['date_debut'])) ?></small>
                    </li>
                <?php endforeach; ?>
                <?php if (!$propositionsEvenements): ?><li class="vide">Aucune proposition en attente.</li><?php endif; ?>
            </ul>
            <?php if ($propositionsEvenements && !aLeDroit('evenements')): ?>
                <p class="carte-corps small text-muted mb-0 border-top">La validation des événements relève du droit « Événements ». Pour donner votre avis, utilisez la <a href="messagerie_equipe.php?nouveau=1">messagerie de l’équipe</a>.</p>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
