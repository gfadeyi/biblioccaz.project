<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../emprunts_lib.php';
exigerDroit('catalogue');

$onglets = [
    'a_preparer' => ['À préparer et retirer', "em.statut = 'reserve'"],
    'en_cours'   => ['En cours', "em.statut = 'en_cours'"],
    'historique' => ['Historique', "em.statut IN ('rendu', 'expire', 'annule')"],
    'blocages'   => ['Blocages', ''],
];
$onglet = isset($onglets[$_GET['onglet'] ?? '']) ? $_GET['onglet'] : 'a_preparer';
$q = trim($_GET['q'] ?? '');
$idFiltre = (int) ($_GET['id'] ?? 0);
$codeCherche = normaliserCodeRetrait((string) ($_GET['code'] ?? ''));
$retour = 'emprunts.php?onglet=' . $onglet . ($q !== '' ? '&q=' . urlencode($q) : '');
$idEquipe = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigerPost($retour);
    $idEmprunt = (int) ($_POST['id_emprunt'] ?? 0);
    $motif = (string) ($_POST['motif'] ?? '');
    try {
        [$type, $message] = match ($_POST['action'] ?? '') {
            'retrait' => array_slice(validerRetrait($pdo, $idEmprunt, $idEquipe), 0, 2),
            'retour' => enregistrerRetour($pdo, $idEmprunt, $idEquipe),
            'annuler' => array_slice(annulerReservation($pdo, $idEmprunt, null, $idEquipe, $motif), 0, 2),
            'lever' => leverBlocage($pdo, (int) ($_POST['id_blocage'] ?? 0), $idEquipe, $motif),
            default => ['danger', "Action inconnue."],
        };
    } catch (PDOException $e) {
        [$type, $message] = ['danger', "L'emprunt n'est pas encore installé : importez sql/maj_emprunts.sql."];
    }
    $destination = ($_POST['action'] ?? '') === 'lever' ? 'emprunts.php?onglet=blocages' : ($idEmprunt ? "emprunts.php?id=$idEmprunt" : $retour);
    rediriger($destination, $type, $message);
}

