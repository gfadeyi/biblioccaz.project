<?php require_once __DIR__ . '/parametres_techniques_lib.php'; ?>
<?php
$messagesNewsletter = [
    'success'     => ['succes', 'Votre demande a été reçue.'],
    'confirmation' => ['succes', 'Si cette adresse peut recevoir une confirmation, un email vient de partir. Confirmez votre choix dans les 24 heures.'],
    'error_email' => ['erreur', 'Adresse email invalide.'],
    'error_csrf'  => ['erreur', 'Votre session a expiré, merci de réessayer.'],
];
$cleNewsletter = is_string($_GET['newsletter'] ?? null) ? $_GET['newsletter'] : '';
$messageNewsletter = $messagesNewsletter[$cleNewsletter] ?? null;
?>
<section class="container-xxl">
    <div class="newsletter" id="newsletter">
        <div>
            <h2>La newsletter BIBLIOccaz</h2>
            <p>Nos dernières pépites d'occasion, les dons et les événements, une fois par mois.</p>
        </div>
        <form method="POST" action="envoyer_newsletter">
            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
            <label for="email-newsletter" class="visually-hidden">Votre adresse email</label>
            <input type="email" id="email-newsletter" name="email_newsletter" placeholder="Votre adresse email" required>
            <button type="submit" class="bouton bouton-action">S'inscrire</button>
            <?php if ($messageNewsletter): ?>
                <p class="message-newsletter <?= $messageNewsletter[0] ?>" role="status"><?= $messageNewsletter[1] ?></p>
            <?php endif; ?>
        </form>
    </div>
</section>
</main>

<footer class="pied">
    <div class="container-xxl">
        <div class="row g-4">
            <div class="col-lg-4">
                <span class="logo-nom">BIBLIOccaz</span>
                <p class="mt-2 mb-0">Livres d'occasion et communauté de lecteurs. Donnez une seconde vie à vos lectures.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h3>Acheter</h3>
                <ul>
                    <li><a href="catalogue">Catalogue</a></li>
                    <li><a href="plus_emprunts">Les plus empruntés</a></li>
                    <li><a href="plus_dons">Les plus donnés</a></li>
                    <li><a href="nouveautes">Nouveautés</a></li>
                    <li><a href="petits_prix">Petits prix</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h3>Communauté</h3>
                <ul>
                    <li><a href="qui-sommes-nous">Qui sommes-nous ?</a></li>
                    <li><a href="boutiques">Nos boutiques</a></li>
                    <li><a href="dons">Voir les livres à donner</a></li>
                    <li><a href="evenements">Événements</a></li>
                    <li><a href="auteurs">Nos auteurs</a></li>
                    <li><a href="vendre">Donner ou revendre</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h3>Mon compte</h3>
                <ul>
                    <li><a href="mon-compte">Mon profil</a></li>
                    <li><a href="commandes">Mes achats</a></li>
                    <li><a href="wishlist">Mes favoris</a></li>
                    <li><a href="service_client">Service client</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h3>Contribuer</h3>
                <ul>
                    <li><a href="rejoindre_equipe">Rejoindre l’équipe</a></li>
                    <li><a href="#newsletter">Recevoir la lettre</a></li>
                </ul>
            </div>
        </div>
        <div class="pied-bas">
            <span>&copy; <?= date('Y') ?> BIBLIOccaz · Projet ESGI</span>
            <nav class="liens-legaux" aria-label="Informations légales">
                <a href="mentions_legales">Mentions légales</a>
                <?php if (texteLegalPersonnalise('texte_cgu') !== ''): ?><a href="cgu">Conditions d’utilisation</a><?php endif; ?>
                <a href="confidentialite">Confidentialité</a>
            </nav>
            <span class="message-paiement-demo"><i class="bi bi-receipt" aria-hidden="true"></i> Paiement simulé · aucun débit, aucune expédition</span>
        </div>
    </div>
</footer>
</div>

<div class="modal fade fenetre-confirmation" id="fenetreConfirmation" tabindex="-1" aria-labelledby="titreConfirmation" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <span class="fenetre-confirmation__icone" aria-hidden="true"><i class="bi bi-exclamation-lg"></i></span>
                <h2 class="h5 mb-2" id="titreConfirmation">Confirmer</h2>
                <p class="mb-0" id="texteConfirmation"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="bouton bouton-contour bouton-petit" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="bouton bouton-danger-plein bouton-petit" id="boutonConfirmer">Confirmer</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= urlStatique('ressources/bootstrap-5.3.0/bootstrap.bundle.min.js') ?>"></script>
<script src="javascript/favoris.js?v=<?= filemtime(__DIR__ . '/javascript/favoris.js') ?>"></script>
<script src="javascript/confirmation.js?v=<?= filemtime(__DIR__ . '/javascript/confirmation.js') ?>"></script>
<?php foreach ($scriptsPage ?? [] as $scriptPage): ?>
    <script src="javascript/<?= $scriptPage ?>?v=<?= filemtime(__DIR__ . '/javascript/' . $scriptPage) ?>"></script>
<?php endforeach; ?>

<?php include __DIR__ . '/partie_apercu_livre.php'; ?>
<script src="<?= urlStatique('javascript/boutique.js') ?>" defer></script>
<script src="<?= urlStatique('javascript/mot_de_passe.js') ?>" defer></script>
<script src="<?= urlStatique('javascript/avis.js') ?>" defer></script>
<script src="<?= urlStatique('javascript/notifications.js') ?>" defer></script>
</body>
</html>
