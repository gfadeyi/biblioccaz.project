<?php
// Journal d'activité (fichier secure_data/logs.txt), actualisé toutes les 5 secondes.
require_once __DIR__ . '/_init.php';
exigerDroit('admin');

$titrePage = "Journal d'activité";
$pageActive = 'logs';
include __DIR__ . '/_haut.php';
?>

<p class="text-muted small">Les 100 dernières lignes du journal, actualisées automatiquement toutes les 5 secondes.</p>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['statTotal', 'Connexions', 'box-arrow-in-right'],
        ['statJour', 'Moyenne / jour', 'calendar-day'],
        ['statSemaine', 'Moyenne / semaine', 'calendar-week'],
        ['statMois', 'Moyenne / mois', 'calendar3'],
    ] as [$idStat, $libelle, $icone]): ?>
        <div class="col-6 col-xl-3">
            <div class="stat">
                <span class="stat-icone"><i class="bi bi-<?= $icone ?>"></i></span>
                <span><span class="stat-valeur d-block" id="<?= $idStat ?>">…</span><span class="stat-libelle"><?= $libelle ?></span></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="carte">
    <div class="carte-entete">
        <span>Dernières entrées</span>
        <select id="filtreType" class="form-select form-select-sm" style="width: auto;" aria-label="Filtrer par type">
            <option value="">Tous les types</option>
        </select>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle small">
            <thead class="table-light text-uppercase">
                <tr><th>Date</th><th>Utilisateur</th><th>Type</th><th>Description</th><th class="text-end">IP</th></tr>
            </thead>
            <tbody id="logTableBody">
                <tr><td colspan="5" class="text-center text-muted py-4">Chargement…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php
$scriptsPage = <<<'JS'
<script>
const couleurs = {
    CONNEXION: 'bg-success', 'DÉCONNEXION': 'bg-info text-dark', MODERATION: 'bg-warning text-dark',
    SECURITE: 'bg-danger', ADMIN: 'bg-dark', STOCK: 'bg-primary', CATALOGUE: 'bg-primary', DON: 'bg-success',
    VISITE: 'bg-light text-dark border'
};
const filtre = document.getElementById('filtreType');
let derniersLogs = [];

// Les journaux contiennent du texte saisi par les visiteurs : on l'insère comme texte, jamais comme HTML
function cellule(texte, classe) {
    const td = document.createElement('td');
    if (classe) td.className = classe;
    td.textContent = texte;
    return td;
}

function afficher() {
    const corps = document.getElementById('logTableBody');
    corps.replaceChildren();
    const lignes = derniersLogs.filter(l => !filtre.value || l.type === filtre.value);
    if (!lignes.length) {
        const tr = document.createElement('tr');
        const td = cellule('Aucune entrée.', 'text-center text-muted py-4');
        td.colSpan = 5;
        tr.appendChild(td);
        corps.appendChild(tr);
        return;
    }
    lignes.forEach(log => {
        const tr = document.createElement('tr');
        tr.appendChild(cellule(log.date, 'text-muted text-nowrap'));
        tr.appendChild(cellule(log.user));
        const tdType = document.createElement('td');
        const badge = document.createElement('span');
        badge.className = 'badge rounded-pill ' + (couleurs[log.type] || 'bg-secondary');
        badge.textContent = log.type;
        tdType.appendChild(badge);
        tr.appendChild(tdType);
        tr.appendChild(cellule(log.desc));
        tr.appendChild(cellule(log.ip, 'text-end text-muted font-monospace'));
        corps.appendChild(tr);
    });
}

async function charger() {
    try {
        const reponse = await fetch('logs_donnees.php', { cache: 'no-store' });
        if (!reponse.ok) return;
        const data = await reponse.json();

        document.getElementById('statTotal').textContent = data.totalConnexions || 0;
        const jours = Object.keys(data.connexionsParJour || {}).length;
        const moyenne = jours ? data.totalConnexions / jours : 0;
        document.getElementById('statJour').textContent = moyenne.toFixed(1);
        document.getElementById('statSemaine').textContent = Math.round(moyenne * 7);
        document.getElementById('statMois').textContent = Math.round(moyenne * 30);

        derniersLogs = data.listeLogs || [];
        // Met à jour la liste des types proposés dans le filtre
        [...new Set(derniersLogs.map(l => l.type))].sort().forEach(type => {
            if (![...filtre.options].some(o => o.value === type)) filtre.add(new Option(type, type));
        });
        afficher();
    } catch (e) {
        console.error('Journal :', e);
    }
}

filtre.addEventListener('change', afficher);
charger();
setInterval(charger, 5000);
</script>
JS;
include __DIR__ . '/_bas.php';
