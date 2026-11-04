<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../decisions_equipe_lib.php';
exigerDroit('equipe');

$typesAutorises = typesDecisionsAutorises($pdo, (string) $_SESSION['role'], DROITS);
$texte = fn(string $champ) => is_string($_GET[$champ] ?? null) ? mb_substr(trim($_GET[$champ]), 0, 100) : '';
$filtres = ['type' => $texte('type'), 'action' => $texte('action'), 'q' => $texte('q'), 'depuis' => $texte('depuis'), 'jusqua' => $texte('jusqua'), 'acteur' => max(0, (int) (is_scalar($_GET['acteur'] ?? null) ? $_GET['acteur'] : 0))];
$disponible = historiqueDecisionsDisponible($pdo);
$page = max(1, (int) (is_scalar($_GET['page'] ?? null) ? $_GET['page'] : 1));
$resultat = historiqueDecisionsEquipe($pdo, $typesAutorises, $filtres, $page);
$page = $resultat['page'] ?? 1;
$acteurs = [];
if ($disponible && $typesAutorises) {
    $requete = $pdo->prepare('SELECT id_acteur, MAX(pseudo_acteur) pseudo FROM equipe_decision WHERE id_acteur IS NOT NULL AND type_objet IN (' . implode(',', array_fill(0, count($typesAutorises), '?')) . ') GROUP BY id_acteur ORDER BY pseudo');
    $requete->execute($typesAutorises);
    $acteurs = $requete->fetchAll();
}
$titrePage = 'Historique des décisions';
$pageActive = 'historique_decisions';
include __DIR__ . '/_haut.php';
?>

<p class="text-muted">Qui a décidé, quand et pourquoi : les nouvelles décisions sont conservées ici, même si le contenu change ensuite. Seuls les domaines autorisés pour votre rôle sont affichés.</p>
<p class="small text-muted">L’historique commence à son activation. Les anciennes décisions ne sont pas reconstituées. Aucun contenu ne peut être modifié depuis cette page.</p>

<?php if (!$disponible): ?>
    <div class="alert alert-info">L’historique n’est pas encore activé. La migration <code>sql/maj_historique_decisions.sql</code> est nécessaire.</div>
<?php else: ?>
    <form method="GET" class="carte p-3 mb-4">
        <div class="row g-3">
            <div class="col-md-6 col-xl-4"><label for="rechercheDecision" class="form-label">Rechercher</label><input id="rechercheDecision" type="search" name="q" class="form-control" maxlength="100" value="<?= e($filtres['q']) ?>" placeholder="Titre, pseudo ou motif"></div>
            <div class="col-md-6 col-xl-4"><label for="typeDecision" class="form-label">Domaine</label><select id="typeDecision" name="type" class="form-select"><option value="">Tous mes domaines</option><?php foreach ($typesAutorises as $type): ?><option value="<?= e($type) ?>" <?= $filtres['type'] === $type ? 'selected' : '' ?>><?= e(OBJETS_DECISIONS_EQUIPE[$type][0]) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6 col-xl-4"><label for="actionDecision" class="form-label">Décision</label><select id="actionDecision" name="action" class="form-select"><option value="">Toutes les décisions</option><?php foreach (ACTIONS_DECISIONS_EQUIPE as $action => $libelle): ?><option value="<?= e($action) ?>" <?= $filtres['action'] === $action ? 'selected' : '' ?>><?= e($libelle) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6 col-xl-4"><label for="acteurDecision" class="form-label">Membre de l’équipe</label><select id="acteurDecision" name="acteur" class="form-select"><option value="0">Tous les membres</option><?php foreach ($acteurs as $acteur): ?><option value="<?= (int) $acteur['id_acteur'] ?>" <?= $filtres['acteur'] === (int) $acteur['id_acteur'] ? 'selected' : '' ?>><?= e($acteur['pseudo']) ?></option><?php endforeach; ?></select></div>
            <div class="col-6 col-xl-4"><label for="debutDecision" class="form-label">Du</label><input id="debutDecision" type="date" name="depuis" class="form-control" value="<?= e($filtres['depuis']) ?>"></div>
            <div class="col-6 col-xl-4"><label for="finDecision" class="form-label">Au</label><input id="finDecision" type="date" name="jusqua" class="form-control" value="<?= e($filtres['jusqua']) ?>"></div>
            <div class="col-12 d-flex flex-wrap gap-2"><button class="btn btn-vert">Filtrer</button><a href="historique_decisions.php" class="btn btn-outline-secondary">Réinitialiser</a><a href="historique_decisions.php?acteur=<?= (int) $_SESSION['user_id'] ?>" class="btn btn-outline-success">Mes décisions</a></div>
        </div>
    </form>
    <p role="status"><?= (int) $resultat['total'] ?> décision<?= $resultat['total'] > 1 ? 's' : '' ?> trouvée<?= $resultat['total'] > 1 ? 's' : '' ?>.</p>
    <?php foreach ($resultat['decisions'] as $decision): ?>
        <article class="carte p-3 mb-3" style="overflow-wrap: anywhere;">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-start">
                <h2 class="h6 fw-bold mb-1"><?= e(ACTIONS_DECISIONS_EQUIPE[$decision['action']] ?? $decision['action']) ?> · <?= e($decision['libelle_objet']) ?></h2>
                <time class="small text-muted" datetime="<?= e(str_replace(' ', 'T', $decision['date_decision'])) ?>"><?= e(date('d/m/Y à H:i:s', strtotime($decision['date_decision']))) ?></time>
            </div>
            <p class="small text-muted mb-2">Par <strong><?= e($decision['pseudo_acteur']) ?></strong> · <?= e(OBJETS_DECISIONS_EQUIPE[$decision['type_objet']][0]) ?> #<?= (int) $decision['id_objet'] ?></p>
            <?php if ($decision['ancien_statut'] !== null || $decision['nouveau_statut'] !== null): ?><p class="small mb-2"><?= e(libelleStatutDecision($decision['ancien_statut'])) ?> → <?= e(libelleStatutDecision($decision['nouveau_statut'])) ?></p><?php endif; ?>
            <p class="mb-2"><strong>Motif :</strong> <?= $decision['motif'] !== null ? nl2br(e($decision['motif'])) : 'Non renseigné.' ?></p>
            <a class="small" href="<?= e(OBJETS_DECISIONS_EQUIPE[$decision['type_objet']][2]) ?>">Ouvrir la file concernée</a>
        </article>
    <?php endforeach; ?>
    <?php if (!$resultat['decisions']): ?><div class="carte p-4 text-muted">Aucune décision enregistrée pour ces critères.</div><?php endif; ?>
    <?php if ($resultat['total'] > 30): ?>
        <nav class="d-flex flex-wrap gap-3 align-items-center" aria-label="Pages de l’historique">
            <?php if ($page > 1): ?><a class="btn btn-outline-success" href="?<?= e(http_build_query($filtres + ['page' => $page - 1])) ?>">Précédente</a><?php endif; ?>
            <span>Page <?= $page ?> sur <?= (int) ceil($resultat['total'] / 30) ?></span>
            <?php if ($page * 30 < $resultat['total']): ?><a class="btn btn-outline-success" href="?<?= e(http_build_query($filtres + ['page' => $page + 1])) ?>">Suivante</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/_bas.php'; ?>
