<?php

const VALIDATION_EMAIL_HEURES = 24;

function validationEmailDisponible(PDO $pdo): bool
{
    try { $pdo->prepare('SELECT email_token_hash, email_token_expire FROM user LIMIT 0')->execute(); return true; }
    catch (PDOException $e) { if (in_array((int) ($e->errorInfo[1] ?? 0), [1054, 1146], true)) { return false; } throw $e; }
}

function demanderValidationEmail(PDO $pdo, string $email): ?string
{
    if (!validationEmailDisponible($pdo)) { return null; }
    $jeton = bin2hex(random_bytes(32));
    $q = $pdo->prepare('UPDATE user SET email_token = NULL, email_token_hash = ?, email_token_expire = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE email = ? AND email_verifie = 0 AND (email_token_expire IS NULL OR email_token_expire <= DATE_SUB(DATE_ADD(NOW(), INTERVAL 24 HOUR), INTERVAL 60 SECOND))');
    $q->execute([hash('sha256', $jeton), $email]);
    return $q->rowCount() === 1 ? $jeton : null;
}

function validationEmailValide(PDO $pdo, string $jeton): bool
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $jeton) || !validationEmailDisponible($pdo)) { return false; }
    $q = $pdo->prepare('SELECT 1 FROM user WHERE email_token_hash = ? AND email_token_expire > NOW() AND email_verifie = 0');
    $q->execute([hash('sha256', $jeton)]);
    return (bool) $q->fetchColumn();
}

function confirmerValidationEmail(PDO $pdo, string $jeton): bool
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $jeton) || !validationEmailDisponible($pdo)) { return false; }
    $q = $pdo->prepare('UPDATE user SET email_verifie = 1, est_verifie = 1, email_token = NULL, email_token_hash = NULL, email_token_expire = NULL WHERE email_token_hash = ? AND email_token_expire > NOW() AND email_verifie = 0');
    $q->execute([hash('sha256', $jeton)]);
    return $q->rowCount() === 1;
}
