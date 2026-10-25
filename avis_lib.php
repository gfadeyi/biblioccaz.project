<?php
// Fonctions communes pour les avis et les notes.

const AVIS_POINTS = 5;                 // points fidélité gagnés pour un nouvel avis (clients)
const AVIS_COMMENTAIRE_MIN = 10;
const AVIS_COMMENTAIRE_MAX = 2000;
const AVIS_MOTIFS = [
    'offensant' => 'Contenu offensant',
    'spam'      => 'Spam ou publicité',
    'hors_sujet'=> 'Hors sujet',
    'spoiler'   => 'Révèle l\'intrigue (spoiler)',
    'autre'     => 'Autre',
];

// Note moyenne et nombre d'avis visibles d'un livre
function noteDuLivre(PDO $pdo, int $idLivre): array
{
    $stmt = $pdo->prepare("SELECT AVG(note) AS moyenne, COUNT(*) AS nb FROM avis WHERE id_livre = ? AND statut = 'visible'");
    $stmt->execute([$idLivre]);
    $r = $stmt->fetch();
    return ['moyenne' => $r['nb'] ? round((float) $r['moyenne'], 1) : 0, 'nb' => (int) $r['nb']];
}

// Affichage en étoiles (gère les demi-étoiles)
function etoiles(float $note, string $classe = ''): string
{
    $html = '<span class="etoiles ' . $classe . '" title="' . number_format($note, 1, ',', '') . ' / 5">';
    for ($i = 1; $i <= 5; $i++) {
        if ($note >= $i) {
            $html .= '<i class="bi bi-star-fill"></i>';
        } elseif ($note >= $i - 0.5) {
            $html .= '<i class="bi bi-star-half"></i>';
        } else {
            $html .= '<i class="bi bi-star"></i>';
        }
    }
    return $html . '</span>';
}

// Jeton anti-CSRF : empêche un autre site d'envoyer des formulaires à la place de l'utilisateur
function jetonCsrf(): string
{
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function verifierCsrf(): bool
{
    return !empty($_POST['csrf']) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function etoilesStyle(): string
{
    return '<style>.etoiles{color:#f5a623;letter-spacing:1px;white-space:nowrap}.etoiles .bi-star{color:#d6d6d6}</style>';
}
