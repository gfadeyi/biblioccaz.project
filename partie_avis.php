<?php
require_once __DIR__ . '/avis_lib.php';
require_once __DIR__ . '/compte_lib.php';
require_once __DIR__ . '/recompenses_regles_lib.php';

$idLivreAvis = (int) $livre['id_livre'];
$idUserCourant = (int) ($_SESSION['user_id'] ?? 0);
$flash = $_SESSION['flash_avis'] ?? null;
unset($_SESSION['flash_avis']);

$trisAvis = [
    'recent' => ['Plus récents', 'a.date_creation DESC'],
    'mieux'  => ['Mieux notés', 'a.note DESC, a.date_creation DESC'],
    'moins'  => ['Moins bien notés', 'a.note ASC, a.date_creation DESC'],
];
$triAvis = isset($trisAvis[$_GET['tri_avis'] ?? '']) ? $_GET['tri_avis'] : 'recent';

$resume = noteDuLivre($pdo, $idLivreAvis);

$repartition = array_fill(1, 5, 0);
$stmt = $pdo->prepare("SELECT note, COUNT(*) AS nb FROM avis WHERE id_livre = ? AND statut = 'visible' GROUP BY note");
$stmt->execute([$idLivreAvis]);
foreach ($stmt->fetchAll() as $r) { $repartition[(int) $r['note']] = (int) $r['nb']; }

