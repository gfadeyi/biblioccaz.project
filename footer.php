<?php require_once __DIR__ . '/parametres_techniques_lib.php'; ?>
<?php
$messagesNewsletter = [
    'success'     => ['succes', 'Inscription réussie ! Un mail de bienvenue vous a été envoyé.'],
    'error_email' => ['erreur', 'Adresse email invalide.'],
    'error_csrf'  => ['erreur', 'Votre session a expiré, merci de réessayer.'],
];
$messageNewsletter = $messagesNewsletter[$_GET['newsletter'] ?? ''] ?? null;
?>
<section class="container-xxl">
    <div class="newsletter" id="newsletter">
        <div>
            <h2>La newsletter BIBLIOccaz</h2>
            <p>Nos dernières pépites d'occasion, les dons et les événements, une fois par mois.</p>
        </div>
        <form method="POST" action="envoyer_newsletter.php">
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
                    <li><a href="catalogue.php">Catalogue</a></li>
                    <li><a href="plus_emprunts.php">Les plus empruntés</a></li>
                    <li><a href="plus_dons.php">Les plus donnés</a></li>
                    <li><a href="nouveauté.php">Nouveautés</a></li>
                    <li><a href="petits_prix.php">Petits prix</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h3>Communauté</h3>
                <ul>
                    <li><a href="dons.php">Livres à donner</a></li>
                    <li><a href="evenements.php">Événements</a></li>
                    <li><a href="auteurs.php">Nos auteurs</a></li>
                    <li><a href="vendre.php">Donner ou revendre</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h3>Mon compte</h3>
                <ul>
                    <li><a href="profil.php">Mon profil</a></li>
                    <li><a href="commandes.php">Mes achats</a></li>
                    <li><a href="wishlist.php">Mes favoris</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h3>Suivez-nous</h3>
                <div class="reseaux">
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                </div>
            </div>
        </div>
        <div class="pied-bas">
            <span>&copy; <?= date('Y') ?> BIBLIOccaz · Projet ESGI</span>
            <nav class="liens-legaux" aria-label="Informations légales">
                <a href="mentions_legales.php">Mentions légales</a>
                <?php if (texteLegalPersonnalise('texte_cgu') !== ''): ?><a href="cgu.php">Conditions d’utilisation</a><?php endif; ?>
                <a href="confidentialite.php">Confidentialité</a>
            </nav>
            <div class="d-flex align-items-center gap-2">
                <span>Paiement sécurisé</span>
                <div class="moyens-paiement">
                    <img src="img/cb.jpg" alt="Carte bancaire" width="31" height="22" loading="lazy">
                    <img src="img/visa.jpg" alt="Visa" width="22" height="22" loading="lazy">
                    <img src="img/mastercard.png" alt="Mastercard" width="28" height="22" loading="lazy">
                    <img src="img/ApplePay.png" alt="Apple Pay" width="33" height="22" loading="lazy">
                    <img src="img/googlepay.jpg" alt="Google Pay" width="39" height="22" loading="lazy">
                </div>
            </div>
        </div>
    </div>
</footer>

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

</body>
</html>
