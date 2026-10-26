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
$estEquipe = in_array($_SESSION['role'] ?? '', ['admin', 'gestionnaire'], true);
$estPublic = in_array($evt['statut'], ['publie', 'annule'], true);
$estCreateur = $idUser > 0 && (int) $evt['id_createur'] === $idUser;
if (!$estPublic && !$estEquipe && !$estCreateur) {
    header("Location: evenements.php");
    exit();
}
$proposeur = proposeurEvenement($pdo, $evt);
$livresAssocies = livresEvenement($pdo, (int) $evt['id_evenement']);
$inscription = $idUser ? inscriptionDe($pdo, (int) $evt['id_evenement'], $idUser) : null;
$statutInscription = $inscription['statut'] ?? null;
$etat = etatEvenement($evt);
$restantes = placesRestantes($evt);
$inscriptionsOuvertes = $estPublic && in_array($etat, ['ouvert', 'complet'], true);
$tauxRemplissage = $evt['places_max'] > 0 ? min(100, (int) round(100 * $evt['nb_inscrits'] / $evt['places_max'])) : 0;
$csrf = jetonCsrf();
$debut = strtotime($evt['date_debut']);

$flash = $_SESSION['flash_evt'] ?? null;
unset($_SESSION['flash_evt']);

function boutonEvenement(string $action, int $idEvt, string $csrf, string $libelle, string $classe, string $confirmation = '', string $titreConfirmation = ''): string
{
    $attributsConfirmation = $confirmation ? ' data-confirmer="' . htmlspecialchars($confirmation) . '"' : '';
    if ($titreConfirmation !== '') {
        $attributsConfirmation .= ' data-titre-confirmer="' . htmlspecialchars($titreConfirmation) . '" data-bouton-confirmer="Confirmer"';
    }
    return "<form action=\"evenement_action.php\" method=\"POST\"$attributsConfirmation>
        <input type=\"hidden\" name=\"csrf\" value=\"$csrf\">
        <input type=\"hidden\" name=\"action\" value=\"$action\">
        <input type=\"hidden\" name=\"id_evenement\" value=\"$idEvt\">
        <button class=\"bouton $classe w-100\">$libelle</button>
    </form>";
}

include 'header.php';
?>

