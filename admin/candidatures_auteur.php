<?php
require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../auteurs_lib.php';
exigerDroit('candidatures_auteur');

$disponible = candidaturesAuteurDisponibles($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $disponible) {
    exigerPost('candidatures_auteur.php');
    $action = $_POST['action'] ?? '';

    if ($action === 'decision') {
        $idCandidature = (int) ($_POST['id_candidature'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $messageDecision = mb_substr(trim(str_replace("\r\n", "\n", (string) ($_POST['message'] ?? ''))), 0, 1000);
        $prevenir = !empty($_POST['prevenir']);
        try {
            $resultat = deciderCandidatureAuteur($pdo, $idCandidature, (int) $_SESSION['user_id'], $decision, $messageDecision);
        } catch (DomainException $e) {
            rediriger('candidatures_auteur.php#candidature-' . $idCandidature, 'warning', $e->getMessage());
        }
        $candidat = $resultat['candidat'];
        $candidature = $resultat['candidature'];
        $acceptee = $candidature['statut'] === 'acceptee';
        insertLog('ADMIN', $acceptee
            ? "Candidature auteur acceptée : @{$candidat['pseudo']} devient auteur (« {$candidature['nom_plume']} »)"
            : "Candidature auteur de @{$candidat['pseudo']} refusée, compte lecteur conservé");

        $mailEnvoye = false;
        if ($prevenir) {
            require_once __DIR__ . '/../mailer.php';
            [$sujet, $texte, $lien, $libelle] = texteMailDecisionAuteur($candidat, $candidature, $messageDecision);
            try {
                $mail = creerMailer();
                $mail->addAddress($candidat['email'], $candidat['prenom']);
                habillerMail($mail, $sujet, $texte, $lien, $libelle);
                $mail->send();
                $mailEnvoye = true;
            } catch (Throwable $e) {
                insertLog('ERREUR', "Mail de décision de candidature auteur non envoyé à @{$candidat['pseudo']} : " . $e->getMessage());
            }
        }
        $texte = $acceptee
            ? "@{$candidat['pseudo']} est maintenant auteur : sa page « {$candidature['nom_plume']} » est publiée."
            : "Candidature de @{$candidat['pseudo']} refusée : son compte lecteur reste actif.";
        if ($prevenir) {
            $texte .= $mailEnvoye ? ' Le mail de réponse est parti.' : " Le mail n'a pas pu partir.";
        }
        rediriger('candidatures_auteur.php', $prevenir && !$mailEnvoye ? 'warning' : 'success', $texte);
    }

    if (in_array($action, ['masquer', 'afficher'], true)) {
        $idAuteur = (int) ($_POST['id_user'] ?? 0);
        $fiche = ficheAuteurDe($pdo, $idAuteur);
        if (!$fiche) {
            rediriger('candidatures_auteur.php#pages', 'warning', 'Page auteur introuvable.');
        }
        $change = masquerFicheAuteur($pdo, $idAuteur, (int) $_SESSION['user_id'], $action === 'masquer');
        if ($change) {
            insertLog('ADMIN', ($action === 'masquer' ? 'Page auteur masquée' : 'Page auteur réaffichée') . " : « {$fiche['nom_plume']} » (membre #$idAuteur)");
        }
        rediriger('candidatures_auteur.php#pages', 'success', $action === 'masquer'
            ? "La page « {$fiche['nom_plume']} » est masquée : elle n'est plus visible sur le site."
            : "La page « {$fiche['nom_plume']} » est de nouveau visible.");
    }
    rediriger('candidatures_auteur.php');
}

$enAttente = [];
$historique = [];
$pages = [];
$refusAnterieurs = [];
if ($disponible) {
    $enAttente = $pdo->query("
        SELECT c.*, u.pseudo, u.email, u.nom, u.prenom, u.email_verifie, u.role, u.statut AS statut_compte, u.date_inscription
        FROM candidature_auteur c INNER JOIN user u ON u.id = c.id_user
        WHERE c.statut = 'en_attente'
        ORDER BY c.date_candidature, c.id_candidature")->fetchAll();
    $historique = $pdo->query("
        SELECT c.id_candidature, c.statut, c.nom_plume, c.date_candidature, c.date_decision, c.message_decision, u.pseudo, d.pseudo AS pseudo_decideur
        FROM candidature_auteur c
        INNER JOIN user u ON u.id = c.id_user
        LEFT JOIN user d ON d.id = c.id_decideur
        WHERE c.statut <> 'en_attente'
        ORDER BY c.date_decision DESC, c.id_candidature DESC
        LIMIT 30")->fetchAll();
    $pages = $pdo->query("
        SELECT f.id_user, f.nom_plume, f.slug, f.est_masquee, f.date_masquage, f.date_modification, u.pseudo, u.role, u.statut, m.pseudo AS pseudo_masquage,
               (SELECT COUNT(*) FROM fiche_auteur_ouvrage o WHERE o.id_user = f.id_user) AS nb_ouvrages
        FROM fiche_auteur f
        INNER JOIN user u ON u.id = f.id_user
        LEFT JOIN user m ON m.id = f.id_masquee_par
        ORDER BY f.date_modification DESC")->fetchAll();
    if ($enAttente) {
        $ids = array_column($enAttente, 'id_user');
        $stmt = $pdo->prepare("SELECT id_user, COUNT(*) AS nb, MAX(date_decision) AS dernier FROM candidature_auteur
            WHERE statut = 'refusee' AND id_user IN (" . implode(', ', array_fill(0, count($ids), '?')) . ") GROUP BY id_user");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $ligne) {
            $refusAnterieurs[(int) $ligne['id_user']] = $ligne;
        }
    }
}

$onglets = [];
if (aLeDroit('admin')) {
    $onglets['candidatures.php'] = 'Modération';
}
$onglets['candidatures_auteur.php'] = 'Auteurs';

$titrePage = 'Candidatures auteur';
$pageActive = 'candidatures_auteur';
$sousTitrePage = "Les lecteurs qui souhaitent présenter leurs livres et devenir auteurs.";
include __DIR__ . '/_haut.php';
?>

<?php if (count($onglets) > 1): ?>
    <ul class="nav nav-pills mb-3">
        <?php foreach ($onglets as $lienOnglet => $libelleOnglet): ?>
            <?php $actif = $lienOnglet === 'candidatures_auteur.php'; ?>
            <li class="nav-item">
                <a class="nav-link <?= $actif ? 'active bg-success' : 'text-success' ?>" href="<?= $lienOnglet ?>" <?= $actif ? 'aria-current="page"' : '' ?>><?= $libelleOnglet ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if (!$disponible): ?>
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Importez <code>sql/maj_candidatures_auteur.sql</code> pour activer les candidatures et les pages auteur.</div>
<?php else: ?>

    <h2 class="h5 fw-bold mb-3">En attente <span class="badge rounded-pill bg-success-subtle text-success-emphasis"><?= count($enAttente) ?></span></h2>
    <?php if (!$enAttente): ?>
        <div class="carte p-5 text-center mb-4">
            <i class="bi bi-check2-circle fs-1 text-success" aria-hidden="true"></i>
            <p class="text-muted mb-0 mt-2">Aucune candidature auteur en attente.</p>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-column gap-3 mb-4">
        <?php foreach ($enAttente as $c): ?>
            <?php
            $idCandidature = (int) $c['id_candidature'];
            $ouvrages = ouvragesCandidature($c);
            $photoCandidat = $c['photo'] && is_file(__DIR__ . '/../img/' . AUTEUR_DOSSIER_PHOTOS . $c['photo']) ? imageLivre(AUTEUR_DOSSIER_PHOTOS . $c['photo']) : null;
            $acceptable = (int) $c['email_verifie'] === 1 && $c['role'] === 'client' && $c['statut_compte'] === 'actif';
            $refus = $refusAnterieurs[(int) $c['id_user']] ?? null;
            ?>
            <article class="carte p-4 candidature-auteur" id="candidature-<?= $idCandidature ?>" aria-labelledby="titre-candidature-<?= $idCandidature ?>">
                <div class="candidature-auteur__entete">
                    <div class="candidature-auteur__portrait" aria-hidden="true">
                        <?php if ($photoCandidat): ?>
                            <img src="<?= e($photoCandidat) ?>" alt="">
                        <?php else: ?>
                            <?= e(mb_strtoupper(mb_substr($c['nom_plume'], 0, 1))) ?>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h3 class="h5 fw-bold mb-1" id="titre-candidature-<?= $idCandidature ?>"><?= e($c['nom_plume']) ?></h3>
                        <p class="small text-muted mb-1"><?= e(trim($c['prenom'] . ' ' . $c['nom'])) ?> · @<?= e($c['pseudo']) ?> · <?= e($c['email']) ?></p>
                        <div class="d-flex flex-wrap gap-1">
                            <?php if ($c['email_verifie']): ?>
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis"><i class="bi bi-envelope-check" aria-hidden="true"></i> Email confirmé</span>
                            <?php else: ?>
                                <span class="badge rounded-pill bg-light text-dark border"><i class="bi bi-envelope-exclamation" aria-hidden="true"></i> Email pas encore confirmé</span>
                            <?php endif; ?>
                            <span class="badge rounded-pill bg-light text-dark border">Reçue le <?= date('d/m/Y à H:i', strtotime($c['date_candidature'])) ?></span>
                            <?php if ($refus): ?>
                                <span class="badge rounded-pill bg-light text-dark border"><i class="bi bi-arrow-repeat" aria-hidden="true"></i> <?= (int) $refus['nb'] ?> refus avant, le dernier le <?= date('d/m/Y', strtotime($refus['dernier'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-lg-7">
                        <h4 class="small fw-bold text-uppercase text-muted mb-2">Biographie</h4>
                        <blockquote class="candidature-motivations mb-3"><?= nl2br(e($c['biographie'])) ?></blockquote>
                        <h4 class="small fw-bold text-uppercase text-muted mb-2">Motivation</h4>
                        <blockquote class="candidature-motivations mb-3"><?= nl2br(e($c['motivation'])) ?></blockquote>
                    </div>
                    <div class="col-lg-5">
                        <h4 class="small fw-bold text-uppercase text-muted mb-2">Ouvrages (<?= count($ouvrages) ?>)</h4>
                        <ul class="liste-ouvrages-admin mb-3">
                            <?php foreach ($ouvrages as $ouvrage): ?>
                                <li>
                                    <strong><?= e($ouvrage['titre']) ?></strong>
                                    <span class="small text-muted d-block"><?= e(implode(' · ', array_filter([$ouvrage['editeur'] ?? null, isset($ouvrage['annee']) ? (string) $ouvrage['annee'] : null, !empty($ouvrage['isbn']) ? 'ISBN ' . $ouvrage['isbn'] : null]))) ?: '—' ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <h4 class="small fw-bold text-uppercase text-muted mb-2">Liens</h4>
                        <?php $liensCandidat = array_filter(array_intersect_key($c, AUTEUR_LIENS)); ?>
                        <?php if ($liensCandidat): ?>
                            <ul class="list-unstyled small mb-0">
                                <?php foreach ($liensCandidat as $champLien => $adresse): ?>
                                    <li class="text-break"><span class="text-muted"><?= e(AUTEUR_LIENS[$champLien]) ?> :</span> <a href="<?= e($adresse) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e($adresse) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="small text-muted mb-0">Aucun lien indiqué.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <form method="POST" class="candidature-auteur__decision">
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="decision">
                    <input type="hidden" name="id_candidature" value="<?= $idCandidature ?>">
                    <label for="message-<?= $idCandidature ?>" class="form-label small fw-bold">Message au candidat <span class="fw-normal text-muted">(obligatoire pour un refus)</span></label>
                    <textarea id="message-<?= $idCandidature ?>" name="message" class="form-control form-control-sm mb-2" rows="2" maxlength="1000" placeholder="Ajouté au mail de réponse"></textarea>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="prevenir" value="1" id="prevenir-<?= $idCandidature ?>" checked>
                        <label class="form-check-label small" for="prevenir-<?= $idCandidature ?>">Prévenir le candidat par mail</label>
                    </div>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <button name="decision" value="accepter" class="btn btn-success fw-bold" <?= $acceptable ? '' : 'disabled aria-describedby="bloque-' . $idCandidature . '"' ?>><i class="bi bi-check-lg" aria-hidden="true"></i> Accepter et publier la page</button>
                        <button name="decision" value="refuser" class="btn btn-outline-danger"><i class="bi bi-x-lg" aria-hidden="true"></i> Refuser</button>
                    </div>
                    <?php if (!$acceptable): ?>
                        <p class="small text-muted mt-2 mb-0" id="bloque-<?= $idCandidature ?>"><?= !(int) $c['email_verifie'] ? "L'acceptation sera possible quand le candidat aura confirmé son adresse email." : "Ce compte n'est plus un compte lecteur actif : seule la décision de refus est possible." ?></p>
                    <?php else: ?>
                        <p class="small text-muted mt-2 mb-0">Accepter passe le compte en auteur et ferme ses sessions : il se reconnecte pour accéder à l'espace créateur. Refuser garde le compte lecteur ; une nouvelle demande sera possible dans <?= AUTEUR_DELAI_NOUVELLE_DEMANDE_JOURS ?> jours.</p>
                    <?php endif; ?>
                </form>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="carte mb-4" id="pages">
        <div class="carte-entete">Pages auteur publiées</div>
        <?php if (!$pages): ?>
            <p class="text-muted p-3 mb-0">Aucune page auteur pour le moment.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-cartes">
                    <thead class="table-light small text-uppercase">
                        <tr><th>Auteur</th><th>Dernière modification</th><th>État</th><th class="text-end">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $p): ?>
                            <?php $visible = !$p['est_masquee'] && $p['role'] === 'auteur' && !in_array($p['statut'], STATUTS_COMPTE_SUSPENDU, true); ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= e($p['nom_plume']) ?></div>
                                    <div class="small text-muted">@<?= e($p['pseudo']) ?> · <?= (int) $p['nb_ouvrages'] ?> ouvrage<?= $p['nb_ouvrages'] > 1 ? 's' : '' ?></div>
                                </td>
                                <td class="small" data-libelle="Dernière modification"><?= date('d/m/Y à H:i', strtotime($p['date_modification'])) ?></td>
                                <td class="small" data-libelle="État">
                                    <?php if ($p['est_masquee']): ?>
                                        <span class="badge rounded-pill bg-secondary">Masquée</span>
                                        <div class="text-muted">par @<?= e($p['pseudo_masquage'] ?? '—') ?> le <?= date('d/m/Y', strtotime($p['date_masquage'])) ?></div>
                                    <?php elseif ($visible): ?>
                                        <span class="badge rounded-pill bg-success">En ligne</span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill bg-light text-dark border">Hors ligne</span>
                                        <div class="text-muted"><?= $p['role'] !== 'auteur' ? 'Le compte n\'est plus auteur' : 'Compte suspendu' ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end cellule-actions">
                                    <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
                                        <?php if ($visible): ?>
                                            <a href="../auteur.php?a=<?= rawurlencode($p['slug']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">Voir<span class="visually-hidden"> la page de <?= e($p['nom_plume']) ?></span></a>
                                        <?php endif; ?>
                                        <?php if ($p['est_masquee']): ?>
                                            <form method="POST" class="d-inline">
                                                <?= champCsrf() ?>
                                                <input type="hidden" name="action" value="afficher">
                                                <input type="hidden" name="id_user" value="<?= (int) $p['id_user'] ?>">
                                                <button class="btn btn-sm btn-outline-success"><i class="bi bi-eye" aria-hidden="true"></i> Réafficher<span class="visually-hidden"> la page de <?= e($p['nom_plume']) ?></span></button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline" data-confirmer="Masquer la page « <?= e($p['nom_plume']) ?> » ? Elle ne sera plus visible sur le site, et l'auteur pourra toujours la modifier." data-titre-confirmer="Masquer la page" data-bouton-confirmer="Masquer">
                                                <?= champCsrf() ?>
                                                <input type="hidden" name="action" value="masquer">
                                                <input type="hidden" name="id_user" value="<?= (int) $p['id_user'] ?>">
                                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-eye-slash" aria-hidden="true"></i> Masquer<span class="visually-hidden"> la page de <?= e($p['nom_plume']) ?></span></button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="carte">
        <div class="carte-entete">Décisions récentes</div>
        <?php if (!$historique): ?>
            <p class="text-muted p-3 mb-0">Aucune décision pour le moment.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0 table-cartes">
                    <thead class="table-light small text-uppercase">
                        <tr><th>Candidat</th><th>Décision</th><th>Par</th><th>Message envoyé</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historique as $h): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= e($h['nom_plume']) ?></div>
                                    <div class="small text-muted">@<?= e($h['pseudo']) ?> · reçue le <?= date('d/m/Y', strtotime($h['date_candidature'])) ?></div>
                                </td>
                                <td data-libelle="Décision">
                                    <span class="badge rounded-pill <?= $h['statut'] === 'acceptee' ? 'bg-success' : 'bg-secondary' ?>"><?= e(STATUTS_CANDIDATURE_AUTEUR[$h['statut']] ?? $h['statut']) ?></span>
                                    <div class="small text-muted"><?= $h['date_decision'] ? date('d/m/Y', strtotime($h['date_decision'])) : '' ?></div>
                                </td>
                                <td class="small" data-libelle="Par"><?= $h['pseudo_decideur'] ? '@' . e($h['pseudo_decideur']) : '—' ?></td>
                                <td class="small text-muted cellule-actions" data-libelle="Message envoyé"><?= $h['message_decision'] ? nl2br(e($h['message_decision'])) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/_bas.php'; ?>
