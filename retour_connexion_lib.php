<?php
const PAGES_SANS_RETOUR_CONNEXION = [
    'login.php', 'logout.php', 'traitement_login.php', 'inscription.php', 'connexion_google.php', 'liaison_google.php',
    'recuparation_mdp.php', 'modifier_mdp.php', 'confirmer_email.php', 'verification_mail.php', 'renvoyer_validation.php',
    'get_captcha_image.php', 'captcha_piece.php', 'captcha_verifier.php', 'erreur_404.php',
];
const ACTION_APRES_CONNEXION_DUREE = 1800;

function retourConnexionValide(?string $chemin): ?string
{
    $chemin = trim((string) $chemin);
    if ($chemin === '' || strlen($chemin) > 600 || preg_match('/[\x00-\x20\x7F\\\\]/', $chemin)) {
        return null;
    }
    if (!preg_match('~^([A-Za-z0-9_%.\-]+)(\?[^#]*)?(#[A-Za-z0-9_\-]*)?$~', $chemin, $morceaux)) {
        return null;
    }
    $page = rawurldecode($morceaux[1]);
    if (!str_ends_with($page, '.php') || str_contains($page, '/') || str_starts_with($page, '.') || str_ends_with($page, '_action.php')
        || preg_match('/^(secrets.*|config|mailer|header|footer|panier_calcul|partie_.*|.*_lib)\.php$/i', $page)
        || in_array($page, PAGES_SANS_RETOUR_CONNEXION, true) || !is_file(__DIR__ . '/' . $page)) {
        return null;
    }
    return $chemin;
}

function cheminPageCourante(): string
{
    $adresse = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $page = basename((string) parse_url($adresse, PHP_URL_PATH));
    if (!str_ends_with(rawurldecode($page), '.php')) {
        $page = 'index.php';
    }
    $requete = (string) parse_url($adresse, PHP_URL_QUERY);
    return $page . ($requete !== '' ? '?' . $requete : '');
}

function lienConnexion(?string $retour = null): string
{
    $retour = retourConnexionValide($retour ?? cheminPageCourante());
    return 'login.php' . ($retour !== null ? '?retour=' . rawurlencode($retour) : '');
}

function memoriserRetourConnexion(?string $retour): bool
{
    $retour = retourConnexionValide($retour);
    if ($retour === null) {
        return false;
    }
    $_SESSION['retour_apres_connexion'] = $retour;
    return true;
}

function oublierRetourConnexion(): void
{
    unset($_SESSION['retour_apres_connexion'], $_SESSION['action_apres_connexion']);
}

function memoriserFavoriApresConnexion(int $idLivre, ?string $retour): void
{
    if ($idLivre <= 0) {
        return;
    }
    $_SESSION['action_apres_connexion'] = ['type' => 'favori', 'id_livre' => $idLivre, 'expire' => time() + ACTION_APRES_CONNEXION_DUREE];
    memoriserRetourConnexion($retour);
}

function actionEnAttenteConnexion(): ?array
{
    $action = $_SESSION['action_apres_connexion'] ?? null;
    if (!is_array($action) || ($action['expire'] ?? 0) < time()) {
        unset($_SESSION['action_apres_connexion']);
        return null;
    }
    return $action;
}

function titreLivreEnAttente(PDO $pdo): ?string
{
    $action = actionEnAttenteConnexion();
    if (!$action || $action['type'] !== 'favori') {
        return null;
    }
    $requete = $pdo->prepare("SELECT titre FROM livre WHERE id_livre = ? AND is_valide = 1");
    $requete->execute([(int) $action['id_livre']]);
    $titre = $requete->fetchColumn();
    return $titre === false ? null : (string) $titre;
}

function terminerConnexionAvecRetour(PDO $pdo, int $idUser): string
{
    $retour = retourConnexionValide($_SESSION['retour_apres_connexion'] ?? null) ?? 'index.php';
    $action = actionEnAttenteConnexion();
    oublierRetourConnexion();
    if ($action && $action['type'] === 'favori') {
        try {
            $requete = $pdo->prepare("SELECT titre FROM livre WHERE id_livre = ? AND is_valide = 1");
            $requete->execute([(int) $action['id_livre']]);
            $titre = $requete->fetchColumn();
            if ($titre !== false) {
                $pdo->prepare("INSERT IGNORE INTO favori (id_user, id_livre) VALUES (?, ?)")->execute([$idUser, (int) $action['id_livre']]);
                $_SESSION['message_apres_connexion'] = "« $titre » a été ajouté à vos favoris.";
                if (function_exists('oublierRecommandations')) {
                    oublierRecommandations();
                }
            }
        } catch (PDOException $e) {
        }
    }
    return $retour;
}

function messageApresConnexion(): ?string
{
    $message = $_SESSION['message_apres_connexion'] ?? null;
    unset($_SESSION['message_apres_connexion']);
    return is_string($message) ? $message : null;
}
