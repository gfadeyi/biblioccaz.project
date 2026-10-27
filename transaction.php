<?php
require_once 'config.php';
require_once 'ventes_lib.php';
require_once 'compte_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$idDemande = (int) ($_GET['id'] ?? 0);

try {
    expirerDemandes($pdo);
    $demande = demandeParId($pdo, $idDemande);
} catch (PDOException $e) {
    $demande = null;
}
$estVendeur = $demande && (int) $demande['id_vendeur'] === $idUser;
$estAcheteur = $demande && (int) $demande['id_acheteur'] === $idUser;

if (!$estVendeur && !$estAcheteur) {
    http_response_code(404);
    $titreDocument = 'Transaction introuvable';
    include 'header.php';
    ?>
    <main class="page-formulaire">
        <div class="carte-formulaire text-center">
            <div class="carte-formulaire__icone"><?= iconeDessin('etiquette') ?></div>
            <h1>Transaction introuvable</h1>
            <p class="carte-formulaire__intro">Cette transaction n'existe pas ou ne vous concerne pas.</p>
            <a href="mes_transactions.php" class="bouton bouton-principal">Mes ventes et achats</a>
        </div>
    </main>
    <?php
    include 'footer.php';
    exit();
}

$statut = $demande['statut'];
$photos = photosAnnonce($pdo, (int) $demande['id_annonce']);
$visuel = urlPhotoAnnonce($photos[0]['fichier'] ?? null, $demande['couverture']);
[$autrePseudo, $autreRole] = $estVendeur ? [$demande['acheteur_pseudo'], 'acheteur'] : [$demande['vendeur_pseudo'], 'vendeur'];
$stmt = $pdo->prepare("SELECT photo FROM `user` WHERE id = ?");
$stmt->execute([$estVendeur ? $demande['id_acheteur'] : $demande['id_vendeur']]);
$autrePhoto = $stmt->fetchColumn() ?: null;
$enRetard = venteEnRetard($demande);
$litigePossible = litigePossible($demande);
$aConfirmer = receptionAConfirmer($demande);

$etapes = [
    ['Demande envoyée', $demande['date_demande'], true],
    [$estVendeur ? 'Vous acceptez' : 'Le vendeur accepte', $demande['date_acceptation'], (bool) $demande['date_acceptation']],
    [$estVendeur ? 'Vous concluez la vente' : 'Le vendeur conclut la vente', $demande['date_validation'], $statut === 'validee'],
    [$estVendeur ? "L'acheteur confirme la réception" : 'Vous confirmez la réception', $demande['date_confirmation'], (bool) $demande['date_confirmation']],
];
$terminee = !in_array($statut, STATUTS_DEMANDE_ACTIFS, true) && $statut !== 'validee';

$conversation = derniereConversationObjet($pdo, 'vente', $idDemande, $idUser);
$messages = [];
if ($conversation) {
    $messages = messagesConversation($pdo, (int) $conversation['id_conversation']);
    marquerConversationLue($pdo, (int) $conversation['id_conversation'], $idUser);
}
$retourConversation = 'objet';
$flashConversation = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
$flash = $_SESSION['flash_vente'] ?? null;
unset($_SESSION['flash_vente']);
$csrf = jetonCsrf();
$scriptsPage = ['messagerie.js'];

function formulaireVente(string $action, int $idDemande, string $csrf, string $contenu, string $confirmation = '', string $classe = ''): string
{
    $attributConfirmation = $confirmation ? ' data-confirmer="' . htmlspecialchars($confirmation) . '"' : '';
    return '<form action="vente_action.php" method="POST" class="' . $classe . '"' . $attributConfirmation . '>'
        . '<input type="hidden" name="csrf" value="' . htmlspecialchars($csrf) . '">'
        . '<input type="hidden" name="action" value="' . $action . '">'
        . '<input type="hidden" name="id_demande" value="' . $idDemande . '">' . $contenu . '</form>';
}

function formulaireMotif(string $action, int $idDemande, string $csrf, string $libelle, string $icone, string $aide, bool $obligatoire, string $bouton): string
{
    $contenu = '<label class="form-label small" for="motif-' . $action . '">' . htmlspecialchars($aide) . '</label>'
        . '<textarea id="motif-' . $action . '" name="motif" class="form-control form-control-sm mb-2" rows="2" maxlength="500"' . ($obligatoire ? ' required' : '') . '></textarea>'
        . '<button class="bouton bouton-contour bouton-petit">' . htmlspecialchars($bouton) . '</button>';
    return '<details class="transaction-actions__motif"><summary class="bouton bouton-contour bouton-petit"><i class="bi bi-' . $icone . '" aria-hidden="true"></i> '
        . htmlspecialchars($libelle) . '</summary>' . formulaireVente($action, $idDemande, $csrf, $contenu) . '</details>';
}

