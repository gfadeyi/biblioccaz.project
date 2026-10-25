<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/secrets.php';

use PHPMailer\PHPMailer\PHPMailer;

function creerMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host     = SMTP_HOST;
    $mail->Port     = SMTP_PORT;
    $mail->CharSet  = 'UTF-8';

    if (SMTP_USER !== '') {
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
    } else {
        $mail->SMTPAuth = false;
    }

    if (SMTP_SECURE === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif (SMTP_SECURE === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure  = '';
        $mail->SMTPAutoTLS = false;
    }

    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    return $mail;
}
