<div class="book-card">
    <div class="card h-100 shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-img-container position-relative">
            <?php $favori = in_array((int) $l['id_livre'], $idsFavoris ?? [], true); ?>
            <?php if (isset($_SESSION['user_id'])): ?>
                <button type="button" class="bouton-coeur <?= $favori ? 'actif' : '' ?>" data-livre="<?= (int) $l['id_livre'] ?>"
                        aria-pressed="<?= $favori ? 'true' : 'false' ?>" title="<?= $favori ? "Retirer de ma liste d'envies" : "Ajouter à ma liste d'envies" ?>">
                    <i class="bi bi-heart<?= $favori ? '-fill' : '' ?>"></i>
                </button>
            <?php else: ?>
                <a href="login.php" class="bouton-coeur" title="Connectez-vous pour ajouter ce livre à vos envies"><i class="bi bi-heart"></i></a>
            <?php endif; ?>
            <?php $img = !empty($l['couverture']) ? $l['couverture'] : 'default.png'; ?>
            <img src="img/<?= rawurlencode($img) ?>" class="img-fluid" style="max-height: 100%; object-fit: contain;">
        </div>
        
        <div class="card-body">
            <h3 class="fw-bold mb-1 line-clamp titre-carte">
                <?= htmlspecialchars($l['titre']) ?>
            </h3> 
            
            <p class="small text-muted mb-<?= isset($l['nb_avis']) ? '1' : '3' ?> text-truncate">
                <?= htmlspecialchars($l['auteur']) ?>
            </p>
            <?php if (isset($l['nb_avis'])): ?>
                <div class="small mb-2" style="min-height: 1.3rem;">
                    <?php if ($l['nb_avis'] > 0): ?>
                        <?= etoiles((float) $l['note_moyenne']) ?> <span class="text-muted">(<?= $l['nb_avis'] ?>)</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
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