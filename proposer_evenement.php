<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'evenements_lib.php';
require_once 'upload_lib.php';

if (($_SESSION['role'] ?? '') !== 'auteur') {
    header("Location: login.php");
    exit();
}
$idAuteur = (int) $_SESSION['user_id'];

$id = (int) ($_GET['id'] ?? $_POST['id_evenement'] ?? 0);
$evt = $id ? chargerEvenement($pdo, $id) : null;
if ($id && (!$evt || (int) $evt['id_createur'] !== $idAuteur)) {
    header("Location: dashboard_auteur.php");
    exit();
}
if ($evt && $evt['statut'] !== 'en_attente') {
    $_SESSION['flash_auteur'] = ['warning', "Cette proposition a déjà été traitée par l'équipe : elle ne peut plus être modifiée."];
    header("Location: dashboard_auteur.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifierCsrf()) {
    $_SESSION['flash_auteur'] = ['danger', "Session expirée, merci de réessayer."];
    header("Location: " . ($evt ? "proposer_evenement.php?id=$id" : "proposer_evenement.php"));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'retirer' && $evt) {
    $_SESSION['flash_auteur'] = retirerPropositionEvenement($pdo, $id, $idAuteur)
        ? ['success', "Votre proposition « {$evt['titre']} » a été retirée."]
        : ['warning', "Cette proposition a déjà été traitée par l'équipe."];
    header("Location: dashboard_auteur.php");
    exit();
}

$stmt = $pdo->prepare("SELECT prenom, nom, pseudo FROM user WHERE id = ?");
$stmt->execute([$idAuteur]);
$moi = $stmt->fetch();
$monNom = trim(($moi['prenom'] ?? '') . ' ' . ($moi['nom'] ?? '')) ?: $moi['pseudo'];

$erreur = '';
$valeurs = $evt ? [
    'titre' => $evt['titre'], 'type' => $evt['type'], 'lieu' => $evt['lieu'], 'auteurs' => $evt['auteurs'],
    'places_max' => $evt['places_max'], 'description' => $evt['description'],
    'jour' => date('Y-m-d', strtotime($evt['date_debut'])),
    'heure_debut' => date('H:i', strtotime($evt['date_debut'])),
    'heure_fin' => date('H:i', strtotime($evt['date_fin'])),
] : ['type' => 'dedicace', 'places_max' => 30, 'auteurs' => $monNom];
$livresCoches = $evt ? array_map('intval', array_column(livresEvenement($pdo, $id), 'id_livre')) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['titre', 'type', 'lieu', 'auteurs', 'places_max', 'description', 'jour', 'heure_debut', 'heure_fin'] as $champ) {
        $valeurs[$champ] = trim((string) ($_POST[$champ] ?? ''));
    }
    $valeurs['date_debut'] = $valeurs['jour'] . ' ' . $valeurs['heure_debut'];
    $valeurs['date_fin'] = $valeurs['jour'] . ' ' . $valeurs['heure_fin'];
    [$livresCoches, $erreur] = livresChoisis($pdo, $_POST['livres'] ?? []);
    $formatValide = preg_match('/^\d{4}-\d{2}-\d{2}$/', $valeurs['jour']) && preg_match('/^\d{2}:\d{2}$/', $valeurs['heure_debut']) && preg_match('/^\d{2}:\d{2}$/', $valeurs['heure_fin']);
    if ($erreur === '' && !$formatValide) {
        $erreur = "Indiquez la date et les horaires de l'événement.";
    }
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
            date('Y-m-d H:i:s', strtotime($valeurs['date_debut'])), date('Y-m-d H:i:s', strtotime($valeurs['date_fin'])), (int) $valeurs['places_max'],
        ];
        $pdo->beginTransaction();
        try {
            if ($evt) {
                $donnees[] = $image ?? $evt['image'];
                array_push($donnees, $id, $idAuteur);
                $pdo->prepare("UPDATE evenement SET titre = ?, description = ?, type = ?, auteurs = ?, lieu = ?, date_debut = ?, date_fin = ?, places_max = ?, image = ?
                               WHERE id_evenement = ? AND id_createur = ? AND statut = 'en_attente'")->execute($donnees);
                $idEvenement = $id;
            } else {
                array_push($donnees, $image, $idAuteur);
                $pdo->prepare("INSERT INTO evenement (titre, description, type, auteurs, lieu, date_debut, date_fin, places_max, attente_auto, image, id_createur, statut)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, 'en_attente')")->execute($donnees);
                $idEvenement = (int) $pdo->lastInsertId();
            }
            enregistrerLivresEvenement($pdo, $idEvenement, $livresCoches);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        insertLog('EVENEMENT', ($evt ? "Modification" : "Proposition") . " de l'événement #$idEvenement par l'auteur #$idAuteur : {$valeurs['titre']}");
        $_SESSION['flash_auteur'] = ['success', $evt
            ? "Votre proposition a été mise à jour."
            : "Merci ! Votre proposition « {$valeurs['titre']} » est envoyée à l'équipe. Vous recevrez un mail dès qu'elle sera examinée."];
        header("Location: dashboard_auteur.php");
        exit();
    }
}

$livresProposes = livresAssociables($pdo, $idAuteur);
$flash = $_SESSION['flash_auteur'] ?? null;
unset($_SESSION['flash_auteur']);
$csrf = jetonCsrf();
$v = fn(string $champ) => htmlspecialchars((string) ($valeurs[$champ] ?? ''));
$scriptsPage = ['choix_livres.js'];

include 'header.php';
?>

