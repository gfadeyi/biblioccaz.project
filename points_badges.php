<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'avis_lib.php';
require_once 'points_lib.php';

$idUser = (int) $_SESSION['user_id'];
$filtresHistorique = ['tous' => 'Tout', 'gagnes' => 'Gagnés', 'utilises' => 'Utilisés', 'attente' => 'En attente'];
$filtre = array_key_exists($_GET['historique'] ?? '', $filtresHistorique) ? $_GET['historique'] : 'tous';

try {
    verifierBadges($pdo, $idUser);
    $niveau = niveauMembre($pdo, $idUser);
    attribuerBonAnniversaire($pdo, $idUser, $niveau);
    $solde = soldePoints($pdo, $idUser);
    $enAttente = pointsEnAttente($pdo, $idUser);
    $badges = badgesMembre($pdo, $idUser);

    $stmt = $pdo->prepare("SELECT niveau_vu FROM user WHERE id = ?");
    $stmt->execute([$idUser]);
    $nouveauNiveau = $niveau['index'] > (int) $stmt->fetchColumn();
    $pdo->prepare("UPDATE user SET niveau_vu = ? WHERE id = ?")->execute([$niveau['index'], $idUser]);
    $pdo->prepare("UPDATE badge_obtenu SET vu = 1 WHERE id_user = ? AND vu = 0")->execute([$idUser]);

    $conditionsFiltre = [
        'tous' => '',
        'gagnes' => " AND points > 0 AND statut = 'valide'",
        'utilises' => " AND type = 'depense'",
        'attente' => " AND statut = 'en_attente'",
    ];
    $stmt = $pdo->prepare("SELECT * FROM point_mouvement WHERE id_user = ?" . $conditionsFiltre[$filtre] . " ORDER BY date_mouvement DESC, id_mouvement DESC LIMIT 100");
    $stmt->execute([$idUser]);
    $mouvements = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM bon_cadeau WHERE id_user = ? AND date_utilisation IS NULL AND date_expiration >= CURDATE() ORDER BY date_expiration");
    $stmt->execute([$idUser]);
    $bonsCadeaux = $stmt->fetchAll();
} catch (PDOException $e) {
    insertLog('ERREUR', "Points et badges : " . $e->getMessage());
    $niveau = niveauPour(0);
    $solde = 0;
    $enAttente = 0;
    $badges = [];
    $nouveauNiveau = false;
    $mouvements = [];
    $bonsCadeaux = [];
}

$badgesClassiques = array_filter($badges, fn($b) => !isset($b['rare']));
$badgesRares = array_filter($badges, fn($b) => isset($b['rare']));
$nombreBadges = count(array_filter($badges, fn($b) => $b['palier'] > 0));
$nouveauxBadges = array_filter($badges, fn($b) => $b['nouveau']);

function texteMesure(array $badge, int $valeur): string
{
    return $valeur . ' ' . $badge['unite'][$valeur > 1 ? 1 : 0];
}

