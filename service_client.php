<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'service_client_lib.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion");
    exit();
}
$idUser = (int) $_SESSION['user_id'];
$disponible = serviceClientDisponible($pdo);
$texte = fn(string $cle) => is_string($_POST[$cle] ?? null) ? $_POST[$cle] : '';
$saisie = ['categorie' => is_string($_GET['categorie'] ?? null) ? $_GET['categorie'] : '', 'sujet' => '', 'message' => '', 'id_commande' => (int) ($_GET['commande'] ?? 0)];
$erreur = '';

if ($disponible && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $texte('action');
    $idDemande = (int) ($_POST['id_demande'] ?? 0);
    if (!verifierCsrf()) {
        $resultat = ['danger', 'Session expirée, merci de réessayer.'];
    } elseif ($action === 'creer') {
        $saisie = ['categorie' => $texte('categorie'), 'sujet' => $texte('sujet'), 'message' => $texte('message'), 'id_commande' => (int) ($_POST['id_commande'] ?? 0)];
        $resultat = creerDemandeClient($pdo, $idUser, $saisie['categorie'], $saisie['sujet'], $saisie['message'], $saisie['id_commande']);
        if ($resultat[0] === 'success') {
            $_SESSION['flash_message'] = [$resultat[0], $resultat[1]];
            header('Location: service_client?id=' . (int) $resultat[2]);
            exit();
        }
        $erreur = $resultat[1];
    } elseif ($action === 'repondre') {
        $resultat = repondreDemandeClient($pdo, $idDemande, $idUser, $texte('message'));
        if ($resultat[0] !== 'success') {
            $_SESSION['brouillon_service'] = [$idDemande, mb_substr($texte('message'), 0, SERVICE_MESSAGE_MAX)];
        }
    } elseif ($action === 'clore') {
        $resultat = cloreDemandeParClient($pdo, $idDemande, $idUser);
    } else {
        $resultat = ['warning', 'Action inconnue.'];
    }
    if ($action !== 'creer') {
        $_SESSION['flash_message'] = $resultat;
        header('Location: service_client?id=' . $idDemande . '#fil');
        exit();
    }
}

$idDemande = (int) ($_GET['id'] ?? 0);
$demande = $disponible && $idDemande ? demandeClient($pdo, $idDemande, $idUser) : null;
$messages = [];
if ($demande) {
    $messages = array_values(array_filter(messagesDemandeClient($pdo, $idDemande), fn($m) => $m['cote'] !== 'interne'));
    if ((int) $demande['non_lu_client']) {
        marquerDemandeLue($pdo, $idDemande, 'client');
    }
}
$demandes = $disponible && !$demande ? demandesDuClient($pdo, $idUser) : [];
$commandes = $disponible && !$demande ? commandesPourService($pdo, $idUser) : [];
$flash = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
$brouillonSession = $_SESSION['brouillon_service'] ?? null;
$brouillon = is_array($brouillonSession) && $brouillonSession[0] === $idDemande ? (string) $brouillonSession[1] : '';
unset($_SESSION['brouillon_service']);
if ($idDemande && !$demande && $disponible && !$flash) {
    $flash = ['warning', 'Cette demande n’existe pas ou ne vous concerne pas.'];
}
$csrf = jetonCsrf();
$titreDocument = $demande ? 'Demande ' . $demande['reference'] : 'Service client';
include 'header.php';
?>