<div class="container mt-4 mb-5 page-proposition">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <a href="dashboard_auteur.php">Espace créateur</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?= $evt ? 'Modifier ma proposition' : 'Proposer un événement' ?></span>
    </nav>
    <h1 class="titre-page-compte"><?= $evt ? 'Modifier ma proposition' : 'Proposer un événement' ?></h1>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>
    <?php if ($erreur): ?>
        <div class="alert alert-danger rounded-3" role="alert"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> <?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <div class="grille-proposition">
        <form method="POST" enctype="multipart/form-data" class="carte-compte formulaire-proposition">
            <input type="hidden" name="csrf" value="<?= $csrf ?>">
            <input type="hidden" name="action" value="enregistrer">
            <?php if ($evt): ?><input type="hidden" name="id_evenement" value="<?= $id ?>"><?php endif; ?>

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label small fw-bold" for="titre">Titre *</label>
                    <input type="text" id="titre" name="titre" class="form-control" maxlength="200" value="<?= $v('titre') ?>" required placeholder="Ex : Dédicace de mon nouveau roman">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold" for="type">Type *</label>
                    <select id="type" name="type" class="form-select">
                        <?php foreach (EVT_TYPES as $cle => [$libelle]): ?>
                            <option value="<?= $cle ?>" <?= ($valeurs['type'] ?? '') === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-md-4">
                    <label class="form-label small fw-bold" for="jour">Date *</label>
                    <input type="date" id="jour" name="jour" class="form-control" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= $v('jour') ?>" required>
                </div>
                <div class="col-6 col-sm-3 col-md-4">
                    <label class="form-label small fw-bold" for="heure_debut">Début *</label>
                    <input type="time" id="heure_debut" name="heure_debut" class="form-control" value="<?= $v('heure_debut') ?>" required>
                </div>
                <div class="col-6 col-sm-3 col-md-4">
                    <label class="form-label small fw-bold" for="heure_fin">Fin *</label>
                    <input type="time" id="heure_fin" name="heure_fin" class="form-control" value="<?= $v('heure_fin') ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label small fw-bold" for="lieu">Lieu *</label>
                    <input type="text" id="lieu" name="lieu" class="form-control" maxlength="255" value="<?= $v('lieu') ?>" required placeholder="Ex : Librairie du Centre, Lyon 2e">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold" for="places_max">Nombre de places *</label>
                    <input type="number" id="places_max" name="places_max" class="form-control" min="1" max="<?= EVT_PLACES_MAX ?>" value="<?= $v('places_max') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold" for="auteurs">Intervenant(s)</label>
                    <input type="text" id="auteurs" name="auteurs" class="form-control" maxlength="255" value="<?= $v('auteurs') ?>" placeholder="Séparez les noms par des virgules">
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold" for="description">Description *</label>
                    <textarea id="description" name="description" class="form-control" rows="6" required placeholder="Le déroulé, à qui s'adresse l'événement, ce que les participants peuvent apporter…"><?= $v('description') ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold" for="image">Image (facultative : JPG, PNG ou WebP, 2 Mo maximum)</label>
                    <div class="d-flex gap-3 align-items-center">
                        <?php if (!empty($evt['image'])): ?><img src="<?= htmlspecialchars(imageEvenement($evt['image'])) ?>" alt="Image actuelle" class="apercu-image-evenement"><?php endif; ?>
                        <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>
                <div class="col-12">
                    <?php include 'partie_choix_livres.php'; ?>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4">
                <button class="bouton bouton-principal"><i class="bi bi-send" aria-hidden="true"></i> <?= $evt ? 'Enregistrer les modifications' : 'Envoyer ma proposition' ?></button>
                <a href="dashboard_auteur.php" class="bouton bouton-discret">Annuler</a>
            </div>
        </form>

        <aside class="d-flex flex-column gap-3">
            <section class="carte-compte etapes-proposition">
                <h2 class="carte-compte__titre"><?= iconeDessin('ticket') ?> Comment ça marche ?</h2>
                <ol>
                    <li><strong>Vous proposez</strong> votre dédicace, rencontre ou atelier.</li>
                    <li><strong>L'équipe l'examine</strong>, elle peut l'ajuster avec vous ou la refuser en expliquant pourquoi.</li>
                    <li><strong>Une fois validé</strong>, l'événement apparaît dans le calendrier avec « Proposé par <?= htmlspecialchars($monNom) ?> ».</li>
                    <li><strong>Vous suivez les inscriptions</strong> depuis votre espace créateur.</li>
                </ol>
                <p class="small text-muted mb-0">Vous recevez un mail à chaque décision. Tant que la proposition est en attente, vous pouvez la modifier ou la retirer.</p>
            </section>
            <?php if ($evt): ?>
                <form method="POST" class="carte-compte" data-confirmer="Retirer votre proposition « <?= htmlspecialchars($evt['titre']) ?> » ? L'équipe ne l'examinera pas."
                      data-titre-confirmer="Retirer la proposition" data-bouton-confirmer="Retirer">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="retirer">
                    <input type="hidden" name="id_evenement" value="<?= $id ?>">
                    <h2 class="h6 fw-bold mb-1">Vous avez changé d'avis ?</h2>
                    <p class="small text-muted">Retirer la proposition l'enlève de la file de l'équipe.</p>
                    <button class="bouton bouton-danger bouton-petit"><i class="bi bi-x-circle" aria-hidden="true"></i> Retirer ma proposition</button>
                </form>
            <?php endif; ?>
        </aside>
    </div>
</div>

<?php include 'footer.php'; ?>
