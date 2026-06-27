<div class="book-card">
    <div class="card h-100 shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-img-container">
            <?php $img = !empty($l['couverture']) ? $l['couverture'] : 'default.png'; ?>
            <img src="img/<?= rawurlencode($img) ?>" class="img-fluid" style="max-height: 100%; object-fit: contain;">
        </div>
        
        <div class="card-body">
            <h3 class="fw-bold mb-1 line-clamp">
                <?= htmlspecialchars($l['titre']) ?>
            </h3> 
            
            <p class="small text-muted mb-3 text-truncate">
                <?= htmlspecialchars($l['auteur']) ?>
            </p>
            
            <div class="mt-auto">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge bg-light text-dark border small">
                        <?php 
                        $etat_propre = htmlspecialchars($l['etat']);
                        if ($etat_propre == 'bon') echo 'Bon état';
                        elseif ($etat_propre == 'tres bon') echo 'Très bon état';
                        elseif ($etat_propre == 'neuf') echo 'Neuf';
                        elseif ($etat_propre == 'use') echo 'État usé';
                        else echo ucfirst($etat_propre);
                        ?>
                    </span>
                    <span class="fw-bold" style="color: #274e13; font-size: 1.1rem;">
                        <?= number_format($l['prix'], 2) ?> €
                    </span>
                </div>
            </div>
        </div>

        <div class="card-footer bg-white border-0 pb-3">
            <a href="detail_livre.php?id=<?= $l['id_livre'] ?>" class="btn btn-sm w-100 rounded-pill text-white" style="background-color: #274e13;">
                Voir le détail
            </a>
        </div>
    </div>
</div>