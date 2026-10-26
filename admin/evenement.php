<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../evenements_lib.php';
exigerDroit('evenements');

$id = (int) ($_GET['id'] ?? 0);
$evt = $id ? chargerEvenement($pdo, $id) : null;
if ($id && !$evt) {
    rediriger('evenements.php', 'warning', "Cet événement n'existe pas.");
}
if ($evt && $evt['statut'] === 'annule') {
    rediriger("evenement_participants.php?id=$id", 'warning', "Un événement annulé ne peut plus être modifié.");
}
if ($evt && in_array($evt['statut'], ['refuse', 'retire'], true)) {
    rediriger('evenements.php?onglet=refuses', 'warning', "Cette proposition a été refusée ou retirée par son auteur : elle ne peut plus être modifiée.");
}
$estProposition = $evt && $evt['statut'] === 'en_attente';
$proposeur = $evt ? proposeurEvenement($pdo, $evt) : null;
$retour = $id ? "evenement.php?id=$id" : 'evenement.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['valider', 'refuser'], true)) {
    exigerPost($retour);
    if (!$estProposition) {
        rediriger($retour, 'warning', "Cette proposition a déjà été traitée.");
    }
    [$typeMessage, $message] = $_POST['action'] === 'valider'
        ? validerEvenementPropose($pdo, $id, (int) $_SESSION['user_id'])
        : refuserEvenementPropose($pdo, $id, (int) $_SESSION['user_id'], (string) ($_POST['motif_refus'] ?? ''));
    $destination = $typeMessage !== 'success' ? $retour : ($_POST['action'] === 'valider' ? "evenement_participants.php?id=$id" : 'evenements.php?onglet=proposes');
    rediriger($destination, $typeMessage, $message);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'annuler' && $evt && !$estProposition) {
    exigerPost($retour);
    $motif = trim($_POST['motif'] ?? '');
    $pdo->prepare("UPDATE evenement SET statut = 'annule', date_annulation = NOW() WHERE id_evenement = ? AND statut = 'publie'")->execute([$id]);
    prevenirAuteurAnnulation($pdo, $evt, $motif);
    $nb = notifierParticipants($pdo, $id, "Événement annulé : " . $evt['titre'],
        "Nous sommes désolés : l'événement « {$evt['titre']} » prévu " . horaireEvenement($evt) . " est annulé."
        . ($motif !== '' ? "\n\nRaison : $motif" : '')
        . "\n\nVotre inscription est automatiquement annulée, vous n'avez rien à faire.");
    insertLog('EVENEMENT', "Annulation de l'événement #$id : {$evt['titre']} ($nb personnes prévenues)");
    rediriger("evenement_participants.php?id=$id", 'success', "Événement annulé. $nb inscrit" . ($nb > 1 ? 's ont' : ' a') . " été prévenu" . ($nb > 1 ? 's' : '') . " par mail.");
}

