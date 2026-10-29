<?php
require_once 'mailer.php';
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'notifications_lib.php';
require_once 'auteurs_lib.php';

use PHPMailer\PHPMailer\Exception;

$disponible = candidaturesAuteurDisponibles($pdo);
$connecte = isset($_SESSION['user_id']);
$membre = null;
$etat = ['etat' => 'ouverte', 'candidature' => null, 'date_possible' => null];

if ($connecte) {
    $stmt = $pdo->prepare('SELECT id, pseudo, prenom, nom, email, role, statut FROM user WHERE id = ?');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $membre = $stmt->fetch();
    if ($disponible && $membre) {
        $etat = etatCandidatureAuteur($pdo, $membre);
    }
    if ($etat['etat'] === 'auteur') {
        header('Location: modifier_fiche_auteur.php');
        exit();
    }
}

$erreurs = [];
$succes = '';
$compte = ['prenom' => '', 'nom' => '', 'pseudo' => '', 'email' => ''];
$valeursAuteur = valeursFormulaireAuteur(null, true);
$ouvragesAuteur = [];
$avecMotivation = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $disponible && $etat['etat'] === 'ouverte') {
    $saisie = saisieFicheAuteur($_POST, true);
    $valeursAuteur = $saisie['valeurs'];
    $ouvragesAuteur = $saisie['ouvrages'];
    $erreurs = $saisie['erreurs'];

    if (!$connecte) {
        foreach (array_keys($compte) as $champ) {
            $compte[$champ] = texteSaisi($_POST, $champ);
        }
        $motDePasse = is_string($_POST['mot_de_passe'] ?? null) ? $_POST['mot_de_passe'] : '';
        $confirmation = is_string($_POST['confirmation'] ?? null) ? $_POST['confirmation'] : '';
        if (in_array('', $compte, true) || $motDePasse === '') {
            array_unshift($erreurs, 'Remplissez tous les champs de votre compte.');
        } elseif (!filter_var($compte['email'], FILTER_VALIDATE_EMAIL)) {
            array_unshift($erreurs, 'L\'adresse email n\'est pas valide.');
        } elseif ($motDePasse !== $confirmation) {
            array_unshift($erreurs, 'Les mots de passe ne correspondent pas.');
        } else {
            $stmt = $pdo->prepare('SELECT id FROM user WHERE pseudo = ? OR email = ?');
            $stmt->execute([$compte['pseudo'], $compte['email']]);
            if ($stmt->fetch()) {
                array_unshift($erreurs, 'Ce pseudo ou cet email est déjà utilisé.');
            }
        }
    }

    if (!verifierCsrf()) {
        $erreurs = ['Votre session a expiré : vérifiez vos informations, ajoutez de nouveau la photo si besoin, puis renvoyez le formulaire.'];
    }

    $photo = null;
    if (!$erreurs) {
        [$photo, $erreurPhoto] = photoAuteurEnvoyee();
        if ($erreurPhoto !== '') {
            $erreurs[] = $erreurPhoto;
        }
    }

    if (!$erreurs) {
        try {
            if ($connecte) {
                deposerCandidatureAuteur($pdo, (int) $membre['id'], $valeursAuteur, $ouvragesAuteur, $photo);
                insertLog('CANDIDATURE', "Nouvelle candidature auteur de @{$membre['pseudo']} (« {$valeursAuteur['nom_plume']} »)");
                notifierParMail($membre['email'], 'Votre candidature auteur est bien reçue',
                    "Bonjour {$membre['prenom']},\n\nMerci ! Votre candidature d'auteur sous le nom « {$valeursAuteur['nom_plume']} » est bien arrivée. L'équipe l'étudie et vous répondra par mail.\n\nEn attendant, votre compte lecteur reste actif.",
                    SITE_URL . '/profil.php', 'Suivre ma demande');
                $succes = "Votre candidature est envoyée. L'équipe l'étudie et vous répondra par mail. En attendant, votre compte lecteur reste actif.";
                $etat = etatCandidatureAuteur($pdo, $membre);
            } else {
                $jeton = bin2hex(random_bytes(32));
                $pdo->beginTransaction();
                try {
                    $pdo->prepare("INSERT INTO user (pseudo, email, mot_de_passe, nom, prenom, role, statut, email_token, email_verifie) VALUES (?, ?, ?, ?, ?, 'client', 'actif', ?, 0)")
                        ->execute([$compte['pseudo'], $compte['email'], password_hash($motDePasse, PASSWORD_DEFAULT), $compte['nom'], $compte['prenom'], $jeton]);
                    $idCandidat = (int) $pdo->lastInsertId();
                    insererCandidatureAuteur($pdo, $idCandidat, $valeursAuteur, $ouvragesAuteur, $photo);
                    $pdo->commit();
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }
                insertLog('CANDIDATURE', "Nouveau compte @{$compte['pseudo']} avec candidature auteur (« {$valeursAuteur['nom_plume']} »)");
                $mail = creerMailer();
                try {
                    $mail->addAddress($compte['email'], $compte['prenom'] . ' ' . $compte['nom']);
                    habillerMail($mail, 'Confirmez votre adresse email',
                        "Bonjour {$compte['prenom']} !\n\nMerci pour votre candidature d'auteur sous le nom « {$valeursAuteur['nom_plume']} ». Pour activer votre compte, confirmez votre adresse email avec le bouton ci-dessous.\n\nVotre compte fonctionne d'abord comme un compte lecteur. L'équipe étudie votre candidature et vous répondra par mail.",
                        SITE_URL . '/verification_mail.php?token=' . $jeton, 'Confirmer mon adresse');
                    $mail->send();
                    $succes = "Votre compte et votre candidature sont enregistrés. Un mail vient de partir à {$compte['email']} : cliquez sur son lien pour confirmer votre adresse. L'équipe étudiera ensuite votre candidature.";
                } catch (Exception $e) {
                    insertLog('ERREUR', 'Mail de validation non envoyé : ' . $mail->ErrorInfo);
                    $succes = "Votre compte et votre candidature sont enregistrés, mais le mail de confirmation n'a pas pu partir. Réessayez plus tard ou contactez-nous.";
                }
            }
        } catch (DomainException $e) {
            $erreurs[] = $e->getMessage();
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
            $erreurs[] = 'Ce pseudo ou cet email est déjà utilisé.';
        }
        if ($erreurs && $photo) {
            effacerPhotoAuteurInutilisee($pdo, $photo);
        }
    }
}

