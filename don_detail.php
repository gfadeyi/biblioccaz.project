<?php
require_once 'config.php';
require_once 'dons_lib.php';
require_once 'messagerie_lib.php';
require_once 'compte_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$stmt = $pdo->prepare("
    SELECT d.*, livre_don.couverture AS couverture_don, c.nom AS categorie_nom,
           dn.pseudo AS donneur_pseudo,
           b.pseudo AS beneficiaire_pseudo
    FROM don d LEFT JOIN livre livre_don ON livre_don.id_livre = d.id_livre
    INNER JOIN user dn ON dn.id = d.id_donneur
    LEFT JOIN user b ON b.id = d.id_beneficiaire
    LEFT JOIN categorie c ON c.id_categorie = d.id_categorie
    WHERE d.id_don = ?");
$stmt->execute([(int) ($_GET['id'] ?? 0)]);
$don = $stmt->fetch();

include 'header.php';

if (!$don) {
    echo "<div class='container my-5'><div class='alert alert-warning'>Ce don n'existe pas.</div><a href='dons.php'>Voir les dons disponibles</a></div>";
    include 'footer.php';
    exit();
}

$idUser = (int) ($_SESSION['user_id'] ?? 0);
$estDonneur = $idUser && $idUser === (int) $don['id_donneur'];
$estBeneficiaire = $idUser && $idUser === (int) $don['id_beneficiaire'];
$estAdmin = ($_SESSION['role'] ?? '') === 'admin';
$statut = statutDon($don);
$flash = $_SESSION['flash_don'] ?? null;
unset($_SESSION['flash_don']);
$csrf = $idUser ? jetonCsrf() : '';
$conversation = $idUser ? derniereConversationObjet($pdo, 'don', (int) $don['id_don'], $idUser) : null;
$messages = [];
if ($conversation) {
    $messages = messagesConversation($pdo, (int) $conversation['id_conversation']);
    marquerConversationLue($pdo, (int) $conversation['id_conversation'], $idUser);
}
$retourConversation = 'objet';
$flashConversation = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
$scriptsPage = ['messagerie.js'];

function boutonActionDon(string $action, int $idDon, string $csrf, string $libelle, string $classe, string $confirmation = ''): string
{
    $attributConfirmation = $confirmation ? ' data-confirmer="' . htmlspecialchars($confirmation) . '"' : '';
    return "<form action=\"don_action.php\" method=\"POST\"$attributConfirmation>
        <input type=\"hidden\" name=\"csrf\" value=\"$csrf\">
        <input type=\"hidden\" name=\"action\" value=\"$action\">
        <input type=\"hidden\" name=\"id_don\" value=\"$idDon\">
        <button class=\"btn $classe rounded-pill w-100\">$libelle</button>
    </form>";
}
?>