$erreur = '';
$valeurs = $evt ?: ['type' => 'rencontre', 'places_max' => 20, 'attente_auto' => 1];
$livresCoches = $evt ? array_map('intval', array_column(livresEvenement($pdo, $id), 'id_livre')) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? 'enregistrer') === 'enregistrer') {
    exigerPost($retour);
    foreach (['titre', 'description', 'type', 'auteurs', 'lieu', 'date_debut', 'date_fin', 'places_max'] as $c) {
        $valeurs[$c] = trim($_POST[$c] ?? '');
    }
    $valeurs['attente_auto'] = isset($_POST['attente_auto']) ? 1 : 0;
    $debut = strtotime($valeurs['date_debut']);
    $fin = strtotime($valeurs['date_fin']);
    $places = (int) $valeurs['places_max'];
    [$livresCoches, $erreur] = livresChoisis($pdo, $_POST['livres'] ?? []);
    if ($erreur === '') {
        $erreur = erreurEvenement($valeurs, $evt);
    }
    $image = null;
    if ($erreur === '') {
        [$image, $erreur] = enregistrerCouverture('image');
    }

    if ($erreur === '') {
        $donnees = [
            $valeurs['titre'], $valeurs['description'], $valeurs['type'], $valeurs['auteurs'] ?: null, $valeurs['lieu'],
            date('Y-m-d H:i:s', $debut), date('Y-m-d H:i:s', $fin), $places, $valeurs['attente_auto'],
        ];
        if ($evt) {
            $sql = "UPDATE evenement SET titre = ?, description = ?, type = ?, auteurs = ?, lieu = ?, date_debut = ?, date_fin = ?,
                    places_max = ?, attente_auto = ?" . ($image ? ", image = ?" : "") . " WHERE id_evenement = ?";
            if ($image) {
                $donnees[] = $image;
            }
            $donnees[] = $id;
            $pdo->beginTransaction();
            $pdo->prepare($sql)->execute($donnees);
            enregistrerLivresEvenement($pdo, $id, $livresCoches);
            $promus = $valeurs['attente_auto'] ? promouvoirListeAttente($pdo, $id) : [];
            $pdo->commit();

            $nouveau = chargerEvenement($pdo, $id);
            prevenirPromus($promus, $nouveau);
            $message = $estProposition ? "Modifications enregistrées. Vous pouvez maintenant valider la proposition." : "Modifications enregistrées.";
            if ($nouveau['date_debut'] !== $evt['date_debut'] || $nouveau['date_fin'] !== $evt['date_fin'] || $nouveau['lieu'] !== $evt['lieu']) {
                $nb = notifierParticipants($pdo, $id, "Changement pour « {$nouveau['titre']} »",
                    "L'événement « {$nouveau['titre']} » auquel vous êtes inscrit a changé :\n\n"
                    . "Quand : " . horaireEvenement($nouveau) . "\nOù : {$nouveau['lieu']}\n\n"
                    . "Si vous ne pouvez plus venir, désinscrivez-vous depuis votre espace pour libérer votre place : " . SITE_URL . "/mes_evenements.php");
                $message .= " $nb participant" . ($nb > 1 ? 's ont' : ' a') . " été prévenu" . ($nb > 1 ? 's' : '') . " du changement.";
            }
            if ($promus) {
                $message .= " " . count($promus) . " personne" . (count($promus) > 1 ? 's' : '') . " de la liste d'attente inscrite" . (count($promus) > 1 ? 's' : '') . ".";
            }
            insertLog('EVENEMENT', "Modification de l'événement #$id : {$valeurs['titre']}");
            rediriger("evenement.php?id=$id", 'success', $message);
        }

        $donnees[] = $image;
        $donnees[] = $_SESSION['user_id'];
        $pdo->prepare("INSERT INTO evenement (titre, description, type, auteurs, lieu, date_debut, date_fin, places_max, attente_auto, image, id_createur)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute($donnees);
        $nouvelId = (int) $pdo->lastInsertId();
        enregistrerLivresEvenement($pdo, $nouvelId, $livresCoches);
        insertLog('EVENEMENT', "Création de l'événement #$nouvelId : {$valeurs['titre']}");
        rediriger("evenement_participants.php?id=$nouvelId", 'success', "Événement créé : il est visible dans le calendrier public.");
    }
}

$formatChamp = fn(?string $d) => $d ? date('Y-m-d\TH:i', strtotime($d)) : '';
$v = fn(string $c) => e((string) ($valeurs[$c] ?? ''));

$titrePage = $estProposition ? 'Proposition d\'événement' : ($evt ? 'Modifier l\'événement' : 'Nouvel événement');
$scriptsPage = '<script src="../javascript/choix_livres.js?v=' . filemtime(__DIR__ . '/../javascript/choix_livres.js') . '"></script>';
$pageActive = 'evenements';
$filAriane = [[null, 'Communauté'], ['evenements.php', 'Événements']];
include __DIR__ . '/_haut.php';
?>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="evenements.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Événements</a>
    <?php if ($evt && !$estProposition): ?>
        <a href="evenement_participants.php?id=<?= $id ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-people"></i> Participants (<?= $evt['nb_inscrits'] ?>)</a>
        <a href="../evenement.php?id=<?= $id ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i> Voir sur le site</a>
    <?php endif; ?>
</div>

<?php if ($erreur): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($erreur) ?></div>
<?php endif; ?>

<?php if ($estProposition): ?>
    <div class="alert alert-info d-flex gap-2 align-items-start">
        <i class="bi bi-pen" aria-hidden="true"></i>
        <div>
            <strong>Proposition de <?= e($proposeur['nom_affiche'] ?? 'un auteur') ?></strong>, reçue le <?= date('d/m/Y à G\hi', strtotime($evt['date_creation'])) ?>.
            Elle n'est pas visible du public. Vous pouvez la corriger ci-dessous, puis la valider ou la refuser.
        </div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-8">
        <form method="POST" enctype="multipart/form-data" class="carte p-4">
            <?= champCsrf() ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-bold" for="titre">Titre *</label>
                    <input type="text" id="titre" name="titre" class="form-control" maxlength="200" value="<?= $v('titre') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold" for="type">Type *</label>
                    <select id="type" name="type" class="form-select">
                        <?php foreach (EVT_TYPES as $cle => [$libelle]): ?>
                            <option value="<?= $cle ?>" <?= ($valeurs['type'] ?? '') === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold" for="auteurs">Auteur(s) invité(s)</label>
                    <input type="text" id="auteurs" name="auteurs" class="form-control" maxlength="255" value="<?= $v('auteurs') ?>" placeholder="Séparez les noms par des virgules">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold" for="lieu">Lieu *</label>
                    <input type="text" id="lieu" name="lieu" class="form-control" maxlength="255" value="<?= $v('lieu') ?>" required placeholder="Ex : Médiathèque, Lyon 3e">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold" for="date_debut">Début *</label>
                    <input type="datetime-local" id="date_debut" name="date_debut" class="form-control" value="<?= e($formatChamp($valeurs['date_debut'] ?? null)) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold" for="date_fin">Fin *</label>
                    <input type="datetime-local" id="date_fin" name="date_fin" class="form-control" value="<?= e($formatChamp($valeurs['date_fin'] ?? null)) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold" for="places_max">Places *</label>
                    <input type="number" id="places_max" name="places_max" class="form-control" min="1" value="<?= $v('places_max') ?>" required>
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="attente_auto" name="attente_auto" <?= !empty($valeurs['attente_auto']) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="attente_auto">Liste d'attente automatique : quand une place se libère, la première personne en attente est inscrite et prévenue par mail. Décochez pour choisir vous-même qui inscrire.</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold" for="description">Description *</label>
                    <textarea id="description" name="description" class="form-control" rows="6" required><?= $v('description') ?></textarea>
                </div>
                <div class="col-12">
                    <?php $livresProposes = livresAssociables($pdo, (int) ($evt['id_createur'] ?? 0)); $racineImages = '../'; include __DIR__ . '/../partie_choix_livres.php'; ?>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold" for="image">Image (JPG, PNG ou WEBP, 2 Mo max)</label>
                    <div class="d-flex gap-3 align-items-center">
                        <?php if (!empty($evt['image'])): ?><img src="<?= e(imageEvenement($evt['image'], '../')) ?>" alt="Image actuelle" class="rounded" style="width: 90px; height: 56px; object-fit: cover;"><?php endif; ?>
                        <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>
            </div>
            <?php if ($evt && $evt['nb_inscrits'] + $evt['nb_attente'] > 0): ?>
                <p class="small text-muted mt-3 mb-0"><i class="bi bi-envelope"></i> Si vous changez la date, l'horaire ou le lieu, les <?= $evt['nb_inscrits'] + $evt['nb_attente'] ?> personnes inscrites ou en attente seront prévenues par mail.</p>
            <?php endif; ?>
            <button class="btn <?= $estProposition ? 'btn-outline-success' : 'btn-vert' ?> px-4 py-2 fw-bold mt-3"><?= $evt ? 'Enregistrer les modifications' : 'Créer l\'événement' ?></button>
        </form>
    </div>

    <?php if ($estProposition): ?>
        <div class="col-xl-4 d-flex flex-column gap-3">
            <form method="POST" class="carte p-4">
                <?= champCsrf() ?>
                <input type="hidden" name="action" value="valider">
                <h2 class="h6 fw-bold text-success"><i class="bi bi-check2-circle"></i> Valider la proposition</h2>
                <p class="small text-muted">L'événement sera publié dans le calendrier, ouvert aux inscriptions, et l'auteur recevra un mail. Enregistrez d'abord vos éventuelles corrections.</p>
                <button class="btn btn-vert w-100"><i class="bi bi-check2"></i> Valider et publier</button>
            </form>
            <form method="POST" class="carte p-4" data-confirmer="Refuser cette proposition ? L'auteur recevra le motif par mail.">
                <?= champCsrf() ?>
                <input type="hidden" name="action" value="refuser">
                <h2 class="h6 fw-bold text-danger"><i class="bi bi-x-circle"></i> Refuser la proposition</h2>
                <label class="form-label small fw-bold" for="motif_refus">Motif (obligatoire, envoyé à l'auteur)</label>
                <textarea id="motif_refus" name="motif_refus" class="form-control mb-3" rows="3" maxlength="<?= EVT_MOTIF_MAX ?>" required placeholder="Ex : la salle n'est pas disponible à cette date, pouvez-vous proposer un autre jour ?"></textarea>
                <button class="btn btn-outline-danger w-100">Refuser la proposition</button>
            </form>
        </div>
    <?php elseif ($evt): ?>
        <div class="col-xl-4">
            <form method="POST" class="carte p-4 border-danger" data-confirmer="Annuler définitivement cet événement ? Tous les inscrits seront prévenus par mail.">
                <?= champCsrf() ?>
                <input type="hidden" name="action" value="annuler">
                <h2 class="h6 fw-bold text-danger"><i class="bi bi-x-octagon"></i> Annuler l'événement</h2>
                <p class="small text-muted">Les <?= $evt['nb_inscrits'] + $evt['nb_attente'] ?> personnes inscrites ou en attente recevront un mail. Cette action est définitive.</p>
                <label class="form-label small fw-bold" for="motif">Raison (facultatif, incluse dans le mail)</label>
                <textarea id="motif" name="motif" class="form-control mb-3" rows="2" placeholder="Ex : l'auteur est malade"></textarea>
                <button class="btn btn-outline-danger w-100">Annuler l'événement</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/_bas.php'; ?>
