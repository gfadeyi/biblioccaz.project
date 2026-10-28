<?php
$lienSessions = fn(int $numero) => 'en_ligne.php?' . http_build_query(['q'=>$recherche,'role'=>$role,'multiples'=>$multiples ? '1' : '', 'page'=>$numero]);
?>
<div class="row g-3 mb-4" aria-label="Synthèse de toutes les sessions valides">
    <?php foreach (['sessions'=>'Sessions valides','utilisateurs'=>'Utilisateurs connectés','multiples'=>'Utilisateurs avec plusieurs sessions'] as $cle=>$libelle): ?>
        <div class="col-md-4">
            <div class="carte p-3 h-100">
                <div class="fs-2 fw-bold text-success">
                    <?= $tableau['indicateurs'][$cle] ?>
                </div>
                <div>
                    <?= e($libelle) ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<p class="small text-muted">Synthèse globale, indépendante des filtres. Données au <?= e($tableau['instant']) ?>. Actualisation à la demande.</p>
<form method="GET" class="carte p-3 mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-6">
            <label for="rechercheSessions" class="form-label">Pseudo, nom ou e-mail</label>
            <input id="rechercheSessions" type="search" name="q" maxlength="100" class="form-control form-control-lg" value="<?= e($recherche) ?>">
        </div>
        <div class="col-md-3">
            <label for="roleSessions" class="form-label">Rôle actuel</label>
            <select id="roleSessions" name="role" class="form-select form-select-lg">
                <option value="">Tous les rôles</option>
                <?php foreach (ROLES_LIBELLES as $cle=>$libelle): ?>
                    <option value="<?= e($cle) ?>" <?= $role === $cle ? 'selected' : '' ?>>
                        <?= e($libelle) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-vert w-100 py-3">Rechercher / Actualiser</button>
        </div>
    </div>
    <label class="form-check mt-3 py-3">
        <input class="form-check-input" type="checkbox" name="multiples" value="1" <?= $multiples ? 'checked' : '' ?>>
        <span class="form-check-label">Uniquement les utilisateurs avec plusieurs sessions</span>
    </label>
    <a href="en_ligne.php" class="small">Réinitialiser les filtres</a>
