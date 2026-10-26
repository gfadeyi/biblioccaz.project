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

$erreurs = [];
$v = array_merge([
    'id_livre' => '', 'titre' => '', 'auteur' => '', 'id_categorie' => '', 'etat' => 'bon',
    'description' => '', 'lieu_retrait' => '', 'conditions_retrait' => '',
    'date_limite' => date('Y-m-d', strtotime('+14 days')),
], array_map('trim', array_filter($_POST, 'is_string')));

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
    if (!$dateLimite || $dateLimite < $min || $dateLimite > $max) {
        $erreurs[] = "La date limite doit être entre aujourd'hui et dans " . DON_DUREE_MAX_JOURS . " jours.";
    }

    $photo = null;
    if (!$erreurs) {
        [$photo, $erreurPhoto] = enregistrerCouverture('photo', 'dons/');
        if ($erreurPhoto !== '') {
            $erreurs[] = $erreurPhoto;
        }
    }

    $idLivre = null;
    if ($v['id_livre'] !== '') {
        $stmt = $pdo->prepare("SELECT id_livre FROM livre WHERE id_livre = ?");
        $stmt->execute([(int) $v['id_livre']]);
        $idLivre = $stmt->fetchColumn() ?: null;
    }

    if (!$erreurs) {
        $stmt = $pdo->prepare("
            INSERT INTO don (id_donneur, id_livre, id_categorie, titre, auteur, etat, description, photo,
                             lieu_retrait, conditions_retrait, date_limite, code, code_court)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'], $idLivre, $v['id_categorie'] !== '' ? (int) $v['id_categorie'] : null,
            $v['titre'], $v['auteur'] ?: null, $v['etat'], $v['description'] ?: null, $photo,
            $v['lieu_retrait'], $v['conditions_retrait'] ?: null, $v['date_limite'],
            bin2hex(random_bytes(16)), genererCodeCourt($pdo),
        ]);
        $idDon = (int) $pdo->lastInsertId();
        insertLog('DON', "Nouveau don #$idDon : " . $v['titre']);
        $_SESSION['flash_don'] = ['success', "Votre don est publié ! Gardez ce QR code : la personne qui viendra chercher le livre devra le scanner."];
        header("Location: don_detail.php?id=$idDon");
        exit();
    }
}

$livresCatalogue = $pdo->query("SELECT id_livre, titre, auteur, id_categorie FROM livre WHERE is_valide = 1 ORDER BY titre")->fetchAll();
$arbreCategoriesDon = arbreCategories($pdo);

include 'header.php';
?>

<div class="container my-5" style="max-width: 760px;">
    <a href="vendre.php" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Donner ou revendre</a>
    <h2 class="fw-bold mt-2 mb-4">Proposer un livre en don</h2>

    <?php if ($erreurs): ?>
        <div class="alert alert-danger rounded-3"><ul class="mb-0 ps-3"><?php foreach ($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
        <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
        <h5 class="fw-bold mb-3">Le livre</h5>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-bold small">Titre *</label>
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
                <label class="form-label fw-bold small">Auteur</label>
                <input type="text" name="auteur" id="don_auteur" class="form-control" maxlength="255" value="<?= htmlspecialchars($v['auteur']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small">Catégorie</label>
                <select name="id_categorie" id="don_categorie" class="form-select">
                    <option value="">—</option>
                    <?= optionsCategories($arbreCategoriesDon, $v['id_categorie']) ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small">État *</label>
                <select name="etat" class="form-select" required>
                    <?php foreach (DON_ETATS as $cle => $libelle): ?>
                        <option value="<?= $cle ?>" <?= $v['etat'] === $cle ? 'selected' : '' ?>><?= $libelle ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold small">Photo (facultative)</label>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label fw-bold small">Description</label>
                <textarea name="description" rows="3" class="form-control" maxlength="1000" placeholder="Annotations, pages cornées, édition..."><?= htmlspecialchars($v['description']) ?></textarea>
            </div>
        </div>

        <h5 class="fw-bold mt-4 mb-3">Le retrait</h5>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label fw-bold small">Lieu de retrait *</label>
                <input type="text" name="lieu_retrait" class="form-control" maxlength="255" required value="<?= htmlspecialchars($v['lieu_retrait']) ?>"
                       placeholder="Ex : Lyon 3e, devant la médiathèque">
                <div class="form-text">Préférez un lieu public. Ne mettez pas votre adresse exacte : vous pourrez la donner à la personne après la réservation.</div>
            </div>
            <div class="col-md-8">
                <label class="form-label fw-bold small">Disponibilités</label>
                <input type="text" name="conditions_retrait" class="form-control" maxlength="255" value="<?= htmlspecialchars($v['conditions_retrait']) ?>" placeholder="Ex : en semaine après 18h, samedi matin">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold small">À récupérer avant le *</label>
                <input type="date" name="date_limite" class="form-control" required value="<?= htmlspecialchars($v['date_limite']) ?>"
                       min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+' . DON_DUREE_MAX_JOURS . ' days')) ?>">
            </div>
        </div>

        <button class="btn btn-success rounded-pill fw-bold py-2 mt-4">Publier mon don et générer le QR code</button>
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

<?php include 'footer.php'; ?>