function carteBadge(array $badge): void
{
    $obtenu = $badge['palier'] > 0;
    $estRare = isset($badge['rare']);
    $classes = 'carte-badge' . ($obtenu ? ' obtenu palier-' . ($estRare ? 'rare' : $badge['palier']) : ' verrouille') . ($badge['nouveau'] ? ' badge-nouveau' : '');
    ?>
    <li class="<?= $classes ?>">
        <span class="carte-badge__embleme" aria-hidden="true">
            <?= iconeDessin($badge['icone']) ?>
            <?php if ($obtenu && !$estRare): ?>
                <span class="carte-badge__crans"><?php for ($i = 1; $i <= 3; $i++): ?><span class="<?= $i <= $badge['palier'] ? 'plein' : '' ?>"></span><?php endfor; ?></span>
            <?php endif; ?>
        </span>
        <span class="carte-badge__nom"><?= htmlspecialchars($badge['nom']) ?></span>
        <?php if ($obtenu): ?>
            <span class="carte-badge__palier"><?= $estRare ? 'Badge rare' : PALIERS_BADGES[$badge['palier']] ?></span>
            <span class="carte-badge__detail">Obtenu le <?= date('d/m/Y', strtotime($badge['date'])) ?></span>
        <?php else: ?>
            <span class="carte-badge__palier"><?= $estRare ? 'Badge rare' : 'À débloquer' ?></span>
            <span class="carte-badge__detail"><?= htmlspecialchars($badge['rare'] ?? 'Bronze dès ' . texteMesure($badge, $badge['paliers'][0])) ?></span>
        <?php endif; ?>
        <?php if ($badge['prochain_seuil'] !== null): ?>
            <span class="carte-badge__progression">
                <span class="jauge-badge" role="progressbar" aria-valuemin="0" aria-valuemax="<?= $badge['prochain_seuil'] ?>" aria-valuenow="<?= min($badge['mesure'], $badge['prochain_seuil']) ?>"
                      aria-label="Progression vers le palier suivant" style="--progression: <?= round($badge['progression'], 3) ?>;"><span></span></span>
                <small><?= min($badge['mesure'], $badge['prochain_seuil']) ?> / <?= texteMesure($badge, $badge['prochain_seuil']) ?><?= $obtenu && !$estRare ? ' pour ' . mb_strtolower(PALIERS_BADGES[$badge['palier'] + 1]) : '' ?></small>
            </span>
        <?php elseif (!$estRare): ?>
            <span class="carte-badge__progression"><small>Palier maximum atteint</small></span>
        <?php endif; ?>
    </li>
    <?php
}

include 'header.php';
?>

