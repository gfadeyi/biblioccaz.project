<?php
const STATUTS_CONNEXION_REFUSEE = ['en_attente_moderateur', 'refuse_temporaire', 'refuse_definitif'];

function refusConnexionMembre(array $user): string
{
    if ((int) $user['email_verifie'] === 0) {
        return 'not_verified';
    }
    return in_array($user['statut'], STATUTS_CONNEXION_REFUSEE, true) ? $user['statut'] : '';
}

function ouvrirSessionMembre(PDO $pdo, array $user, string $journal = 'Connexion réussie'): string
{
    $premiereConnexionModo = false;
    if ($user['role'] === 'moderateur' && $user['statut'] === 'valide_moderateur') {
        $pdo->prepare("UPDATE user SET statut = 'actif' WHERE id = ?")->execute([$user['id']]);
        $premiereConnexionModo = true;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['pseudo'] = $user['pseudo'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['nom'] = $user['nom'];
    $_SESSION['prenom'] = $user['prenom'];
    $_SESSION['connexion_time'] = time();
    enregistrerSessionUtilisateur($pdo, $user);

    insertLog('CONNEXION', $journal);

    if ($premiereConnexionModo) {
        $_SESSION['welcome_modo'] = "Bienvenue dans l'équipe de modération ! Votre compte a été validé par l'administrateur. Votre mission sera désormais de modérer nos livres et d'en modifier les descriptifs.";
    }

    $retour = ($_SESSION['retour_apres_connexion'] ?? '') === 'achat.php' ? 'achat.php' : 'index.php';
    unset($_SESSION['retour_apres_connexion']);
    return $retour;
}
