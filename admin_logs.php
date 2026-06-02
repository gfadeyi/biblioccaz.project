<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'header.php';
?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark">Statistiques de connexion</h2>
            <p class="text-muted small mb-0">Données en temps réel (Actualisation auto toutes les 5s).</p>
        </div>
        <div>
            <a href="admin.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-2"></i>Retour au Dashboard
            </a>
        </div>
    </div>

    <div class="row g-3 mb-5">
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm p-3 bg-dark text-white text-center h-100" style="border-radius: 12px;">
                <span class="small text-uppercase fw-bold text-light opacity-75 d-block font-monospace">Total Connexions</span>
                <h3 class="fw-bold text-warning mt-2 mb-0" id="statTotal">0</h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm p-3 bg-primary text-white text-center h-100" style="border-radius: 12px;">
                <span class="small text-uppercase fw-bold text-light opacity-75 d-block font-monospace">Moyenne / Jour</span>
                <h3 class="fw-bold mt-2 mb-0" id="statJour">0</h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-2">
            <div class="card border-0 shadow-sm p-3 bg-info text-white text-center h-100" style="border-radius: 12px;">
                <span class="small text-uppercase fw-bold text-dark opacity-75 d-block font-monospace">Moyenne / Semaine</span>
                <h3 class="fw-bold text-dark mt-2 mb-0" id="statSemaine">0</h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm p-3 text-white text-center h-100" style="border-radius: 12px; background-color: #6f42c1;">
                <span class="small text-uppercase fw-bold text-light opacity-75 d-block font-monospace">Moyenne / Mois</span>
                <h3 class="fw-bold mt-2 mb-0" id="statMois">0</h3>
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="card border-0 shadow-sm p-3 bg-success text-white text-center h-100" style="border-radius: 12px;">
                <span class="small text-uppercase fw-bold text-light opacity-75 d-block font-monospace">Durée Session Moyenne</span>
                <h3 class="fw-bold mt-2 mb-0" id="moyenneDisplay">0 s</h3>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm border-0 mb-5" style="border-radius: 15px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small">
                    <tr>
                        <th class="ps-4">Date & Heure</th>
                        <th>Utilisateur (ID + Pseudo)</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th class="text-end pe-4">IP</th>
                    </tr>
                </thead>
                <tbody id="logTableBody">
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function chargerDonnesAudit() {
    try {
        const response = await fetch("get_stats_logs.php");
        const data = await response.json();
        
        if (data) {
            document.getElementById("statTotal").textContent = data.totalConnexions || 0;
            
            let totalSecs = parseInt(data.sessionMoyenne) || 0;
            if (totalSecs > 0) {
                let mins = Math.floor(totalSecs / 60);
                let secs = totalSecs % 60;
                let finalTime = "";
                if (mins > 0) finalTime += mins + " min ";
                if (secs > 0 || mins === 0) finalTime += secs + " s";
                document.getElementById("moyenneDisplay").textContent = finalTime;
            } else {
                document.getElementById("moyenneDisplay").textContent = "0 s";
            }

            if (data.connexionsParJour) {
                let cles = Object.keys(data.connexionsParJour);
                if (cles.length > 0) {
                    let totalConn = data.totalConnexions;
                    let moy = totalConn / cles.length;
                    document.getElementById("statJour").textContent = moy.toFixed(1);
                    document.getElementById("statSemaine").textContent = Math.round(moy * 7);
                    document.getElementById("statMois").textContent = Math.round(moy * 30);
                }
            }
            
            if (data.listeLogs) {
                const tableBody = document.getElementById("logTableBody");
                let rows = "";
                data.listeLogs.forEach(log => {
                    let badge = "bg-secondary";
                    if (log.type === "CONNEXION") badge = "bg-success";
                    if (log.type === "DÉCONNEXION") badge = "bg-info text-dark";
                    if (log.type === "MODERATION") badge = "bg-warning text-dark";
                    if (log.type === "VISITE") badge = "bg-light text-dark border";

                    let userClass = (log.user === "Invité") ? "bg-secondary-subtle text-secondary border-0" : "bg-light text-dark border";

                    rows += `<tr>
                        <td class="ps-4 small text-muted">${log.date}</td>
                        <td><span class="badge ${userClass} fw-normal">${log.user}</span></td>
                        <td><span class="badge ${badge} rounded-pill">${log.type}</span></td>
                        <td class="small">${log.desc}</td>
                        <td class="text-end pe-4 small text-muted font-monospace">${log.ip}</td>
                    </tr>`;
                });
                tableBody.innerHTML = rows;
            }
        }
    } catch (e) {
        console.error("Erreur :", e);
    }
}

setInterval(chargerDonnesAudit, 5000);
window.onload = chargerDonnesAudit;
</script>

<?php include 'footer.php'; ?>