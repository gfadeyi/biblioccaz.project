<?php
require_once 'config.php';
require_once 'dons_lib.php';
require_once 'categories_lib.php';
require_once 'avis_lib.php';
require_once 'upload_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
        header('Location: mes_dons.php'); exit();
    }
}
$erreurs = [];
$v = array_merge([
    'id_livre' => '', 'titre' => '', 'auteur' => '', 'id_categorie' => '', 'etat' => 'bon',
    'description' => '', 'lieu_retrait' => '', 'conditions_retrait' => '',
    'date_limite' => date('Y-m-d', strtotime('+14 days')),
], array_map(fn($valeur) => $valeur === null ? '' : (string) $valeur, $donAModifier ?: []), array_map('trim', array_filter($_POST, 'is_string')));

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !empty($_GET['livre'])) {
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
    if (!$erreurs) {
        [$photo, $erreurPhoto] = enregistrerCouverture('photo', 'dons/');
        if ($erreurPhoto !== '') {
            $erreurs[] = $erreurPhoto;
        } elseif ($photo === null && empty($donAModifier['photo'])) {
            $erreurs[] = "Ajoutez une photo du livre que vous donnez pour montrer son état.";
        }
    }

    $idLivre = null;
    if ($v['id_livre'] !== '') {
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
            $donnees = [$idLivre, $v['id_categorie'] !== '' ? (int) $v['id_categorie'] : null, $v['titre'], $v['auteur'] ?: null, $v['etat'], $v['description'] ?: null, $photo ?: ($donAModifier['photo'] ?? null), $v['lieu_retrait'], $v['conditions_retrait'] ?: null, $v['date_limite']];
            if ($idDon) {
                $requete = $pdo->prepare('SELECT statut FROM don WHERE id_don = ? AND id_donneur = ? FOR UPDATE');
                $requete->execute([$idDon, (int) $_SESSION['user_id']]);
                if (!in_array($requete->fetchColumn(), ['en_validation', 'refuse', 'disponible'], true)) { throw new DomainException('La réservation ou le statut de ce don a changé. Rechargez la page.'); }
                $pdo->prepare("UPDATE don SET id_livre = ?, id_categorie = ?, titre = ?, auteur = ?, etat = ?, description = ?, photo = ?, lieu_retrait = ?, conditions_retrait = ?, date_limite = ?, statut = 'en_validation' WHERE id_don = ?")
                    ->execute([...$donnees, $idDon]);
            } else {
                $pdo->prepare("INSERT INTO don (id_livre, id_categorie, titre, auteur, etat, description, photo, lieu_retrait, conditions_retrait, date_limite, id_donneur, code, code_court, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_validation')")
                    ->execute([...$donnees, (int) $_SESSION['user_id'], bin2hex(random_bytes(16)), genererCodeCourt($pdo)]);
                $idDon = (int) $pdo->lastInsertId();
            }
            $pdo->commit();
            $anciennePhoto = basename((string) ($donAModifier['photo'] ?? ''));
            if ($photo && $anciennePhoto !== '' && $anciennePhoto !== $photo && is_file(__DIR__ . '/img/dons/' . $anciennePhoto)) {
                unlink(__DIR__ . '/img/dons/' . $anciennePhoto);
            }
            insertLog('DON', 'Don #' . $idDon . ' soumis à validation');
            $_SESSION['flash_don'] = ['success', 'Votre don a été envoyé à l’équipe pour validation. Il sera réservable après acceptation.'];
            header('Location: don_detail.php?id=' . $idDon); exit();
        } catch (Throwable $erreur) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            if ($photo && is_file(__DIR__ . '/img/dons/' . $photo)) { unlink(__DIR__ . '/img/dons/' . $photo); }
            $erreurs[] = $erreur instanceof DomainException ? $erreur->getMessage() : 'Le don n’a pas pu être enregistré. Merci de réessayer.';
        }
    }
}

$livresCatalogue = $pdo->query("SELECT id_livre, titre, auteur, id_categorie FROM livre WHERE is_valide = 1 ORDER BY titre")->fetchAll();
$arbreCategoriesDon = arbreCategories($pdo);

include 'header.php';
?>

<div class="container my-5" style="max-width: 760px;">
    <a href="vendre.php" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Donner ou revendre</a>
    <h2 class="fw-bold mt-2 mb-4"><?= $donAModifier ? 'Modifier mon don' : 'Proposer un livre en don' ?></h2>

    <?php if ($erreurs): ?>
        <div class="alert alert-danger rounded-3"><ul class="mb-0 ps-3"><?php foreach ($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
        <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
        <p class="text-muted">Chaque nouveau don ou modification est vérifié par l’équipe avant publication. Pour une modification, la photo actuelle est conservée si vous n’en choisissez pas une nouvelle.</p>
        <h5 class="fw-bold mb-3">Le livre</h5>
        <div class="row g-3">
            <div class="col-12">
                <label for="don_titre" class="form-label fw-bold small">Titre *</label>
                <input type="text" name="titre" id="don_titre" class="form-control" list="livres_catalogue" maxlength="255" required
                       value="<?= htmlspecialchars($v['titre']) ?>" placeholder="Commencez à taper : les livres du catalogue sont proposés" autocomplete="off">
                <datalist id="livres_catalogue">
                    <?php foreach ($livresCatalogue as $l): ?>
                        <option value="<?= htmlspecialchars($l['titre']) ?>"><?= htmlspecialchars($l['auteur']) ?></option>
                    <?php endforeach; ?>
                </datalist>
                <input type="hidden" name="id_livre" id="don_id_livre" value="<?= htmlspecialchars($v['id_livre']) ?>">
                <div class="form-text" id="don_lien_info"><?= $v['id_livre'] !== '' ? '✓ Livre lié au catalogue' : '' ?></div>
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

<script>
const livres = <?= json_encode(array_map(fn($l) => ['id' => $l['id_livre'], 't' => $l['titre'], 'a' => $l['auteur'], 'c' => $l['id_categorie']], $livresCatalogue), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
document.getElementById('don_titre').addEventListener('input', function () {
    const trouve = livres.find(l => l.t.toLowerCase() === this.value.trim().toLowerCase());
    document.getElementById('don_id_livre').value = trouve ? trouve.id : '';
    document.getElementById('don_lien_info').textContent = trouve ? '✓ Livre lié au catalogue' : '';
    if (trouve) {
        if (!document.getElementById('don_auteur').value) document.getElementById('don_auteur').value = trouve.a || '';
        if (trouve.c) document.getElementById('don_categorie').value = trouve.c;
    }
});
</script>

<script src="javascript/don_photo.js?v=<?= filemtime(__DIR__ . '/javascript/don_photo.js') ?>" defer></script>
<?php include 'footer.php'; ?>
