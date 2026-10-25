<?php
require_once 'config.php';
require_once 'evenements_lib.php';
require_once 'avis_lib.php';

$evt = chargerEvenement($pdo, (int) ($_GET['id'] ?? 0));
if (!$evt) {
    header("Location: evenements.php");
    exit();
}

$idUser = (int) ($_SESSION['user_id'] ?? 0);
$inscription = $idUser ? inscriptionDe($pdo, (int) $evt['id_evenement'], $idUser) : null;
$statutInscription = $inscription['statut'] ?? null;
$etat = etatEvenement($evt);
$restantes = placesRestantes($evt);
$estEquipe = in_array($_SESSION['role'] ?? '', ['admin', 'gestionnaire'], true);
$csrf = jetonCsrf();

$flash = $_SESSION['flash_evt'] ?? null;
unset($_SESSION['flash_evt']);

function boutonEvenement(string $action, int $idEvt, string $csrf, string $libelle, string $classe, string $confirmation = ''): string
{
    $onsubmit = $confirmation ? ' onsubmit="return confirm(\'' . htmlspecialchars(addslashes($confirmation)) . '\');"' : '';
    return "<form action=\"evenement_action.php\" method=\"POST\"$onsubmit>
        <input type=\"hidden\" name=\"csrf\" value=\"$csrf\">
        <input type=\"hidden\" name=\"action\" value=\"$action\">
        <input type=\"hidden\" name=\"id_evenement\" value=\"$idEvt\">
        <button class=\"btn $classe rounded-pill w-100\">$libelle</button>
    </form>";
}

include 'header.php';
?>

