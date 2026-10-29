<?php
$lienSessions = fn(int $numero) => 'en_ligne.php?' . http_build_query(['q'=>$recherche,'role'=>$role,'multiples'=>$multiples ? '1' : '', 'page'=>$numero]);
$instant = $tableau['instant'];
$filtresActifs = $recherche !== '' || $role !== '' || $multiples;
$caseSansJs = fn(string $texte) => '<label class="form-check confirmation-sans-js" data-confirmation-sans-js><input type="checkbox" name="confirmer" value="1" required class="form-check-input"> <span class="form-check-label">' . e($texte) . '</span></label>';
?>
<div class="en-ligne-stats mb-2" aria-label="Synthèse de toutes les connexions en cours">
    <?php foreach (['sessions'=>['plug','Connexions en cours'],'utilisateurs'=>['people','Membres connectés'],'multiples'=>['layers','Membres connectés plusieurs fois']] as $cle=>[$icone,$libelle]): ?>
        <div class="stat">
            <span class="stat-icone"><i class="bi bi-<?= $icone ?>" aria-hidden="true"></i></span>
            <span class="stat-valeur"><?= $tableau['indicateurs'][$cle] ?></span>
            <span class="stat-libelle"><?= e($libelle) ?></span>
        </div>
    <?php endforeach; ?>
</div>
<p class="small text-muted mb-4">Tous les membres, sans tenir compte des filtres. Mis à jour à <?= e(date('H:i:s', strtotime($instant))) ?> · <a href="<?= e($lienSessions($tableau['page'])) ?>">Actualiser</a></p>

<form method="GET" class="carte p-3 mb-4 en-ligne-filtres">
    <div class="en-ligne-filtres__champs">
        <div>
            <label for="rechercheSessions" class="form-label">Rechercher un membre</label>
            <input id="rechercheSessions" type="search" name="q" maxlength="100" class="form-control" placeholder="Pseudo, nom ou e-mail" value="<?= e($recherche) ?>">
        </div>
        <div>
            <label for="roleSessions" class="form-label">Rôle</label>
            <select id="roleSessions" name="role" class="form-select">
                <option value="">Tous les rôles</option>
                <?php foreach (ROLES_LIBELLES as $cle=>$libelle): ?>
                    <option value="<?= e($cle) ?>" <?= $role === $cle ? 'selected' : '' ?>><?= e($libelle) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn btn-vert"><i class="bi bi-search" aria-hidden="true"></i> Filtrer</button>
    </div>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
        <label class="form-check mb-0 py-2">
            <input class="form-check-input" type="checkbox" name="multiples" value="1" <?= $multiples ? 'checked' : '' ?>>
            <span class="form-check-label">Seulement les membres connectés plusieurs fois</span>
        </label>
        <?php if ($filtresActifs): ?>
            <a href="en_ligne.php" class="small">Réinitialiser les filtres</a>
        <?php endif; ?>
    </div>
</form>

