<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/secrets.php';
require_once __DIR__ . '/parametres_techniques_lib.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

function reponsesErreurSmtp(?string $ligne = null): array
{
    static $reponses = [];
    if ($ligne !== null && preg_match('/^SERVER -> CLIENT: ([45]\d\d\b.*)$/s', trim($ligne), $correspondance)) {
        $reponses = array_slice([...$reponses, $correspondance[1]], -3);
    }
    return $reponses;
}

function creerMailer(): PHPMailer
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = fn(string $ligne) => reponsesErreurSmtp($ligne);
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
    appliquerParametresCourriel($mail);
    return $mail;
}

function diagnosticEchecMail(?PHPMailer $mail, Throwable $erreur): string
{
    $raison = $mail !== null && $mail->ErrorInfo !== '' ? $mail->ErrorInfo : $erreur->getMessage();
    $reponsesServeur = reponsesErreurSmtp();
    if ($reponsesServeur) {
        $raison .= ' Réponse du serveur : ' . implode(' | ', $reponsesServeur);
    }
    if (SMTP_PASS !== '') {
        $raison = str_replace(SMTP_PASS, '***', $raison);
    }
    $raison = preg_replace('/[^\s<>:;,"\'()\[\]]+@([A-Za-z0-9.-]+)/', '***@$1', $raison);
    $raison = mb_substr(trim(preg_replace('/\s+/', ' ', $raison)), 0, 500);
    return sprintf('%s (hôte %s, port %d, chiffrement %s, authentification %s)', $raison !== '' ? $raison : 'raison inconnue', SMTP_HOST, SMTP_PORT, SMTP_SECURE !== '' ? SMTP_SECURE : 'aucun', SMTP_USER !== '' ? 'oui' : 'non');
}

function texteMailEnHtml(string $texte): string
{
    $paragraphes = preg_split("/\n\s*\n/", trim($texte));
    $html = '';
    foreach ($paragraphes as $paragraphe) {
        $lignes = array_map(function (string $ligne): string {
            $ligne = htmlspecialchars($ligne, ENT_QUOTES, 'UTF-8');
            return preg_replace('~(https?://[^\s<]+)~', '<a href="$1" style="color:#274e13;font-weight:600;word-break:break-all;">$1</a>', $ligne);
        }, explode("\n", $paragraphe));
        $html .= '<p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#17230f;">' . implode('<br>', $lignes) . '</p>';
    }
    return $html;
}

function habillerMail(PHPMailer $mail, string $sujet, string $texte, ?string $lienBouton = null, ?string $libelleBouton = null, ?string $piedPersonnalise = null): void
{
    $piedTexte = $piedPersonnalise ?? "Ceci est un mail automatique de " . MAIL_FROM_NAME . ", merci de ne pas y répondre.";
    $bouton = '';
    if ($lienBouton && $libelleBouton) {
        $bouton = '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;"><tr><td style="border-radius:999px;background:#274e13;">'
            . '<a href="' . htmlspecialchars($lienBouton, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:13px 28px;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:999px;">'
            . htmlspecialchars($libelleBouton, ENT_QUOTES, 'UTF-8') . '</a></td></tr></table>'
            . '<p style="margin:0 0 20px;font-size:13px;line-height:1.5;color:#56634f;word-break:break-all;">Le bouton ne fonctionne pas ? Copiez ce lien dans votre navigateur :<br><a href="' . htmlspecialchars($lienBouton, ENT_QUOTES, 'UTF-8') . '" style="color:#3f7d25;">' . htmlspecialchars($lienBouton, ENT_QUOTES, 'UTF-8') . '</a></p>';
    }
    $mail->isHTML(true);
    $mail->Subject = $sujet;
    $mail->Body = '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . htmlspecialchars($sujet, ENT_QUOTES, 'UTF-8') . '</title></head>'
        . '<body style="margin:0;padding:0;background:#f2f7ee;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f2f7ee;"><tr><td align="center" style="padding:28px 12px;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border:1px solid #dce5d6;border-radius:16px;">'
        . '<tr><td style="padding:24px 32px 18px;border-bottom:3px solid #c5dfb2;">'
        . '<a href="' . htmlspecialchars(SITE_URL, ENT_QUOTES, 'UTF-8') . '" style="font-family:Georgia,\'Times New Roman\',serif;font-size:24px;font-weight:700;color:#274e13;text-decoration:none;">BIBLIOccaz</a>'
        . '<div style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#56634f;margin-top:2px;">Donnez une seconde vie à vos lectures</div></td></tr>'
        . '<tr><td style="padding:28px 32px 8px;font-family:Arial,Helvetica,sans-serif;">'
        . '<h1 style="margin:0 0 18px;font-family:Georgia,\'Times New Roman\',serif;font-size:22px;line-height:1.3;color:#0e1f07;">' . htmlspecialchars($sujet, ENT_QUOTES, 'UTF-8') . '</h1>'
        . texteMailEnHtml($texte) . $bouton . '</td></tr>'
        . '<tr><td style="padding:16px 32px 24px;border-top:1px solid #dce5d6;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.5;color:#56634f;">'
        . htmlspecialchars($piedTexte, ENT_QUOTES, 'UTF-8') . '</td></tr>'
        . '</table></td></tr></table></body></html>';
    $mail->AltBody = trim($texte) . ($lienBouton ? "\n\n" . ($libelleBouton ? $libelleBouton . ' : ' : '') . $lienBouton : '') . "\n\n---------------\n" . $piedTexte;
}