<div class="container my-4">
    <a href="evenements.php" class="small text-decoration-none text-success"><i class="bi bi-arrow-left"></i> Tous les événements</a>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3 mt-3"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <div class="row g-4 mt-1">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
                <?php if ($evt['image']): ?>
                    <img src="<?= htmlspecialchars(imageEvenement($evt['image'])) ?>" alt="" style="width: 100%; max-height: 320px; object-fit: cover;">
                <?php endif; ?>
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap gap-2 mb-2"><?= badgeTypeEvenement($evt['type']) ?> <?= badgeEvenement($evt) ?></div>
                    <h1 class="h3 fw-bold mb-3"><?= htmlspecialchars($evt['titre']) ?></h1>

                    <?php if ($etat === 'annule'): ?>
                        <div class="alert alert-danger"><i class="bi bi-x-octagon me-1"></i> Cet événement a été annulé.</div>
                    <?php endif; ?>

                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="bi bi-calendar3 text-success me-2"></i><?= ucfirst(horaireEvenement($evt)) ?></li>
                        <li class="mb-2"><i class="bi bi-geo-alt text-success me-2"></i><?= htmlspecialchars($evt['lieu']) ?></li>
                        <?php if ($evt['auteurs']): ?>
                            <li class="mb-2"><i class="bi bi-person-badge text-success me-2"></i>Avec <?= htmlspecialchars($evt['auteurs']) ?></li>
                        <?php endif; ?>
                        <li><i class="bi bi-people text-success me-2"></i><?= $evt['places_max'] ?> places
                            <?php if (in_array($etat, ['ouvert', 'complet'], true)): ?>
                                · <?= $restantes > 0 ? "<strong>$restantes</strong> encore disponible" . ($restantes > 1 ? 's' : '') : 'complet' ?>
                            <?php endif; ?>
                        </li>
                    </ul>

                    <div style="white-space: pre-line;"><?= htmlspecialchars($evt['description']) ?></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <?php if ($statutInscription === 'inscrit'): ?>
                    <h2 class="h6 fw-bold text-center"><i class="bi bi-ticket-perforated text-success"></i> Votre billet</h2>
                    <?php if ($inscription['present']): ?>
                        <div class="alert alert-success small text-center mb-2">Présence validée. Merci d'être venu !</div>
                    <?php endif; ?>
                    <div id="qr-billet" class="text-center my-2" data-url="<?= htmlspecialchars(urlPresence($inscription)) ?>"></div>
                    <div class="text-center small text-muted">Code du billet</div>
                    <div class="text-center fs-4 fw-bold font-monospace mb-2" style="letter-spacing: 4px;"><?= $inscription['code_court'] ?></div>
                    <p class="small text-muted text-center">Présentez ce QR code (ou ce code) à l'accueil le jour de l'événement.</p>
                    <?php if (in_array($etat, ['ouvert', 'complet'], true)): ?>
                        <button class="btn btn-outline-secondary btn-sm rounded-pill w-100 mb-2" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer le billet</button>
                        <?= boutonEvenement('desinscrire', (int) $evt['id_evenement'], $csrf, 'Annuler mon inscription', 'btn-link btn-sm text-danger', 'Annuler votre inscription ? Votre place sera donnée à quelqu\'un d\'autre.') ?>
                    <?php endif; ?>

                <?php elseif ($statutInscription === 'attente' && in_array($etat, ['ouvert', 'complet'], true)): ?>
                    <h2 class="h6 fw-bold"><i class="bi bi-hourglass-split text-warning"></i> Liste d'attente</h2>
                    <p class="small mb-3">Vous êtes <strong><?= positionAttente($pdo, $inscription) ?><sup>e</sup></strong> sur la liste d'attente.
                        <?= $evt['attente_auto'] ? "Si une place se libère, vous serez inscrit automatiquement et prévenu par mail." : "Si une place se libère, l'équipe vous inscrira et vous préviendra par mail." ?></p>
                    <?= boutonEvenement('desinscrire', (int) $evt['id_evenement'], $csrf, "Quitter la liste d'attente", 'btn-outline-danger btn-sm', "Quitter la liste d'attente ?") ?>

                <?php elseif ($etat === 'ouvert' || $etat === 'complet'): ?>
                    <h2 class="h6 fw-bold mb-3"><?= $etat === 'ouvert' ? 'Participer' : 'Événement complet' ?></h2>
                    <?php if (!$idUser): ?>
                        <a href="login.php" class="btn btn-success rounded-pill w-100">Se connecter pour s'inscrire</a>
                    <?php elseif ($etat === 'ouvert'): ?>
                        <?= boutonEvenement('inscrire', (int) $evt['id_evenement'], $csrf, '<i class="bi bi-check2-circle"></i> Je m\'inscris', 'btn-success fw-bold') ?>
                        <p class="small text-muted mt-2 mb-0">Gratuit. Vous recevrez votre billet par mail et dans votre espace.</p>
                    <?php else: ?>
                        <?= boutonEvenement('inscrire', (int) $evt['id_evenement'], $csrf, '<i class="bi bi-hourglass"></i> Rejoindre la liste d\'attente', 'btn-warning fw-bold') ?>
                        <p class="small text-muted mt-2 mb-0"><?= (int) $evt['nb_attente'] ?> personne<?= $evt['nb_attente'] > 1 ? 's' : '' ?> déjà en attente.</p>
                    <?php endif; ?>

                <?php elseif ($etat === 'en_cours'): ?>
                    <p class="text-muted mb-0 text-center">L'événement a commencé, les inscriptions sont closes.</p>
                <?php elseif ($etat === 'termine'): ?>
                    <p class="text-muted mb-0 text-center">Cet événement est terminé.</p>
                <?php else: ?>
                    <p class="text-muted mb-0 text-center">Cet événement a été annulé.</p>
                <?php endif; ?>

                <?php if ($estEquipe): ?>
                    <hr>
                    <a href="admin/evenement_participants.php?id=<?= $evt['id_evenement'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill w-100"><i class="bi bi-gear"></i> Gérer dans le back-office</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($statutInscription === 'inscrit'): ?>
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script>
    (function () {
        const zone = document.getElementById('qr-billet');
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
            header, footer, .offcanvas, .btn, form, a[href="evenements.php"] { display: none !important; }
        }
    </style>
<?php endif; ?>

<?php include 'footer.php'; ?>