$moisFr = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
$dateFr = fn(string $date) => (int) date('j', strtotime($date)) . ' ' . $moisFr[(int) date('n', strtotime($date)) - 1] . ' ' . date('Y', strtotime($date));
$csrf = jetonCsrf();
$scriptsPage = ['fiche_auteur.js'];
$titreDocument = 'Devenir auteur';
include 'header.php';
?>

<div class="container mt-4 mb-5 page-proposition page-candidature-auteur">
    <?php if ($connecte): ?>
        <nav aria-label="Fil d'Ariane" class="fil-ariane">
            <a href="profil.php">Mon compte</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page">Devenir auteur</span>
        </nav>
    <?php endif; ?>
    <h1 class="titre-page-compte">Devenir auteur sur BIBLIOccaz</h1>
    <p class="text-muted mb-4">Présentez-vous et présentez vos livres. Après validation par l'équipe, votre fiche devient votre page publique d'auteur.</p>

    <?php if ($succes): ?>
        <section class="carte-compte etat-candidature" role="status">
            <h2 class="carte-compte__titre"><i class="bi bi-envelope-check" aria-hidden="true"></i> Candidature envoyée</h2>
            <p class="texte-profil"><?= htmlspecialchars($succes) ?></p>
            <a href="<?= $connecte ? 'profil.php' : 'login.php' ?>" class="bouton bouton-principal bouton-petit"><?= $connecte ? 'Revenir à mon compte' : 'Aller à la connexion' ?></a>
        </section>
    <?php elseif (!$disponible): ?>
        <div class="alert alert-info rounded-3" role="status"><i class="bi bi-info-circle me-1" aria-hidden="true"></i> Les candidatures d'auteur ouvrent bientôt. Revenez dans quelques jours.</div>
    <?php elseif ($etat['etat'] === 'interdite'): ?>
        <div class="alert alert-info rounded-3" role="status"><i class="bi bi-info-circle me-1" aria-hidden="true"></i> Ce compte ne peut pas déposer de candidature d'auteur. Seul un compte lecteur actif peut le faire.</div>
    <?php elseif ($etat['etat'] === 'en_attente'): ?>
        <section class="carte-compte etat-candidature" role="status">
            <h2 class="carte-compte__titre"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Candidature en cours d'étude</h2>
            <p class="texte-profil">Votre candidature sous le nom « <?= htmlspecialchars($etat['candidature']['nom_plume']) ?> », envoyée le <?= $dateFr($etat['candidature']['date_candidature']) ?>, est en cours d'étude. L'équipe vous répondra par mail.</p>
            <p class="texte-profil mb-0">En attendant, votre compte reste un compte lecteur.</p>
        </section>
    <?php elseif ($etat['etat'] === 'refusee_recente'): ?>
        <section class="carte-compte etat-candidature" role="status">
            <h2 class="carte-compte__titre"><i class="bi bi-info-circle" aria-hidden="true"></i> Candidature non retenue</h2>
            <p class="texte-profil">L'équipe n'a pas retenu votre candidature du <?= $dateFr($etat['candidature']['date_candidature']) ?>.</p>
            <?php if ($etat['candidature']['message_decision']): ?>
                <blockquote class="message-equipe"><?= nl2br(htmlspecialchars($etat['candidature']['message_decision'])) ?></blockquote>
            <?php endif; ?>
            <p class="texte-profil mb-0">Vous pourrez déposer une nouvelle candidature à partir du <strong><?= $dateFr($etat['date_possible']) ?> à <?= date('H:i', strtotime($etat['date_possible'])) ?></strong>.</p>
        </section>
    <?php else: ?>
        <?php if ($erreurs): ?>
            <div class="alert alert-danger rounded-3" role="alert" tabindex="-1" id="erreurs-candidature">
                <p class="fw-bold mb-1"><i class="bi bi-exclamation-triangle me-1" aria-hidden="true"></i> Merci de corriger ces points :</p>
                <ul class="mb-0">
                    <?php foreach ($erreurs as $erreurCandidature): ?>
                        <li><?= htmlspecialchars($erreurCandidature) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="grille-proposition">
            <form method="POST" enctype="multipart/form-data" class="carte-compte formulaire-proposition fiche-auteur-form">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <?php if (!$connecte): ?>
                    <fieldset class="fiche-auteur-form__bloc">
                        <legend>Votre compte</legend>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="prenom" class="form-label fw-bold small">Prénom *</label>
                                <input type="text" id="prenom" name="prenom" class="form-control" autocomplete="given-name" value="<?= htmlspecialchars($compte['prenom']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="nom" class="form-label fw-bold small">Nom *</label>
                                <input type="text" id="nom" name="nom" class="form-control" autocomplete="family-name" value="<?= htmlspecialchars($compte['nom']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="pseudo" class="form-label fw-bold small">Pseudo *</label>
                                <input type="text" id="pseudo" name="pseudo" class="form-control" autocomplete="nickname" value="<?= htmlspecialchars($compte['pseudo']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label fw-bold small">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" autocomplete="email" value="<?= htmlspecialchars($compte['email']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="mot-de-passe" class="form-label fw-bold small">Mot de passe *</label>
                                <input type="password" id="mot-de-passe" name="mot_de_passe" class="form-control" autocomplete="new-password" required>
                            </div>
                            <div class="col-md-6">
                                <label for="confirmation" class="form-label fw-bold small">Confirmation *</label>
                                <input type="password" id="confirmation" name="confirmation" class="form-control" autocomplete="new-password" required>
                            </div>
                        </div>
                        <p class="form-text mb-0">Déjà un compte lecteur ? <a href="login.php">Connectez-vous</a> puis candidatez depuis votre compte.</p>
                    </fieldset>
                <?php endif; ?>

                <?php include 'partie_formulaire_auteur.php'; ?>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="bouton bouton-principal"><i class="bi bi-send" aria-hidden="true"></i> Envoyer ma candidature</button>
                    <a href="<?= $connecte ? 'profil.php' : 'inscription.php' ?>" class="bouton bouton-discret">Annuler</a>
                </div>
            </form>

            <aside class="carte-compte etapes-proposition">
                <h2 class="carte-compte__titre"><?= iconeDessin('plume') ?> Comment ça marche ?</h2>
                <ol>
                    <?php if (!$connecte): ?>
                        <li><strong>Vous confirmez votre adresse email</strong> avec le lien reçu par mail.</li>
                    <?php endif; ?>
                    <li><strong>L'équipe étudie votre candidature</strong> et vous répond par mail.</li>
                    <li><strong>En attendant</strong>, votre compte fonctionne comme un compte lecteur.</li>
                    <li><strong>Une fois acceptée</strong>, votre fiche devient votre page publique et vous accédez à l'espace créateur.</li>
                </ol>
                <p class="small text-muted mb-0">Après un refus, une nouvelle demande est possible au bout de <?= AUTEUR_DELAI_NOUVELLE_DEMANDE_JOURS ?> jours.</p>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
