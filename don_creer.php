<?php
require_once 'config.php';
require_once 'dons_lib.php';
require_once 'categories_lib.php';
require_once 'avis_lib.php';
require_once 'upload_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion");
    exit();
}

$idDon = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$donAModifier = null;
if ($idDon) {
    $requete = $pdo->prepare('SELECT * FROM don WHERE id_don = ? AND id_donneur = ?');
    $requete->execute([$idDon, (int) $_SESSION['user_id']]);
    $donAModifier = $requete->fetch();
    if (!$donAModifier || !in_array(statutDon($donAModifier), ['en_validation', 'refuse', 'disponible', 'expire'], true)) {
        $_SESSION['flash_don'] = ['warning', 'Ce don ne peut pas être modifié.'];
        header('Location: mes_dons'); exit();
    }
}
$photosDisponibles = photosDonDisponibles($pdo);
$photosExistantes = $donAModifier ? photosSupplementairesDon($pdo, (int) $donAModifier['id_don']) : [];
$erreurs = [];
$v = array_merge([
    'id_livre' => '', 'titre' => '', 'auteur' => '', 'id_categorie' => '', 'etat' => 'bon',
    'description' => '', 'lieu_retrait' => '', 'conditions_retrait' => '',
    'date_limite' => date('Y-m-d', strtotime('+14 days')), 'id_commande_ligne' => '',
], array_map(fn($valeur) => $valeur === null ? '' : (string) $valeur, $donAModifier ?: []), array_map('trim', array_filter($_POST, 'is_string')));