$stmt = $pdo->prepare("
    SELECT a.*, u.pseudo, u.photo,
           EXISTS (SELECT 1 FROM commande_ligne cl INNER JOIN commande c ON c.id_commande = cl.id_commande
                   WHERE cl.id_livre = a.id_livre AND c.id_user = a.id_user) AS achat_verifie
    FROM avis a INNER JOIN user u ON u.id = a.id_user
    WHERE a.id_livre = ? AND a.statut = 'visible'
    ORDER BY {$trisAvis[$triAvis][1]}");
$stmt->execute([$idLivreAvis]);
$listeAvis = $stmt->fetchAll();

$monAvis = null;
if ($idUserCourant) {
    $stmt = $pdo->prepare("SELECT * FROM avis WHERE id_livre = ? AND id_user = ?");
    $stmt->execute([$idLivreAvis, $idUserCourant]);
    $monAvis = $stmt->fetch() ?: null;
}

$dejaSignales = [];
if ($idUserCourant) {
    $stmt = $pdo->prepare("SELECT s.id_avis FROM avis_signalement s INNER JOIN avis a ON a.id_avis = s.id_avis WHERE s.id_user = ? AND a.id_livre = ?");
    $stmt->execute([$idUserCourant, $idLivreAvis]);
    $dejaSignales = array_column($stmt->fetchAll(), 'id_avis');
}

$csrf = $idUserCourant ? jetonCsrf() : '';
$lienTri = fn($t) => 'detail_livre.php?' . http_build_query(['id' => $idLivreAvis, 'offre' => $_GET['offre'] ?? null, 'tri_avis' => $t]) . '#avis';
?>
<?= etoilesStyle() ?>
<style>
    .note-saisie { display: flex; width: max-content; flex-direction: row-reverse; gap: 4px; }
    .note-saisie { position: relative; }
    .note-saisie input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .note-saisie input:focus-visible + label { outline: 2px solid var(--texte-vif); outline-offset: 2px; border-radius: 4px; }
    .note-saisie label { display: inline-flex; align-items: center; justify-content: center; min-width: 44px; min-height: 44px; font-size: 1.8rem; color: var(--encre-douce); cursor: pointer; transition: color .15s; }
    .note-saisie label:hover, .note-saisie label:hover ~ label,
    .note-saisie input:checked ~ label { color: var(--texte-vif); }
    .barre-note { height: 8px; background: var(--vert-100); border-radius: 10px; overflow: hidden; }
    .barre-note div { height: 100%; background: var(--vert-400); border-radius: inherit; }
    .carte-avis { border-bottom: 1px solid var(--trait); }
    .carte-avis:last-child { border-bottom: 0; }
    .etoile-repartition { color: var(--texte-vif); }
</style>

<section id="avis" class="container mb-5">
    <h3 class="fw-bold border-bottom pb-2 mb-4">Avis des lecteurs</h3>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> rounded-3"><?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm p-4" style="border-radius: 15px;">
                <?php if ($resume['nb']): ?>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="display-5 fw-bold" style="color:#274e13;"><?= number_format($resume['moyenne'], 1, ',', '') ?></span>
                        <div>
                            <?= etoiles($resume['moyenne'], 'fs-5') ?>
                            <div class="text-muted small"><?= $resume['nb'] ?> avis</div>
                        </div>
                    </div>
                    <?php for ($n = 5; $n >= 1; $n--): ?>
                        <?php $pourcent = $resume['nb'] ? round($repartition[$n] * 100 / $resume['nb']) : 0; ?>
                        <div class="d-flex align-items-center gap-2 small mb-1">
                            <span class="text-nowrap" style="width: 52px;"><?= $n ?> <i class="bi bi-star-fill etoile-repartition" aria-hidden="true"></i></span>
                            <div class="barre-note flex-grow-1"><div style="width: <?= $pourcent ?>%;"></div></div>
                            <span class="text-muted" style="width: 26px; text-align: right;"><?= $repartition[$n] ?></span>
                        </div>
                    <?php endfor; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Aucun avis pour l'instant. Soyez le premier à donner le vôtre !</p>
                <?php endif; ?>
            </div>

            <div class="card border-0 shadow-sm p-4 mt-3" style="border-radius: 15px;">
                <?php if (!$idUserCourant): ?>
                    <p class="mb-2 small text-muted">Connectez-vous pour donner votre avis sur ce livre.</p>
                    <a href="login.php" class="btn btn-success rounded-pill btn-sm">Se connecter</a>
                <?php else: ?>
                    <h6 class="fw-bold mb-3"><?= $monAvis ? 'Modifier mon avis' : 'Donner mon avis' ?></h6>
                    <?php if ($monAvis && $monAvis['statut'] === 'masque'): ?>
                        <div class="alert alert-warning small py-2">Votre avis a été masqué par la modération et n'est plus visible publiquement.</div>
                    <?php endif; ?>
                    <form action="action_avis.php" method="POST">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="action" value="enregistrer">
                        <input type="hidden" name="id_livre" value="<?= $idLivreAvis ?>">

                        <div class="note-saisie mb-2" role="radiogroup" aria-label="Note">
                            <?php for ($n = 5; $n >= 1; $n--): ?>
                                <input type="radio" name="note" id="note<?= $n ?>" value="<?= $n ?>" <?= (int) ($monAvis['note'] ?? 0) === $n ? 'checked' : '' ?> required>
                                <label for="note<?= $n ?>" title="<?= $n ?> étoile<?= $n > 1 ? 's' : '' ?>"><i class="bi bi-star-fill" aria-hidden="true"></i><span class="visually-hidden"><?= $n ?> étoile<?= $n > 1 ? 's' : '' ?></span></label>
                            <?php endfor; ?>
                        </div>
                        <label for="avis-titre" class="form-label small fw-bold mb-1">Titre <span class="fw-normal text-muted">(facultatif)</span></label>
                        <input type="text" id="avis-titre" name="titre" maxlength="120" class="form-control form-control-sm mb-2" placeholder="En quelques mots" value="<?= htmlspecialchars($monAvis['titre'] ?? '') ?>">
                        <label for="avis-commentaire" class="form-label small fw-bold mb-1">Votre avis</label>
                        <textarea id="avis-commentaire" name="commentaire" rows="4" class="form-control form-control-sm mb-2" minlength="<?= AVIS_COMMENTAIRE_MIN ?>" maxlength="<?= AVIS_COMMENTAIRE_MAX ?>"
                                  placeholder="Qu'avez-vous pensé de ce livre ?" required><?= htmlspecialchars($monAvis['commentaire'] ?? '') ?></textarea>
                        <button class="bouton bouton-principal bouton-petit w-100"><?= $monAvis ? 'Mettre à jour mon avis' : 'Publier mon avis' ?></button>
                        <?php if (!$monAvis && ($_SESSION['role'] ?? '') === 'client'): ?>
                            <div class="form-text text-center">+<?= pointsRegle($pdo, 'avis') ?> points fidélité pour un avis éligible validé</div>
                        <?php endif; ?>
                    </form>
                    <?php if ($monAvis): ?>
                        <form action="action_avis.php" method="POST" class="mt-2" data-confirmer="Supprimer votre avis sur ce livre ? Les points en attente pour cet avis seront annulés." data-titre-confirmer="Supprimer mon avis" data-bouton-confirmer="Supprimer">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="id_livre" value="<?= $idLivreAvis ?>">
                            <input type="hidden" name="id_avis" value="<?= $monAvis['id_avis'] ?>">
                            <button class="bouton bouton-danger bouton-petit w-100"><i class="bi bi-trash3" aria-hidden="true"></i> Supprimer mon avis</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($listeAvis): ?>
        <div class="col-lg-8">
            <?php if ($listeAvis): ?>
                <div class="d-flex justify-content-end gap-1 mb-3 small">
                    <span class="text-muted me-1 align-self-center">Trier :</span>
                    <?php foreach ($trisAvis as $cle => [$libelle]): ?>
                        <a href="<?= htmlspecialchars($lienTri($cle)) ?>" class="btn btn-sm rounded-pill <?= $triAvis === $cle ? 'btn-success' : 'btn-outline-secondary' ?>"><?= $libelle ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm px-4" style="border-radius: 15px;">

                <?php foreach ($listeAvis as $a): ?>
                    <div class="carte-avis py-3">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <?= etoiles((float) $a['note']) ?>
                                <?php if ($a['titre']): ?><strong><?= htmlspecialchars($a['titre']) ?></strong><?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?= date('d/m/Y', strtotime($a['date_creation'])) ?>
                                <?= $a['date_modification'] ? ' · modifié' : '' ?>
                            </small>
                        </div>
                        <div class="auteur-avis small text-muted mt-2">
                            <?= avatarMembre($a['pseudo'], $a['photo'], 'petit') ?>
                            <strong><?= htmlspecialchars(pseudoAffiche($a['pseudo'])) ?></strong>
                            <?php if ($a['achat_verifie']): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1"><i class="bi bi-patch-check-fill"></i> Achat vérifié</span>
                            <?php endif; ?>
                        </div>
                        <p class="mt-2 mb-1" style="white-space: pre-line;"><?= htmlspecialchars($a['commentaire']) ?></p>

                        <?php if ($idUserCourant && (int) $a['id_user'] !== $idUserCourant): ?>
                            <?php if (in_array($a['id_avis'], $dejaSignales)): ?>
                                <small class="text-muted"><i class="bi bi-flag-fill"></i> Vous avez signalé cet avis</small>
                            <?php else: ?>
                                <details class="small">
                                    <summary class="text-muted" style="cursor:pointer;"><i class="bi bi-flag" aria-hidden="true"></i> Signaler</summary>
                                    <form action="action_avis.php" method="POST" class="d-flex gap-2 mt-2">
                                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                        <input type="hidden" name="action" value="signaler">
                                        <input type="hidden" name="id_livre" value="<?= $idLivreAvis ?>">
                                        <input type="hidden" name="id_avis" value="<?= $a['id_avis'] ?>">
                                        <select name="motif" class="form-select form-select-sm" aria-label="Motif du signalement" required>
                                            <option value="">Motif...</option>
                                            <?php foreach (AVIS_MOTIFS as $cle => $libelle): ?>
                                                <option value="<?= $cle ?>"><?= $libelle ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-outline-danger btn-sm text-nowrap">Envoyer</button>
                                    </form>
                                </details>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
