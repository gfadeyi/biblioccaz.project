<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/avis_lib.php';
require_once __DIR__ . '/collection_personnelle_lib.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if (!in_array($_SESSION['role'] ?? '',['client','auteur'],true)) { http_response_code(403); exit('Accès réservé aux lecteurs et auteurs.'); }
$idUser = (int) $_SESSION['user_id'];
$disponible = collectionPersonnelleDisponible($pdo);
$idSerie = filter_var($_GET['serie'] ?? 0,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) { http_response_code(403); $erreur = 'La session du formulaire a expiré. Rechargez la page.'; }
    elseif (!$disponible) { http_response_code(503); $erreur = 'La collection personnelle n’est pas encore activée.'; }
    else {
        $idSerie = filter_var($_POST['serie'] ?? 0,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]) ?: 0;
        try {
            if (!$idSerie) { throw new DomainException('Choisissez une série.'); }
            $action = $_POST['action'] ?? '';
            if ($action === 'enregistrer') {
                $volumes = $_POST['volumes'] ?? [];
                if (!is_array($volumes)) { throw new DomainException('Sélection de volumes invalide.'); }
                enregistrerCollectionPersonnelle($pdo,$idUser,$idSerie,$volumes);
                $_SESSION['flash_collection'] = 'Votre collection est enregistrée.';
            } elseif ($action === 'retirer') {
                retirerSerieCollection($pdo,$idUser,$idSerie);
                $_SESSION['flash_collection'] = 'La série a été retirée de votre collection.';
                $idSerie = 0;
            } else { throw new DomainException('Action inconnue.'); }
            header('Location: ma_collection.php' . ($idSerie ? '?serie=' . $idSerie : '')); exit;
        } catch (DomainException $e) { http_response_code(422); $erreur = $e->getMessage(); }
        catch (PDOException $e) { http_response_code(503); $erreur = 'L’enregistrement est momentanément indisponible. Vos choix précédents sont conservés.'; insertLog('ERREUR','Enregistrement de collection indisponible'); }
    }
}
$recherche = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']),0,100) : '';
$resultats = [];
if ($recherche !== '') {
    $motif = '%' . str_replace(['!','%','_'],['!!','!%','!_'],$recherche) . '%';
    $requete = $pdo->prepare("SELECT s.id_serie,s.nom,s.auteur FROM serie s WHERE (s.nom LIKE ? ESCAPE '!' OR s.auteur LIKE ? ESCAPE '!') AND EXISTS(SELECT 1 FROM livre_serie ls JOIN livre l ON l.id_livre=ls.id_livre WHERE ls.id_serie=s.id_serie AND l.is_valide=1) ORDER BY s.nom LIMIT 50");
    $requete->execute([$motif,$motif]);$resultats=$requete->fetchAll(PDO::FETCH_ASSOC);
}
$serie = null;$tomes=[];$possedes=[];$manquants=[];$offres=[];
if ($idSerie) {
    $requete=$pdo->prepare('SELECT id_serie,nom,auteur FROM serie WHERE id_serie=?');$requete->execute([$idSerie]);$serie=$requete->fetch(PDO::FETCH_ASSOC);
    if (!$serie) { http_response_code(404); $erreur='Série introuvable.'; }
    else {
        $tomes=tomesDeSerie($pdo,$idSerie);
        $possedes=$disponible?volumesCollectionPersonnelle($pdo,$idUser,$idSerie):[];
        foreach(offresMagasinDesLivres($pdo,array_column($tomes,'id_livre')) as $offre){if($offre['libre']>0&&!isset($offres[$offre['id_livre']]))$offres[$offre['id_livre']]=$offre;}
        foreach($tomes as $tome){if(!isset($possedes[cleVolumeCollection($tome)]))$manquants[]=$tome;}
    }
}
$mesSeries=$disponible?seriesCollectionPersonnelle($pdo,$idUser):[];
$flash=$_SESSION['flash_collection']??'';unset($_SESSION['flash_collection']);
$titreDocument='Mes collections';
include __DIR__.'/header.php';
?>
<main class="container-xxl py-4">
    <span class="surtitre">Votre bibliothèque, volume par volume</span>
    <h1>Mes collections</h1>
    <p class="chapo">Cochez ce que vous possédez. Retrouvez les volumes manquants parmi les références du catalogue.</p>
    <?php if($erreur): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($erreur) ?></p><?php endif; ?>
    <?php if($flash): ?><p class="alert alert-success" role="status"><?= htmlspecialchars($flash) ?></p><?php endif; ?>
    <?php if(!$disponible): ?><p class="alert alert-secondary">Le suivi n’est pas encore activé. Vous pouvez consulter les séries, mais aucun choix ne sera enregistré.</p><?php endif; ?>
    <form method="get" action="ma_collection.php" class="d-flex flex-wrap gap-2 align-items-end my-4">
        <div class="flex-grow-1"><label for="recherche-serie" class="form-label">Chercher une série ou une collection</label><input class="form-control" type="search" name="q" id="recherche-serie" maxlength="100" value="<?= htmlspecialchars($recherche) ?>" placeholder="Ex. One Piece, Agatha Christie…"></div>
        <button class="bouton bouton-action" type="submit">Chercher</button>
    </form>
    <?php if($recherche !== ''): ?>
        <section class="carte-compte mb-4"><h2 class="h4">Résultats de recherche</h2>
            <?php if(!$resultats): ?><p>Aucune série trouvée. Essayez un autre titre.</p><?php endif; ?>
            <ul><?php foreach($resultats as $r): ?><li><a href="ma_collection.php?serie=<?= (int)$r['id_serie'] ?>"><?= htmlspecialchars($r['nom']) ?></a> — <?= htmlspecialchars((string)$r['auteur']) ?></li><?php endforeach; ?></ul>
            <?php if(count($resultats)===50): ?><p class="small">50 résultats affichés. Précisez votre recherche pour retrouver les autres séries.</p><?php endif; ?>
        </section>
    <?php endif; ?>
    <section class="mb-4"><h2 class="h4">Mes séries suivies</h2>
        <?php if(!$mesSeries): ?><p>Votre collection commence ici : cherchez une série, puis enregistrez les volumes que vous possédez.</p><?php endif; ?>
        <ul class="collection-series"><?php foreach($mesSeries as $s): ?><li><a href="ma_collection.php?serie=<?= (int)$s['id_serie'] ?>"><?= htmlspecialchars($s['nom']) ?></a><span class="small"><?= (int)$s['possedes'] ?> volume(s) enregistré(s)</span></li><?php endforeach; ?></ul>
    </section>
    <?php if($serie): ?>
        <section class="carte-compte mb-4">
            <h2><?= htmlspecialchars($serie['nom']) ?></h2>
            <p><?= count($tomes)-count($manquants) ?> / <?= count($tomes) ?> volume(s) du catalogue possédé(s).</p>
            <p class="small">Un tome simple et un volume double ou intégral sont suivis séparément. Les autres éditions du même volume sont regroupées dans cette série. Les éditions Perfect, Deluxe et les autres découpages ont leur propre série lorsqu’ils sont distingués au catalogue. Les références absentes du catalogue ne sont pas comptées.</p>
            <?php if($disponible && count($tomes)<=COLLECTION_VOLUMES_MAX): ?>
                <form method="post" action="ma_collection.php?serie=<?= $idSerie ?>">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf()) ?>"><input type="hidden" name="serie" value="<?= $idSerie ?>"><input type="hidden" name="action" value="enregistrer">
                    <fieldset><legend class="h5">Je possède ces volumes</legend>
                        <div class="collection-volumes"><?php foreach($tomes as $tome): ?>
                            <label class="collection-volume"><input type="checkbox" name="volumes[]" value="<?= (int)$tome['id_livre'] ?>" <?= isset($possedes[cleVolumeCollection($tome)])?'checked':'' ?>><span><?= $tome['numero_tome']!==null?'Volume '.htmlspecialchars(numeroVolumeSerie($tome)).' — ':'' ?><?= htmlspecialchars($tome['titre']) ?></span></label>
                        <?php endforeach; ?></div>
                    </fieldset>
                    <button type="submit" class="bouton bouton-action mt-3">Enregistrer mes volumes</button>
                    <p class="small mt-2">Les volumes affichés non cochés seront retirés de vos choix. Le dernier enregistrement remplace les choix précédents ; les anciennes références masquées au catalogue restent conservées.</p>
                </form>
            <?php endif; ?>
            <?php if(count($tomes)>COLLECTION_VOLUMES_MAX): ?><p class="alert alert-secondary">Cette série dépasse les 500 volumes gérés par ce formulaire. Son suivi détaillé n’est pas encore disponible.</p><?php endif; ?>
            <?php if($disponible): ?><form method="post" action="ma_collection.php" class="mt-3" data-confirmer="Retirer cette série et ses volumes de votre collection ?"><input type="hidden" name="csrf" value="<?= htmlspecialchars(jetonCsrf()) ?>"><input type="hidden" name="serie" value="<?= $idSerie ?>"><input type="hidden" name="action" value="retirer"><button type="submit" class="bouton bouton-discret bouton-petit">Ne plus suivre cette série</button></form><?php endif; ?>
        </section>
        <section id="volumes-manquants"><h2>Les volumes qui me manquent</h2><p>Offres magasin consultées maintenant ; prix et stock revérifiés dans le panier. Aucun achat n’est ajouté automatiquement à votre collection.</p>
            <?php if(!$tomes): ?><p>Aucun volume public référencé pour le moment.</p><?php elseif(!$manquants): ?><p class="alert alert-success">Tous les volumes actuellement référencés pour cette série sont cochés.</p><?php endif; ?>
            <div class="collection-manquants"><?php foreach($manquants as $tome): $offre=$offres[$tome['id_livre']]??null; ?>
                <article class="carte-compte"><h3 class="h5"><a href="detail_livre.php?id=<?= (int)$tome['id_livre'] ?>"><?= htmlspecialchars($tome['titre']) ?></a></h3>
                    <?php if($tome['numero_tome']!==null): ?><p class="small">Volume <?= htmlspecialchars(numeroVolumeSerie($tome)) ?></p><?php endif; ?>
                    <p class="mb-0"><?= $offre?'Dès '.number_format((float)$offre['prix'],2,',',' ').' € — '.htmlspecialchars(libelleEtat($offre['etat'])):'Indisponible en magasin pour le moment' ?></p>
                </article>
            <?php endforeach; ?></div>
        </section>
    <?php endif; ?>
</main>
<?php include __DIR__.'/footer.php'; ?>