$ligneCommande = null;
$idLigneDemandee = $donAModifier ? 0 : (int) ($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id_commande_ligne'] ?? 0) : ($_GET['ligne'] ?? 0));
if ($idLigneDemandee > 0) {
    $ligneCommande = ligneCommandeADonner($pdo, $idLigneDemandee, (int) $_SESSION['user_id']);
    if (!$ligneCommande) {
        $_SESSION['flash_message'] = ['warning', 'Ce livre ne peut pas être donné depuis cette commande : elle doit être livrée et chaque exemplaire ne se donne qu’une fois.'];
        header('Location: commandes'); exit();
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $v = array_merge($v, ['id_livre' => (string) $ligneCommande['id_livre'], 'titre' => (string) $ligneCommande['titre'], 'auteur' => (string) ($ligneCommande['auteur'] ?? ''), 'id_categorie' => (string) ($ligneCommande['id_categorie'] ?? ''), 'etat' => etatDonDepuisCommande((string) $ligneCommande['etat'])]);
    }
    $v['id_commande_ligne'] = (string) $ligneCommande['id_ligne'];
} elseif ($_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($_GET['livre'])) {
    $stmt = $pdo->prepare("SELECT id_livre, titre, auteur, id_categorie FROM livre WHERE id_livre = ? AND is_valide = 1");
    $stmt->execute([(int) $_GET['livre']]);
    if ($l = $stmt->fetch()) {
        $v = array_merge($v, array_map('strval', $l));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) { $erreurs[] = "Session expirée, merci de réessayer."; }
    if ($v['titre'] === '' || mb_strlen($v['titre']) > 255) { $erreurs[] = "Indiquez le titre du livre."; }
    if (!isset(DON_ETATS[$v['etat']])) { $erreurs[] = "Choisissez l'état du livre."; }
    if ($v['lieu_retrait'] === '') { $erreurs[] = "Indiquez où récupérer le livre (ville, quartier, lieu public...)."; }

    $dateLimite = DateTime::createFromFormat('Y-m-d', $v['date_limite']);
    $min = new DateTime('today');
    $max = (new DateTime('today'))->modify('+' . DON_DUREE_MAX_JOURS . ' days');
    if (!$dateLimite || $dateLimite->format('Y-m-d') !== $v['date_limite'] || $dateLimite < $min || $dateLimite > $max) {
        $erreurs[] = "La date limite doit être entre aujourd'hui et dans " . DON_DUREE_MAX_JOURS . " jours.";
    }

    $photo = null;
    $nouvellesPhotos = [];
    $photosRetirees = [];
    if ($photosDisponibles) {
        $idsARetirer = array_map('intval', is_array($_POST['retirer_photos'] ?? null) ? $_POST['retirer_photos'] : []);
        $photosRetirees = array_values(array_filter($photosExistantes, fn($p) => in_array((int) $p['id_photo'], $idsARetirer, true)));
        $envois = fichiersEnvoyes('photos');
        if (count($photosExistantes) - count($photosRetirees) + count($envois) > DON_PHOTOS_MAX - 1) {
            $erreurs[] = 'Vous pouvez ajouter ' . (DON_PHOTOS_MAX - 1) . ' photos en plus de la photo principale, soit ' . DON_PHOTOS_MAX . ' au total.';
        }
    }
    if (!$erreurs) {
        [$photo, $erreurPhoto] = enregistrerCouverture('photo', 'dons/');
        if ($erreurPhoto !== '') {
            $erreurs[] = $erreurPhoto;
        } elseif ($photo === null && empty($donAModifier['photo'])) {
            $erreurs[] = "Ajoutez une photo du livre que vous donnez pour montrer son état.";
        }
    }
    if (!$erreurs && $photosDisponibles) {
        foreach ($envois as $envoi) {
            [$fichierPhoto, $erreurPhoto] = enregistrerImageEnvoyee($envoi, 'dons/');
            if ($erreurPhoto !== '') {
                $erreurs[] = $erreurPhoto;
                break;
            }
            $nouvellesPhotos[] = $fichierPhoto;
        }
        if ($erreurs) {
            foreach (array_filter([$photo, ...$nouvellesPhotos]) as $fichierPhoto) {
                @unlink(__DIR__ . '/img/dons/' . $fichierPhoto);
            }
            $photo = null;
            $nouvellesPhotos = [];
        }
    }

    $idLivre = null;
    if ($ligneCommande) {
        $idLivre = (int) $ligneCommande['id_livre'];
    } elseif ($v['id_livre'] !== '') {
        $stmt = $pdo->prepare("SELECT id_livre FROM livre WHERE id_livre = ? AND is_valide = 1");
        $stmt->execute([(int) $v['id_livre']]);
        $idLivre = $stmt->fetchColumn() ?: null;
    }

    if (!$erreurs) {
        try {
            $pdo->beginTransaction();
            $requete = $pdo->prepare('SELECT statut, email_verifie FROM `user` WHERE id = ? FOR UPDATE');
            $requete->execute([(int) $_SESSION['user_id']]);
            $donneur = $requete->fetch();
            if (!$donneur || !(int) $donneur['email_verifie'] || !in_array($donneur['statut'], ['actif', 'valide_moderateur'], true)) { throw new DomainException('Votre compte doit être actif et votre email confirmé.'); }
            if ($ligneCommande && !ligneCommandeADonner($pdo, (int) $ligneCommande['id_ligne'], (int) $_SESSION['user_id'], true)) { throw new DomainException('Ce livre de votre commande a déjà été proposé en don.'); }
            $donnees = [$idLivre, $v['id_categorie'] !== '' ? (int) $v['id_categorie'] : null, $v['titre'], $v['auteur'] ?: null, $v['etat'], $v['description'] ?: null, $photo ?: ($donAModifier['photo'] ?? null), $v['lieu_retrait'], $v['conditions_retrait'] ?: null, $v['date_limite']];
            if ($idDon) {
                $requete = $pdo->prepare('SELECT statut FROM don WHERE id_don = ? AND id_donneur = ? FOR UPDATE');
                $requete->execute([$idDon, (int) $_SESSION['user_id']]);
                if (!in_array($requete->fetchColumn(), ['en_validation', 'refuse', 'disponible'], true)) { throw new DomainException('La réservation ou le statut de ce don a changé. Rechargez la page.'); }
                $pdo->prepare("UPDATE don SET id_livre = ?, id_categorie = ?, titre = ?, auteur = ?, etat = ?, description = ?, photo = ?, lieu_retrait = ?, conditions_retrait = ?, date_limite = ?, statut = 'en_validation' WHERE id_don = ?")
                    ->execute([...$donnees, $idDon]);
            } elseif ($ligneCommande) {
                $pdo->prepare("INSERT INTO don (id_livre, id_categorie, titre, auteur, etat, description, photo, lieu_retrait, conditions_retrait, date_limite, id_donneur, code, code_court, statut, id_commande_ligne) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_validation', ?)")
                    ->execute([...$donnees, (int) $_SESSION['user_id'], bin2hex(random_bytes(16)), genererCodeCourt($pdo), (int) $ligneCommande['id_ligne']]);
                $idDon = (int) $pdo->lastInsertId();
            } else {
                $pdo->prepare("INSERT INTO don (id_livre, id_categorie, titre, auteur, etat, description, photo, lieu_retrait, conditions_retrait, date_limite, id_donneur, code, code_court, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_validation')")
                    ->execute([...$donnees, (int) $_SESSION['user_id'], bin2hex(random_bytes(16)), genererCodeCourt($pdo)]);
                $idDon = (int) $pdo->lastInsertId();
            }
            if ($photosDisponibles) {
                foreach ($photosRetirees as $photoRetiree) {
                    $pdo->prepare('DELETE FROM don_photo WHERE id_photo = ? AND id_don = ?')->execute([(int) $photoRetiree['id_photo'], $idDon]);
                }
                $ordre = count($photosExistantes) - count($photosRetirees);
                foreach ($nouvellesPhotos as $fichierPhoto) {
                    $pdo->prepare('INSERT INTO don_photo (id_don, fichier, ordre) VALUES (?, ?, ?)')->execute([$idDon, $fichierPhoto, ++$ordre]);
                }
            }
            $pdo->commit();
            foreach ($photosRetirees as $photoRetiree) {
                @unlink(__DIR__ . '/img/dons/' . basename($photoRetiree['fichier']));
            }
            $anciennePhoto = basename((string) ($donAModifier['photo'] ?? ''));
            if ($photo && $anciennePhoto !== '' && $anciennePhoto !== $photo && is_file(__DIR__ . '/img/dons/' . $anciennePhoto)) {
                unlink(__DIR__ . '/img/dons/' . $anciennePhoto);
            }
            insertLog('DON', 'Don #' . $idDon . ' soumis à validation');
            $_SESSION['flash_don'] = ['success', 'Votre don a été envoyé à l’équipe pour validation. Il sera réservable après acceptation.'];
            header('Location: don_detail?id=' . $idDon); exit();
        } catch (Throwable $erreur) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            foreach (array_filter([$photo, ...$nouvellesPhotos]) as $fichierPhoto) {
                if (is_file(__DIR__ . '/img/dons/' . $fichierPhoto)) { unlink(__DIR__ . '/img/dons/' . $fichierPhoto); }
            }
            $erreurs[] = $erreur instanceof DomainException ? $erreur->getMessage() : 'Le don n’a pas pu être enregistré. Merci de réessayer.';
        }
    }
}

