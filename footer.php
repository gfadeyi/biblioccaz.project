</div>
</main>


<div style="text-align: center; padding: 20px; background-color: #D9EAD3; border-top: 2px solid #274E13;">
    <h3 style="color: #274E13; margin-top: 0;">Rejoignez la newsletter BIBLIOccaz</h3>
    <p style="color: #333333; font-size: 14px;">Recevez nos dernières pépites littéraires d'occasion chaque mois.</p>
    
    <?php if (isset($_GET['newsletter'])): ?>
        <div style="margin-bottom: 15px; font-weight: bold; color: <?php echo $_GET['newsletter'] === 'success' ? '#274E13' : '#cc0000'; ?>;">
            <?php 
                if ($_GET['newsletter'] === 'success') echo " Inscription réussie ! Un mail de bienvenue vous a été envoyé.";
                if ($_GET['newsletter'] === 'error_email') echo " Adresse email invalide.";
            ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="envoyer_newsletter.php">
        <input type="email" name="email_newsletter" placeholder="Votre adresse email..." required 
               style="padding: 10px; width: 250px; border: 1px solid #274E13; border-radius: 4px;">
        <button type="submit" 
                style="padding: 10px 20px; background-color: #8FCE00; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">
            S'inscrire
        </button>
    </form>
</div>


<footer class="bg-white border-top py-5 mt-5" style="border-top: 4px solid var(--border-green) !important;">
    <div class="container text-center">
        <div class="row">
            <div class="col-md-4 mb-3"><h3>BIBLIOccaz</h3><p class="small"> Donnez une seconde vie à vos lectures.</p></div>
            <div class="col-md-4 mb-3"><h3>Support</h3><ul class="list-unstyled small"><li>Contact</li><li>Mentions légales</li></ul></div>
            <div class="col-md-4 mb-3"><h3>Suivez-nous</h3><i class="bi bi-instagram px-2"></i><i class="bi bi-facebook px-2"></i></div>
        </div>
        <hr class="my-4">
        <div class="d-flex flex-wrap justify-content-center align-items-center mb-3 gap-3">
            <span class="text-success fw-bold small text-uppercase" style="letter-spacing: 1px; font-size: 0.7rem;">
             Paiement 100% sécurisé
            </span>
        <div class="d-flex align-items-center gap-2 border rounded p-1 bg-white shadow-sm">
        <img src="img/cb.jpg" alt="cb" style="height: 22px; width: auto;">
        <img src="img/visa.jpg" alt="visa" style="height: 22px; width: auto;">
        <img src="img/mastercard.png" alt="mastercard" style="height: 22px; width: auto;">
        <img src="img/Applepay.png" alt="apple Pay" style="height: 22px; width: auto;">
        <img src="img/googlepay.jpg" alt="google Pay" style="height: 22px; width: auto;">
    </div>
</div>
        <p class="small text-muted mb-0">&copy; 2026 BIBLIOccaz - ESGI</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>