</form>
<section aria-labelledby="resultatsSessions">
    <h2 id="resultatsSessions" class="h5">
        <?= $tableau['total'] ?> utilisateur(s) correspondant aux filtres</h2>
    <?php if (!$tableau['membres']): ?>
        <div class="carte p-4 text-muted" role="status">Aucun résultat. Essayez un autre filtre ou une autre recherche.</div>
    <?php endif; ?>
    <?php foreach ($tableau['membres'] as $membre): $connexions = $tableau['sessions'][$membre['id']] ?? []; $modifiable = $membre['role'] !== 'admin' || (int) $membre['id'] === (int) $_SESSION['user_id']; ?>
        <article class="carte p-3 mb-3">
            <h3 class="h6 mb-1 text-break">@<?= e($membre['pseudo']) ?> <span class="badge text-bg-light">
                    <?= (int) $membre['nombre'] ?> session(s)</span>
            </h3>
            <p class="small text-muted text-break mb-2">
                <?= e(trim(($membre['prenom'] ?? '') . ' ' . ($membre['nom'] ?? ''))) ?> · <?= e(ROLES_LIBELLES[$membre['role']] ?? $membre['role']) ?> · <?= e($membre['email']) ?>
            </p>
            <details>
                <summary class="py-3">Consulter les sessions de @<?= e($membre['pseudo']) ?>
                </summary>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($connexions as $connexion): ?>
                        <li class="border-top py-3">
                            <p class="mb-1">
                                <strong>
                                    <?= $connexion['espace'] === 'admin' ? 'Back-office' : 'Site' ?>
                                </strong> · ouverte le <?= e($connexion['date_connexion']) ?>
                                <?php if ($connexion['courante']): ?> <span class="badge text-bg-success">Cette connexion</span>
                                <?php endif; ?>
                            </p>
                            <p class="small text-muted">Dernière requête connue : <?= e($connexion['derniere_activite']) ?>
                            </p>
                            <?php if ($modifiable): ?>
                                <details>
                                    <summary class="small py-3">Fermer cette session</summary>
                                    <form method="POST" class="mt-2">
                                        <?= champCsrf() ?>
                                        <input type="hidden" name="action" value="une">
                                        <input type="hidden" name="id_user" value="<?= (int) $membre['id'] ?>">
                                        <input type="hidden" name="id_suivi" value="<?= (int) $connexion['id_suivi'] ?>">
                                        <label class="form-check py-3">
                                            <input type="checkbox" name="confirmer" value="1" required class="form-check-input">
                                            <span class="form-check-label">Je confirme la fermeture de cette session<?= $connexion['courante'] ? ' et ma déconnexion' : '' ?>.</span>
                                        </label>
                                        <button class="btn btn-sm btn-outline-danger py-3">Confirmer la fermeture</button>
                                    </form>
                                </details>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($modifiable && $connexions): ?>
                    <details class="border-top pt-2">
                        <summary class="py-3">Fermer toutes les sessions de cet utilisateur</summary>
                        <form method="POST">
                            <?= champCsrf() ?>
                            <input type="hidden" name="action" value="toutes">
                            <input type="hidden" name="id_user" value="<?= (int) $membre['id'] ?>">
                            <input type="hidden" name="id_suivi" value="<?= (int) $connexions[0]['id_suivi'] ?>">
                            <label class="form-check py-3">
                                <input type="checkbox" name="confirmer" value="1" required class="form-check-input">
                                <span class="form-check-label">Je confirme la fermeture de toutes les sessions de @<?= e($membre['pseudo']) ?>.</span>
                            </label>
                            <button class="btn btn-sm btn-outline-danger py-3">Fermer toutes ses sessions</button>
                        </form>
                    </details>
                <?php endif; ?>
                <?php if (!$modifiable): ?>
                    <p class="small text-muted">Administrateur protégé : aucune fermeture autorisée.</p>
                <?php endif; ?>
            </details>
        </article>
    <?php endforeach; ?>
    <?php if ($tableau['pages'] > 1): ?>
        <nav aria-label="Pagination des utilisateurs" class="d-flex flex-wrap align-items-center gap-3 my-3">
            <?php if ($tableau['page'] > 1): ?>
                <a class="btn btn-outline-secondary" href="<?= e($lienSessions($tableau['page']-1)) ?>">Précédente</a>
            <?php endif; ?>
            <span>Page <?= $tableau['page'] ?> / <?= $tableau['pages'] ?>
            </span>
            <?php if ($tableau['page'] < $tableau['pages']): ?>
                <a class="btn btn-outline-secondary" href="<?= e($lienSessions($tableau['page']+1)) ?>">Suivante</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</section>
<section class="carte border border-danger p-3 mt-5" aria-labelledby="fermetureGlobale">
    <h2 id="fermetureGlobale" class="h5">Fermeture globale des clients et auteurs</h2>
    <p>Hors filtres : <strong>
            <?= $tableau['cibles']['sessions'] ?> session(s)</strong> de <strong>
            <?= $tableau['cibles']['utilisateurs'] ?> compte(s)</strong> actuellement valides. Administrateurs, modérateurs et gestionnaires sont exclus.</p>
    <details>
        <summary class="py-3">Préparer la fermeture globale</summary>
        <form method="POST" class="mt-2">
            <?= champCsrf() ?>
            <input type="hidden" name="action" value="clients_auteurs">
            <input type="hidden" name="comptes_attendus" value="<?= $tableau['cibles']['utilisateurs'] ?>">
            <input type="hidden" name="sessions_attendues" value="<?= $tableau['cibles']['sessions'] ?>">
            <label class="form-check py-3">
                <input type="checkbox" name="confirmer" value="1" required class="form-check-input">
                <span class="form-check-label">Je confirme la fermeture des <?= $tableau['cibles']['sessions'] ?> session(s) de ces <?= $tableau['cibles']['utilisateurs'] ?> compte(s). Ma connexion administrateur est conservée.</span>
            </label>
            <button class="btn btn-outline-danger py-3" <?= $tableau['cibles']['sessions'] ? '' : 'disabled' ?>>Fermer toutes les sessions clients et auteurs</button>
        </form>
    </details>
</section>
