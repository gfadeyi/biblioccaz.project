<?php
require_once 'config.php';
require_once 'ventes_lib.php';
require_once 'compte_lib.php';
require_once 'avis_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$idAnnonce = (int) ($_GET['id'] ?? 0);
$idUser = (int) ($_SESSION['user_id'] ?? 0);
$annonce = annonceParId($pdo, $idAnnonce);
$estVendeur = $annonce && $idUser === (int) $annonce['id_proprietaire'];
$visible = $annonce && ($estVendeur || (
    in_array($annonce['statut_annonce'], ['publiee', 'reservee'], true) && !in_array($annonce['vendeur_statut'], STATUTS_COMPTE_SUSPENDU, true)
));

if (!$visible) {
    http_response_code(404);
    $titreDocument = 'Annonce indisponible';
    include 'header.php';
    ?>
    <main class="page-formulaire">
        <div class="carte-formulaire text-center">
            <div class="carte-formulaire__icone"><?= iconeDessin('etiquette') ?></div>
            <h1>Annonce indisponible</h1>
            <p class="carte-formulaire__intro">Cette annonce a été vendue, retirée par son vendeur ou n'existe pas.</p>
            <a href="catalogue.php?offre=particulier" class="bouton bouton-principal">Voir les annonces entre particuliers</a>
        </div>
    </main>
    <?php
    include 'footer.php';
    exit();
}

$photos = photosAnnonce($pdo, $idAnnonce);
$niveauVendeur = niveauMembre($pdo, (int) $annonce['id_proprietaire']);
$stmt = $pdo->prepare("SELECT COUNT(*) FROM exemplaire WHERE id_proprietaire = ? AND " . conditionAnnoncePubliee('exemplaire') . " AND id_exemplaire <> ?");
$stmt->execute([(int) $annonce['id_proprietaire'], $idAnnonce]);
$autresAnnoncesVendeur = (int) $stmt->fetchColumn();
$nbAutresOffres = 0;
if ((int) $annonce['livre_valide']) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM " . sqlOffres() . " o WHERE o.id_livre = ?");
    $stmt->execute([(int) $annonce['id_livre']]);
    $nbAutresOffres = max(0, (int) $stmt->fetchColumn() - ($annonce['statut_annonce'] === 'publiee' ? 1 : 0));
}
$imagesGalerie = $photos
    ? array_map(fn($p) => urlPhotoAnnonce($p['fichier'], null), $photos)
    : [urlImage($annonce['couverture'])];

$maDemande = $idUser && !$estVendeur ? demandeActiveAcheteur($pdo, $idAnnonce, $idUser) : null;
$raisonAchat = $idUser && !$estVendeur && !$maDemande ? raisonInterdictionAchat($pdo, $idUser, $annonce) : '';
$nbDemandes = $estVendeur || $annonce['statut_annonce'] === 'reservee' ? nombreDemandesAnnonce($pdo, $idAnnonce) : 0;
$flashVente = $_SESSION['flash_vente'] ?? null;
unset($_SESSION['flash_vente']);
$csrf = jetonCsrf();

$titreDocument = $annonce['titre'] . ' · annonce de @' . $annonce['vendeur_pseudo'];
include 'header.php';
?>