$tablePresente = true;
$emprunts = $blocages = [];
$empruntCode = null;
$compteursEmprunts = ['a_preparer' => 0, 'en_cours' => 0, 'en_retard' => 0, 'blocages' => 0];
$nombres = array_fill_keys(array_keys($onglets), 0);
try {
    verifierEmprunts($pdo);
    $compteursEmprunts = compteursEmprunts($pdo);
    $nombres = ['a_preparer' => $compteursEmprunts['a_preparer'], 'en_cours' => $compteursEmprunts['en_cours'], 'blocages' => $compteursEmprunts['blocages'],
                'historique' => (int) $pdo->query("SELECT COUNT(*) FROM emprunt WHERE statut IN ('rendu', 'expire', 'annule')")->fetchColumn()];
    if ($codeCherche !== '') {
        $empruntCode = empruntParCode($pdo, $codeCherche);
    }
    $champsConversation = ",
        (SELECT MAX(c.id_conversation) FROM conversation c WHERE c.type_objet = 'emprunt' AND c.id_objet = em.id_emprunt) AS id_conversation,
        (SELECT m.id_auteur = em.id_user FROM conversation c2 INNER JOIN conversation_message m ON m.id_conversation = c2.id_conversation
         WHERE c2.type_objet = 'emprunt' AND c2.id_objet = em.id_emprunt AND c2.statut = 'ouverte' AND m.est_systeme = 0
         ORDER BY m.date_envoi DESC, m.id_message DESC LIMIT 1) AS question_lecteur";
    if ($onglet === 'blocages' && !$idFiltre) {
        $sql = "SELECT b.*, u.pseudo, lp.pseudo AS leve_par, (b.date_levee IS NULL AND b.date_fin > NOW()) AS actif
                FROM blocage_emprunt b INNER JOIN `user` u ON u.id = b.id_user LEFT JOIN `user` lp ON lp.id = b.id_leve_par";
        $parametres = [];
        if ($q !== '') {
            $sql .= " WHERE u.pseudo LIKE ?";
            $parametres[] = '%' . $q . '%';
        }
        $stmt = $pdo->prepare($sql . " ORDER BY actif DESC, b.date_debut DESC LIMIT 100");
        $stmt->execute($parametres);
        $blocages = $stmt->fetchAll();
    } else {
        $requete = str_replace('SELECT em.*,', 'SELECT em.*' . $champsConversation . ',', requeteEmprunt());
        $sql = $requete . " WHERE (" . ($idFiltre ? 'em.id_emprunt = ?' : $onglets[$onglet][1]) . ")";
        $parametres = $idFiltre ? [$idFiltre] : [];
        if ($q !== '') {
            $sql .= " AND (l.titre LIKE ? OR u.pseudo LIKE ? OR em.code_retrait = ?)";
            array_push($parametres, '%' . $q . '%', '%' . $q . '%', strtoupper($q));
        }
        $ordre = match ($onglet) {
            'a_preparer' => 'em.date_expiration_reservation, em.date_reservation',
            'en_cours' => 'em.date_retour_prevue, em.date_retrait',
            default => 'COALESCE(em.date_fin, em.date_reservation) DESC',
        };
        $stmt = $pdo->prepare($sql . " ORDER BY $ordre LIMIT 100");
        $stmt->execute($parametres);
        $emprunts = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $tablePresente = false;
}

$titrePage = 'Emprunts';
$pageActive = 'emprunts';
$sousTitrePage = "Réservations à préparer, retraits au comptoir, emprunts en cours (retards en tête), retours et blocages.";
include __DIR__ . '/_haut.php';

function carteEmpruntAdmin(array $em, bool $resultatCode = false): void
{
    $id = (int) $em['id_emprunt'];
    $identifiantCarte = ($resultatCode ? 'resultat-' : '') . $id;
    $retard = empruntEnRetard($em);
    $joursRetard = joursDeRetard($em);
    $reservationExpiree = $em['statut'] === 'reserve' && $em['date_expiration_reservation'] < date('Y-m-d H:i:s');
    ?>
    <div class="col-xl-6">
        <article class="carte p-3 h-100 d-flex flex-column emprunt-admin<?= $retard ? ' emprunt-admin--retard' : '' ?>" id="emprunt-<?= $identifiantCarte ?>">
            <div class="d-flex gap-3">
                <img src="<?= imageLivre($em['couverture']) ?>" alt="" class="emprunt-admin__visuel" loading="lazy">
                <div class="min-w-0 flex-grow-1">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div class="min-w-0">
                            <a href="exemplaires.php?id=<?= (int) $em['id_livre'] ?>#emprunt" class="fw-bold text-decoration-none"><?= e($em['titre']) ?></a>
                            <div class="small text-muted">Emprunt n° <?= $id ?> · exemplaire n° <?= (int) $em['id_exemplaire'] ?> (<?= e(libelleEtat($em['etat'])) ?>)</div>
                        </div>
                        <span class="emprunt-admin__code" title="Code de retrait"><?= e($em['code_retrait']) ?></span>
                    </div>
                    <div class="small mt-1">
                        Lecteur <strong>@<?= e($em['pseudo']) ?></strong>
                        <?= in_array($em['membre_statut'], STATUTS_COMPTE_SUSPENDU, true) ? ' <span class="text-danger">(suspendu)</span>' : '' ?>
                    </div>
                    <div class="small text-muted">
                        <?php if ($em['statut'] === 'reserve'): ?>
                            Réservé le <?= date('d/m/Y H:i', strtotime($em['date_reservation'])) ?> · à retirer avant le <?= date('d/m/Y', strtotime($em['date_expiration_reservation'])) ?>
                        <?php elseif ($em['statut'] === 'en_cours'): ?>
                            Retiré le <?= date('d/m/Y', strtotime($em['date_retrait'])) ?><?= $em['valide_par_pseudo'] ? ' (@' . e($em['valide_par_pseudo']) . ')' : '' ?> · retour prévu le <strong><?= date('d/m/Y', strtotime($em['date_retour_prevue'])) ?></strong>
                            · renouvelé <?= (int) $em['nb_renouvellements'] ?>/<?= (int) $em['renouvellements_max'] ?><?= (int) $em['nb_relances'] ? ' · ' . (int) $em['nb_relances'] . ' relance' . ($em['nb_relances'] > 1 ? 's' : '') : '' ?>
                        <?php elseif ($em['statut'] === 'rendu'): ?>
                            Retiré le <?= date('d/m/Y', strtotime($em['date_retrait'])) ?> · rendu le <?= date('d/m/Y', strtotime($em['date_retour'])) ?><?= $em['retour_par_pseudo'] ? ' (@' . e($em['retour_par_pseudo']) . ')' : '' ?>
                        <?php else: ?>
                            Réservé le <?= date('d/m/Y', strtotime($em['date_reservation'])) ?><?= $em['motif_annulation'] ? ' · ' . e($em['motif_annulation']) : '' ?>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <span class="badge rounded-pill <?= in_array($em['statut'], ['reserve', 'en_cours'], true) ? 'bg-success-subtle text-success-emphasis' : 'bg-light text-dark border' ?>"><?= e(STATUTS_EMPRUNT[$em['statut']][0] ?? $em['statut']) ?></span>
                        <?php if ($retard): ?><span class="badge rounded-pill bg-danger-subtle text-danger-emphasis"><i class="bi bi-alarm" aria-hidden="true"></i> <?= $joursRetard ?> jour<?= $joursRetard > 1 ? 's' : '' ?> de retard</span><?php endif; ?>
                        <?php if ($em['statut'] === 'rendu' && $joursRetard > 0): ?><span class="badge rounded-pill bg-danger-subtle text-danger-emphasis">Rendu avec <?= $joursRetard ?> j de retard</span><?php endif; ?>
                        <?php if ($reservationExpiree): ?><span class="badge rounded-pill bg-light text-dark border">Délai de retrait dépassé</span><?php endif; ?>
                        <?php if (!empty($em['question_lecteur'])): ?><span class="badge rounded-pill bg-success-subtle text-success-emphasis"><i class="bi bi-chat-dots" aria-hidden="true"></i> Question du lecteur</span><?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2 mt-3 pt-2 mt-auto align-items-start">
                <?php if ($em['statut'] === 'reserve' && !$reservationExpiree): ?>
                    <form method="POST">
                        <?= champCsrf() ?>
                        <input type="hidden" name="action" value="retrait">
                        <input type="hidden" name="id_emprunt" value="<?= $id ?>">
                        <button class="btn btn-sm btn-success fw-bold"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Valider le retrait</button>
                    </form>
                <?php endif; ?>
                <?php if ($em['statut'] === 'en_cours'): ?>
                    <form method="POST" data-confirmer="Enregistrer le retour de « <?= e($em['titre']) ?> » par @<?= e($em['pseudo']) ?> ?<?= $retard ? ' Retard de ' . $joursRetard . ' jour' . ($joursRetard > 1 ? 's' : '') . ((int) $em['blocage_retard'] ? ' : les emprunts du lecteur seront bloqués ' . min($joursRetard, BLOCAGE_MAX_JOURS) . ' jour' . (min($joursRetard, BLOCAGE_MAX_JOURS) > 1 ? 's' : '') . '.' : '.') : ' Rendu à l\'heure : +' . POINTS_EMPRUNT . ' points.' ?>" data-bouton-confirmer="Enregistrer le retour">
                        <?= champCsrf() ?>
                        <input type="hidden" name="action" value="retour">
                        <input type="hidden" name="id_emprunt" value="<?= $id ?>">
                        <button class="btn btn-sm btn-success fw-bold"><i class="bi bi-box-arrow-in-down-left" aria-hidden="true"></i> Livre rendu</button>
                    </form>
                <?php endif; ?>
                <?php if (in_array($em['statut'], ['reserve', 'en_cours', 'rendu'], true)): ?>
                    <a href="emprunt_ticket.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Ticket</a>
                <?php endif; ?>
                <?php if (!empty($em['id_conversation'])): ?>
                    <a href="conversation.php?id=<?= (int) $em['id_conversation'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat-dots" aria-hidden="true"></i> Conversation</a>
                <?php endif; ?>
                <?php if ($em['statut'] === 'reserve'): ?>
                    <details class="annonce-admin__motif">
                        <summary class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle" aria-hidden="true"></i> Annuler</summary>
                        <form method="POST" class="mt-2">
                            <?= champCsrf() ?>
                            <input type="hidden" name="action" value="annuler">
                            <input type="hidden" name="id_emprunt" value="<?= $id ?>">
                            <label class="form-label small" for="motif-<?= $identifiantCarte ?>">Motif envoyé au lecteur</label>
                            <textarea id="motif-<?= $identifiantCarte ?>" name="motif" class="form-control form-control-sm mb-2" rows="2" maxlength="500" required placeholder="Ex. : exemplaire abîmé, introuvable en rayon…"></textarea>
                            <button class="btn btn-sm btn-danger">Annuler la réservation</button>
                        </form>
                    </details>
                <?php endif; ?>
            </div>
        </article>
    </div>
    <?php
}
?>

<?php if (!$tablePresente): ?>
    <div class="alert alert-warning" role="alert">L'emprunt n'est pas encore installé : importez <code>sql/maj_emprunts.sql</code>.</div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <form method="GET" class="carte p-3 h-100">
                <label class="fw-bold small d-block mb-2" for="code"><i class="bi bi-qr-code-scan" aria-hidden="true"></i> Retrait ou retour au comptoir</label>
                <div class="d-flex gap-2">
                    <input type="text" id="code" name="code" value="<?= e($codeCherche) ?>" class="form-control text-uppercase" maxlength="80" autocomplete="off" placeholder="Code à 6 caractères" style="letter-spacing: .15em;" required>
                    <button class="btn btn-success"><i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Chercher</span></button>
                </div>
                <p class="small text-muted mb-0 mt-2">Ou scannez le QR code du lecteur avec un téléphone connecté au back-office.</p>
            </form>
        </div>
        <div class="col-lg-7">
            <div class="row g-2 h-100">
                <?php foreach ([
                    ['À retirer', $compteursEmprunts['a_preparer'], 'bookmark-check', 'a_preparer', false],
                    ['En cours', $compteursEmprunts['en_cours'], 'book', 'en_cours', false],
                    ['En retard', $compteursEmprunts['en_retard'], 'alarm', 'en_cours', $compteursEmprunts['en_retard'] > 0],
                    ['Blocages actifs', $compteursEmprunts['blocages'], 'slash-circle', 'blocages', false],
                ] as [$libelle, $nombre, $icone, $cible, $alerte]): ?>
                    <div class="col-6 col-md-3">
                        <a href="?onglet=<?= $cible ?>" class="carte p-3 h-100 d-block text-decoration-none chiffre-emprunt<?= $alerte ? ' chiffre-emprunt--alerte' : '' ?>">
                            <i class="bi bi-<?= $icone ?>" aria-hidden="true"></i>
                            <span class="chiffre-emprunt__nombre"><?= (int) $nombre ?></span>
                            <span class="chiffre-emprunt__libelle"><?= e($libelle) ?></span>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($codeCherche !== ''): ?>
        <?php if ($empruntCode): ?>
            <h2 class="h6 fw-bold mb-2">Résultat pour le code <?= e($codeCherche) ?></h2>
            <div class="row g-3 mb-4"><?php carteEmpruntAdmin($empruntCode, true); ?></div>
        <?php else: ?>
            <div class="alert alert-warning" role="status">Aucun emprunt avec le code « <?= e($codeCherche) ?> ».</div>
        <?php endif; ?>
    <?php endif; ?>

    <ul class="nav nav-pills mb-3 flex-wrap">
        <?php foreach ($onglets as $cle => [$libelle]): ?>
            <li class="nav-item">
                <a class="nav-link <?= $onglet === $cle && !$idFiltre ? 'active bg-success' : 'text-success' ?>" href="?onglet=<?= $cle ?>"<?= $onglet === $cle && !$idFiltre ? ' aria-current="page"' : '' ?>>
                    <?= e($libelle) ?> <span class="badge rounded-pill <?= $onglet === $cle && !$idFiltre ? 'bg-light text-success' : 'bg-success-subtle text-success-emphasis' ?>"><?= $nombres[$cle] ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <form class="d-flex gap-2 mb-3" method="GET">
        <input type="hidden" name="onglet" value="<?= e($onglet) ?>">
        <input type="search" name="q" value="<?= e($q) ?>" class="form-control" aria-label="Rechercher un emprunt" style="max-width: 320px;" placeholder="<?= $onglet === 'blocages' ? 'Pseudo' : 'Titre, pseudo ou code' ?>">
        <button class="btn btn-outline-secondary" aria-label="Rechercher"><i class="bi bi-search" aria-hidden="true"></i></button>
    </form>

    <?php if ($onglet === 'blocages' && !$idFiltre): ?>
        <?php if (!$blocages): ?>
            <div class="carte p-5 text-center">
                <i class="bi bi-check2-circle fs-1 text-success" aria-hidden="true"></i>
                <p class="text-muted mb-0 mt-2">Aucun blocage d'emprunt.</p>
            </div>
        <?php else: ?>
            <div class="carte">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <caption class="visually-hidden">Blocages des emprunts et motifs de levée</caption>
                        <thead class="table-light small text-uppercase"><tr><th scope="col">Lecteur</th><th scope="col">Motif</th><th scope="col">Période</th><th scope="col" class="text-end">Action</th></tr></thead>
                        <tbody>
                            <?php foreach ($blocages as $b): ?>
                                <tr class="<?= $b['actif'] ? '' : 'text-muted' ?>">
                                    <td class="fw-bold">@<?= e($b['pseudo']) ?></td>
                                    <td class="small"><?= e($b['motif']) ?><?= $b['id_emprunt'] ? ' · <a href="?id=' . (int) $b['id_emprunt'] . '">emprunt n° ' . (int) $b['id_emprunt'] . '</a>' : '' ?></td>
                                    <td class="small">
                                        <?= (int) $b['jours'] ?> jour<?= $b['jours'] > 1 ? 's' : '' ?> : du <?= date('d/m/Y', strtotime($b['date_debut'])) ?> au <?= date('d/m/Y H:i', strtotime($b['date_fin'])) ?>
                                        <?php if ($b['date_levee']): ?>
                                            <div class="text-success">Levé le <?= date('d/m/Y', strtotime($b['date_levee'])) ?><?= $b['leve_par'] ? ' par @' . e($b['leve_par']) : '' ?> : <?= e((string) $b['motif_levee']) ?></div>
                                        <?php elseif (!$b['actif']): ?>
                                            <div>Terminé</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($b['actif']): ?>
                                            <details class="annonce-admin__motif text-start d-inline-block">
                                                <summary class="btn btn-sm btn-outline-success"><i class="bi bi-unlock" aria-hidden="true"></i> Lever</summary>
                                                <form method="POST" class="mt-2">
                                                    <?= champCsrf() ?>
                                                    <input type="hidden" name="action" value="lever">
                                                    <input type="hidden" name="id_blocage" value="<?= (int) $b['id_blocage'] ?>">
                                                    <label class="form-label small" for="motif-blocage-<?= (int) $b['id_blocage'] ?>">Motif envoyé au lecteur</label>
                                                    <textarea id="motif-blocage-<?= (int) $b['id_blocage'] ?>" name="motif" class="form-control form-control-sm mb-2" rows="2" maxlength="500" required placeholder="Ex. : retard justifié (hospitalisation)"></textarea>
                                                    <button class="btn btn-sm btn-success">Lever le blocage</button>
                                                </form>
                                            </details>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if (!$emprunts): ?>
            <div class="carte p-5 text-center">
                <i class="bi bi-check2-circle fs-1 text-success" aria-hidden="true"></i>
                <p class="text-muted mb-0 mt-2"><?= $onglet === 'a_preparer' ? 'Aucune réservation à préparer.' : ($onglet === 'en_cours' ? 'Aucun emprunt en cours.' : 'Aucun emprunt dans cette liste.') ?></p>
            </div>
        <?php endif; ?>
        <div class="row g-3">
            <?php foreach ($emprunts as $em): ?>
                <?php carteEmpruntAdmin($em); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/_bas.php'; ?>