<div class="container mt-4 mb-5 page-points">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Mes points et badges</span>
    </nav>
    <h1 class="titre-page-compte">Mes points et badges</h1>

    <section class="carte-niveau<?= $nouveauNiveau ? ' niveau-debloque' : '' ?>" aria-labelledby="titreNiveau">
        <div class="carte-niveau__principal">
            <span class="carte-niveau__embleme" aria-hidden="true">
                <?= iconeDessin('medaille') ?>
                <span class="carte-niveau__rang"><?= $niveau['index'] + 1 ?>/<?= count(NIVEAUX) ?></span>
            </span>
            <div class="carte-niveau__texte">
                <?php if ($nouveauNiveau): ?>
                    <p class="carte-niveau__annonce" role="status"><i class="bi bi-stars" aria-hidden="true"></i> Nouveau niveau atteint !</p>
                <?php endif; ?>
                <p class="carte-niveau__surtitre">Votre niveau</p>
                <h2 id="titreNiveau" class="carte-niveau__nom"><?= htmlspecialchars($niveau['nom']) ?></h2>
                <?php if ($niveau['prochain']): ?>
                    <div class="jauge-niveau jauge-niveau--grande" role="progressbar" aria-valuemin="<?= $niveau['seuil'] ?>" aria-valuemax="<?= $niveau['prochain']['seuil'] ?>" aria-valuenow="<?= $niveau['cumul'] ?>"
                         aria-label="Progression vers <?= htmlspecialchars($niveau['prochain']['nom']) ?>" style="--progression: <?= round($niveau['progression'], 3) ?>;"><span></span></div>
                    <p class="carte-niveau__reste">Encore <strong><?= $niveau['reste'] ?> points</strong> pour devenir « <?= htmlspecialchars($niveau['prochain']['nom']) ?> »</p>
                <?php else: ?>
                    <p class="carte-niveau__reste">Vous avez atteint le plus haut niveau. Merci de faire vivre la bibliothèque !</p>
                <?php endif; ?>
            </div>
        </div>
        <dl class="carte-niveau__chiffres">
            <div class="chiffre-cle principal">
                <dt>Solde disponible</dt>
                <dd><?= $solde ?> <small>pts</small></dd>
            </div>
            <div class="chiffre-cle">
                <dt>Total gagné</dt>
                <dd><?= $niveau['cumul'] ?> <small>pts</small></dd>
            </div>
            <div class="chiffre-cle">
                <dt>En attente</dt>
                <dd><?= $enAttente ?> <small>pts</small></dd>
            </div>
        </dl>
        <ol class="echelle-niveaux" aria-label="Les six niveaux">
            <?php foreach (niveauxRegles($pdo) as $index => $etape): ?>
                <li class="<?= $index < $niveau['index'] ? 'passe' : ($index === $niveau['index'] ? 'actuel' : '') ?>" <?= $index === $niveau['index'] ? 'aria-current="step"' : '' ?>>
                    <span class="echelle-niveaux__point" aria-hidden="true"></span>
                    <span class="echelle-niveaux__nom"><?= htmlspecialchars($etape['nom']) ?></span>
                    <span class="echelle-niveaux__seuil"><?= number_format($etape['seuil'], 0, ',', ' ') ?> pts</span>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>

    <div class="row g-4 mt-1">
        <div class="col-lg-6">
            <section class="carte-compte h-100" aria-labelledby="titreAvantages">
                <h2 id="titreAvantages" class="carte-compte__titre"><?= iconeDessin('tampon') ?> Mes avantages</h2>
                <div class="avantages">
                    <div class="avantages__colonne">
                        <h3><?= htmlspecialchars($niveau['nom']) ?> <span class="etiquette-niveau">actuel</span></h3>
                        <?php $avantagesActuels = avantagesNiveau($niveau); ?>
                        <?php if ($avantagesActuels): ?>
                            <ul class="liste-avantages">
                                <?php foreach ($avantagesActuels as $avantage): ?>
                                    <li><i class="bi bi-check2" aria-hidden="true"></i> <?= htmlspecialchars($avantage) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="texte-doux">Vos points se transforment déjà en bons de réduction. Les avantages permanents commencent au niveau « Dévoreur de pages ».</p>
                        <?php endif; ?>
                    </div>
                    <?php if ($niveau['prochain']): ?>
                        <div class="avantages__colonne a-venir">
                            <h3><?= htmlspecialchars($niveau['prochain']['nom']) ?> <span class="etiquette-niveau">dès <?= number_format($niveau['prochain']['seuil'], 0, ',', ' ') ?> pts</span></h3>
                            <?php $avantagesSuivants = avantagesNiveau($niveau['prochain']); ?>
                            <ul class="liste-avantages">
                                <?php foreach ($avantagesSuivants ?: ['Un pas de plus vers les avantages permanents'] as $avantage): ?>
                                    <li><i class="bi bi-lock" aria-hidden="true"></i> <?= htmlspecialchars($avantage) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
        <div class="col-lg-6">
            <section class="carte-compte h-100" aria-labelledby="titreBons">
                <h2 id="titreBons" class="carte-compte__titre"><?= iconeDessin('etiquette') ?> Transformer mes points</h2>
                <ul class="grille-bons">
                    <?php foreach (BONS_POINTS as $points => $montant): ?>
                        <?php $atteint = $solde >= $points; ?>
                        <li class="bon-points <?= $atteint ? 'atteint' : '' ?>">
                            <span class="bon-points__montant"><?= formaterPrix($montant) ?></span>
                            <span class="bon-points__cout"><?= $points ?> points</span>
                            <span class="bon-points__etat"><?= $atteint ? '<i class="bi bi-check2-circle" aria-hidden="true"></i> Disponible' : 'Encore ' . ($points - $solde) . ' pts' ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php foreach ($bonsCadeaux as $bon): ?>
                    <p class="bon-cadeau"><i class="bi bi-gift" aria-hidden="true"></i> Bon cadeau de <?= formaterPrix((float) $bon['montant']) ?> à utiliser avant le <?= date('d/m/Y', strtotime($bon['date_expiration'])) ?></p>
                <?php endforeach; ?>
                <p class="texte-doux mb-0">Choisissez votre bon au moment de payer. Un bon par commande, dès <?= BON_MINIMUM_LIVRES ?> € de livres et jusqu'à 50 % du montant des livres.</p>
            </section>
        </div>
    </div>

    <section class="mt-5" aria-labelledby="titreBadges">
        <div class="entete-section-compte">
            <h2 id="titreBadges" class="titre-section-compte">Mes badges</h2>
            <span class="texte-doux"><?= $nombreBadges ?> sur <?= count($badges) ?> obtenus · bonus de <?= pointsRegle($pdo, 'badge_bronze') ?>, <?= pointsRegle($pdo, 'badge_argent') ?> et <?= pointsRegle($pdo, 'badge_or') ?> points par palier</span>
        </div>
        <?php if ($nouveauxBadges): ?>
            <p class="annonce-badges" role="status"><i class="bi bi-award" aria-hidden="true"></i>
                <?= count($nouveauxBadges) > 1 ? 'Nouveaux badges débloqués' : 'Nouveau badge débloqué' ?> : <?= htmlspecialchars(implode(', ', array_column($nouveauxBadges, 'nom'))) ?> !</p>
        <?php endif; ?>
        <ul class="grille-badges"><?php foreach ($badgesClassiques as $badge) { carteBadge($badge); } ?></ul>
        <h3 class="sous-titre-badges">Badges rares <span class="texte-doux">+<?= pointsRegle($pdo, 'badge_rare') ?> points chacun</span></h3>
        <ul class="grille-badges"><?php foreach ($badgesRares as $badge) { carteBadge($badge); } ?></ul>
    </section>

    <div class="row g-4 mt-4">
        <div class="col-lg-8">
            <section class="carte-compte" aria-labelledby="titreHistorique">
                <div class="entete-section-compte">
                    <h2 id="titreHistorique" class="carte-compte__titre mb-0"><?= iconeDessin('pile') ?> Historique</h2>
                    <nav class="onglets-historique" aria-label="Filtrer l'historique">
                        <?php foreach ($filtresHistorique as $cle => $libelle): ?>
                            <a href="?historique=<?= $cle ?>#titreHistorique" class="<?= $filtre === $cle ? 'actif' : '' ?>" <?= $filtre === $cle ? 'aria-current="page"' : '' ?>><?= $libelle ?></a>
                        <?php endforeach; ?>
                    </nav>
                </div>
                <?php if (!$mouvements): ?>
                    <div class="historique-vide">
                        <?= iconeDessin('medaille') ?>
                        <p>Aucun mouvement pour l'instant. Vos prochains achats, avis et dons apparaîtront ici.</p>
                    </div>
                <?php else: ?>
                    <ul class="liste-mouvements">
                        <?php foreach ($mouvements as $mouvement): ?>
                            <?php $points = (int) $mouvement['points']; ?>
                            <li class="mouvement statut-<?= htmlspecialchars($mouvement['statut']) ?>">
                                <span class="mouvement__date"><?= date('d/m/Y', strtotime($mouvement['date_mouvement'])) ?></span>
                                <span class="mouvement__motif">
                                    <span class="mouvement__type"><?= htmlspecialchars(TYPES_MOUVEMENT[$mouvement['type']] ?? $mouvement['type']) ?></span>
                                    <?= htmlspecialchars($mouvement['libelle']) ?>
                                    <?php if ($mouvement['statut'] !== 'valide'): ?>
                                        <span class="mouvement__statut"><?= STATUTS_MOUVEMENT[$mouvement['statut']] ?? htmlspecialchars($mouvement['statut']) ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="mouvement__points <?= $points >= 0 ? 'positif' : 'negatif' ?>"><?= $points >= 0 ? '+' : '−' ?><?= abs($points) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
        <div class="col-lg-4">
            <section class="carte-compte" aria-labelledby="titreGagner">
                <h2 id="titreGagner" class="carte-compte__titre"><?= iconeDessin('seconde-vie') ?> Gagner des points</h2>
                <ul class="regles-points">
                    <li><strong><?= pointsRegle($pdo, 'achat') ?> point(s)</strong> par euro payé en livres</li>
                    <li><strong>+<?= pointsRegle($pdo, 'premier_achat') ?></strong> pour votre premier achat</li>
                    <li><strong>+<?= pointsRegle($pdo, 'avis') ?></strong> par avis validé sur un livre acheté (<?= quotaRegle($pdo, 'avis') ?> par mois)</li>
                    <li><strong>+<?= pointsRegle($pdo, 'premier_avis') ?></strong> pour votre premier avis validé</li>
                    <li><strong>+<?= pointsRegle($pdo, 'don') ?></strong> quand un de vos dons est récupéré</li>
                    <li><strong>+<?= pointsRegle($pdo, 'presence') ?></strong> par présence à un événement</li>
                    <li><strong>Bonus par palier</strong> à chaque nouveau badge débloqué, selon le barème affiché</li>
                </ul>
                <p class="texte-doux mb-0">Votre niveau dépend du total gagné : utiliser vos points ne vous fait jamais descendre.</p>
            </section>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