<div class="container mt-4 mb-5 page-annonce">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="catalogue.php?offre=particulier">Entre particuliers</a>
        <?php if ((int) $annonce['livre_valide']): ?>
            <span aria-hidden="true">›</span>
            <a href="detail_livre.php?id=<?= (int) $annonce['id_livre'] ?>"><?= htmlspecialchars($annonce['titre']) ?></a>
        <?php endif; ?>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Annonce de @<?= htmlspecialchars($annonce['vendeur_pseudo']) ?></span>
    </nav>

    <?php if ($flashVente): ?>
        <div class="alert alert-<?= htmlspecialchars($flashVente[0]) ?> rounded-3" role="status"><?= htmlspecialchars($flashVente[1]) ?></div>
    <?php endif; ?>

    <?php if (!$estVendeur && $annonce['statut_annonce'] === 'reservee'): ?>
        <div class="bandeau-statut-annonce" role="status">
            <?= pastilleStatutAnnonce('reservee') ?>
            <span>Ce livre est réservé pour un autre lecteur. Vous pouvez rejoindre la file d'attente : si la vente ne se fait pas, votre demande passera en priorité.</span>
        </div>
    <?php elseif ($estVendeur && $annonce['statut_annonce'] === 'reservee'): ?>
        <div class="bandeau-statut-annonce" role="status">
            <?= pastilleStatutAnnonce('reservee') ?>
            <span>Vous avez accepté une demande : l'annonce est réservée jusqu'à la fin de l'échange. <a href="mes_transactions.php">Voir mes ventes</a></span>
        </div>
    <?php elseif ($estVendeur && $annonce['statut_annonce'] !== 'publiee'): ?>
        <div class="bandeau-statut-annonce" role="status">
            <?= pastilleStatutAnnonce($annonce['statut_annonce']) ?>
            <span>
                <?= match ($annonce['statut_annonce']) {
                    'en_validation' => "Seul vous voyez cette page : l'annonce sera visible de tous dès sa validation par l'équipe.",
                    'refusee' => "Annonce refusée : " . htmlspecialchars((string) $annonce['motif_moderation']),
                    'retiree' => $annonce['motif_moderation'] ? "Retirée par l'équipe : " . htmlspecialchars($annonce['motif_moderation']) : "Vous avez retiré cette annonce : elle n'est plus visible.",
                    default => "Cette annonce n'est plus proposée à la vente.",
                } ?>
            </span>
        </div>
    <?php endif; ?>

    <div class="annonce-grille">
        <section class="galerie-annonce" aria-label="Photos de l'annonce">
            <div class="galerie-annonce__principale">
                <img src="<?= htmlspecialchars($imagesGalerie[0]) ?>" alt="<?= $photos ? 'Photo 1 de l\'exemplaire' : 'Couverture de ' . htmlspecialchars($annonce['titre']) ?>" id="photo-principale-annonce">
            </div>
            <?php if (count($imagesGalerie) > 1): ?>
                <div class="galerie-annonce__vignettes">
                    <?php foreach ($imagesGalerie as $index => $image): ?>
                        <a href="<?= htmlspecialchars($image) ?>" class="galerie-annonce__vignette<?= $index === 0 ? ' actif' : '' ?>" data-photo="<?= htmlspecialchars($image) ?>"
                           aria-label="Afficher la photo <?= $index + 1 ?>" <?= $index === 0 ? 'aria-current="true"' : '' ?>>
                            <img src="<?= htmlspecialchars($image) ?>" alt="" loading="lazy">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="annonce-infos">
            <p class="annonce-infos__surtitre"><i class="bi bi-people" aria-hidden="true"></i> Vente entre particuliers</p>
            <h1 class="annonce-infos__titre"><?= htmlspecialchars($annonce['titre']) ?></h1>
            <?php if ($annonce['auteur']): ?><p class="annonce-infos__auteur">Par <?= htmlspecialchars($annonce['auteur']) ?></p><?php endif; ?>
            <div class="annonce-infos__pastilles">
                <span class="pastille-etat"><i class="bi bi-stars" aria-hidden="true"></i> <?= htmlspecialchars(libelleEtat($annonce['etat'])) ?></span>
                <span class="pastille-etat neutre"><i class="bi bi-<?= MODES_REMISE[$annonce['mode_remise']][1] ?? 'question' ?>" aria-hidden="true"></i> <?= htmlspecialchars(libelleModeRemise($annonce['mode_remise'], $annonce['ville_remise'])) ?></span>
            </div>
            <h2 class="annonce-infos__sous-titre">Le mot du vendeur</h2>
            <?php if ($annonce['description']): ?>
                <p class="annonce-infos__description"><?= htmlspecialchars($annonce['description']) ?></p>
            <?php else: ?>
                <p class="annonce-infos__description text-muted">Le vendeur n'a pas ajouté de description.</p>
            <?php endif; ?>
            <?php if ((int) $annonce['livre_valide']): ?>
                <a href="detail_livre.php?id=<?= (int) $annonce['id_livre'] ?>#offres" class="lien-voir-tout mt-2">
                    <i class="bi bi-book" aria-hidden="true"></i> Fiche du livre<?= $nbAutresOffres ? ' · ' . $nbAutresOffres . ' autre' . ($nbAutresOffres > 1 ? 's offres' : ' offre') : '' ?>
                </a>
            <?php endif; ?>
        </section>

        <aside class="annonce-achat">
            <div class="carte-compte annonce-achat__carte">
                <p class="annonce-achat__prix"><?= formaterPrix($annonce['prix']) ?></p>
                <p class="annonce-achat__note">Paiement directement au vendeur, lors de l'échange.</p>
                <?php if ($estVendeur): ?>
                    <?php if (in_array($annonce['statut_annonce'], ANNONCE_STATUTS_MODIFIABLES, true)): ?>
                        <a href="annonce_creer.php?id=<?= $idAnnonce ?>" class="bouton bouton-contour w-100"><i class="bi bi-pencil" aria-hidden="true"></i> Modifier mon annonce</a>
                    <?php endif; ?>
                    <?php if ($nbDemandes): ?>
                        <a href="mes_transactions.php" class="bouton bouton-principal w-100 mt-2"><i class="bi bi-inbox" aria-hidden="true"></i> <?= $nbDemandes ?> demande<?= $nbDemandes > 1 ? 's' : '' ?> en cours</a>
                    <?php endif; ?>
                    <a href="mes_annonces.php" class="annonce-achat__lien">Toutes mes annonces</a>
                <?php elseif ($maDemande): ?>
                    <div class="annonce-achat__demande"><?= pastilleStatutDemande($maDemande) ?></div>
                    <a href="transaction.php?id=<?= (int) $maDemande['id_demande'] ?>" class="bouton bouton-principal w-100"><i class="bi bi-chat-dots" aria-hidden="true"></i> Suivre ma demande</a>
                <?php elseif (!$idUser): ?>
                    <a href="login.php" class="bouton bouton-principal w-100">Se connecter pour acheter</a>
                    <p class="annonce-achat__lien">Une conversation s'ouvre avec le vendeur ; votre email reste privé.</p>
                <?php elseif ($raisonAchat !== ''): ?>
                    <button type="button" class="bouton bouton-principal w-100" disabled aria-describedby="annonce-raison">Demander à acheter</button>
                    <p class="annonce-achat__lien" id="annonce-raison"><?= htmlspecialchars($raisonAchat) ?></p>
                <?php else: ?>
                    <form method="POST" action="vente_action.php" class="formulaire-demande">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                        <input type="hidden" name="action" value="demander">
                        <input type="hidden" name="id_annonce" value="<?= $idAnnonce ?>">
                        <?php if ($annonce['mode_remise'] === 'les_deux'): ?>
                            <fieldset class="formulaire-demande__remise">
                                <legend>Remise du livre</legend>
                                <?php foreach (['main_propre', 'envoi'] as $index => $mode): ?>
                                    <label class="choix-remise">
                                        <input type="radio" name="mode_remise" value="<?= $mode ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                        <span><i class="bi bi-<?= MODES_REMISE[$mode][1] ?>" aria-hidden="true"></i> <?= htmlspecialchars($mode === 'main_propre' ? libelleModeRemise($mode, $annonce['ville_remise']) : MODES_REMISE[$mode][0]) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        <?php endif; ?>
                        <label for="message-demande" class="formulaire-demande__label">Un mot pour le vendeur <span>(facultatif)</span></label>
                        <textarea id="message-demande" name="message" rows="3" maxlength="<?= MESSAGE_LONGUEUR_MAX ?>" class="form-control" placeholder="Bonjour, votre livre m'intéresse…"></textarea>
                        <button type="submit" class="bouton bouton-principal w-100 mt-3">
                            <?= $annonce['statut_annonce'] === 'reservee' ? "<i class=\"bi bi-people\" aria-hidden=\"true\"></i> Rejoindre la file d'attente" : "<i class=\"bi bi-bag-heart\" aria-hidden=\"true\"></i> Demander à acheter" ?>
                        </button>
                    </form>
                    <p class="annonce-achat__lien">Une conversation s'ouvre avec le vendeur ; votre email reste privé.</p>
                <?php endif; ?>

                <div class="vendeur-annonce">
                    <?= avatarMembre($annonce['vendeur_pseudo'], $annonce['vendeur_photo'], 'vendeur-annonce__avatar') ?>
                    <div>
                        <p class="vendeur-annonce__pseudo">@<?= htmlspecialchars($annonce['vendeur_pseudo']) ?></p>
                        <p class="vendeur-annonce__niveau"><i class="bi bi-award" aria-hidden="true"></i> <?= htmlspecialchars($niveauVendeur['nom']) ?></p>
                        <p class="vendeur-annonce__depuis">
                            Membre depuis <?= $annonce['vendeur_depuis'] ? date('Y', strtotime($annonce['vendeur_depuis'])) : '—' ?>
                            <?= $autresAnnoncesVendeur ? ' · ' . $autresAnnoncesVendeur . ' autre' . ($autresAnnoncesVendeur > 1 ? 's annonces' : ' annonce') : '' ?>
                        </p>
                    </div>
                </div>
            </div>
            <p class="annonce-achat__confiance"><i class="bi bi-shield-check" aria-hidden="true"></i> Annonce vérifiée par l'équipe BIBLIOccaz avant sa mise en ligne.</p>
        </aside>
    </div>
</div>

<?php if (count($imagesGalerie) > 1): ?>
<script>
document.querySelectorAll('.galerie-annonce__vignette').forEach(function (vignette, index) {
    vignette.addEventListener('click', function (evenement) {
        evenement.preventDefault();
        const principale = document.getElementById('photo-principale-annonce');
        principale.src = vignette.dataset.photo;
        principale.alt = 'Photo ' + (index + 1) + " de l'exemplaire";
        document.querySelectorAll('.galerie-annonce__vignette').forEach(function (autre) {
            autre.classList.toggle('actif', autre === vignette);
            if (autre === vignette) { autre.setAttribute('aria-current', 'true'); } else { autre.removeAttribute('aria-current'); }
        });
    });
});
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>
