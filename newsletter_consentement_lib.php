<?php
require_once __DIR__ . '/notifications_lib.php';

const NOTICE_NEWSLETTER = 'Je souhaite recevoir, une fois par mois, la newsletter BibliOccaz. Je peux me désabonner à tout moment.';

function consentementNewsletterDisponible(PDO $pdo): bool
{
    try {
        $pdo->prepare('SELECT confirmation_hash, generation FROM newsletter_consentement LIMIT 0')->execute();
        $pdo->prepare('SELECT empreinte FROM newsletter_desinscription LIMIT 0')->execute();
        return true;
    } catch (PDOException $e) {
        if (in_array((int) ($e->errorInfo[1] ?? 0), [1146, 1054], true)) { return false; }
        throw $e;
    }
}

function synchroniserAbonnementNewsletter(PDO $pdo, string $email, bool $actif, string $source): void
{
    $q = $pdo->prepare('SELECT id FROM user WHERE email = ?');
    $q->execute([$email]);
    $id = $q->fetchColumn();
    $pdo->prepare('UPDATE user SET is_newsletter = ?, date_newsletter = NOW() WHERE email = ?')->execute([$actif ? 1 : 0, $email]);
    if ($actif && !$id) {
        $pdo->prepare('INSERT INTO newsletter_abonne (email, actif) VALUES (?, 1) ON DUPLICATE KEY UPDATE actif = 1, date_inscription = NOW(), date_desinscription = NULL')->execute([$email]);
    } else {
        $pdo->prepare('UPDATE newsletter_abonne SET actif = 0, date_desinscription = NOW() WHERE email = ?')->execute([$email]);
    }
    journalNewsletter($pdo, $email, $id ? (int) $id : null, $actif, $source);
}

function enregistrerPreferenceNewsletter(PDO $pdo, string $email, bool $actif, string $source): void
{
    if (!consentementNewsletterDisponible($pdo)) { throw new DomainException('La gestion des abonnements est momentanément indisponible.'); }
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT IGNORE INTO newsletter_consentement (email) VALUES (?)')->execute([$email]);
        $q = $pdo->prepare('SELECT actif FROM newsletter_consentement WHERE email = ? FOR UPDATE');
        $q->execute([$email]);
        $ancien = (bool) $q->fetchColumn();
        if ($actif) {
            $pdo->prepare('UPDATE newsletter_consentement SET actif = 1, confirmation_hash = NULL, confirmation_expire = NULL, date_confirmation = NOW(), date_desinscription = NULL, generation = generation + 1, source = ?, notice = ? WHERE email = ?')->execute([$source, NOTICE_NEWSLETTER, $email]);
        } else {
            $pdo->prepare('UPDATE newsletter_consentement SET actif = 0, confirmation_hash = NULL, confirmation_expire = NULL, date_desinscription = NOW() WHERE email = ?')->execute([$email]);
        }
        if ($actif || $ancien) { synchroniserAbonnementNewsletter($pdo, $email, $actif, $source); }
        else {
            $pdo->prepare('UPDATE user SET is_newsletter = 0, date_newsletter = NOW() WHERE email = ?')->execute([$email]);
            $pdo->prepare('UPDATE newsletter_abonne SET actif = 0, date_desinscription = NOW() WHERE email = ?')->execute([$email]);
        }
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
}

function demanderConfirmationNewsletter(PDO $pdo, string $email): ?string
{
    if (!consentementNewsletterDisponible($pdo)) { throw new DomainException('La newsletter est momentanément indisponible.'); }
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT IGNORE INTO newsletter_consentement (email) VALUES (?)')->execute([$email]);
        $q = $pdo->prepare('SELECT actif, date_demande > DATE_SUB(NOW(), INTERVAL 60 SECOND) AS recente FROM newsletter_consentement WHERE email = ? FOR UPDATE');
        $q->execute([$email]);
        $etat = $q->fetch(PDO::FETCH_ASSOC);
        if ($etat['actif'] || $etat['recente']) { $pdo->commit(); return null; }
        $jeton = bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE newsletter_consentement SET confirmation_hash = ?, confirmation_expire = DATE_ADD(NOW(), INTERVAL 24 HOUR), date_demande = NOW() WHERE email = ?')->execute([hash('sha256', $jeton), $email]);
        $pdo->commit();
        return $jeton;
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
}

function confirmationNewsletterValide(PDO $pdo, string $jeton): bool
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $jeton) || !consentementNewsletterDisponible($pdo)) { return false; }
    $q = $pdo->prepare('SELECT 1 FROM newsletter_consentement WHERE confirmation_hash = ? AND confirmation_expire > NOW() AND actif = 0');
    $q->execute([hash('sha256', $jeton)]);
    return (bool) $q->fetchColumn();
}

