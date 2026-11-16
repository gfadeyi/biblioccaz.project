<?php
require_once 'config.php';
require_once 'recompenses_regles_lib.php';

function chiffreCleSite(PDO $pdo, string $requete): ?int
{
    try {
        return (int) $pdo->query($requete)->fetchColumn();
    } catch (PDOException $e) {
        return null;
    }
}

function pointsQuiSommesNous(PDO $pdo, string $action, int $defaut): int
{
    try {
        return pointsRegle($pdo, $action);
    } catch (Throwable $e) {
        return $defaut;
    }
}

$chiffresCles = [
    ['pile', chiffreCleSite($pdo, 'SELECT COUNT(*) FROM livre WHERE is_valide = 1'), 'livre au catalogue', 'livres au catalogue', 'catalogue', 'Parcourir le catalogue'],
    ['seconde-vie', chiffreCleSite($pdo, "SELECT COUNT(*) FROM don WHERE statut = 'recupere'"), 'don réalisé', 'dons réalisés', 'dons', 'Voir les livres à donner'],
    ['lecteur', chiffreCleSite($pdo, "SELECT COUNT(*) FROM user WHERE role IN ('client', 'auteur') AND statut <> 'refuse_definitif'"), 'membre inscrit', 'membres inscrits', 'inscription', 'Rejoindre la communauté'],
];
$pointsDon = pointsQuiSommesNous($pdo, 'don', 20);
$pointsPresence = pointsQuiSommesNous($pdo, 'presence', 10);
$nombreFrancais = fn(int $nombre): string => number_format($nombre, 0, ',', "\u{202F}");

$titreDocument = 'Qui sommes-nous ?';
$scriptsPage = ['qui_sommes_nous.js'];
include 'header.php';
?>