<div class="container my-5">
    <a href="dons.php" class="text-decoration-none small text-muted"><i class="bi bi-arrow-left"></i> Tous les dons</a>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash[0] === 'success' ? 'success' : 'danger' ?> rounded-3 mt-3"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <div class="row g-4 mt-1">
        <div class="col-md-4">
            <div class="bg-light rounded-4 d-flex align-items-center justify-content-center p-3" style="height: 340px;">
                <img src="<?= htmlspecialchars(photoDon($don['photo'], $don['couverture_don'])) ?>" alt="<?= htmlspecialchars(legendePhotoDon($don['photo'], $don['couverture_don']) . ' : ' . $don['titre']) ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;">
            </div>
            <p class="small text-muted mt-2 mb-0"><?= htmlspecialchars(legendePhotoDon($don['photo'], $don['couverture_don'])) ?></p>
        </div>

        <div class="col-md-5">
            <div class="d-flex align-items-center gap-2 mb-2">
                <?= badgeStatutDon($don) ?>
                <span class="badge bg-light text-dark border rounded-pill"><i class="bi bi-gift"></i> Don gratuit</span>
                <?php if ($don['categorie_nom']): ?><span class="badge rounded-pill" style="background:#274e13;"><?= htmlspecialchars($don['categorie_nom']) ?></span><?php endif; ?>
            </div>
            <h1 class="fw-bold"><?= htmlspecialchars($don['titre']) ?></h1>
            <?php if ($don['auteur']): ?><p class="text-muted h5"><?= htmlspecialchars($don['auteur']) ?></p><?php endif; ?>
            <?php if ($don['id_livre']): ?>
                <a href="detail_livre.php?id=<?= $don['id_livre'] ?>" class="small">Voir la fiche du livre et ses avis →</a>
            <?php endif; ?>

            <table class="table table-sm mt-3">
                <tr><th>État</th><td><?= DON_ETATS[$don['etat']] ?? htmlspecialchars($don['etat']) ?></td></tr>
                <tr><th>Lieu de retrait</th><td><i class="bi bi-geo-alt text-success"></i> <?= htmlspecialchars($don['lieu_retrait']) ?></td></tr>
                <?php if ($don['conditions_retrait']): ?><tr><th>Disponibilités</th><td><?= htmlspecialchars($don['conditions_retrait']) ?></td></tr><?php endif; ?>
                <tr><th>À récupérer avant le</th><td><?= date('d/m/Y', strtotime($don['date_limite'])) ?></td></tr>
                <tr><th>Proposé par</th><td><?= htmlspecialchars($don['donneur_pseudo']) ?>, le <?= date('d/m/Y', strtotime($don['date_creation'])) ?></td></tr>
                <?php if ($statut === 'recupere'): ?><tr><th>Récupéré le</th><td><?= date('d/m/Y à H:i', strtotime($don['date_recuperation'])) ?></td></tr><?php endif; ?>
            </table>
            <?php if ($don['description']): ?>
                <h6 class="fw-bold">Description</h6>
                <p class="text-secondary" style="white-space: pre-line;"><?= htmlspecialchars($don['description']) ?></p>
            <?php endif; ?>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">

                <?php if ($estDonneur): ?>
                    <h6 class="fw-bold text-center">QR code du don</h6>
                    <div id="qr-don" class="text-center my-2" data-url="<?= htmlspecialchars(urlValidationDon($don)) ?>"></div>
                    <div class="text-center small text-muted">Code de secours</div>
                    <div class="text-center fs-4 fw-bold font-monospace mb-2" style="letter-spacing: 4px;"><?= $don['code_court'] ?></div>
                    <?php if ($statut === 'reserve' || $statut === 'disponible'): ?>
                        <p class="small text-muted text-center">Au moment du retrait, faites scanner ce QR code (ou donnez ce code) à la personne qui récupère le livre.</p>
                        <button class="btn btn-outline-secondary btn-sm rounded-pill mb-3" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
                    <?php endif; ?>

                    <?php if ($statut === 'reserve'): ?>
                        <div class="alert alert-warning small">
                            Réservé par <strong><?= htmlspecialchars($don['beneficiaire_pseudo']) ?></strong>
                            le <?= date('d/m/Y', strtotime($don['date_reservation'])) ?>.<br>
                            <a href="#conversation">Convenir du retrait avec @<?= htmlspecialchars($don['beneficiaire_pseudo']) ?></a>
                        </div>
                        <?= boutonActionDon('liberer', (int) $don['id_don'], $csrf, 'Libérer la réservation', 'btn-outline-warning btn-sm mb-2', 'La personne ne viendra pas ? Le don redeviendra disponible pour les autres.') ?>
                    <?php endif; ?>
                    <?php if (in_array($statut, ['disponible', 'reserve', 'expire'], true)): ?>
                        <?= boutonActionDon('annuler', (int) $don['id_don'], $csrf, 'Annuler le don', 'btn-outline-danger btn-sm', 'Annuler définitivement ce don ?') ?>
                    <?php endif; ?>

                <?php elseif ($estBeneficiaire && $statut === 'reserve'): ?>
                    <h6 class="fw-bold"><i class="bi bi-bookmark-check text-success"></i> Vous avez réservé ce livre</h6>
                    <p class="small">Convenez du jour et du lieu de retrait avec <strong>@<?= htmlspecialchars($don['donneur_pseudo']) ?></strong> <a href="#conversation">dans la conversation ci-dessous</a>.</p>
                    <p class="small text-muted">Sur place, scannez le QR code du donneur avec votre téléphone, ou saisissez le code qu'il vous donnera :</p>
                    <a href="don_valider.php" class="btn btn-success rounded-pill w-100 mb-2"><i class="bi bi-qr-code-scan"></i> Saisir le code</a>
                    <?= boutonActionDon('annuler_reservation', (int) $don['id_don'], $csrf, 'Annuler ma réservation', 'btn-link btn-sm text-danger', 'Annuler votre réservation ?') ?>

                <?php elseif ($estBeneficiaire && $statut === 'recupere'): ?>
                    <div class="text-center"><i class="bi bi-check-circle-fill text-success fs-1"></i><p class="fw-bold mt-2 mb-0">Vous avez récupéré ce livre. Bonne lecture !</p></div>

                <?php elseif ($statut === 'disponible'): ?>
                    <h5 class="fw-bold text-success mb-3">Gratuit</h5>
                    <?php if (!$idUser): ?>
                        <a href="login.php" class="btn btn-success rounded-pill w-100">Se connecter pour réserver</a>
                    <?php else: ?>
                        <?= boutonActionDon('reserver', (int) $don['id_don'], $csrf, '<i class="bi bi-bookmark-plus"></i> Réserver ce livre', 'btn-success fw-bold') ?>
                        <p class="small text-muted mt-2 mb-0">Une conversation s'ouvrira avec le donneur pour organiser le retrait. Vos adresses email restent privées.</p>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="text-muted mb-0 text-center">Ce don n'est plus disponible.</p>
                <?php endif; ?>

                <?php if ($estAdmin && !$estDonneur && in_array($statut, ['disponible', 'reserve', 'expire'], true)): ?>
                    <hr>
                    <?= boutonActionDon('annuler', (int) $don['id_don'], $csrf, 'Annuler (modération)', 'btn-outline-danger btn-sm', 'Annuler ce don en tant qu\'administrateur ?') ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($conversation): ?>
        <div class="don-conversation carte-compte">
            <?php include 'partie_conversation.php'; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($estDonneur): ?>
    <script src="<?= urlStatique('ressources/qrcode-generator-1.4.4/qrcode.js') ?>"></script>
    <script>
    (function () {
        const zone = document.getElementById('qr-don');
        if (!zone || typeof qrcode === 'undefined') return;
        const qr = qrcode(0, 'M');
        qr.addData(zone.dataset.url);
        qr.make();
        zone.innerHTML = qr.createSvgTag({ cellSize: 5, margin: 2, scalable: true });
        const svg = zone.querySelector('svg');
        svg.style.width = '180px'; svg.style.height = '180px';
    })();
    </script>
    <style>
        @media print {
            header, footer, .offcanvas, .btn, form, .alert, a[href="dons.php"] { display: none !important; }
        }
    </style>
<?php endif; ?>

<?php include 'footer.php'; ?>
