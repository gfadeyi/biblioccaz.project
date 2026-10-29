<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('BIBLIOCCAZ_ADMIN');
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../avis_lib.php';
require_once __DIR__ . '/../upload_lib.php';

const ROLES_EQUIPE = ['admin', 'gestionnaire', 'moderateur'];

const DROITS = [
    'equipe'     => ['admin', 'gestionnaire', 'moderateur'],
    'moderation' => ['admin', 'gestionnaire', 'moderateur'],
    'catalogue'  => ['admin', 'gestionnaire'],
    'evenements' => ['admin', 'gestionnaire'],
    'admin'      => ['admin'],
    'candidatures_auteur' => ['admin'],
];

const ROLES_LIBELLES = [
    'admin'        => 'Administrateur',
    'gestionnaire' => 'Gestionnaire',
    'moderateur'   => 'Modérateur',
    'auteur'       => 'Auteur',
    'client'       => 'Client',
];

const ROLES_ATTRIBUABLES = ['gestionnaire', 'moderateur', 'auteur', 'client'];

function estEquipe(): bool
{
    return in_array($_SESSION['role'] ?? '', ROLES_EQUIPE, true);
}

function aLeDroit(string $droit): bool
{
    return in_array($_SESSION['role'] ?? '', DROITS[$droit] ?? [], true);
}

function exigerDroit(string $droit, bool $json = false): void
{
    if (aLeDroit($droit)) {
        return;
    }
    if ($json) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['erreur' => 'Accès refusé']);
        exit();
    }
    if (!estEquipe()) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $_SESSION['admin_retour'] = basename($_SERVER['SCRIPT_NAME']) . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']);
        }
        header('Location: login.php');
        exit();
    }
    global $pdo;
    http_response_code(403);
    $titrePage = 'Accès refusé';
    $pageActive = '';
    include __DIR__ . '/_haut.php';
    echo '<div class="alert alert-warning"><i class="bi bi-lock me-2"></i>Votre rôle ne donne pas accès à cette page.</div>';
    include __DIR__ . '/_bas.php';
    exit();
}

function flash(string $type, string $texte): void
{
    $_SESSION['flash_admin'] = [$type, $texte];
}

function rediriger(string $url, ?string $type = null, ?string $texte = null): void
{
    if ($type !== null) {
        flash($type, $texte);
    }
    header("Location: $url");
    exit();
}

function exigerPost(string $retour): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifierCsrf()) {
        rediriger($retour, 'danger', 'Session expirée ou requête invalide, merci de réessayer.');
    }
}

function champCsrf(): string
{
    return '<input type="hidden" name="csrf" value="' . jetonCsrf() . '">';
}

function e(?string $texte): string
{
    return htmlspecialchars((string) $texte, ENT_QUOTES);
}

function imageLivre(?string $fichier): string
{
    return urlImage($fichier, '../');
}

function compteursEquipe(PDO $pdo): array
{
    $c = ['livres' => 0, 'avis' => 0, 'candidatures' => 0, 'candidatures_auteur' => 0, 'avis_a_valider' => 0, 'evenements_proposes' => 0, 'annonces' => 0, 'messages' => 0, 'ventes' => 0, 'emprunts' => 0, 'emprunts_a_preparer' => 0, 'emprunts_en_retard' => 0, 'emprunts_questions' => 0];
    try {
        $c['livres'] = (int) $pdo->query("SELECT COUNT(*) FROM livre l WHERE l.is_valide = 0 AND NOT EXISTS (SELECT 1 FROM exemplaire e WHERE e.id_livre = l.id_livre AND e.id_proprietaire IS NOT NULL)")->fetchColumn();
        $c['avis'] = (int) $pdo->query("SELECT COUNT(DISTINCT id_avis) FROM avis_signalement WHERE traite = 0")->fetchColumn();
        $c['candidatures'] = (int) $pdo->query("SELECT COUNT(*) FROM user WHERE statut = 'en_attente_moderateur'")->fetchColumn();
        $c['avis_a_valider'] = (int) $pdo->query("SELECT COUNT(*) FROM avis WHERE statut = 'visible' AND date_validation IS NULL")->fetchColumn();
        $c['evenements_proposes'] = (int) $pdo->query("SELECT COUNT(*) FROM evenement WHERE statut = 'en_attente'")->fetchColumn();
    } catch (PDOException $e) {
    }
    try {
        $c['messages'] = (int) $pdo->query("SELECT COUNT(DISTINCT id_message) FROM message_signalement WHERE traite = 0")->fetchColumn();
    } catch (PDOException $e) {
    }
    try {
        $c['ventes'] = (int) $pdo->query("SELECT COUNT(*) FROM demande_achat WHERE en_litige = 1 OR (statut = 'acceptee' AND date_acceptation < NOW() - INTERVAL 14 DAY)")->fetchColumn();
    } catch (PDOException $e) {
    }
    try {
        $c['emprunts_a_preparer'] = (int) $pdo->query("SELECT COUNT(*) FROM emprunt WHERE statut = 'reserve'")->fetchColumn();
        $c['emprunts_en_retard'] = (int) $pdo->query("SELECT COUNT(*) FROM emprunt WHERE statut = 'en_cours' AND date_retour_prevue < CURDATE()")->fetchColumn();
        $c['emprunts_questions'] = (int) $pdo->query("
            SELECT COUNT(*) FROM conversation c INNER JOIN emprunt em ON em.id_emprunt = c.id_objet
            WHERE c.type_objet = 'emprunt' AND c.statut = 'ouverte'
              AND (SELECT m.id_auteur FROM conversation_message m WHERE m.id_conversation = c.id_conversation AND m.est_systeme = 0
                   ORDER BY m.date_envoi DESC, m.id_message DESC LIMIT 1) = em.id_user")->fetchColumn();
        $c['emprunts'] = $c['emprunts_a_preparer'] + $c['emprunts_en_retard'] + $c['emprunts_questions'];
    } catch (PDOException $e) {
    }
    try {
        $c['candidatures_auteur'] = (int) $pdo->query("SELECT COUNT(*) FROM candidature_auteur WHERE statut = 'en_attente'")->fetchColumn();
    } catch (PDOException $e) {
    }
    try {
        $c['annonces'] = (int) $pdo->query("SELECT COUNT(*) FROM exemplaire WHERE statut_annonce = 'en_validation'")->fetchColumn();
    } catch (PDOException $e) {
    }
    return $c;
}