<section aria-labelledby="resultatsSessions">
    <h2 id="resultatsSessions" class="h5 mb-3"><?= $tableau['total'] ?> membre(s) connecté(s)<?= $filtresActifs ? ' correspondant aux filtres' : '' ?> <span class="small text-muted fw-normal">· activité la plus récente en premier</span></h2>
    <?php if (!$tableau['membres']): ?>
        <div class="carte p-4 text-muted" role="status">Aucun résultat. Essayez un autre filtre ou une autre recherche.</div>
    <?php endif; ?>
    <?php foreach ($tableau['membres'] as $membre):
        $connexions = $tableau['sessions'][$membre['id']] ?? [];
        $estMoi = (int) $membre['id'] === (int) $_SESSION['user_id'];
        $modifiable = $membre['role'] !== 'admin' || $estMoi;
        $nomComplet = trim(($membre['prenom'] ?? '') . ' ' . ($membre['nom'] ?? '')); ?>
        <article class="carte membre-en-ligne mb-3" aria-labelledby="membre-<?= (int) $membre['id'] ?>">
            <header class="membre-en-ligne__entete">
                <span class="membre-en-ligne__initiale" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($membre['prenom'] ?: $membre['pseudo'], 0, 1))) ?></span>
                <div class="membre-en-ligne__identite">
                    <h3 id="membre-<?= (int) $membre['id'] ?>" class="h6 mb-0 text-break">@<?= e($membre['pseudo']) ?><?= $estMoi ? ' <span class="small text-muted fw-normal">(vous)</span>' : '' ?></h3>
                    <p class="small text-muted text-break mb-0"><?= e(implode(' · ', array_filter([$nomComplet, $membre['email']], 'strlen'))) ?></p>
                </div>
                <div class="membre-en-ligne__resume">
                    <span class="badge-role badge-role--<?= e($membre['role']) ?>"><?= e(ROLES_LIBELLES[$membre['role']] ?? $membre['role']) ?></span>
                    <span class="small text-muted">Actif <?= e(ilYA($membre['derniere_activite'], $instant)) ?> · <?= (int) $membre['nombre'] ?> connexion<?= (int) $membre['nombre'] > 1 ? 's' : '' ?></span>
                </div>
            </header>
            <ul class="liste-connexions">
                <?php foreach ($connexions as $connexion):
                    $appareil = $connexion['appareil'] ?: 'Appareil inconnu';
                    $espace = $connexion['espace'] === 'admin' ? 'Back-office' : 'Site'; ?>
                    <li class="connexion">
                        <div class="connexion__appareil">
                            <i class="bi bi-<?= $connexion['espace'] === 'admin' ? 'shield-lock' : 'globe2' ?>" aria-hidden="true"></i>
                            <div>
                                <strong class="<?= $connexion['appareil'] ? '' : 'text-muted' ?>"><?= e($appareil) ?></strong>
                                <span class="connexion__espace"><?= $espace ?></span>
                                <?php if ($connexion['courante']): ?><span class="badge text-bg-success">Cette connexion</span><?php endif; ?>
                            </div>
                        </div>
                        <dl class="connexion__dates">
                            <div><dt>Connexion</dt><dd><?= e(dateConnexion($connexion['date_connexion'], $instant)) ?></dd></div>
                            <div><dt>Dernière activité</dt><dd><time datetime="<?= e(date('c', strtotime($connexion['derniere_activite']))) ?>" title="<?= e(date('d/m/Y H:i:s', strtotime($connexion['derniere_activite']))) ?>"><?= e(ilYA($connexion['derniere_activite'], $instant)) ?></time></dd></div>
                        </dl>
                        <?php if ($modifiable): ?>
                            <form method="POST" class="connexion__action" data-confirmer="<?= e($connexion['courante'] ? 'Fermer votre connexion actuelle ? Vous serez déconnecté(e) du back-office.' : 'Fermer la connexion de @' . $membre['pseudo'] . ' (' . $appareil . ', ' . $espace . ') ? La personne devra se reconnecter.') ?>" data-titre-confirmer="Fermer la connexion" data-bouton-confirmer="Fermer la connexion">
                                <?= champCsrf() ?>
                                <input type="hidden" name="action" value="une">
                                <input type="hidden" name="id_user" value="<?= (int) $membre['id'] ?>">
                                <input type="hidden" name="id_suivi" value="<?= (int) $connexion['id_suivi'] ?>">
                                <?= $caseSansJs('Je confirme la fermeture de cette connexion' . ($connexion['courante'] ? ' et ma déconnexion' : '') . '.') ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle" aria-hidden="true"></i> Fermer<span class="visually-hidden"> la connexion <?= e($appareil) ?> de @<?= e($membre['pseudo']) ?></span></button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if (!$modifiable): ?>
                <p class="membre-en-ligne__pied small text-muted mb-0"><i class="bi bi-lock" aria-hidden="true"></i> Administrateur protégé : aucune fermeture autorisée depuis cette page.</p>
            <?php elseif (count($connexions) > 1): ?>
                <div class="membre-en-ligne__pied">
                    <form method="POST" data-confirmer="<?= e('Fermer les ' . count($connexions) . ' connexions de @' . $membre['pseudo'] . ' ?' . ($estMoi ? ' Vous serez déconnecté(e) du back-office.' : ' La personne devra se reconnecter partout.')) ?>" data-titre-confirmer="Fermer toutes ses connexions" data-bouton-confirmer="Tout fermer">
                        <?= champCsrf() ?>
                        <input type="hidden" name="action" value="toutes">
                        <input type="hidden" name="id_user" value="<?= (int) $membre['id'] ?>">
                        <input type="hidden" name="id_suivi" value="<?= (int) $connexions[0]['id_suivi'] ?>">
                        <?= $caseSansJs('Je confirme la fermeture de toutes les connexions de @' . $membre['pseudo'] . '.') ?>
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-octagon" aria-hidden="true"></i> Fermer toutes ses connexions</button>
                    </form>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    <?php if ($tableau['pages'] > 1): ?>
        <nav aria-label="Pagination des membres" class="d-flex flex-wrap align-items-center gap-3 my-3">
            <?php if ($tableau['page'] > 1): ?>
                <a class="btn btn-outline-secondary" href="<?= e($lienSessions($tableau['page']-1)) ?>">Précédente</a>
            <?php endif; ?>
            <span>Page <?= $tableau['page'] ?> / <?= $tableau['pages'] ?></span>
            <?php if ($tableau['page'] < $tableau['pages']): ?>
                <a class="btn btn-outline-secondary" href="<?= e($lienSessions($tableau['page']+1)) ?>">Suivante</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>

<section class="zone-sensible mt-5" aria-labelledby="fermetureGlobale">
    <h2 id="fermetureGlobale" class="h5"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Zone sensible</h2>
    <p class="mb-1"><strong>Fermeture globale des clients et auteurs</strong></p>
    <p class="small mb-3">Concerne <strong><?= $tableau['cibles']['sessions'] ?> connexion(s)</strong> de <strong><?= $tableau['cibles']['utilisateurs'] ?> compte(s)</strong>, sans tenir compte des filtres. Les administrateurs, modérateurs et gestionnaires restent connectés.</p>
    <form method="POST" data-confirmer="<?= e('Fermer les ' . $tableau['cibles']['sessions'] . ' connexion(s) de ' . $tableau['cibles']['utilisateurs'] . ' compte(s) client ou auteur ? Tous devront se reconnecter. Votre connexion administrateur est conservée.') ?>" data-titre-confirmer="Fermeture globale" data-bouton-confirmer="Tout fermer">
        <?= champCsrf() ?>
        <input type="hidden" name="action" value="clients_auteurs">
        <input type="hidden" name="comptes_attendus" value="<?= $tableau['cibles']['utilisateurs'] ?>">
        <input type="hidden" name="sessions_attendues" value="<?= $tableau['cibles']['sessions'] ?>">
        <?= $caseSansJs('Je confirme la fermeture des ' . $tableau['cibles']['sessions'] . ' connexion(s) de ces ' . $tableau['cibles']['utilisateurs'] . ' compte(s). Ma connexion administrateur est conservée.') ?>
        <button class="btn btn-danger" <?= $tableau['cibles']['sessions'] ? '' : 'disabled' ?>><i class="bi bi-power" aria-hidden="true"></i> Fermer toutes les sessions clients et auteurs</button>
    </form>
</section>
