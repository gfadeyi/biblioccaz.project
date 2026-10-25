<?php
require_once __DIR__ . '/_init.php';
exigerDroit('admin');

$titrePage = 'En ligne';
$pageActive = 'en_ligne';
include __DIR__ . '/_haut.php';
?>

<p class="text-muted small">Membres actifs dans les 5 dernières minutes (actualisation automatique).</p>

<div class="carte" style="max-width: 720px;">
    <div class="carte-entete"><span id="nbEnLigne">…</span></div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light small text-uppercase"><tr><th>Membre</th><th>Rôle</th><th class="text-end">Dernière activité</th></tr></thead>
            <tbody id="corpsEnLigne"></tbody>
        </table>
    </div>
</div>

<?php
$scriptsPage = <<<'JS'
<script>
function cellule(texte, classe) {
    const td = document.createElement('td');
    if (classe) td.className = classe;
    td.textContent = texte;
    return td;
}

async function rafraichir() {
    try {
        const reponse = await fetch('en_ligne_donnees.php', { cache: 'no-store' });
        if (!reponse.ok) return;
        const membres = await reponse.json();
        document.getElementById('nbEnLigne').textContent = membres.length + ' membre' + (membres.length > 1 ? 's' : '') + ' en ligne';

        const corps = document.getElementById('corpsEnLigne');
        corps.replaceChildren();
        if (!membres.length) {
            const tr = document.createElement('tr');
            const td = cellule('Aucun membre actif.', 'text-center text-muted py-4');
            td.colSpan = 3;
            tr.appendChild(td);
            corps.appendChild(tr);
        }
        membres.forEach(m => {
            const tr = document.createElement('tr');
            const td = cellule('@' + m.pseudo, 'fw-bold');
            td.insertAdjacentHTML('afterbegin', '<i class="bi bi-circle-fill text-success me-2" style="font-size: .5rem;"></i>');
            tr.appendChild(td);
            tr.appendChild(cellule(m.role, 'small'));
            tr.appendChild(cellule(m.heure, 'text-end text-muted small'));
            corps.appendChild(tr);
        });
    } catch (e) {
        console.error('En ligne :', e);
    }
}
rafraichir();
setInterval(rafraichir, 5000);
</script>
JS;
include __DIR__ . '/_bas.php';