$arbreCategoriesDon = arbreCategories($pdo);

include 'header.php';
?>

<div class="container my-5" style="max-width: 760px;">
    <a href="vendre" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Donner ou revendre</a>
    <h2 class="fw-bold mt-2 mb-4"><?= $donAModifier ? 'Modifier mon don' : 'Proposer un livre en don' ?></h2>

    <?php if ($erreurs): ?>
        <div class="alert alert-danger rounded-3"><ul class="mb-0 ps-3"><?php foreach ($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
        <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
        <?php if ($ligneCommande): ?>
            <input type="hidden" name="id_commande_ligne" value="<?= (int) $ligneCommande['id_ligne'] ?>">
            <div class="alert alert-success rounded-3 d-flex gap-2 align-items-start" role="note"><i class="bi bi-bag-check" aria-hidden="true"></i><span>Vous donnez un livre acheté sur BIBLIOccaz (commande n°<?= (int) $ligneCommande['id_commande'] ?>) : titre, auteur, catégorie et état sont déjà remplis. Ajoutez une photo de votre exemplaire et le lieu de retrait.</span></div>
        <?php endif; ?>
        <p class="text-muted">Chaque nouveau don ou modification est vérifié par l’équipe avant publication. Pour une modification, la photo actuelle est conservée si vous n’en choisissez pas une nouvelle.</p>
        <h5 class="fw-bold mb-3">Le livre</h5>
        <div class="row g-3">
            <div class="col-12">
                <label for="don_titre" class="form-label fw-bold small">Titre *</label>
                <div class="recherche-don" data-recherche-don>
                    <input type="text" name="titre" id="don_titre" class="form-control" maxlength="255" required
                           value="<?= htmlspecialchars($v['titre']) ?>" placeholder="Titre, auteur ou ISBN : les livres du catalogue sont proposés" autocomplete="off"
                           role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="don_suggestions" aria-describedby="don_lien_info">
                    <ul class="recherche-don__liste" id="don_suggestions" role="listbox" aria-label="Livres du catalogue" hidden></ul>
                </div>
                <input type="hidden" name="id_livre" id="don_id_livre" value="<?= htmlspecialchars($v['id_livre']) ?>">
                <button type="button" class="bouton-scanner-isbn" data-scanner-isbn data-scanner-mode="saisie" data-scanner-cible="don_titre" hidden><i class="bi bi-upc-scan" aria-hidden="true"></i><span>Scanner un ISBN</span></button>
                <div class="form-text" id="don_lien_info" aria-live="polite"><?= $v['id_livre'] !== '' ? '✓ Livre lié au catalogue' : 'Tapez au moins deux lettres pour retrouver le livre dans le catalogue, ou saisissez un titre libre.' ?></div>
            </div>
            <div class="col-md-6">
                <label for="don_auteur" class="form-label fw-bold small">Auteur</label>
                <input type="text" name="auteur" id="don_auteur" class="form-control" maxlength="255" value="<?= htmlspecialchars($v['auteur']) ?>">
            </div>
            <div class="col-md-6">
                <label for="don_categorie" class="form-label fw-bold small">Catégorie</label>
                <select name="id_categorie" id="don_categorie" class="form-select">
                    <option value="">—</option>
                    <?= optionsCategories($arbreCategoriesDon, $v['id_categorie']) ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="don-etat" class="form-label fw-bold small">État *</label>
                <select id="don-etat" name="etat" class="form-select" required>
                    <?php foreach (DON_ETATS as $cle => $libelle): ?>
                        <option value="<?= $cle ?>" <?= $v['etat'] === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="don-photo" class="form-label fw-bold small">Photo du livre *</label>
                <input id="don-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control" <?= empty($donAModifier['photo']) ? 'required' : '' ?> aria-describedby="don-photo-aide don-photo-erreur">
                <div id="don-photo-aide" class="form-text">Photographiez votre exemplaire pour montrer son état. JPG, PNG ou WebP, 2 Mo maximum.</div>
                <p id="don-photo-erreur" class="small text-danger mt-2" role="status" hidden></p>
                <img id="don-photo-apercu" alt="Aperçu de la photo de votre livre" class="mt-3 rounded" style="max-width: 100%; max-height: 180px; object-fit: contain;" hidden>
            </div>
            <?php if ($photosDisponibles): ?>
                <div class="col-12">
                    <label for="don-photos" class="form-label fw-bold small">Autres photos (facultatif)</label>
                    <input id="don-photos" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" class="form-control" multiple data-photos-max="<?= DON_PHOTOS_MAX - 1 ?>" data-photos-existantes="<?= count($photosExistantes) ?>" aria-describedby="don-photos-aide don-photos-erreur">
                    <div id="don-photos-aide" class="form-text">Dos, tranche, page intérieure… <?= DON_PHOTOS_MAX ?> photos au total avec la principale, 2 Mo chacune.</div>
                    <p id="don-photos-erreur" class="small text-danger mt-2" role="status" hidden></p>
                    <div class="apercu-photos-don" id="don-photos-apercu"></div>
                    <?php if ($photosExistantes): ?>
                        <fieldset class="photos-don-existantes mt-2">
                            <legend class="small fw-bold mb-1">Photos déjà ajoutées : cochez celles à retirer</legend>
                            <?php foreach ($photosExistantes as $photoExistante): ?>
                                <label class="photo-don-existante">
                                    <input type="checkbox" name="retirer_photos[]" value="<?= (int) $photoExistante['id_photo'] ?>">
                                    <img src="<?= htmlspecialchars(cheminPhotoDon($photoExistante['fichier'])) ?>" alt="Photo supplémentaire" loading="lazy" decoding="async">
                                    <span>Retirer</span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="col-12">
                <label for="don-description" class="form-label fw-bold small">Description</label>
                <textarea id="don-description" name="description" rows="3" class="form-control" maxlength="1000" placeholder="Annotations, pages cornées, édition..."><?= htmlspecialchars($v['description']) ?></textarea>
            </div>
        </div>

        <h5 class="fw-bold mt-4 mb-3">Le retrait</h5>
        <div class="row g-3">
            <div class="col-12">
                <label for="don-lieu-retrait" class="form-label fw-bold small">Lieu de retrait *</label>
                <input id="don-lieu-retrait" type="text" name="lieu_retrait" class="form-control" maxlength="255" required value="<?= htmlspecialchars($v['lieu_retrait']) ?>"
                       placeholder="Ex : Lyon 3e, devant la médiathèque">
                <div class="form-text">Préférez un lieu public. Ne mettez pas votre adresse exacte : vous pourrez la donner à la personne après la réservation.</div>
            </div>
            <div class="col-md-8">
                <label for="don-conditions-retrait" class="form-label fw-bold small">Disponibilités</label>
                <input id="don-conditions-retrait" type="text" name="conditions_retrait" class="form-control" maxlength="255" value="<?= htmlspecialchars($v['conditions_retrait']) ?>" placeholder="Ex : en semaine après 18h, samedi matin">
            </div>
            <div class="col-md-4">
                <label for="don-date-limite" class="form-label fw-bold small">À récupérer avant le *</label>
                <input id="don-date-limite" type="date" name="date_limite" class="form-control" required value="<?= htmlspecialchars($v['date_limite']) ?>"
                       min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+' . DON_DUREE_MAX_JOURS . ' days')) ?>">
            </div>
        </div>

        <button class="btn btn-success rounded-pill fw-bold py-2 mt-4">Envoyer mon don pour validation</button>
    </form>
</div>

<script src="<?= urlStatique('javascript/don_recherche.js') ?>" defer></script>
<script src="javascript/don_photo.js?v=<?= filemtime(__DIR__ . '/javascript/don_photo.js') ?>" defer></script>
<?php include 'footer.php'; ?>