<div class="container my-4 page-service" style="max-width: 900px;">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="mon-compte">Mon compte</a>
        <span aria-hidden="true">›</span>
        <?php if ($demande): ?>
            <a href="service_client">Service client</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page"><?= htmlspecialchars((string) $demande['reference']) ?></span>
        <?php else: ?>
            <span aria-current="page">Service client</span>
        <?php endif; ?>
    </nav>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3" role="status"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <?php if (!$disponible): ?>
        <h1 class="titre-page-compte">Service client</h1>
        <div class="alert alert-warning rounded-3">L’espace de demandes n’est pas encore disponible.</div>
    <?php elseif ($demande): ?>
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h1 class="titre-page-compte mb-1"><?= htmlspecialchars($demande['sujet']) ?></h1>
                <p class="text-muted mb-0"><?= htmlspecialchars((string) $demande['reference']) ?> · <?= htmlspecialchars(SERVICE_CATEGORIES[$demande['categorie']] ?? 'Autre') ?><?= $demande['id_commande'] ? ' · commande n°' . (int) $demande['id_commande'] : '' ?></p>
            </div>
            <span class="statut-retour statut-retour--<?= SERVICE_STATUTS[$demande['statut']][1] ?? 'attente' ?>"><?= libelleStatutDemande($demande['statut']) ?></span>
        </div>

        <ol class="fil-service" id="fil" aria-label="Échanges avec l’équipe">
            <?php foreach ($messages as $message): ?>
                <?php if ($message['cote'] === 'systeme'): ?>
                    <li class="fil-service__systeme"><?= htmlspecialchars($message['contenu']) ?> <time datetime="<?= date('c', strtotime($message['date_envoi'])) ?>"><?= date('d/m H:i', strtotime($message['date_envoi'])) ?></time></li>
                <?php else: ?>
                    <li class="fil-service__message fil-service__message--<?= $message['cote'] === 'client' ? 'moi' : 'equipe' ?>">
                        <span class="fil-service__auteur"><?= $message['cote'] === 'client' ? 'Vous' : 'Équipe BIBLIOccaz' ?> · <time datetime="<?= date('c', strtotime($message['date_envoi'])) ?>"><?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?></time></span>
                        <p><?= nl2br(htmlspecialchars($message['contenu'])) ?></p>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>

        <?php $close = $demande['statut'] === 'resolue'; ?>
        <?php if ($close): ?>
            <p class="demande-fermee" role="status"><i class="bi bi-lock" aria-hidden="true"></i> Cette demande est close. Son historique reste consultable, mais vous ne pouvez plus y envoyer de message. <a href="service_client#nouvelle">Ouvrir une nouvelle demande</a></p>
        <?php else: ?>
            <form method="POST" class="carte-compte mt-3">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="repondre">
                <input type="hidden" name="id_demande" value="<?= (int) $demande['id_demande'] ?>">
                <label for="service-reponse" class="form-label fw-bold">Votre message</label>
                <textarea id="service-reponse" name="message" class="form-control mb-2" rows="4" maxlength="<?= SERVICE_MESSAGE_MAX ?>" required><?= htmlspecialchars($brouillon) ?></textarea>
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <button type="submit" class="btn btn-success rounded-pill fw-bold px-4">Envoyer</button>
                    <small class="text-muted">Vos coordonnées ne sont jamais affichées dans ce fil.</small>
                </div>
            </form>
            <?php if (!$close): ?>
                <form method="POST" class="mt-2 text-end" data-confirmer="Votre question est réglée ? La demande sera close et vous ne pourrez plus y envoyer de message." data-bouton-confirmer="Clore la demande">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="clore">
                    <input type="hidden" name="id_demande" value="<?= (int) $demande['id_demande'] ?>">
                    <button type="submit" class="btn btn-link btn-sm">Ma question est réglée</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        <h1 class="titre-page-compte mb-1">Service client</h1>
        <p class="text-muted mb-4">Une question sur une commande, un retour, votre compte ou un don ? Écrivez à l’équipe : la réponse arrive ici, avec une notification et un mail.</p>

        <?php if ($demandes): ?>
            <h2 class="h5 fw-bold">Mes demandes</h2>
            <ul class="liste-retours mb-4">
                <?php foreach ($demandes as $ligne): ?>
                    <li>
                        <a href="service_client?id=<?= (int) $ligne['id_demande'] ?>" class="liste-retours__lien<?= (int) $ligne['non_lu_client'] ? ' liste-retours__lien--nouveau' : '' ?>">
                            <span><strong><?= htmlspecialchars($ligne['sujet']) ?></strong><?= (int) $ligne['non_lu_client'] ? ' <span class="pastille-reponse">Nouvelle réponse</span>' : '' ?><small class="d-block text-muted"><?= htmlspecialchars((string) $ligne['reference']) ?> · <?= htmlspecialchars(SERVICE_CATEGORIES[$ligne['categorie']] ?? 'Autre') ?> · <?= (int) $ligne['nb_messages'] ?> message<?= (int) $ligne['nb_messages'] > 1 ? 's' : '' ?> · <?= date('d/m/Y', strtotime($ligne['date_maj'])) ?></small></span>
                            <span class="statut-retour statut-retour--<?= SERVICE_STATUTS[$ligne['statut']][1] ?? 'attente' ?>"><?= libelleStatutDemande($ligne['statut']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2 class="h5 fw-bold" id="nouvelle">Nouvelle demande</h2>
        <?php if ($erreur): ?><div class="alert alert-warning rounded-3" role="alert"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
        <form method="POST" class="carte-compte">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="creer">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="service-categorie" class="form-label fw-bold">Catégorie *</label>
                    <select id="service-categorie" name="categorie" class="form-select" required>
                        <option value="" disabled<?= isset(SERVICE_CATEGORIES[$saisie['categorie']]) ? '' : ' selected' ?>>Choisissez</option>
                        <?php foreach (SERVICE_CATEGORIES as $cle => $libelle): ?><option value="<?= $cle ?>"<?= $saisie['categorie'] === $cle ? ' selected' : '' ?>><?= htmlspecialchars($libelle) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6" id="zone-commande-service" data-categories-commande="<?= htmlspecialchars(implode(' ', SERVICE_CATEGORIES_COMMANDE)) ?>">
                    <label for="service-commande" class="form-label fw-bold">Commande concernée</label>
                    <select id="service-commande" name="id_commande" class="form-select">
                        <option value="0">Aucune</option>
                        <?php foreach ($commandes as $commande): ?><option value="<?= (int) $commande['id_commande'] ?>"<?= $saisie['id_commande'] === (int) $commande['id_commande'] ? ' selected' : '' ?>>N°<?= (int) $commande['id_commande'] ?> du <?= date('d/m/Y', strtotime($commande['date_commande'])) ?> · <?= formaterPrix((float) $commande['total']) ?><?= $commande['statut'] === 'annulee' ? ' (annulée)' : '' ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label for="service-sujet" class="form-label fw-bold">Sujet *</label>
                    <input id="service-sujet" type="text" name="sujet" class="form-control" minlength="5" maxlength="<?= SERVICE_SUJET_MAX ?>" required value="<?= htmlspecialchars($saisie['sujet']) ?>" placeholder="Ex : Colis toujours pas reçu">
                </div>
                <div class="col-12">
                    <label for="service-message" class="form-label fw-bold">Message *</label>
                    <textarea id="service-message" name="message" class="form-control" rows="5" maxlength="<?= SERVICE_MESSAGE_MAX ?>" required><?= htmlspecialchars($saisie['message']) ?></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-success rounded-pill fw-bold px-4 mt-3">Envoyer ma demande</button>
        </form>
    <?php endif; ?>
</div>

<?php $scriptsPage = ['service_client.js']; include 'footer.php'; ?>
