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
                    <li><a href="nouveauté.php">Nouveautés</a></li>
                    <li><a href="petits_prix.php">Petits prix</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h3>Communauté</h3>
                <ul>
                    <li><a href="dons.php">Livres à donner</a></li>
                    <li><a href="evenements.php">Événements</a></li>
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
            <div class="d-flex align-items-center gap-2">
                <span>Paiement sécurisé</span>
                <div class="moyens-paiement">
                    <img src="img/cb.jpg" alt="Carte bancaire">
                    <img src="img/visa.jpg" alt="Visa">
                    <img src="img/mastercard.png" alt="Mastercard">
                    <img src="img/ApplePay.png" alt="Apple Pay">
                    <img src="img/googlepay.jpg" alt="Google Pay">
                </div>
            </div>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="javascript/favoris.js?v=<?= filemtime(__DIR__ . '/javascript/favoris.js') ?>"></script>
<?php foreach ($scriptsPage ?? [] as $scriptPage): ?>
    <script src="javascript/<?= $scriptPage ?>?v=<?= filemtime(__DIR__ . '/javascript/' . $scriptPage) ?>"></script>
<?php endforeach; ?>

</body>
</html>
