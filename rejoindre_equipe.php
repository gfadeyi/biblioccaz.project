<?php
require_once 'config.php';
require_once 'avis_lib.php';
require_once 'candidatures_equipe_lib.php';

$disponible = candidaturesEquipeDisponibles($pdo);
$membre = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT id, pseudo, prenom, nom, email, role, statut FROM user WHERE id = ?');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $membre = $stmt->fetch() ?: null;
}
$etat = $disponible ? etatCandidatureEquipe($pdo, $membre) : ['etat' => 'indisponible', 'candidature' => null, 'date_possible' => null];

$valeurs = [
    'role_vise' => array_key_exists($_GET['role'] ?? '', ROLES_CANDIDATURE_EQUIPE) ? $_GET['role'] : '',
    'prenom' => $membre['prenom'] ?? '',
    'nom' => $membre['nom'] ?? '',
    'pseudo_souhaite' => $membre['pseudo'] ?? '',
    'email' => $membre['email'] ?? '',
    'motivations' => '',
];
$erreurs = [];
$envoyee = !empty($_SESSION['candidature_equipe_envoyee']);
unset($_SESSION['candidature_equipe_envoyee']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $etat['etat'] === 'ouverte') {
    $saisie = saisieCandidatureEquipe($_POST, $membre);
    $valeurs = $saisie['valeurs'];
    $erreurs = $saisie['erreurs'];
    if (!verifierCsrf()) {
        $erreurs = ['Votre session a expiré : vérifiez vos informations puis renvoyez le formulaire.'];
    }
    if (!$erreurs) {
        try {
            deposerCandidatureEquipe($pdo, $valeurs, $membre);
            insertLog('CANDIDATURE', 'Nouvelle candidature ' . $valeurs['role_vise'] . ' de ' . ($membre ? '@' . $membre['pseudo'] : $valeurs['prenom'] . ' ' . $valeurs['nom']));
            $_SESSION['candidature_equipe_envoyee'] = true;
            header('Location: rejoindre_equipe.php');
            exit();
        } catch (DomainException $e) {
            $erreurs[] = $e->getMessage();
        }
    }
}

$csrf = jetonCsrf();
include 'header.php';
?>