function confirmerNewsletter(PDO $pdo, string $jeton): bool
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $jeton) || !consentementNewsletterDisponible($pdo)) { return false; }
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('SELECT email FROM newsletter_consentement WHERE confirmation_hash = ? AND confirmation_expire > NOW() AND actif = 0 FOR UPDATE');
        $q->execute([hash('sha256', $jeton)]);
        $email = $q->fetchColumn();
        if (!$email) { $pdo->commit(); return false; }
        $pdo->prepare("UPDATE newsletter_consentement SET actif = 1, confirmation_hash = NULL, confirmation_expire = NULL, date_confirmation = NOW(), date_desinscription = NULL, generation = generation + 1, source = 'confirmation', notice = ? WHERE email = ?")->execute([NOTICE_NEWSLETTER, $email]);
        synchroniserAbonnementNewsletter($pdo, $email, true, 'confirmation');
        $pdo->commit();
        return true;
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
}

function lienDesinscriptionNewsletter(PDO $pdo, string $email): string
{
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('SELECT generation FROM newsletter_consentement WHERE email = ? AND actif = 1 FOR UPDATE');
        $q->execute([$email]);
        $generation = $q->fetchColumn();
        if ($generation === false) { throw new DomainException('Cet abonnement n’est plus actif.'); }
        $jeton = bin2hex(random_bytes(32));
        $pdo->prepare('INSERT INTO newsletter_desinscription (empreinte, email, generation) VALUES (?, ?, ?)')->execute([hash('sha256', $jeton), $email, $generation]);
        $pdo->commit();
        return rtrim(SITE_URL, '/') . '/newsletter_confirmer.php?mode=desinscription&token=' . $jeton;
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
}

function desinscriptionNewsletterValide(PDO $pdo, string $jeton): bool
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $jeton) || !consentementNewsletterDisponible($pdo)) { return false; }
    $q = $pdo->prepare('SELECT 1 FROM newsletter_desinscription d JOIN newsletter_consentement c ON c.email = d.email AND c.generation = d.generation WHERE d.empreinte = ?');
    $q->execute([hash('sha256', $jeton)]);
    return (bool) $q->fetchColumn();
}

function desinscrireNewsletterParJeton(PDO $pdo, string $jeton): bool
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $jeton) || !consentementNewsletterDisponible($pdo)) { return false; }
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('SELECT c.email, c.actif FROM newsletter_desinscription d JOIN newsletter_consentement c ON c.email = d.email AND c.generation = d.generation WHERE d.empreinte = ? FOR UPDATE');
        $q->execute([hash('sha256', $jeton)]);
        $c = $q->fetch(PDO::FETCH_ASSOC);
        if (!$c) { $pdo->commit(); return false; }
        $pdo->prepare('UPDATE newsletter_consentement SET actif = 0, confirmation_hash = NULL, confirmation_expire = NULL, date_desinscription = COALESCE(date_desinscription, NOW()) WHERE email = ?')->execute([$c['email']]);
        if ($c['actif']) { synchroniserAbonnementNewsletter($pdo, $c['email'], false, 'lien_email'); }
        $pdo->commit();
        return true;
    } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
}

function envoyerEditionNewsletter(PDO $pdo, array $emails, string $sujet, string $texte): array
{
    if (!consentementNewsletterDisponible($pdo)) { throw new DomainException('L’envoi de la newsletter est momentanément indisponible.'); }
    require_once __DIR__ . '/mailer.php';
    $envoyes = 0;
    $echecs = 0;
    foreach (array_unique($emails) as $email) {
        try {
            $lien = lienDesinscriptionNewsletter($pdo, $email);
            $mail = creerMailer();
            $mail->Timeout = 5;
            $mail->addAddress($email);
            $mail->addCustomHeader('List-Unsubscribe', '<' . $lien . '>');
            habillerMail($mail, $sujet, $texte, SITE_URL, 'Découvrir les nouveautés', 'Vous recevez la newsletter BibliOccaz. Désabonnement sans compte : ' . $lien);
            $mail->Body = str_replace('</body>', '<p style="text-align:center"><a href="' . htmlspecialchars($lien, ENT_QUOTES, 'UTF-8') . '">Se désabonner de la newsletter</a></p></body>', $mail->Body);
            $mail->send();
            $envoyes++;
        } catch (Throwable $e) { $echecs++; insertLog('ERREUR', 'Un envoi de newsletter a échoué.'); }
    }
    return ['envoyes' => $envoyes, 'echecs' => $echecs];
}