$titreDocument = ($estVendeur ? 'Vente' : 'Achat') . ' · ' . $demande['titre'];
include 'header.php';
?>

<div class="container mt-4 mb-5 page-transaction">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <a href="mes_transactions.php<?= $estVendeur ? '?onglet=ventes' : '' ?>">Mes ventes et achats</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?= htmlspecialchars($demande['titre']) ?></span>
    </nav>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3" role="status"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <div class="transaction-grille">
        <section class="carte-compte transaction-resume">
            <a href="annonce.php?id=<?= (int) $demande['id_annonce'] ?>" class="transaction-resume__visuel" tabindex="-1" aria-hidden="true">
                <img src="<?= htmlspecialchars($visuel) ?>" alt="">
            </a>
            <div class="transaction-resume__contenu">
                <p class="transaction-resume__surtitre">
                    <i class="bi bi-<?= $estVendeur ? 'box-arrow-up-right' : 'bag' ?>" aria-hidden="true"></i>
                    <?= $estVendeur ? 'Votre vente' : 'Votre achat' ?> · demande n° <?= $idDemande ?>
                </p>
                <h1 class="transaction-resume__titre"><?= htmlspecialchars($demande['titre']) ?></h1>
                <?php if ($demande['auteur']): ?><p class="transaction-resume__auteur"><?= htmlspecialchars($demande['auteur']) ?></p><?php endif; ?>
                <div class="transaction-resume__pastilles"><?= pastilleStatutDemande($demande) ?></div>
                <dl class="transaction-resume__details">
                    <div><dt>Prix convenu</dt><dd class="transaction-resume__prix"><?= formaterPrix($demande['prix']) ?></dd></div>
                    <div><dt>Remise</dt><dd><i class="bi bi-<?= MODES_REMISE[$demande['mode_remise']][1] ?? 'question' ?>" aria-hidden="true"></i> <?= htmlspecialchars(libelleRemiseDemande($demande)) ?></dd></div>
                    <div><dt>État</dt><dd><?= htmlspecialchars(libelleEtat($demande['etat'])) ?></dd></div>
                    <div>
                        <dt><?= $estVendeur ? 'Acheteur' : 'Vendeur' ?></dt>
                        <dd class="transaction-resume__membre"><?= avatarMembre($autrePseudo, $autrePhoto, 'transaction-resume__avatar') ?> @<?= htmlspecialchars($autrePseudo) ?></dd>
                    </div>
                </dl>
                <a href="annonce.php?id=<?= (int) $demande['id_annonce'] ?>" class="lien-voir-tout">Voir l'annonce</a>
            </div>
        </section>

        <aside class="carte-compte transaction-actions">
            <?php if ($terminee): ?>
                <h2 class="transaction-actions__titre"><?= htmlspecialchars(STATUTS_DEMANDE[$statut][0] ?? '') ?></h2>
                <p class="transaction-actions__texte">
                    <?= match ($statut) {
                        'expiree' => "Le vendeur n'a pas répondu à temps. Le livre est peut-être proposé ailleurs : jetez un œil aux autres offres.",
                        'annulee_equipe' => "L'équipe BIBLIOccaz a annulé cette transaction.",
                        default => 'Cette transaction est terminée.',
                    } ?>
                </p>
                <?php if ($demande['motif']): ?>
                    <p class="transaction-actions__motif-texte"><strong>Motif :</strong> <?= htmlspecialchars($demande['motif']) ?></p>
                <?php endif; ?>
                <?php if ($estAcheteur): ?>
                    <a href="detail_livre.php?id=<?= (int) $demande['id_livre'] ?>#offres" class="bouton bouton-contour bouton-petit">Voir les autres offres</a>
                <?php endif; ?>

            <?php elseif ($estVendeur && $statut === 'en_attente'): ?>
                <h2 class="transaction-actions__titre">@<?= htmlspecialchars($demande['acheteur_pseudo']) ?> veut acheter votre livre</h2>
                <p class="transaction-actions__texte">
                    Répondez avant le <?= date('d/m/Y', strtotime($demande['date_attente'] . ' +' . DEMANDE_EXPIRATION_JOURS . ' days')) ?>.
                    En acceptant, l'annonce est réservée pour cet acheteur ; les autres demandes passent en file d'attente.
                </p>
                <?= formulaireVente('accepter', $idDemande, $csrf, '<button class="bouton bouton-principal w-100"><i class="bi bi-hand-thumbs-up" aria-hidden="true"></i> Accepter la demande</button>') ?>
                <?= formulaireMotif('refuser', $idDemande, $csrf, 'Refuser', 'x-lg', 'Un mot pour l\'acheteur (facultatif)', false, 'Refuser la demande') ?>

            <?php elseif ($estVendeur && $statut === 'en_file'): ?>
                <h2 class="transaction-actions__titre">Demande en file d'attente</h2>
                <p class="transaction-actions__texte">Votre livre est réservé pour un autre acheteur. Si cette vente ne se fait pas, cette demande redeviendra prioritaire.</p>
                <?= formulaireMotif('refuser', $idDemande, $csrf, 'Refuser', 'x-lg', 'Un mot pour l\'acheteur (facultatif)', false, 'Refuser la demande') ?>

            <?php elseif ($estAcheteur && in_array($statut, ['en_attente', 'en_file'], true)): ?>
                <h2 class="transaction-actions__titre"><?= $statut === 'en_file' ? "Vous êtes en file d'attente" : 'Demande envoyée' ?></h2>
                <p class="transaction-actions__texte">
                    <?= $statut === 'en_file'
                        ? "Le livre est réservé pour un autre lecteur. S'il redevient disponible, votre demande passera en priorité et vous serez prévenu par mail."
                        : "@" . htmlspecialchars($demande['vendeur_pseudo']) . " a jusqu'au " . date('d/m/Y', strtotime($demande['date_attente'] . ' +' . DEMANDE_EXPIRATION_JOURS . ' days')) . " pour répondre. Vous pouvez lui écrire ci-dessous." ?>
                </p>
                <?= formulaireVente('annuler', $idDemande, $csrf, '<button class="bouton bouton-contour bouton-petit"><i class="bi bi-x-lg" aria-hidden="true"></i> Annuler ma demande</button>', 'Annuler votre demande d\'achat ?') ?>

            <?php elseif ($estVendeur && $statut === 'acceptee'): ?>
                <h2 class="transaction-actions__titre">Échange à organiser</h2>
                <p class="transaction-actions__texte">
                    Convenez du lieu et du moment <a href="#conversation">dans la conversation</a>. Le paiement se fait directement entre vous.
                    Une fois le livre remis et payé, indiquez que la vente est conclue : le reçu sera disponible pour vous deux.
                </p>
                <?= formulaireVente('conclure', $idDemande, $csrf, '<button class="bouton bouton-principal w-100"><i class="bi bi-bag-check" aria-hidden="true"></i> Vente conclue</button>',
                    "L'échange de « " . $demande['titre'] . " » a bien eu lieu ? Le livre passera en vendu et le reçu sera généré.") ?>
                <?= formulaireMotif('annuler', $idDemande, $csrf, 'Annuler la vente', 'arrow-counterclockwise', "Pourquoi annulez-vous ? (facultatif)", false, 'Annuler et remettre en vente') ?>

            <?php elseif ($estAcheteur && $statut === 'acceptee'): ?>
                <h2 class="transaction-actions__titre">Demande acceptée !</h2>
                <p class="transaction-actions__texte">
                    Convenez de l'échange avec @<?= htmlspecialchars($demande['vendeur_pseudo']) ?> <a href="#conversation">dans la conversation</a>.
                    Vous payez directement le vendeur. Après l'échange, il indiquera la vente conclue : vous aurez alors <?= CONFIRMATION_JOURS ?> jours pour confirmer la réception ou signaler un problème.
                </p>
                <?= formulaireMotif('annuler', $idDemande, $csrf, 'Annuler', 'x-lg', "Pourquoi annulez-vous ? (facultatif)", false, 'Annuler mon achat') ?>

            <?php elseif ($statut === 'validee' && $aConfirmer && $estAcheteur): ?>
                <h2 class="transaction-actions__titre">Avez-vous bien reçu le livre ?</h2>
                <p class="transaction-actions__texte">
                    @<?= htmlspecialchars($demande['vendeur_pseudo']) ?> a indiqué la vente conclue le <?= date('d/m/Y', strtotime($demande['date_validation'])) ?>.
                    Confirmez la réception, ou signalez un problème, avant le <?= dateLimiteConfirmation($demande) ?>. Sans signalement, la vente sera confirmée automatiquement.
                </p>
                <?php if (!(int) $demande['en_litige']): ?>
                    <?= formulaireVente('confirmer', $idDemande, $csrf, '<button class="bouton bouton-principal w-100"><i class="bi bi-box-seam" aria-hidden="true"></i> J\'ai bien reçu le livre</button>',
                        'Confirmer que vous avez bien reçu « ' . $demande['titre'] . ' » ?') ?>
                <?php endif; ?>
                <a href="recu_vente.php?id=<?= $idDemande ?>" class="bouton bouton-contour w-100"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Télécharger le reçu</a>

            <?php elseif ($statut === 'validee' && $aConfirmer): ?>
                <div class="transaction-actions__succes"><i class="bi bi-bag-check" aria-hidden="true"></i></div>
                <h2 class="transaction-actions__titre">Vente conclue</h2>
                <p class="transaction-actions__texte">
                    En attente de la confirmation de @<?= htmlspecialchars($demande['acheteur_pseudo']) ?>.
                    <?= (int) $demande['en_litige'] ? '' : 'Vos ' . POINTS_VENTE . ' points seront crédités à sa confirmation, ou automatiquement le ' . dateLimiteConfirmation($demande) . ' sans signalement.' ?>
                </p>
                <a href="recu_vente.php?id=<?= $idDemande ?>" class="bouton bouton-principal w-100"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Télécharger le reçu</a>
                <p class="transaction-actions__note">Reçu n° <?= htmlspecialchars((string) $demande['numero_recu']) ?></p>

            <?php elseif ($statut === 'validee'): ?>
                <div class="transaction-actions__succes"><i class="bi bi-bag-check" aria-hidden="true"></i></div>
                <h2 class="transaction-actions__titre">Vente conclue</h2>
                <p class="transaction-actions__texte">
                    Le <?= date('d/m/Y', strtotime($demande['date_validation'])) ?>, réception confirmée le <?= date('d/m/Y', strtotime($demande['date_confirmation'])) ?>.
                    <?= $estVendeur ? 'Merci d\'avoir donné une seconde vie à ce livre !' : 'Bonne lecture !' ?>
                </p>
                <a href="recu_vente.php?id=<?= $idDemande ?>" class="bouton bouton-principal w-100"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Télécharger le reçu</a>
                <p class="transaction-actions__note">Reçu n° <?= htmlspecialchars((string) $demande['numero_recu']) ?></p>
            <?php endif; ?>

            <?php if ($enRetard): ?>
                <p class="transaction-actions__alerte"><i class="bi bi-alarm" aria-hidden="true"></i> Acceptée il y a plus de <?= VENTE_RETARD_JOURS ?> jours : pensez à conclure ou annuler. L'équipe suit les ventes en retard.</p>
            <?php endif; ?>

            <?php if ((int) $demande['en_litige']): ?>
                <p class="transaction-actions__alerte"><i class="bi bi-flag" aria-hidden="true"></i> Un problème a été signalé : l'équipe examine la transaction<?= $aConfirmer ? ' et les points de la vente restent bloqués jusqu\'à sa décision' : '' ?>.</p>
            <?php elseif ($litigePossible): ?>
                <details class="transaction-litige">
                    <summary><i class="bi bi-flag" aria-hidden="true"></i> Signaler un problème à l'équipe</summary>
                    <?= formulaireVente('litige', $idDemande, $csrf,
                        '<label class="form-label small" for="motif-litige">Décrivez le problème (livre non reçu, non conforme, paiement…)</label>'
                        . '<textarea id="motif-litige" name="motif" class="form-control form-control-sm mb-2" rows="3" maxlength="500" required></textarea>'
                        . '<button class="bouton bouton-contour bouton-petit">Envoyer à l\'équipe</button>') ?>
                </details>
            <?php endif; ?>
        </aside>
    </div>

    <?php if (!$terminee): ?>
        <ol class="frise-transaction" aria-label="Étapes de la vente">
            <?php foreach ($etapes as [$libelle, $date, $faite]): ?>
                <li class="frise-transaction__etape<?= $faite ? ' faite' : '' ?>">
                    <span class="frise-transaction__point" aria-hidden="true"><i class="bi bi-<?= $faite ? 'check-lg' : 'circle' ?>"></i></span>
                    <span class="frise-transaction__libelle"><?= htmlspecialchars($libelle) ?><?= $faite ? '<span class="visually-hidden"> (fait)</span>' : '' ?></span>
                    <?php if ($date): ?><span class="frise-transaction__date"><?= date('d/m/Y', strtotime($date)) ?></span><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <?php if ($conversation): ?>
        <div class="don-conversation carte-compte">
            <?php include 'partie_conversation.php'; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
