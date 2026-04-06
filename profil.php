<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    die("Utilisateur introuvable.");
}

$isAdmin = ($user['role'] === 'admin');
$points = $user['solde_points'] ?? 0;

include 'header.php';
?>

<style>
    .profile-bg { background-color: #fcfcfc; min-height: 100vh; padding-bottom: 50px; }
    .section-title { font-weight: 700; font-size: 1.5rem; position: relative; display: inline-block; margin-bottom: 25px; }
    .section-title::after { content: ""; position: absolute; left: 0; bottom: -5px; width: 60%; height: 3px; background-color: <?= $isAdmin ? '#274e13' : '#ffc107' ?>; border-radius: 2px; }
    .info-box { background: white; border-radius: 12px; padding: 30px; border: 1px solid #eee; margin-bottom: 40px; }
    .quick-access-card { background: white; border: 1px solid #f1f1f1; border-radius: 8px; padding: 20px; height: 100%; transition: 0.3s; cursor: pointer; text-decoration: none !important; display: block; }
    .quick-access-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); border-color: #274e13; }
    .card-icon { width: 45px; height: 45px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
    .card-icon i { font-size: 1.3rem; color: #274e13; }
    .card-title { color: #274e13; font-weight: 600; margin-bottom: 5px; font-size: 0.95rem; }
    .card-text { color: #777; font-size: 0.85rem; margin-bottom: 0; }
</style>

<div class="profile-bg">
    <div class="container pt-4">
        
        <h2 class="section-title"><?= $isAdmin ? 'Espace Administrateur' : 'Mon Compte' ?></h2>

        <div class="info-box d-flex flex-column flex-md-row justify-content-between align-items-md-center shadow-sm">
            <div class="d-flex align-items-center mb-3 mb-md-0">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-4" style="width: 70px; height: 70px; border: 2px solid #274e13;">
                    <i class="bi bi-person-circle text-success fs-2"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['pseudo']) ?> <small class="text-muted text-capitalize">(#<?= $user['id'] ?>)</small></h5>
                    <p class="text-muted mb-1 small"><?= htmlspecialchars($user['email']) ?></p>
                    
                    <?php if ($isAdmin): ?>
                        <span class="badge bg-danger rounded-pill px-3 small">ADMINISTRATEUR</span>
                    <?php else: ?>
                        <span class="badge bg-success rounded-pill px-3 small"><?= $points ?> Points BIBLIOccaz</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if ($isAdmin): ?>
                    <a href="admin.php" class="btn btn-dark rounded-pill px-4 fw-bold">Dashboard</a>
                <?php endif; ?>
                <a href="modifier_profil.php" class="btn btn-outline-success rounded-pill px-4 fw-bold">Modifier le profil</a>
            </div>
        </div>

        <h2 class="section-title">Accès rapides</h2>
        <div class="row g-3">
            
            <?php if ($isAdmin): ?>
                <div class="col-md-4 col-lg-3">
                    <a href="moderation_catalogue.php" class="quick-access-card">
                        <div class="card-icon"><i class="bi bi-shield-check"></i></div>
                        <div class="card-title">Modération</div>
                        <p class="card-text">Valider les fiches livres.</p>
                    </a>
                </div>
                <div class="col-md-4 col-lg-3">
                    <a href="gestion_utilisateurs.php" class="quick-access-card">
                        <div class="card-icon"><i class="bi bi-people"></i></div>
                        <div class="card-title">Membres</div>
                        <p class="card-text">Gérer les comptes.</p>
                    </a>
                </div>
                <div class="col-md-4 col-lg-3">
                    <a href="admin_logs.php" class="quick-access-card">
                        <div class="card-icon"><i class="bi bi-terminal"></i></div>
                        <div class="card-title">Logs</div>
                        <p class="card-text">Historique système.</p>
                    </a>
                </div>
            <?php else: ?>
                <div class="col-md-4 col-lg-3">
                    <a href="commandes.php" class="quick-access-card">
                        <div class="card-icon"><i class="bi bi-bag-check"></i></div>
                        <div class="card-title">Mes achats</div>
                        <p class="card-text">Historique de mes commandes.</p>
                    </a>
                </div>
                <div class="col-md-4 col-lg-3">
                    <a href="adresses.php" class="quick-access-card">
                        <div class="card-icon"><i class="bi bi-geo-alt"></i></div>
                        <div class="card-title">Mes adresses</div>
                        <p class="card-text">Gérer mes lieux de livraison.</p>
                    </a>
                </div>
                <div class="col-md-4 col-lg-3">
                    <a href="alertes.php" class="quick-access-card">
                        <div class="card-icon"><i class="bi bi-bell"></i></div>
                        <div class="card-title">Mes alertes</div>
                        <p class="card-text">Disponibilité des livres.</p>
                    </a>
                </div>
                <div class="col-md-4 col-lg-3">
                    <a href="avis.php" class="quick-access-card">
                        <div class="card-icon"><i class="bi bi-star"></i></div>
                        <div class="card-title">Mes avis</div>
                        <p class="card-text">Notes et commentaires laissés.</p>
                    </a>
                </div>
                <div class="col-md-4 col-lg-3">
                    <a href="evenements.php" class="quick-access-card">
                        <div class="card-icon"><i class="bi bi-calendar-event"></i></div>
                        <div class="card-title">Événements</div>
                        <p class="card-text">Mes réservations ateliers.</p>
                    </a>
                </div>
                <div class="col-md-4 col-lg-3">
                    <a href="vendre.php" class="quick-access-card">
                        <div class="card-icon"><i class="bi bi-gift"></i></div>
                        <div class="card-title">Mes dons</div>
                        <p class="card-text">Suivre mes partages.</p>
                    </a>
                </div>
                <div class="col-md-4 col-lg-3">
                    <a href="wishlist.php" class="quick-access-card">
                        <div class="card-icon"><i class="bi bi-heart"></i></div>
                        <div class="card-title">Ma liste d'envies</div>
                        <p class="card-text">Mes coups de cœur.</p>
                    </a>
                </div>
            <?php endif; ?>

            <div class="col-md-4 col-lg-3">
                <a href="logout.php" class="quick-access-card border-danger-subtle">
                    <div class="card-icon" style="background:#fff5f5;"><i class="bi bi-box-arrow-right text-danger"></i></div>
                    <div class="card-title text-danger">Déconnexion</div>
                    <p class="card-text">Quitter la session.</p>
                </a>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>