<div class="page-formulaire">
    <div class="carte-formulaire carte-formulaire--large">
        <div class="carte-formulaire__icone"><?= iconeDessin('bulle') ?></div>
        <h1>Rejoindre l'équipe</h1>
        <p class="carte-formulaire__intro">Modération ou gestion : présentez-vous, l'équipe étudie chaque candidature et vous répond par mail.</p>

        <?php if ($envoyee): ?>
            <div class="carte-formulaire__encart" role="status">
                <p class="mb-2"><i class="bi bi-envelope-check" aria-hidden="true"></i> Merci, votre candidature est bien reçue.</p>
                <p class="mb-3">Si elle est acceptée, vous recevrez un mail <?= $membre ? 'qui vous ouvrira l’accès au back-office.' : 'pour choisir le mot de passe de votre nouveau compte.' ?></p>
                <a href="index.php" class="bouton bouton-principal bouton-petit">Revenir à l'accueil</a>
            </div>
        <?php elseif ($etat['etat'] === 'indisponible'): ?>
            <div class="carte-formulaire__encart" role="status"><p class="mb-0">Les candidatures ouvriront très bientôt. Revenez un peu plus tard.</p></div>
        <?php elseif ($etat['etat'] === 'equipe'): ?>
            <div class="carte-formulaire__encart" role="status">
                <p class="mb-3">Vous faites déjà partie de l'équipe.</p>
                <a href="admin/login.php" class="bouton bouton-principal bouton-petit">Accéder au back-office</a>
            </div>
        <?php elseif ($etat['etat'] === 'interdite'): ?>
            <div class="carte-formulaire__encart" role="status"><p class="mb-0">Votre compte ne permet pas de postuler pour le moment.</p></div>
        <?php elseif ($etat['etat'] === 'en_attente'): ?>
            <div class="carte-formulaire__encart" role="status">
                <p class="mb-0">Votre candidature au rôle de <?= htmlspecialchars(mb_strtolower(ROLES_CANDIDATURE_EQUIPE[$etat['candidature']['role_vise']] ?? '')) ?>, envoyée le <?= date('d/m/Y', strtotime($etat['candidature']['date_candidature'])) ?>, est en cours d'étude. L'équipe vous répondra par mail.</p>
            </div>
        <?php elseif ($etat['etat'] === 'refusee_recente'): ?>
            <div class="carte-formulaire__encart" role="status">
                <p class="mb-0">Votre dernière candidature n'a pas été retenue. Vous pourrez en déposer une nouvelle à partir du <strong><?= date('d/m/Y', strtotime($etat['date_possible'])) ?></strong>.</p>
            </div>
        <?php else: ?>
            <?php if ($erreurs): ?>
                <div class="alert alert-danger small" role="alert">
                    <?php foreach ($erreurs as $erreur): ?>
                        <div><?= htmlspecialchars($erreur) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <fieldset class="mb-3">
                    <legend class="form-label">Le rôle qui vous intéresse *</legend>
                    <div class="choix-role-equipe">
                        <?php foreach (ROLES_CANDIDATURE_EQUIPE as $role => $libelle): ?>
                            <label class="choix-role-equipe__option">
                                <input type="radio" name="role_vise" value="<?= $role ?>" <?= $valeurs['role_vise'] === $role ? 'checked' : '' ?> required>
                                <span class="choix-role-equipe__titre"><?= iconeDessin($role === 'moderateur' ? 'tampon' : 'carton') ?> <?= $libelle ?></span>
                                <span class="choix-role-equipe__detail"><?= htmlspecialchars(MISSIONS_CANDIDATURE_EQUIPE[$role]) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="prenom" class="form-label">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" class="form-control" maxlength="100" autocomplete="given-name" value="<?= htmlspecialchars($valeurs['prenom']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="nom" class="form-label">Nom *</label>
                        <input type="text" id="nom" name="nom" class="form-control" maxlength="100" autocomplete="family-name" value="<?= htmlspecialchars($valeurs['nom']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="pseudo-souhaite" class="form-label">Pseudo souhaité *</label>
                        <input type="text" id="pseudo-souhaite" name="pseudo_souhaite" class="form-control" minlength="3" maxlength="50" autocomplete="nickname" value="<?= htmlspecialchars($valeurs['pseudo_souhaite']) ?>" aria-describedby="aide-pseudo-souhaite" required>
                        <div id="aide-pseudo-souhaite" class="form-text">Lettres, chiffres, point, tiret ou tiret bas. S'il est déjà pris, l'équipe vous en proposera un proche.</div>
                    </div>
                    <div class="col-12">
                        <label for="email" class="form-label">Adresse email *</label>
                        <?php if ($membre): ?>
                            <input type="email" id="email" class="form-control" value="<?= htmlspecialchars($membre['email']) ?>" aria-describedby="aide-email" readonly>
                            <div id="aide-email" class="form-text">L'adresse de votre compte : la réponse de l'équipe y sera envoyée.</div>
                        <?php else: ?>
                            <input type="email" id="email" name="email" class="form-control" maxlength="150" autocomplete="email" value="<?= htmlspecialchars($valeurs['email']) ?>" aria-describedby="aide-email" required>
                            <div id="aide-email" class="form-text">Si votre candidature est acceptée, votre compte sera créé avec cette adresse. Vous avez déjà un compte ? <a href="login.php">Connectez-vous</a> pour postuler.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label for="motivations" class="form-label">Motivations et informations *</label>
                        <textarea id="motivations" name="motivations" class="form-control" rows="6" minlength="<?= MOTIVATIONS_EQUIPE_MIN ?>" maxlength="<?= MOTIVATIONS_EQUIPE_MAX ?>" aria-describedby="aide-motivations" required><?= htmlspecialchars($valeurs['motivations']) ?></textarea>
                        <div id="aide-motivations" class="form-text">Pourquoi ce rôle, vos disponibilités, ce que vous aimez lire, une expérience utile…</div>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="bouton bouton-principal">Envoyer ma candidature</button>
                        <p class="small text-muted mt-3 mb-0">Ces informations servent uniquement à étudier votre candidature. Pour en savoir plus et exercer vos droits, consultez la <a href="confidentialite.php">politique de confidentialité</a>.</p>
                    </div>
                </div>
            </form>
        <?php endif; ?>
        <?php if (!$membre): ?>
            <p class="carte-formulaire__pied">Vous cherchez plutôt un compte lecteur ? <a href="inscription.php">Créer un compte</a></p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
