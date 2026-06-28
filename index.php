<?php
require_once 'config.php';
include 'header.php';

<script src="recherche.js"></script>

$nouveautes = $pdo->query("
    SELECT l.*, e.prix, e.etat 
    FROM livre l 
    INNER JOIN exemplaire e ON l.id_livre = e.id_livre 
    WHERE e.id_exemplaire = (
        SELECT id_exemplaire FROM exemplaire 
        WHERE id_livre = l.id_livre 
        ORDER BY prix ASC LIMIT 1
    )
    ORDER BY l.id_livre DESC 
    LIMIT 8
")->fetchAll();

$meilleuresVentes = $pdo->query("
    SELECT l.*, e.prix, e.etat 
    FROM livre l 
    INNER JOIN exemplaire e ON l.id_livre = e.id_livre 
    WHERE e.id_exemplaire = (
        SELECT id_exemplaire FROM exemplaire 
        WHERE id_livre = l.id_livre 
        ORDER BY prix ASC LIMIT 1
    )
    ORDER BY l.id_livre ASC 
    LIMIT 8
")->fetchAll();

$petitsPrix = $pdo->query("
    SELECT l.*, e.prix, e.etat 
    FROM livre l 
    INNER JOIN exemplaire e ON l.id_livre = e.id_livre 
    WHERE e.prix < 10 
    AND e.id_exemplaire = (
        SELECT id_exemplaire FROM exemplaire 
        WHERE id_livre = l.id_livre 
        ORDER BY prix ASC LIMIT 1
    )
    ORDER BY e.prix ASC 
    LIMIT 8
")->fetchAll();
?>

<style>
    .section-title { color: #274e13; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .section-title::after { content: ""; flex: 1; height: 2px; background: #eee; }
    .slider-viewport { overflow-x: auto; display: flex; gap: 20px; padding: 10px 0 25px 0; scroll-behavior: smooth; }
    .slider-viewport::-webkit-scrollbar { height: 6px; }
    .slider-viewport::-webkit-scrollbar-thumb { background: #274e13; border-radius: 10px; }
    
    .book-card { flex: 0 0 250px; transition: transform 0.3s ease; }
    .book-card:hover { transform: translateY(-5px); }
    
    .card-img-container { background: #f8f9fa; height: 220px; display: flex; align-items: center; justify-content: center; padding: 15px; }

    .line-clamp {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;  
        overflow: hidden;
        height: 2.8em; 
        line-height: 1.4em;
    }

    .book-card .card {
        min-height: 420px; 
        display: flex;
        flex-direction: column;
    }

    .card-body {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }
</style>

<div class="container mt-5">
    <div class="mb-5">
        <h1 class="section-title">Nouveau sur BIBLIOccaz</h1>
        <div class="slider-viewport">
            <?php foreach ($nouveautes as $l): include 'partie_carte_livre.php'; endforeach; ?>
        </div>
    </div>

    <div class="mb-5">
        <h1 class="section-title">Nos meilleures ventes</h1>
        <div class="slider-viewport">
            <?php foreach ($meilleuresVentes as $l): include 'partie_carte_livre.php'; endforeach; ?>
        </div>
    </div>

    <div class="mb-5">
        <h1 class="section-title">Livres à petits prix (-10€)</h1>
        <div class="slider-viewport">
            <?php foreach ($petitsPrix as $l): include 'partie_carte_livre.php'; endforeach; ?>
        </div>
    </div>
</div>

<?php include 'footer.php';  