<div class="page-qui-sommes-nous">
    <section class="qsn-bandeau">
        <div class="container-xxl">
            <nav aria-label="Fil d'Ariane" class="fil-ariane">
                <a href="./">Accueil</a>
                <span aria-hidden="true">›</span>
                <span aria-current="page">Qui sommes-nous ?</span>
            </nav>
            <span class="surtitre"><?= iconeDessin('seconde-vie') ?> Librairie communautaire d’occasion</span>
            <h1>Qui sommes-nous ?</h1>
            <p class="chapo">BIBLIOccaz réunit des lecteurs qui préfèrent faire circuler les livres plutôt que les laisser dormir ou disparaître. Acheter d’occasion, donner, emprunter, se retrouver autour d’une lecture : chaque geste offre une nouvelle vie à un livre.</p>
        </div>
    </section>

    <section class="container-xxl qsn-section" aria-labelledby="qsn-chiffres">
        <h2 id="qsn-chiffres" class="visually-hidden">Les chiffres clés</h2>
        <ul class="qsn-chiffres">
            <?php foreach ($chiffresCles as [$icone, $valeur, $singulier, $pluriel, $lien, $texteLien]): ?>
                <li class="qsn-chiffre">
                    <span class="qsn-chiffre__icone" aria-hidden="true"><?= iconeDessin($icone) ?></span>
                    <?php if ($valeur !== null): ?>
                        <strong class="qsn-chiffre__valeur"><?= $nombreFrancais($valeur) ?></strong>
                        <span class="qsn-chiffre__libelle"><?= $valeur > 1 ? $pluriel : $singulier ?></span>
                    <?php else: ?>
                        <strong class="qsn-chiffre__valeur">—</strong>
                        <span class="qsn-chiffre__libelle"><?= $pluriel ?> (chiffre momentanément indisponible)</span>
                    <?php endif; ?>
                    <a href="<?= $lien ?>" class="qsn-chiffre__lien"><?= $texteLien ?> <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="qsn-note">Chiffres calculés en direct à partir du site.</p>
    </section>

    <section class="container-xxl qsn-section qsn-histoire" aria-labelledby="qsn-titre-histoire" data-histoire-repliable>
        <div class="qsn-histoire__texte">
            <h2 id="qsn-titre-histoire">Notre histoire</h2>
            <p>BIBLIOccaz est né d’un projet étudiant à l’ESGI, autour d’une idée simple : un livre lu une fois mérite d’être lu encore.</p>
            <div class="qsn-histoire__suite" id="qsn-histoire-suite">
                <p>Ce qui devait être une petite librairie d’occasion en ligne est devenu un lieu de partage, où l’on achète, donne, prête et recommande des livres entre lecteurs.</p>
                <p>Le site continue de grandir avec sa communauté : chaque nouvelle fonctionnalité part d’un besoin réel, qu’il s’agisse de retrouver une série, de donner ses livres sans se compliquer la vie ou de rencontrer d’autres passionnés.</p>
            </div>
            <button type="button" class="qsn-lire-suite" aria-expanded="false" aria-controls="qsn-histoire-suite" hidden><span>Lire la suite</span> <i class="bi bi-chevron-down" aria-hidden="true"></i></button>
        </div>
        <blockquote class="qsn-citation">
            <p>« Un livre n’a pas de date de péremption. Il attend simplement son prochain lecteur. »</p>
            <footer>L’esprit BIBLIOccaz</footer>
        </blockquote>
    </section>

    <section class="container-xxl qsn-section" aria-labelledby="qsn-titre-mission">
        <h2 id="qsn-titre-mission">Notre mission : écologique et solidaire</h2>
        <div class="qsn-cartes">
            <article class="qsn-carte">
                <span class="qsn-carte__icone" aria-hidden="true"><?= iconeDessin('livre-ouvert') ?></span>
                <h3>La seconde vie des livres</h3>
                <p>Chaque livre d’occasion remis en circulation, c’est du papier, de l’encre et du transport en moins. Nous décrivons honnêtement l’état de chaque exemplaire pour que vous choisissiez en confiance.</p>
            </article>
            <article class="qsn-carte">
                <span class="qsn-carte__icone" aria-hidden="true"><?= iconeDessin('tampon') ?></span>
                <h3>Contre le pilon</h3>
                <p>Chaque année, des livres invendus ou délaissés finissent détruits alors qu’ils sont en parfait état. Nous préférons leur trouver un lecteur : vente à petit prix, don ou emprunt plutôt que la benne.</p>
            </article>
            <article class="qsn-carte">
                <span class="qsn-carte__icone" aria-hidden="true"><?= iconeDessin('seconde-vie') ?></span>
                <h3>Des dons gratuits avec QR code</h3>
                <p>Proposez un livre en don, un lecteur le réserve et vient le chercher. Il scanne le QR code de votre don au moment de la remise : le don est validé, sans argent ni intermédiaire, et vous gagnez <?= $pointsDon ?> points.</p>
            </article>
            <article class="qsn-carte">
                <span class="qsn-carte__icone" aria-hidden="true"><?= iconeDessin('medaille') ?></span>
                <h3>Des points de fidélité</h3>
                <p>Achats, avis, dons et participation aux rencontres rapportent des points, des niveaux et des badges. Les points se transforment en bons de réduction sur vos prochains livres.</p>
            </article>
            <article class="qsn-carte">
                <span class="qsn-carte__icone" aria-hidden="true"><?= iconeDessin('ticket') ?></span>
                <h3>Rencontres et événements</h3>
                <p>Lectures, dédicaces, ateliers : les événements réunissent les lecteurs autour des livres. Votre présence le jour J vous rapporte <?= $pointsPresence ?> points.</p>
            </article>
            <article class="qsn-carte">
                <span class="qsn-carte__icone" aria-hidden="true"><?= iconeDessin('bulle') ?></span>
                <h3>Une communauté de lecteurs</h3>
                <p>Avis modérés, ventes entre lecteurs, emprunts, auteurs qui présentent leurs œuvres : BIBLIOccaz se construit avec celles et ceux qui le font vivre.</p>
            </article>
        </div>
    </section>

    <section class="container-xxl qsn-section">
        <div class="qsn-appel">
            <div>
                <h2>Envie de participer ?</h2>
                <p>Donnez un livre qui dort sur votre étagère, trouvez votre prochaine lecture ou rejoignez l’équipe.</p>
            </div>
            <div class="qsn-appel__actions">
                <a href="vendre" class="bouton bouton-principal">Donner ou revendre</a>
                <a href="catalogue" class="bouton bouton-contour">Voir le catalogue</a>
                <a href="rejoindre_equipe" class="bouton bouton-contour">Rejoindre l’équipe</a>
            </div>
        </div>
    </section>
</div>

<?php include 'footer.php'; ?>
