<?php
require_once 'config.php';
require_once 'evenements_lib.php';

if (($_SESSION['role'] ?? '') !== 'auteur') {
    header("Location: login.php");
    exit();
}

$evt = chargerEvenement($pdo, (int) ($_GET['id'] ?? 0));
if (!$evt || (int) $evt['id_createur'] !== (int) $_SESSION['user_id'] || !in_array($evt['statut'], ['publie', 'annule'], true) || empty($evt['date_validation'])) {
    header("Location: dashboard_auteur.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT u.pseudo, u.prenom, u.nom, i.statut, i.date_inscription, i.present, i.prioritaire, i.id_inscription
    FROM evenement_inscription i INNER JOIN user u ON u.id = i.id_user
    WHERE i.id_evenement = ? AND i.statut IN ('inscrit', 'attente')
    ORDER BY i.statut = 'attente', i.prioritaire DESC, i.date_inscription, i.id_inscription");
$stmt->execute([$evt['id_evenement']]);
$participants = $stmt->fetchAll();

function nomParticipant(array $participant): string
{
    $prenom = trim((string) $participant['prenom']);
    $nom = trim((string) $participant['nom']);
    if ($prenom === '') {
        return $participant['pseudo'];
    }
    return $prenom . ($nom !== '' ? ' ' . mb_strtoupper(mb_substr($nom, 0, 1)) . '.' : '');
}

$aCommence = $evt['date_debut'] <= date('Y-m-d H:i:s');
$inscrits = array_values(array_filter($participants, fn($p) => $p['statut'] === 'inscrit'));
$enAttente = array_values(array_filter($participants, fn($p) => $p['statut'] === 'attente'));

include 'header.php';
?>

<div class="container mt-4 mb-5 page-inscrits">
    <nav aria-label="Fil d'Ariane" class="fil-ariane">
        <a href="profil.php">Mon compte</a>
        <span aria-hidden="true">›</span>
        <a href="dashboard_auteur.php">Espace créateur</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page">Inscrits</span>
    </nav>
    <h1 class="titre-page-compte mb-1"><?= htmlspecialchars($evt['titre']) ?></h1>
    <p class="text-muted"><?= ucfirst(horaireEvenement($evt)) ?> · <?= htmlspecialchars($evt['lieu']) ?></p>

    <?php if ($evt['statut'] === 'annule'): ?>
        <div class="alert alert-danger rounded-3">Cet événement a été annulé par l'équipe : les inscrits ont été prévenus.</div>
    <?php endif; ?>

    <div class="chiffres-inscrits">
        <div class="carte-compte"><strong><?= (int) $evt['nb_inscrits'] ?> / <?= (int) $evt['places_max'] ?></strong><span>places réservées</span></div>
        <div class="carte-compte"><strong><?= (int) $evt['nb_attente'] ?></strong><span>en liste d'attente</span></div>
        <?php if ($aCommence): ?>
            <div class="carte-compte"><strong><?= (int) $evt['nb_presents'] ?></strong><span>présent<?= $evt['nb_presents'] > 1 ? 's' : '' ?> le jour J</span></div>
        <?php else: ?>
            <div class="carte-compte"><strong><?= placesRestantes($evt) ?></strong><span>place<?= placesRestantes($evt) > 1 ? 's' : '' ?> encore libre<?= placesRestantes($evt) > 1 ? 's' : '' ?></span></div>
        <?php endif; ?>
    </div>

    <section class="carte-compte mt-3">
        <h2 class="carte-compte__titre"><?= iconeDessin('lecteur') ?> Inscrits</h2>
        <?php if (!$inscrits): ?>
            <p class="text-muted mb-0">Personne n'est encore inscrit. Partagez la page de l'événement : <a href="evenement.php?id=<?= (int) $evt['id_evenement'] ?>"><?= htmlspecialchars(SITE_URL . '/evenement.php?id=' . $evt['id_evenement']) ?></a></p>
        <?php else: ?>
            <ol class="liste-inscrits">
                <?php foreach ($inscrits as $p): ?>
                    <li>
                        <span class="liste-inscrits__nom"><?= htmlspecialchars(nomParticipant($p)) ?></span>
                        <small>inscrit le <?= date('d/m/Y', strtotime($p['date_inscription'])) ?></small>
                        <?php if ($aCommence): ?>
                            <?= $p['present'] ? '<span class="etiquette-profil"><i class="bi bi-check2" aria-hidden="true"></i> Présent</span>' : '<span class="etiquette-profil attente">Absent</span>' ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>

    <?php if ($enAttente): ?>
        <section class="carte-compte mt-3">
            <h2 class="carte-compte__titre"><?= iconeDessin('clochette') ?> Liste d'attente</h2>
            <ol class="liste-inscrits">
                <?php foreach ($enAttente as $p): ?>
                    <li>
                        <span class="liste-inscrits__nom"><?= htmlspecialchars(nomParticipant($p)) ?></span>
                        <small>depuis le <?= date('d/m/Y', strtotime($p['date_inscription'])) ?></small>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    <?php endif; ?>

    <p class="small text-muted mt-3 mb-0"><i class="bi bi-shield-lock" aria-hidden="true"></i> Pour protéger les lecteurs, seuls leur prénom et l'initiale de leur nom sont affichés. L'accueil le jour J et les contacts restent gérés par l'équipe BibliOccaz.</p>
</div>

<?php include 'footer.php'; ?>