<div class="container mt-4 mb-5 page-evenement">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="evenements.php">Événements</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?= htmlspecialchars($evt['titre']) ?></span>
    </nav>

    <?php if ($flash): ?>
        <div class="alert alert-<?= htmlspecialchars($flash[0]) ?> rounded-3" role="status"><?= htmlspecialchars($flash[1]) ?></div>
    <?php endif; ?>

    <div class="grille-evenement">
        <article class="carte-compte evt-fiche">
            <?php if ($evt['image']): ?>
                <div class="evt-fiche__image"><img src="<?= htmlspecialchars(imageEvenement($evt['image'])) ?>" alt=""></div>
            <?php endif; ?>

            <div class="evt-fiche__entete">
                <div class="evt-date" aria-hidden="true">
                    <span><?= EVT_JOURS[(int) date('w', $debut)] ?></span>
                    <strong><?= date('j', $debut) ?></strong>
                    <span><?= EVT_MOIS_COURTS[(int) date('n', $debut) - 1] ?> <?= date('Y', $debut) ?></span>
                </div>
                <div class="evt-fiche__titres">
                    <div class="d-flex flex-wrap gap-2 mb-2"><?= badgeTypeEvenement($evt['type']) ?> <?= $estPublic ? badgeEvenement($evt) : libelleStatutEvenement($evt) ?></div>
                    <h1 class="evt-fiche__titre"><?= htmlspecialchars($evt['titre']) ?></h1>
                </div>
            </div>

            <?php if ($etat === 'annule'): ?>
                <div class="alert alert-danger rounded-3"><i class="bi bi-x-octagon me-1" aria-hidden="true"></i> Cet événement a été annulé.</div>
            <?php elseif (!$estPublic): ?>
                <div class="alert alert-secondary rounded-3"><i class="bi bi-eye me-1" aria-hidden="true"></i> Aperçu : cette proposition est « <?= EVT_STATUTS[$evt['statut']][0] ?? htmlspecialchars($evt['statut']) ?> » et n'est pas visible du public.</div>
            <?php endif; ?>

            <ul class="evt-infos">
                <li><span class="evt-infos__icone"><i class="bi bi-calendar3" aria-hidden="true"></i></span><span><strong>Quand</strong><?= ucfirst(horaireEvenement($evt)) ?></span></li>
                <li><span class="evt-infos__icone"><i class="bi bi-geo-alt" aria-hidden="true"></i></span><span><strong>Où</strong><?= htmlspecialchars($evt['lieu']) ?></span></li>
                <?php if ($evt['auteurs']): ?>
                    <li><span class="evt-infos__icone"><i class="bi bi-person-badge" aria-hidden="true"></i></span><span><strong>Avec</strong><?= htmlspecialchars($evt['auteurs']) ?></span></li>
                <?php endif; ?>
                <?php if ($proposeur): ?>
                    <li><span class="evt-infos__icone"><i class="bi bi-pen" aria-hidden="true"></i></span><span><strong>Proposé par</strong><?= htmlspecialchars($proposeur['nom_affiche']) ?></span></li>
                <?php endif; ?>
            </ul>

            <h2 class="evt-fiche__sous-titre">Au programme</h2>
            <div class="evt-fiche__description"><?= htmlspecialchars($evt['description']) ?></div>
        </article>

        <aside class="evt-panneau">
            <?php if ($estPublic && $etat !== 'annule'): ?>
                <section class="carte-compte evt-places" aria-label="Places">
                    <div class="evt-places__chiffres">
                        <?php if ($etat === 'termine' || $etat === 'en_cours'): ?>
                            <strong><?= (int) $evt['nb_inscrits'] ?></strong> <span>participant<?= $evt['nb_inscrits'] > 1 ? 's' : '' ?> inscrit<?= $evt['nb_inscrits'] > 1 ? 's' : '' ?></span>
                        <?php elseif ($restantes > 0): ?>
                            <strong><?= $restantes ?></strong> <span>place<?= $restantes > 1 ? 's' : '' ?> restante<?= $restantes > 1 ? 's' : '' ?> sur <?= (int) $evt['places_max'] ?></span>
                        <?php else: ?>
                            <strong>Complet</strong> <span><?= (int) $evt['places_max'] ?> places réservées</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!in_array($etat, ['termine', 'en_cours'], true)): ?>
                    <div class="evt-jauge<?= $restantes === 0 ? ' pleine' : ($tauxRemplissage >= 75 ? ' presque' : '') ?>" role="meter" aria-valuemin="0" aria-valuemax="<?= (int) $evt['places_max'] ?>" aria-valuenow="<?= (int) $evt['nb_inscrits'] ?>" aria-label="Places réservées">
                        <span style="--remplissage: <?= $tauxRemplissage ?>%"></span>
                    </div>
                    <?php endif; ?>
                    <p class="evt-places__note">
                        <?php if ($inscriptionsOuvertes && $restantes > 0 && $restantes <= 3): ?>
                            <i class="bi bi-lightning-charge" aria-hidden="true"></i> Plus que quelques places, ne tardez pas !
                        <?php elseif ($inscriptionsOuvertes && $restantes === 0): ?>
                            <i class="bi bi-hourglass-split" aria-hidden="true"></i> <?= (int) $evt['nb_attente'] ?> personne<?= $evt['nb_attente'] > 1 ? 's' : '' ?> en liste d'attente.
                        <?php elseif ($inscriptionsOuvertes): ?>
                            <i class="bi bi-gift" aria-hidden="true"></i> Gratuit, sur inscription.
                        <?php endif; ?>
                    </p>
                </section>
            <?php endif; ?>

            <?php if (!$estPublic): ?>
                <section class="carte-compte">
                    <h2 class="evt-panneau__titre"><?= EVT_STATUTS[$evt['statut']][0] ?? 'Proposition' ?></h2>
                    <p class="small text-muted">Les inscriptions ouvriront quand l'équipe aura validé l'événement.</p>
                    <?php if ($estCreateur && $evt['statut'] === 'en_attente'): ?>
                        <a href="proposer_evenement.php?id=<?= (int) $evt['id_evenement'] ?>" class="bouton bouton-contour bouton-petit w-100 mb-2"><i class="bi bi-pencil" aria-hidden="true"></i> Modifier ma proposition</a>
                    <?php endif; ?>
                    <?php if ($estCreateur): ?>
                        <a href="dashboard_auteur.php" class="bouton bouton-discret bouton-petit w-100">Retour à mon espace créateur</a>
                    <?php endif; ?>
                </section>

            <?php elseif ($statutInscription === 'inscrit' && $etat !== 'annule'): ?>
                <section class="billet" aria-labelledby="titreBillet">
                    <div class="billet__haut">
                        <span class="billet__etiquette"><i class="bi bi-ticket-perforated" aria-hidden="true"></i> Votre billet</span>
                        <h2 class="billet__titre" id="titreBillet"><?= htmlspecialchars($evt['titre']) ?></h2>
                        <p class="billet__quand"><?= ucfirst(horaireEvenement($evt)) ?><br><?= htmlspecialchars($evt['lieu']) ?></p>
                    </div>
                    <div class="billet__bas">
                        <?php if ($inscription['present']): ?>
                            <p class="billet__present"><i class="bi bi-check2-circle" aria-hidden="true"></i> Présence validée, merci d'être venu !</p>
                        <?php endif; ?>
                        <div id="qr-billet" class="billet__qr" data-url="<?= htmlspecialchars(urlPresence($inscription)) ?>"></div>
                        <span class="billet__legende">Code du billet</span>
                        <strong class="billet__code"><?= htmlspecialchars($inscription['code_court']) ?></strong>
                        <?php if ($etat !== 'termine'): ?>
                            <p class="billet__aide">Présentez ce QR code, ou ce code, à l'accueil le jour de l'événement.</p>
                        <?php endif; ?>
                    </div>
                </section>
                <?php if ($inscriptionsOuvertes): ?>
                    <div class="evt-panneau__actions">
                        <button type="button" class="bouton bouton-contour bouton-petit w-100" onclick="window.print()"><i class="bi bi-printer" aria-hidden="true"></i> Imprimer le billet</button>
                        <?= boutonEvenement('desinscrire', (int) $evt['id_evenement'], $csrf, 'Annuler mon inscription', 'bouton-discret bouton-petit evt-desinscription', "Annuler votre inscription ? Votre place sera donnée à quelqu'un d'autre.", 'Annuler mon inscription') ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($statutInscription === 'attente' && $inscriptionsOuvertes): ?>
                <section class="carte-compte evt-attente">
                    <h2 class="evt-panneau__titre"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Vous êtes sur la liste d'attente</h2>
                    <p class="evt-attente__rang"><strong><?= positionAttente($pdo, $inscription) ?><sup>e</sup></strong> dans la file</p>
                    <p class="small text-muted"><?= $evt['attente_auto'] ? "Si une place se libère, vous serez inscrit automatiquement et prévenu par mail." : "Si une place se libère, l'équipe vous inscrira et vous préviendra par mail." ?></p>
                    <?= boutonEvenement('desinscrire', (int) $evt['id_evenement'], $csrf, "Quitter la liste d'attente", 'bouton-danger bouton-petit', "Quitter la liste d'attente ?", "Quitter la liste d'attente") ?>
                </section>

            <?php elseif ($inscriptionsOuvertes): ?>
                <section class="carte-compte">
                    <h2 class="evt-panneau__titre"><?= $etat === 'ouvert' ? 'Je participe' : 'Événement complet' ?></h2>
                    <?php if (!$idUser): ?>
                        <p class="small text-muted">Connectez-vous pour réserver votre place<?= $etat === 'complet' ? " ou rejoindre la liste d'attente" : '' ?>.</p>
                        <a href="login.php" class="bouton bouton-principal w-100"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Se connecter pour s'inscrire</a>
                    <?php elseif ($etat === 'ouvert'): ?>
                        <?= boutonEvenement('inscrire', (int) $evt['id_evenement'], $csrf, '<i class="bi bi-check2-circle" aria-hidden="true"></i> Réserver ma place', 'bouton-principal') ?>
                        <p class="small text-muted mt-2 mb-0">Vous recevrez votre billet par mail et dans votre espace.</p>
                    <?php else: ?>
                        <?= boutonEvenement('inscrire', (int) $evt['id_evenement'], $csrf, '<i class="bi bi-hourglass" aria-hidden="true"></i> Rejoindre la liste d\'attente', 'bouton-contour') ?>
                        <p class="small text-muted mt-2 mb-0">Nous vous préviendrons par mail si une place se libère.</p>
                    <?php endif; ?>
                </section>

            <?php else: ?>
                <section class="carte-compte evt-clos">
                    <p class="mb-0"><?= match ($etat) {
                        'en_cours' => "L'événement a commencé : les inscriptions sont closes.",
                        'termine'  => "Cet événement est terminé. Retrouvez les prochains rendez-vous dans le calendrier.",
                        default    => "Cet événement a été annulé.",
                    } ?></p>
                    <a href="evenements.php" class="lien-voir-tout mt-2">Voir les prochains événements <i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                </section>
            <?php endif; ?>

            <?php if ($estEquipe): ?>
                <a href="admin/<?= $estPublic ? 'evenement_participants' : 'evenement' ?>.php?id=<?= (int) $evt['id_evenement'] ?>" class="bouton bouton-discret bouton-petit w-100"><i class="bi bi-gear" aria-hidden="true"></i> Gérer dans le back-office</a>
            <?php endif; ?>
        </aside>
    </div>

    <?php if ($livresAssocies): ?>
        <section class="livres-evenement" aria-labelledby="titreLivresEvenement">
            <h2 class="evt-fiche__sous-titre" id="titreLivresEvenement">Les livres à découvrir pendant l'événement</h2>
            <div class="livres-evenement__liste">
                <?php foreach ($livresAssocies as $livre): ?>
                    <a href="detail_livre.php?id=<?= (int) $livre['id_livre'] ?>" class="livres-evenement__livre">
                        <img src="img/<?= rawurlencode($livre['couverture'] ?: 'default.png') ?>" alt="" loading="lazy">
                        <strong><?= htmlspecialchars($livre['titre']) ?></strong>
                        <small><?= htmlspecialchars($livre['auteur'] ?? '') ?></small>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<?php if ($statutInscription === 'inscrit' && $estPublic && $etat !== 'annule'): ?>
    <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
    <script>
    (function () {
        const zone = document.getElementById('qr-billet');
        if (!zone || typeof qrcode === 'undefined') return;
        const qr = qrcode(0, 'M');
        qr.addData(zone.dataset.url);
        qr.make();
        zone.innerHTML = qr.createSvgTag({ cellSize: 5, margin: 0, scalable: true });
        zone.querySelector('svg').setAttribute('aria-label', 'QR code du billet');
    })();
    </script>
<?php endif; ?>

<?php include 'footer.php'; ?>
