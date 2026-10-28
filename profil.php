<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'avis_lib.php';
require_once 'compte_lib.php';
require_once 'evenements_lib.php';

$idUser = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (!verifierCsrf()) {
        $_SESSION['flash_profil'] = ['danger', "Votre session a expiré, merci de réessayer."];
        header("Location: profil.php");
        exit();
    }

    if ($action === 'photo') {
        try {
            enregistrerPhotoProfil($pdo, $idUser, $_FILES['photo'] ?? []);
            insertLog('PROFIL', "Nouvelle photo de profil");
            $_SESSION['flash_profil'] = ['success', "Votre photo de profil est enregistrée."];
        } catch (RuntimeException $e) {
            $_SESSION['flash_profil'] = ['danger', $e->getMessage()];
        }
        header("Location: profil.php");
        exit();
    }

    if ($action === 'supprimer_photo') {
        supprimerPhotoProfil($pdo, $idUser);
        insertLog('PROFIL', "Photo de profil retirée");
        $_SESSION['flash_profil'] = ['success', "Votre photo a été retirée : votre initiale la remplace."];
        header("Location: profil.php");
        exit();
    }

    if ($action === 'newsletter') {
        $abonner = ($_POST['abonner'] ?? '') === '1';
        $pdo->prepare("UPDATE user SET is_newsletter = ?, date_newsletter = NOW() WHERE id = ?")->execute([$abonner ? 1 : 0, $idUser]);
        insertLog('NEWSLETTER', $abonner ? "Abonnement à la newsletter depuis le profil" : "Désabonnement de la newsletter depuis le profil");
        $stmtMail = $pdo->prepare("SELECT email FROM user WHERE id = ?");
        $stmtMail->execute([$idUser]);
        $emailMembre = (string) $stmtMail->fetchColumn();
        journalNewsletter($pdo, $emailMembre, $idUser, $abonner, 'profil');
        notifierParMail($emailMembre,
            $abonner ? "Inscription à la newsletter confirmée" : "Désinscription de la newsletter confirmée",
            $abonner ? "Merci ! Vous recevrez désormais les nouveautés et pépites d'occasion de BIBLIOccaz.\nVous pouvez vous désabonner à tout moment depuis votre espace : " . SITE_URL . "/profil.php"
                     : "Vous ne recevrez plus la newsletter de BIBLIOccaz.\nVous pouvez vous réabonner à tout moment depuis votre espace : " . SITE_URL . "/profil.php");
        $_SESSION['flash_profil'] = ['success', $abonner ? "Vous êtes abonné à la newsletter." : "Vous êtes désabonné de la newsletter."];
        header("Location: profil.php");
        exit();
    }

    header("Location: profil.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$idUser]);
$user = $stmt->fetch();
if (!$user) {
    die("Utilisateur introuvable.");
}

$role = $user['role'];
$estClient = $role === 'client';
$estEquipe = in_array($role, ['admin', 'gestionnaire', 'moderateur'], true);
$estAuteur = $role === 'auteur';
$libellesRolesProfil = ['admin' => 'Administrateur', 'gestionnaire' => 'Gestionnaire', 'moderateur' => 'Modérateur', 'auteur' => 'Auteur', 'client' => 'Lecteur'];
$moisFr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$dateInscription = strtotime($user['date_inscription']);
$membreDepuis = $moisFr[(int) date('n', $dateInscription) - 1] . ' ' . date('Y', $dateInscription);

function lignesProfil(PDO $pdo, string $sql, array $parametres): array
{
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametres);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

$niveau = $estClient ? niveauMembre($pdo, $idUser) : null;
$dernieresCommandes = $estClient ? lignesProfil($pdo, "
    SELECT c.id_commande, c.date_commande, c.total, c.statut,
           (SELECT COALESCE(SUM(cl.quantite), 0) FROM commande_ligne cl WHERE cl.id_commande = c.id_commande) AS nb_livres
    FROM commande c WHERE c.id_user = ? ORDER BY c.date_commande DESC, c.id_commande DESC LIMIT 3", [$idUser]) : [];
$favoris = lignesProfil($pdo, "
    SELECT l.id_livre, l.titre, l.couverture
    FROM favori f INNER JOIN livre l ON l.id_livre = f.id_livre
    WHERE f.id_user = ? ORDER BY f.date_ajout DESC", [$idUser]);
$alertes = lignesProfil($pdo, "
    SELECT l.id_livre, l.titre FROM alerte_stock a INNER JOIN livre l ON l.id_livre = a.id_livre
    WHERE a.id_user = ? AND a.date_envoi IS NULL ORDER BY a.date_creation DESC", [$idUser]);
$evenementsAVenir = lignesProfil($pdo, "
    SELECT e.id_evenement, e.titre, e.date_debut, e.date_fin, e.lieu, i.statut AS statut_inscription
    FROM evenement_inscription i INNER JOIN evenement e ON e.id_evenement = i.id_evenement
    WHERE i.id_user = ? AND i.statut IN ('inscrit', 'attente') AND e.statut = 'publie' AND e.date_fin >= NOW()
    ORDER BY e.date_debut LIMIT 3", [$idUser]);
$dons = lignesProfil($pdo, "SELECT statut, date_limite FROM don WHERE id_donneur = ?", [$idUser]);
$donsEnCours = count(array_filter($dons, fn($don) => in_array(statutDon($don), ['disponible', 'reserve'], true)));
$donsRecuperes = count(array_filter($dons, fn($don) => $don['statut'] === 'recupere'));

$aTraiterEquipe = [];
if ($estEquipe) {
    $aTraiterEquipe = [
        ['admin/moderation_livres.php', 'shield-check', 'Fiches de livres à valider', "SELECT COUNT(*) FROM livre WHERE is_valide = 0"],
        ['admin/avis_a_valider.php', 'patch-check', 'Avis à valider', "SELECT COUNT(*) FROM avis WHERE statut = 'visible' AND date_validation IS NULL"],
        ['admin/moderation_avis.php', 'flag', 'Avis signalés', "SELECT COUNT(DISTINCT id_avis) FROM avis_signalement WHERE traite = 0"],
    ];
    if (in_array($role, ['admin', 'gestionnaire'], true)) {
        $aTraiterEquipe[] = ['admin/evenements.php?onglet=proposes', 'calendar-plus', 'Événements proposés', "SELECT COUNT(*) FROM evenement WHERE statut = 'en_attente'"];
    }
    foreach ($aTraiterEquipe as $index => $tache) {
        try {
            $aTraiterEquipe[$index][3] = (int) $pdo->query($tache[3])->fetchColumn();
        } catch (PDOException $e) {
            $aTraiterEquipe[$index][3] = 0;
        }
    }
}

$flashProfil = $_SESSION['flash_profil'] ?? null;
unset($_SESSION['flash_profil']);
if (isset($_GET['mdp'])) {
    $flashProfil = ['success', "Votre mot de passe a bien été modifié."];
}
$csrf = jetonCsrf();
$scriptsPage = ['photo_profil.js'];

include 'header.php';
?>

<div class="container mt-4 mb-5 page-profil">
    <h1 class="titre-page-compte"><?= $estEquipe ? 'Mon espace équipe' : ($estAuteur ? 'Mon espace auteur' : 'Mon compte') ?></h1>

    <?php if ($flashProfil): ?>
        <div class="alert alert-<?= $flashProfil[0] === 'success' ? 'success' : 'danger' ?> rounded-3" role="status"><?= htmlspecialchars($flashProfil[1]) ?></div>
    <?php endif; ?>

    <section class="carte-compte profil-identite" aria-label="Mon identité">
        <div class="profil-photo">
            <div class="profil-photo__cadre" id="cadrePhoto">
                <?= avatarMembre($user['pseudo'], $user['photo'] ?? null, 'grand') ?>
                <img class="profil-photo__apercu" id="apercuPhoto" alt="Aperçu de votre nouvelle photo" hidden>
            </div>
            <form method="POST" enctype="multipart/form-data" class="profil-photo__formulaire" id="formulairePhoto">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="photo">
                <input type="hidden" name="MAX_FILE_SIZE" value="<?= PHOTO_PROFIL_TAILLE_MAX ?>">
                <input type="file" name="photo" id="champPhoto" class="visually-hidden" accept="image/jpeg,image/png,image/webp" data-taille-max="<?= PHOTO_PROFIL_TAILLE_MAX ?>">
                <label for="champPhoto" class="bouton bouton-contour bouton-petit" id="boutonChoisirPhoto"><i class="bi bi-camera" aria-hidden="true"></i> <?= cheminPhotoMembre($user['photo'] ?? null) ? 'Changer la photo' : 'Ajouter une photo' ?></label>
                <div class="profil-photo__choix" id="choixPhoto">
                    <button class="bouton bouton-principal bouton-petit"><i class="bi bi-check-lg" aria-hidden="true"></i> Enregistrer</button>
                    <button type="button" class="bouton bouton-discret bouton-petit" id="annulerPhoto">Annuler</button>
                </div>
                <small class="profil-photo__aide" id="aidePhoto" aria-live="polite">JPG, PNG ou WebP, 2 Mo maximum.</small>
            </form>
            <?php if (cheminPhotoMembre($user['photo'] ?? null)): ?>
                <form method="POST" id="formulaireRetirerPhoto" data-confirmer="Retirer votre photo ? Votre initiale sur fond vert la remplacera." data-titre-confirmer="Retirer ma photo" data-bouton-confirmer="Retirer">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="supprimer_photo">
                    <button class="bouton-lien">Revenir à mon initiale</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="profil-identite__texte">
            <h2 class="profil-identite__pseudo"><?= htmlspecialchars(pseudoAffiche($user['pseudo'])) ?></h2>
            <?php if (trim(($user['prenom'] ?? '') . ($user['nom'] ?? '')) !== ''): ?>
                <p class="profil-identite__nom"><?= htmlspecialchars(trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))) ?></p>
            <?php endif; ?>
            <ul class="profil-identite__details">
                <li><i class="bi bi-envelope" aria-hidden="true"></i> <?= htmlspecialchars($user['email']) ?></li>
                <?php if (!empty($user['ville'])): ?><li><i class="bi bi-geo-alt" aria-hidden="true"></i> <?= htmlspecialchars(trim(($user['cp'] ?? '') . ' ' . $user['ville'])) ?></li><?php endif; ?>
                <li><i class="bi bi-calendar-heart" aria-hidden="true"></i> Membre depuis <?= $membreDepuis ?></li>
            </ul>
            <span class="niveau-lecteur"><?= $estClient ? htmlspecialchars($niveau['nom']) : $libellesRolesProfil[$role] ?? 'Membre' ?></span>
        </div>

        <div class="profil-identite__actions">
            <a href="modifier_profil.php" class="bouton bouton-contour bouton-petit"><i class="bi bi-pencil" aria-hidden="true"></i> Modifier mes infos</a>
            <a href="modifier_mdp.php" class="bouton bouton-discret bouton-petit"><i class="bi bi-key" aria-hidden="true"></i> Mot de passe</a>
            <?php if ($estClient): ?>
                <a href="adresses.php" class="bouton bouton-discret bouton-petit"><i class="bi bi-geo" aria-hidden="true"></i> Mes adresses</a>
                <a href="avis.php" class="bouton bouton-discret bouton-petit"><i class="bi bi-chat-square-text" aria-hidden="true"></i> Mes avis</a>
            <?php endif; ?>
            <?php if ($estEquipe): ?>
                <a href="admin/index.php" class="bouton bouton-principal bouton-petit"><i class="bi bi-speedometer2" aria-hidden="true"></i> Back-office</a>
            <?php endif; ?>
            <form method="POST" action="logout.php"><input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf(), ENT_QUOTES, 'UTF-8') ?>"><button type="submit" class="bouton bouton-discret bouton-petit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Déconnexion</button></form>
        </div>
    </section>

    <?php if ($estClient): ?>
        <a href="points_badges.php" class="carte-compte profil-niveau">
            <span class="profil-niveau__embleme" aria-hidden="true"><?= iconeDessin('medaille') ?><span><?= $niveau['index'] + 1 ?>/<?= count(NIVEAUX) ?></span></span>
            <span class="profil-niveau__texte">
                <span class="profil-niveau__surtitre">Votre niveau</span>
                <strong><?= htmlspecialchars($niveau['nom']) ?></strong>
                <span class="jauge-niveau" style="--progression: <?= round($niveau['progression'], 3) ?>;"><span></span></span>
                <small><?= $niveau['prochain'] ? 'Encore ' . $niveau['reste'] . ' points pour devenir « ' . htmlspecialchars($niveau['prochain']['nom']) . ' »' : 'Niveau maximum atteint, bravo !' ?></small>
            </span>
            <span class="profil-niveau__solde"><strong><?= (int) $user['solde_points'] ?></strong> points à utiliser</span>
            <span class="lien-voir-tout">Mes points et badges <i class="bi bi-chevron-right" aria-hidden="true"></i></span>
        </a>
    <?php endif; ?>

    <div class="grille-profil">
        <?php if ($estEquipe): ?>
            <section class="carte-compte">
                <h2 class="carte-compte__titre"><?= iconeDessin('tampon') ?> À traiter</h2>
                <ul class="liste-profil">
                    <?php foreach ($aTraiterEquipe as [$lienTache, $iconeTache, $libelleTache, $nombreTache]): ?>
                        <li>
                            <a href="<?= $lienTache ?>"><i class="bi bi-<?= $iconeTache ?>" aria-hidden="true"></i> <?= $libelleTache ?></a>
                            <span class="compteur-menu<?= $nombreTache > 0 ? '' : ' vide' ?>"><?= $nombreTache ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="admin/index.php" class="lien-voir-tout">Ouvrir le back-office <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </section>
        <?php endif; ?>

        <?php if ($estAuteur): ?>
            <section class="carte-compte">
                <h2 class="carte-compte__titre"><?= iconeDessin('plume') ?> Espace créateur</h2>
                <p class="texte-profil">Proposez vos livres et vos rencontres, suivez leur validation et les inscriptions à vos événements.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="dashboard_auteur.php" class="bouton bouton-principal bouton-petit">Mon espace créateur</a>
                    <a href="proposer_evenement.php" class="bouton bouton-contour bouton-petit">Proposer un événement</a>
                    <a href="proposer_ouvrage.php" class="bouton bouton-discret bouton-petit">Proposer un livre</a>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($estClient): ?>
            <section class="carte-compte">
                <h2 class="carte-compte__titre"><?= iconeDessin('colis') ?> Mes dernières commandes</h2>
                <?php if (!$dernieresCommandes): ?>
                    <p class="texte-profil">Aucune commande pour l'instant. Votre première vous rapportera 20 points de bienvenue.</p>
                    <a href="catalogue.php" class="lien-voir-tout">Découvrir le catalogue <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                <?php else: ?>
                    <ul class="liste-profil">
                        <?php foreach ($dernieresCommandes as $commande): ?>
                            <li>
                                <span>
                                    <strong>N° <?= (int) $commande['id_commande'] ?></strong>
                                    <small class="d-block"><?= date('d/m/Y', strtotime($commande['date_commande'])) ?> · <?= (int) $commande['nb_livres'] ?> livre<?= $commande['nb_livres'] > 1 ? 's' : '' ?></small>
                                </span>
                                <?php if ($commande['statut'] === 'annulee'): ?>
                                    <span class="etiquette-profil annulee">Annulée</span>
                                <?php else: ?>
                                    <strong class="liste-profil__montant"><?= formaterPrix((float) $commande['total']) ?></strong>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="commandes.php" class="lien-voir-tout">Toutes mes commandes <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                <?php endif; ?>
            </section>

            <section class="carte-compte">
                <h2 class="carte-compte__titre"><?= iconeDessin('marque-page') ?> Mes favoris <span class="compteur-menu"><?= count($favoris) ?></span></h2>
                <?php if (!$favoris): ?>
                    <p class="texte-profil">Touchez le cœur d'un livre pour le garder ici et le retrouver plus tard.</p>
                <?php else: ?>
                    <div class="couvertures-profil">
                        <?php foreach (array_slice($favoris, 0, 5) as $favori): ?>
                            <a href="detail_livre.php?id=<?= (int) $favori['id_livre'] ?>" title="<?= htmlspecialchars($favori['titre']) ?>">
                                <img src="<?= htmlspecialchars(urlImage($favori['couverture'])) ?>" alt="<?= htmlspecialchars($favori['titre']) ?>" loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <a href="wishlist.php" class="lien-voir-tout">Ma liste d'envies <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </section>

            <section class="carte-compte">
                <h2 class="carte-compte__titre"><?= iconeDessin('clochette') ?> Mes alertes <span class="compteur-menu"><?= count($alertes) ?></span></h2>
                <?php if (!$alertes): ?>
                    <p class="texte-profil">Un livre épuisé vous intéresse ? Demandez à être prévenu dès son retour en stock.</p>
                <?php else: ?>
                    <ul class="liste-profil">
                        <?php foreach (array_slice($alertes, 0, 3) as $alerte): ?>
                            <li><a href="detail_livre.php?id=<?= (int) $alerte['id_livre'] ?>"><?= htmlspecialchars($alerte['titre']) ?></a><span class="etiquette-profil">En attente</span></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a href="alertes.php" class="lien-voir-tout">Gérer mes alertes <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            </section>
        <?php endif; ?>

        <section class="carte-compte">
            <h2 class="carte-compte__titre"><?= iconeDessin('ticket') ?> Événements à venir</h2>
            <?php if (!$evenementsAVenir): ?>
                <p class="texte-profil">Aucune inscription en cours. Rencontres, ateliers et dédicaces vous attendent.</p>
                <a href="evenements.php" class="lien-voir-tout">Voir le programme <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            <?php else: ?>
                <ul class="liste-profil">
                    <?php foreach ($evenementsAVenir as $evenement): ?>
                        <li>
                            <span>
                                <a href="evenement.php?id=<?= (int) $evenement['id_evenement'] ?>"><?= htmlspecialchars($evenement['titre']) ?></a>
                                <small class="d-block"><?= htmlspecialchars(dateLongue($evenement['date_debut'])) ?></small>
                            </span>
                            <span class="etiquette-profil<?= $evenement['statut_inscription'] === 'attente' ? ' attente' : '' ?>"><?= $evenement['statut_inscription'] === 'attente' ? "Liste d'attente" : 'Inscrit' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="mes_evenements.php" class="lien-voir-tout">Mes billets <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
            <?php endif; ?>
        </section>

        <?php if ($estClient): ?>
            <section class="carte-compte">
                <h2 class="carte-compte__titre"><?= iconeDessin('seconde-vie') ?> Mes dons</h2>
                <div class="chiffres-profil">
                    <span><strong><?= $donsEnCours ?></strong> en cours</span>
                    <span><strong><?= $donsRecuperes ?></strong> récupéré<?= $donsRecuperes > 1 ? 's' : '' ?></span>
                </div>
                <p class="texte-profil">Chaque don récupéré vous rapporte <?= DON_POINTS ?> points.</p>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="don_creer.php" class="bouton bouton-contour bouton-petit"><i class="bi bi-plus-lg" aria-hidden="true"></i> Donner un livre</a>
                    <a href="mes_dons.php" class="lien-voir-tout">Mes dons <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                </div>
            </section>
        <?php endif; ?>

        <section class="carte-compte">
            <h2 class="carte-compte__titre"><?= iconeDessin('enveloppe') ?> Newsletter</h2>
            <p class="texte-profil">
                <?= $user['is_newsletter'] ? 'Vous êtes abonné : une lettre par mois avec nos pépites, dons et événements.' : 'Une lettre par mois avec nos pépites d\'occasion, les dons et les événements.' ?>
            </p>
            <form method="POST">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="newsletter">
                <input type="hidden" name="abonner" value="<?= $user['is_newsletter'] ? '0' : '1' ?>">
                <?php if ($user['is_newsletter']): ?>
                    <span class="etiquette-profil"><i class="bi bi-check2" aria-hidden="true"></i> Abonné</span>
                    <button class="bouton-lien ms-2">Se désabonner</button>
                <?php else: ?>
                    <button class="bouton bouton-principal bouton-petit"><i class="bi bi-envelope-paper" aria-hidden="true"></i> M'abonner</button>
                <?php endif; ?>
            </form>
        </section>
    </div>
</div>

<?php include 'footer.php'; ?>
