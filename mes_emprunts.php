<?php
require_once 'config.php';
require_once 'emprunts_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$flash = $_SESSION['flash_emprunt'] ?? null;
unset($_SESSION['flash_emprunt']);

$indisponible = false;
$reservations = $enCours = $historique = $attentes = [];
$blocage = null;
$raison = '';
$conversations = [];
try {
    verifierEmprunts($pdo);
    foreach (empruntsMembre($pdo, $idUser) as $emprunt) {
        if ($emprunt['statut'] === 'reserve') {
            $reservations[] = $emprunt;
        } elseif ($emprunt['statut'] === 'en_cours') {
            $enCours[] = $emprunt;
        } else {
            $historique[] = $emprunt;
        }
    }
    usort($historique, fn($a, $b) => strcmp((string) $b['date_fin'], (string) $a['date_fin']));
    $attentes = attentesMembre($pdo, $idUser);
    $blocage = blocageActif($pdo, $idUser);
    $raison = raisonInterdictionEmprunt($pdo, $idUser);
    $stmt = $pdo->prepare("
        SELECT c.id_objet, c.id_conversation FROM conversation c
        INNER JOIN conversation_participant p ON p.id_conversation = c.id_conversation AND p.id_user = ?
        WHERE c.type_objet = 'emprunt'");
    $stmt->execute([$idUser]);
    $conversations = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $indisponible = true;
}
$csrf = jetonCsrf();
$nbActifs = count($reservations) + count($enCours);
$urlQr = fn(array $emprunt) => SITE_URL . '/admin/emprunts.php?code=' . rawurlencode($emprunt['code_retrait']);

$titreDocument = 'Mes emprunts';
include 'header.php';
?>

<div class="container mt-4 mb-5 page-mes-emprunts">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Mes emprunts</span>
    </nav>

    <div class="entete-mes-annonces">
        <div>
            <h1 class="titre-page-compte mb-1">Mes emprunts</h1>
            <p class="text-muted mb-0">Empruntez gratuitement jusqu'à <?= EMPRUNTS_EN_COURS_MAX ?> livres du magasin, à retirer et à rendre en boutique.</p>
        </div>
        <a href="catalogue.php?offre=emprunt" class="bouton bouton-contour bouton-petit"><i class="bi bi-search" aria-hidden="true"></i> Livres à emprunter</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3" role="status"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <?php if ($indisponible): ?>
        <div class="alert alert-warning rounded-3" role="alert">L'emprunt de livres est momentanément indisponible.</div>
    <?php else: ?>
        <div class="resume-emprunts">
            <div class="resume-emprunts__jauge" aria-label="<?= $nbActifs ?> emprunt<?= $nbActifs > 1 ? 's' : '' ?> ou réservation<?= $nbActifs > 1 ? 's' : '' ?> sur <?= EMPRUNTS_EN_COURS_MAX ?>">
                <?php for ($i = 1; $i <= EMPRUNTS_EN_COURS_MAX; $i++): ?>
                    <span class="resume-emprunts__case<?= $i <= $nbActifs ? ' pleine' : '' ?>" aria-hidden="true"></span>
                <?php endfor; ?>
                <strong><?= $nbActifs ?> / <?= EMPRUNTS_EN_COURS_MAX ?></strong> en cours
            </div>
            <?php if ($blocage): ?>
                <p class="resume-emprunts__alerte" role="alert">
                    <i class="bi bi-slash-circle" aria-hidden="true"></i>
                    Emprunts bloqués jusqu'au <strong><?= date('d/m/Y à H:i', strtotime($blocage['date_fin'])) ?></strong>
                    <span class="d-block small"><?= htmlspecialchars($blocage['motif']) ?> : <?= (int) $blocage['jours'] ?> jour<?= $blocage['jours'] > 1 ? 's' : '' ?> de blocage.</span>
                </p>
            <?php elseif ($raison !== ''): ?>
                <p class="resume-emprunts__alerte" role="alert"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> <?= htmlspecialchars($raison) ?></p>
            <?php else: ?>
                <p class="resume-emprunts__regles">
                    <?= DUREE_EMPRUNT_DEFAUT ?> jours en général, renouvelables <?= RENOUVELLEMENTS_DEFAUT ?> fois · <?= pointsRegle($pdo, 'emprunt') ?> points par livre rendu à l'heure
                </p>
            <?php endif; ?>
        </div>

        <?php if (!$reservations && !$enCours && !$attentes && !$historique): ?>
            <div class="carte-compte etat-vide-compte">
                <?= iconeDessin('livre-ouvert') ?>
                <h2 class="h5">Aucun emprunt pour le moment</h2>
                <p>Certains livres du magasin s'empruntent gratuitement : réservez-en un en ligne, retirez-le en boutique, rendez-le après votre lecture.</p>
                <a href="catalogue.php?offre=emprunt" class="bouton bouton-principal bouton-petit">Voir les livres à emprunter</a>
            </div>
        <?php endif; ?>

        <?php if ($reservations): ?>
            <section class="section-emprunts" aria-labelledby="titre-reservations">
                <h2 class="section-emprunts__titre" id="titre-reservations"><?= iconeDessin('ticket') ?> À retirer en magasin</h2>
                <?php foreach ($reservations as $emprunt): ?>
                    <article class="carte-compte carte-emprunt carte-emprunt--reserve" id="emprunt-<?= (int) $emprunt['id_emprunt'] ?>">
                        <img src="<?= htmlspecialchars(urlImage($emprunt['couverture'])) ?>" alt="" class="carte-emprunt__couverture" loading="lazy">
                        <div class="carte-emprunt__contenu">
                            <h3 class="carte-emprunt__titre"><a href="detail_livre.php?id=<?= (int) $emprunt['id_livre'] ?>"><?= htmlspecialchars($emprunt['titre']) ?></a></h3>
                            <p class="carte-emprunt__auteur"><?= htmlspecialchars($emprunt['auteur'] ?? '') ?></p>
                            <p class="carte-emprunt__pastilles"><?= pastilleStatutEmprunt($emprunt) ?></p>
                            <p class="carte-emprunt__date">À retirer avant le <strong><?= date('d/m/Y', strtotime($emprunt['date_expiration_reservation'])) ?></strong> · <?= (int) $emprunt['duree_jours'] ?> jours d'emprunt à partir du retrait</p>
                            <div class="carte-emprunt__actions">
                                <a href="emprunt_ticket.php?id=<?= (int) $emprunt['id_emprunt'] ?>" class="bouton bouton-contour bouton-petit"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Bon de réservation</a>
                                <?php if (isset($conversations[$emprunt['id_emprunt']])): ?>
                                    <a href="messages.php?id=<?= (int) $conversations[$emprunt['id_emprunt']] ?>#conversation" class="bouton bouton-contour bouton-petit"><i class="bi bi-chat" aria-hidden="true"></i> Écrire à l'équipe</a>
                                <?php endif; ?>
                                <form action="emprunt_action.php" method="POST" data-confirmer="Annuler la réservation de « <?= htmlspecialchars($emprunt['titre']) ?> » ? L'exemplaire sera proposé à un autre lecteur." data-titre-confirmer="Annuler la réservation" data-bouton-confirmer="Annuler la réservation">
                                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="action" value="annuler">
                                    <input type="hidden" name="id_emprunt" value="<?= (int) $emprunt['id_emprunt'] ?>">
                                    <button class="bouton bouton-lien bouton-petit">Annuler</button>
                                </form>
                            </div>
                        </div>
                        <div class="carte-emprunt__code">
                            <div class="qr-emprunt" data-url="<?= htmlspecialchars($urlQr($emprunt)) ?>" aria-hidden="true"></div>
                            <span class="carte-emprunt__code-libelle">Code de retrait</span>
                            <span class="carte-emprunt__code-valeur"><?= htmlspecialchars($emprunt['code_retrait']) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if ($enCours): ?>
            <section class="section-emprunts" aria-labelledby="titre-en-cours">
                <h2 class="section-emprunts__titre" id="titre-en-cours"><?= iconeDessin('livre-ouvert') ?> En cours de lecture</h2>
                <?php foreach ($enCours as $emprunt): ?>
                    <?php $refusRenouvellement = raisonRefusRenouvellement($pdo, $emprunt); $retard = empruntEnRetard($emprunt); ?>
                    <article class="carte-compte carte-emprunt<?= $retard ? ' carte-emprunt--retard' : '' ?>" id="emprunt-<?= (int) $emprunt['id_emprunt'] ?>">
                        <img src="<?= htmlspecialchars(urlImage($emprunt['couverture'])) ?>" alt="" class="carte-emprunt__couverture" loading="lazy">
                        <div class="carte-emprunt__contenu">
                            <h3 class="carte-emprunt__titre"><a href="detail_livre.php?id=<?= (int) $emprunt['id_livre'] ?>"><?= htmlspecialchars($emprunt['titre']) ?></a></h3>
                            <p class="carte-emprunt__auteur"><?= htmlspecialchars($emprunt['auteur'] ?? '') ?></p>
                            <p class="carte-emprunt__pastilles"><?= pastilleStatutEmprunt($emprunt) ?></p>
                            <p class="carte-emprunt__date">
                                <?= $retard ? 'Devait être rendu le' : 'À rendre le' ?> <strong><?= date('d/m/Y', strtotime($emprunt['date_retour_prevue'])) ?></strong>
                                · retiré le <?= date('d/m/Y', strtotime($emprunt['date_retrait'])) ?>
                                · renouvelé <?= (int) $emprunt['nb_renouvellements'] ?>/<?= (int) $emprunt['renouvellements_max'] ?>
                            </p>
                            <?php if ($retard): ?>
                                <p class="carte-emprunt__alerte"><i class="bi bi-alarm" aria-hidden="true"></i> Rapportez-le au magasin au plus vite<?= (int) $emprunt['blocage_retard'] ? " : vos emprunts seront ensuite bloqués autant de jours que de jours de retard (" . BLOCAGE_MAX_JOURS . " au maximum)." : '.' ?></p>
                            <?php endif; ?>
                            <div class="carte-emprunt__actions">
                                <?php if ($refusRenouvellement === ''): ?>
                                    <form action="emprunt_action.php" method="POST">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="renouveler">
                                        <input type="hidden" name="id_emprunt" value="<?= (int) $emprunt['id_emprunt'] ?>">
                                        <button class="bouton bouton-principal bouton-petit"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Renouveler (+<?= RENOUVELLEMENT_JOURS ?> jours)</button>
                                    </form>
                                <?php endif; ?>
                                <a href="emprunt_ticket.php?id=<?= (int) $emprunt['id_emprunt'] ?>" class="bouton bouton-contour bouton-petit"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Ticket d'emprunt</a>
                                <?php if (isset($conversations[$emprunt['id_emprunt']])): ?>
                                    <a href="messages.php?id=<?= (int) $conversations[$emprunt['id_emprunt']] ?>#conversation" class="bouton bouton-contour bouton-petit"><i class="bi bi-chat" aria-hidden="true"></i> Écrire à l'équipe</a>
                                <?php endif; ?>
                            </div>
                            <?php if ($refusRenouvellement !== '' && !$retard): ?>
                                <p class="carte-emprunt__aide"><i class="bi bi-info-circle" aria-hidden="true"></i> <?= htmlspecialchars($refusRenouvellement) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="carte-emprunt__echeance<?= $retard ? ' retard' : '' ?>">
                            <?php $jours = (int) (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($emprunt['date_retour_prevue']))->format('%r%a'); ?>
                            <span class="carte-emprunt__jours"><?= abs($jours) ?></span>
                            <span><?= $jours < 0 ? 'jour' . (abs($jours) > 1 ? 's' : '') . ' de retard' : ($jours === 0 ? "à rendre aujourd'hui" : 'jour' . ($jours > 1 ? 's' : '') . ' restant' . ($jours > 1 ? 's' : '')) ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if ($attentes): ?>
            <section class="section-emprunts" aria-labelledby="titre-attentes">
                <h2 class="section-emprunts__titre" id="titre-attentes"><?= iconeDessin('clochette') ?> Listes d'attente <span class="text-muted small fw-normal"><?= count($attentes) ?>/<?= ATTENTES_EMPRUNT_MAX ?></span></h2>
                <ul class="liste-attentes">
                    <?php foreach ($attentes as $attente): ?>
                        <li class="carte-compte ligne-attente">
                            <img src="<?= htmlspecialchars(urlImage($attente['couverture'])) ?>" alt="" class="ligne-attente__couverture" loading="lazy">
                            <span class="ligne-attente__texte">
                                <a href="detail_livre.php?id=<?= (int) $attente['id_livre'] ?>" class="ligne-attente__titre"><?= htmlspecialchars($attente['titre']) ?></a>
                                <span class="ligne-attente__detail">
                                    Position <?= (int) $attente['position'] ?> · inscrit le <?= date('d/m/Y', strtotime($attente['date_demande'])) ?>
                                    <?= $attente['prochain_retour'] ? ' · prochain retour prévu le ' . date('d/m/Y', strtotime($attente['prochain_retour'])) : '' ?>
                                </span>
                            </span>
                            <form action="emprunt_action.php" method="POST">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="quitter_attente">
                                <input type="hidden" name="id_livre" value="<?= (int) $attente['id_livre'] ?>">
                                <button class="bouton bouton-lien bouton-petit" aria-label="Quitter la liste d'attente de <?= htmlspecialchars($attente['titre']) ?>">Quitter</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php if ($historique): ?>
            <section class="section-emprunts" aria-labelledby="titre-historique">
                <h2 class="section-emprunts__titre" id="titre-historique"><?= iconeDessin('pile') ?> Historique</h2>
                <div class="carte-compte p-0 table-responsive">
                    <table class="table table-sm align-middle mb-0 table-historique-emprunts">
                        <caption class="visually-hidden">Historique de vos emprunts</caption>
                        <thead>
                            <tr><th scope="col">Livre</th><th scope="col">Statut</th><th scope="col">Retiré</th><th scope="col">Rendu</th><th scope="col"><span class="visually-hidden">Ticket</span></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historique as $emprunt): ?>
                                <tr id="emprunt-<?= (int) $emprunt['id_emprunt'] ?>">
                                    <td><a href="detail_livre.php?id=<?= (int) $emprunt['id_livre'] ?>"><?= htmlspecialchars($emprunt['titre']) ?></a></td>
                                    <td><?= pastilleStatutEmprunt($emprunt) ?></td>
                                    <td><?= $emprunt['date_retrait'] ? date('d/m/Y', strtotime($emprunt['date_retrait'])) : '—' ?></td>
                                    <td><?= $emprunt['date_retour'] ? date('d/m/Y', strtotime($emprunt['date_retour'])) : '—' ?></td>
                                    <td class="text-end">
                                        <?php if ($emprunt['statut'] === 'rendu'): ?>
                                            <a href="emprunt_ticket.php?id=<?= (int) $emprunt['id_emprunt'] ?>" class="small" aria-label="Ticket de l'emprunt de <?= htmlspecialchars($emprunt['titre']) ?>"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Ticket</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php if ($reservations): ?>
    <script src="<?= urlStatique('ressources/qrcode-generator-1.4.4/qrcode.js') ?>"></script>
    <script>
    (function () {
        if (typeof qrcode === 'undefined') return;
        document.querySelectorAll('.qr-emprunt').forEach(function (zone) {
            const qr = qrcode(0, 'M');
            qr.addData(zone.dataset.url);
            qr.make();
            zone.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 16, scalable: true });
        });
    })();
    </script>
<?php endif; ?>

<?php include 'footer.php'; ?>
