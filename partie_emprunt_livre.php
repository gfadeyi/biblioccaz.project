<?php
$roleConnecte = $_SESSION['role'] ?? '';
$monEmprunt = $idUserConnecte ? empruntActifLivre($pdo, (int) $id, $idUserConnecte) : null;
$raisonEmprunt = $idUserConnecte && !$monEmprunt ? raisonInterdictionEmprunt($pdo, $idUserConnecte, $etatEmprunt['libres'] > 0) : '';
$positionAttenteLivre = $idUserConnecte && !$monEmprunt ? positionAttenteEmprunt($pdo, (int) $id, $idUserConnecte) : 0;
$exemplaireReference = $etatEmprunt['exemplaires'][0];
?>
<div class="bloc-emprunt" id="emprunt">
    <h2 class="bloc-emprunt__titre"><?= iconeDessin('livre-ouvert') ?> Emprunter ce livre</h2>
    <p class="bloc-emprunt__conditions">
        Gratuit · <?= (int) $exemplaireReference['duree_emprunt_jours'] ?> jours
        <?php if ((int) $exemplaireReference['renouvellements_max'] > 0): ?>
            · renouvelable <?= (int) $exemplaireReference['renouvellements_max'] ?> fois
        <?php endif; ?>
        · retrait en magasin
    </p>
    <p class="bloc-emprunt__dispo">
        <?php if ($etatEmprunt['libres'] > 0): ?>
            <span class="pastille-etat"><i class="bi bi-check-circle" aria-hidden="true"></i> <?= $etatEmprunt['libres'] ?> exemplaire<?= $etatEmprunt['libres'] > 1 ? 's' : '' ?> disponible<?= $etatEmprunt['libres'] > 1 ? 's' : '' ?></span>
        <?php else: ?>
            <span class="pastille-etat attente"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Tous réservés ou empruntés</span>
            <?php if ($etatEmprunt['prochain_retour']): ?>
                <span class="d-block small text-muted mt-1">Prochain retour prévu le <?= date('d/m/Y', strtotime($etatEmprunt['prochain_retour'])) ?><?= $etatEmprunt['attente'] ? ' · ' . $etatEmprunt['attente'] . ' lecteur' . ($etatEmprunt['attente'] > 1 ? 's' : '') . ' en attente' : '' ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </p>

    <?php if (!$idUserConnecte): ?>
        <a href="login.php" class="bouton bouton-contour w-100">Se connecter pour emprunter</a>
    <?php elseif ($monEmprunt): ?>
        <p class="bloc-emprunt__info">
            <?php if ($monEmprunt['statut'] === 'reserve'): ?>
                <i class="bi bi-bookmark-check" aria-hidden="true"></i> Vous l'avez réservé : à retirer avant le <?= date('d/m/Y', strtotime($monEmprunt['date_expiration_reservation'])) ?> avec le code <strong><?= htmlspecialchars($monEmprunt['code_retrait']) ?></strong>.
            <?php else: ?>
                <i class="bi bi-book" aria-hidden="true"></i> Vous l'empruntez : à rendre le <?= date('d/m/Y', strtotime($monEmprunt['date_retour_prevue'])) ?>.
            <?php endif; ?>
        </p>
        <a href="mes_emprunts.php#emprunt-<?= (int) $monEmprunt['id_emprunt'] ?>" class="bouton bouton-contour w-100">Voir mes emprunts</a>
    <?php elseif ($positionAttenteLivre > 0): ?>
        <p class="bloc-emprunt__info"><i class="bi bi-people" aria-hidden="true"></i> Vous êtes n° <?= $positionAttenteLivre ?> sur la liste d'attente : il vous sera réservé dès son retour.</p>
        <form action="emprunt_action.php" method="POST">
            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
            <input type="hidden" name="action" value="quitter_attente">
            <input type="hidden" name="id_livre" value="<?= (int) $id ?>">
            <input type="hidden" name="retour" value="livre">
            <button class="btn btn-link btn-sm text-muted p-0">Quitter la liste d'attente</button>
        </form>
    <?php elseif ($raisonEmprunt !== ''): ?>
        <p class="bloc-emprunt__refus"><i class="bi bi-info-circle" aria-hidden="true"></i> <?= htmlspecialchars($raisonEmprunt) ?></p>
        <?php if (in_array($roleConnecte, ROLES_EMPRUNTEURS, true)): ?>
            <a href="mes_emprunts.php" class="small text-muted">Voir mes emprunts</a>
        <?php endif; ?>
    <?php elseif ($etatEmprunt['libres'] > 0): ?>
        <form action="emprunt_action.php" method="POST">
            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
            <input type="hidden" name="action" value="reserver">
            <input type="hidden" name="id_livre" value="<?= (int) $id ?>">
            <input type="hidden" name="retour" value="livre">
            <button class="bouton bouton-principal w-100"><i class="bi bi-bookmark-plus" aria-hidden="true"></i> Réserver pour emprunter</button>
        </form>
        <p class="bloc-emprunt__aide">À retirer en magasin sous <?= RESERVATION_JOURS_OUVRES ?> jours ouvrés.</p>
    <?php else: ?>
        <form action="emprunt_action.php" method="POST">
            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
            <input type="hidden" name="action" value="attendre">
            <input type="hidden" name="id_livre" value="<?= (int) $id ?>">
            <input type="hidden" name="retour" value="livre">
            <button class="bouton bouton-contour w-100"><i class="bi bi-people" aria-hidden="true"></i> Rejoindre la liste d'attente</button>
        </form>
        <p class="bloc-emprunt__aide">Il vous sera réservé automatiquement à son retour, et vous serez prévenu par mail.</p>
    <?php endif; ?>
</div>
