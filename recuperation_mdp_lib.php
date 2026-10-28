<?php

const RECUPERATION_DELAI_SECONDES = 60;
const RECUPERATION_FENETRE_SECONDES = 900;
const RECUPERATION_DEMANDES_SESSION = 5;

function autoriserDemandeRecuperation(): bool
{
    $maintenant = time();
    $demandes = array_values(array_filter($_SESSION['demandes_recuperation'] ?? [], fn($date) => is_int($date) && $date > $maintenant - RECUPERATION_FENETRE_SECONDES));
    $_SESSION['demandes_recuperation'] = $demandes;
    if (count($demandes) >= RECUPERATION_DEMANDES_SESSION) {
        return false;
    }
    $_SESSION['demandes_recuperation'][] = $maintenant;
    return true;
}

function preparerRecuperation(PDO $pdo, string $email): ?array
{
    $jeton = bin2hex(random_bytes(32));
    $empreinte = hash('sha256', $jeton);
    $stmt = $pdo->prepare("UPDATE user SET reset_token_hash = ?, reset_expire = DATE_ADD(NOW(), INTERVAL 1 HOUR)
        WHERE email = ? AND (reset_expire IS NULL OR reset_expire <= DATE_SUB(DATE_ADD(NOW(), INTERVAL 1 HOUR), INTERVAL " . RECUPERATION_DELAI_SECONDES . " SECOND))");
    $stmt->execute([$empreinte, $email]);
    if ($stmt->rowCount() !== 1) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, prenom, email FROM user WHERE reset_token_hash = ? AND email = ?');
    $stmt->execute([$empreinte, $email]);
    $membre = $stmt->fetch();
    return $membre ? $membre + ['jeton' => $jeton] : null;
}

function consommerJetonRecuperation(PDO $pdo, int $idUser, string $jeton, string $motDePasse): bool
{
    $stmt = $pdo->prepare('UPDATE user SET mot_de_passe = ?, reset_token_hash = NULL, reset_expire = NULL
        WHERE id = ? AND reset_token_hash = ? AND reset_expire > NOW()');
    $stmt->execute([password_hash($motDePasse, PASSWORD_DEFAULT), $idUser, hash('sha256', $jeton)]);
    return $stmt->rowCount() === 1;
}
