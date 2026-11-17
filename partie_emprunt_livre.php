<?php
$roleConnecte = $_SESSION['role'] ?? '';
$reseauEmprunt = empruntsBoutiquesDisponibles($pdo);
$boutiquesEmprunt = boutiquesEmprunt($pdo);
$idBoutiqueChoisie = filter_var($_GET['boutique'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$boutiqueChoisie = $idBoutiqueChoisie ? boutiqueEmprunt($pdo, $idBoutiqueChoisie) : null;
if (!$boutiqueChoisie || !(int) $boutiqueChoisie['actif']) { $idBoutiqueChoisie = 0; }
$etatToutesBoutiques = $etatEmprunt;
if ($idBoutiqueChoisie) { $etatEmprunt = etatEmpruntLivre($pdo, (int) $id, $idBoutiqueChoisie); }
$monEmprunt = $idUserConnecte ? empruntActifLivre($pdo, (int) $id, $idUserConnecte) : null;
$raisonEmprunt = $idUserConnecte && !$monEmprunt ? raisonInterdictionEmprunt($pdo, $idUserConnecte, $etatEmprunt['libres'] > 0) : '';
$positionAttenteLivre = $idUserConnecte && !$monEmprunt && $idBoutiqueChoisie ? positionAttenteEmprunt($pdo, (int) $id, $idUserConnecte, $idBoutiqueChoisie) : 0;
$exemplaireReference = $etatEmprunt['exemplaires'][0] ?? ['duree_emprunt_jours' => 0, 'renouvellements_max' => 0];
if ($monEmprunt) { $exemplaireReference = ['duree_emprunt_jours' => $monEmprunt['duree_jours'], 'renouvellements_max' => $monEmprunt['renouvellements_max']]; }
?>
<div class="bloc-emprunt" id="emprunt">
    <h2 class="bloc-emprunt__titre"><?= iconeDessin('livre-ouvert') ?> Emprunter ce livre</h2>
    <?php if (!$monEmprunt && $reseauEmprunt): ?>
        <form action="livre#emprunt" method="GET" class="mb-3">
            <input type="hidden" name="id" value="<?= (int) $id ?>">
            <label for="boutique-emprunt" class="form-label fw-bold">Où souhaitez-vous retirer et rendre ce livre ?</label>
            <select class="form-select mb-2" id="boutique-emprunt" name="boutique" required>
                <option value="" disabled<?= !$idBoutiqueChoisie ? ' selected' : '' ?>>Choisir une boutique</option>
                <?php foreach ($boutiquesEmprunt as $b): ?>
                    <?php $copies = array_filter($etatToutesBoutiques['exemplaires'], fn($ex) => (int) $ex['id_boutique'] === (int) $b['id_boutique']); $libresBoutique = count(array_filter($copies, fn($ex) => $ex['statut_pret'] === 'disponible')); ?>
                    <option value="<?= (int) $b['id_boutique'] ?>"<?= $idBoutiqueChoisie === (int) $b['id_boutique'] ? ' selected' : '' ?><?= !$copies ? ' disabled' : '' ?>><?= htmlspecialchars($b['nom']) ?> · <?= $libresBoutique ? 'Disponible' : ($copies ? 'Liste d’attente' : 'Non proposé') ?></option>
                <?php endforeach; ?>
            </select>
            <button class="bouton bouton-contour w-100">Voir cette boutique</button>
        </form>
    <?php endif; ?>
    <?php if (!$reseauEmprunt): ?>
        <p>Le choix de boutique est momentanément indisponible. Vos emprunts déjà enregistrés restent consultables dans « Mes emprunts ».</p>
    <?php elseif (!$monEmprunt && !$idBoutiqueChoisie): ?>
        <p>Choisissez une boutique pour voir ses disponibilités. Le retrait et le retour se feront au même comptoir.</p>
    <?php elseif (!$monEmprunt && $etatEmprunt['total'] === 0): ?>
        <p>Ce livre n’est pas proposé à l’emprunt dans cette boutique. Choisissez une autre boutique dans la liste.</p>
    <?php else: ?>
    <p class="bloc-emprunt__aide"><?= htmlspecialchars($monEmprunt ? libelleBoutiqueEmprunt($monEmprunt) : $boutiqueChoisie['nom']) ?> · retrait et retour au même comptoir.</p>
    <p class="bloc-emprunt__conditions">
        Gratuit · <?= (int) $exemplaireReference['duree_emprunt_jours'] ?> jours
        <?php if ((int) $exemplaireReference['renouvellements_max'] > 0): ?>
            · renouvelable <?= (int) $exemplaireReference['renouvellements_max'] ?> fois
        <?php endif; ?>
        · retrait en magasin
    </p>
    <p class="bloc-emprunt__dispo">
        <?php if ($etatEmprunt['libres'] > 0): ?>
            <span class="pastille-etat"><i class="bi bi-check-circle" aria-hidden="true"></i> Disponible dans cette boutique</span>
        <?php else: ?>
            <span class="pastille-etat attente"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Tous réservés ou empruntés</span>
            <?php if ($etatEmprunt['prochain_retour']): ?>
                <span class="d-block small text-muted mt-1">Prochain retour prévu le <?= date('d/m/Y', strtotime($etatEmprunt['prochain_retour'])) ?><?= $etatEmprunt['attente'] ? ' · ' . $etatEmprunt['attente'] . ' lecteur' . ($etatEmprunt['attente'] > 1 ? 's' : '') . ' en attente' : '' ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </p>

    <?php if (!$idUserConnecte): ?>
        <a href="<?= htmlspecialchars(lienConnexion()) ?>" class="bouton bouton-contour w-100">Se connecter pour emprunter</a>
    <?php elseif ($monEmprunt): ?>
        <p class="bloc-emprunt__info">
            <?php if ($monEmprunt['statut'] === 'reserve'): ?>
                <i class="bi bi-bookmark-check" aria-hidden="true"></i> Vous l'avez réservé : à retirer avant le <?= date('d/m/Y', strtotime($monEmprunt['date_expiration_reservation'])) ?> avec le code <strong><?= htmlspecialchars($monEmprunt['code_retrait']) ?></strong>.
            <?php else: ?>
                <i class="bi bi-book" aria-hidden="true"></i> Vous l'empruntez : à rendre le <?= date('d/m/Y', strtotime($monEmprunt['date_retour_prevue'])) ?>.
            <?php endif; ?>
        </p>
        <a href="mes_emprunts#emprunt-<?= (int) $monEmprunt['id_emprunt'] ?>" class="bouton bouton-contour w-100">Voir mes emprunts</a>
    <?php elseif ($positionAttenteLivre > 0): ?>
        <p class="bloc-emprunt__info"><i class="bi bi-people" aria-hidden="true"></i> Vous êtes n° <?= $positionAttenteLivre ?> sur la liste d'attente : il vous sera réservé dès son retour.</p>
        <form action="emprunt_action" method="POST">
            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
            <input type="hidden" name="action" value="quitter_attente">
            <input type="hidden" name="id_boutique" value="<?= (int) $idBoutiqueChoisie ?>">
            <input type="hidden" name="id_livre" value="<?= (int) $id ?>">
            <input type="hidden" name="retour" value="livre">
            <button class="btn btn-link btn-sm text-muted p-0">Quitter la liste d'attente</button>
        </form>
    <?php elseif ($raisonEmprunt !== ''): ?>
        <p class="bloc-emprunt__refus"><i class="bi bi-info-circle" aria-hidden="true"></i> <?= htmlspecialchars($raisonEmprunt) ?></p>
        <?php if (in_array($roleConnecte, ROLES_EMPRUNTEURS, true)): ?>
            <a href="mes_emprunts" class="small text-muted">Voir mes emprunts</a>
        <?php endif; ?>
    <?php elseif ($etatEmprunt['libres'] > 0): ?>
        <form action="emprunt_action" method="POST">
            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
            <input type="hidden" name="action" value="reserver">
            <input type="hidden" name="id_boutique" value="<?= (int) $idBoutiqueChoisie ?>">
            <input type="hidden" name="id_livre" value="<?= (int) $id ?>">
            <input type="hidden" name="retour" value="livre">
            <button class="bouton bouton-principal w-100"><i class="bi bi-bookmark-plus" aria-hidden="true"></i> Réserver pour emprunter</button>
        </form>
        <p class="bloc-emprunt__aide">Réservation gratuite, sans panier ni paiement. À retirer en magasin avec votre code sous <?= regleEmprunt($pdo, 'reservation_jours_ouvres') ?> jours ouvrés. La durée d’emprunt commence lors du retrait validé par l’équipe.</p>
    <?php else: ?>
        <form action="emprunt_action" method="POST">
            <input type="hidden" name="csrf" value="<?= jetonCsrf() ?>">
            <input type="hidden" name="action" value="attendre">
            <input type="hidden" name="id_boutique" value="<?= (int) $idBoutiqueChoisie ?>">
            <input type="hidden" name="id_livre" value="<?= (int) $id ?>">
            <input type="hidden" name="retour" value="livre">
            <button class="bouton bouton-contour w-100"><i class="bi bi-people" aria-hidden="true"></i> Rejoindre la liste d'attente</button>
        </form>
        <p class="bloc-emprunt__aide">Il vous sera réservé automatiquement à son retour, et vous serez prévenu par mail.</p>
    <?php endif; ?>
    <?php endif; ?>
</div>
