<?php
require_once __DIR__ . '/parametres_site_lib.php';

const CHAMPS_TECHNIQUES = [
    'mail_from' => ['Adresse d’envoi autorisée par votre serveur SMTP', 'email', 150],
    'mail_from_name' => ['Nom affiché dans les courriels', 'text', 100],
    'mail_reply_to' => ['Adresse de réponse (facultative)', 'email', 150],
    'editeur_nom' => ['Nom de l’éditeur', 'text', 200],
    'editeur_statut' => ['Statut de l’éditeur', 'text', 500],
    'editeur_adresse' => ['Adresse de l’éditeur', 'textarea', 1000],
    'editeur_email' => ['Contact public', 'email', 150],
    'directeur_publication' => ['Directeur de la publication', 'text', 200],
    'hebergeur_nom' => ['Nom de l’hébergeur', 'text', 200],
    'hebergeur_adresse' => ['Adresse de l’hébergeur', 'textarea', 1000],
    'hebergeur_telephone' => ['Téléphone de l’hébergeur', 'text', 100],
    'date_mise_a_jour' => ['Date de mise à jour des informations légales', 'text', 100],
    'texte_mentions' => ['Texte personnalisé des mentions légales', 'textarea', 30000],
    'texte_confidentialite' => ['Texte personnalisé de la politique de confidentialité', 'textarea', 30000],
    'texte_cgu' => ['Conditions générales d’utilisation', 'textarea', 30000],
];

function parametresTechniquesParDefaut(): array
{
    $informations = defined('INFOS_LEGALES') && is_array(INFOS_LEGALES) ? INFOS_LEGALES : [];
    $valeurs = [];
    foreach (CHAMPS_TECHNIQUES as $cle => $champ) { $valeurs[$cle] = is_string($informations[$cle] ?? null) ? $informations[$cle] : ''; }
    $valeurs['mail_from'] = MAIL_FROM;
    $valeurs['mail_from_name'] = MAIL_FROM_NAME;
    return $valeurs;
}

function parametresTechniques(): array
{
    global $pdo;
    static $valeurs = null;
    if ($valeurs === null) {
        $defaut = parametresTechniquesParDefaut();
        $etat = $pdo instanceof PDO ? chargerParametreSite($pdo, 'technique', $defaut) : ['valeurs' => $defaut];
        $valeurs = array_replace($defaut, $etat['valeurs']);
    }
    return $valeurs;
}

function validerParametresTechniques(array $saisie): array
{
    if (array_diff_key($saisie, CHAMPS_TECHNIQUES) || count($saisie) !== count(CHAMPS_TECHNIQUES)) { throw new DomainException('Tous les champs sont nécessaires.'); }
    $valeurs = [];
    foreach (CHAMPS_TECHNIQUES as $cle => [$libelle, $type, $maximum]) {
        $valeur = $saisie[$cle] ?? null;
        if (!is_string($valeur) || mb_strlen($valeur) > $maximum || ($type !== 'textarea' && preg_match('/[\r\n]/', $valeur))) { throw new DomainException($libelle . ' : contenu invalide ou trop long.'); }
        $valeur = trim($valeur);
        if ($type === 'email' && $valeur !== '' && !filter_var($valeur, FILTER_VALIDATE_EMAIL)) { throw new DomainException($libelle . ' : adresse invalide.'); }
        $valeurs[$cle] = $valeur;
    }
    if ($valeurs['mail_from'] === '' || $valeurs['mail_from_name'] === '') { throw new DomainException('L’adresse et le nom d’envoi sont obligatoires.'); }
    $domaineAutorise = strtolower(substr(strrchr(MAIL_FROM, '@'), 1));
    if (strtolower(substr(strrchr($valeurs['mail_from'], '@'), 1)) !== $domaineAutorise) { throw new DomainException('L’adresse d’envoi doit rester sur le domaine déjà configuré : ' . $domaineAutorise . '.'); }
    return $valeurs;
}

function appliquerParametresCourriel(object $mail): void
{
    $valeurs = parametresTechniques();
    $mail->setFrom($valeurs['mail_from'], $valeurs['mail_from_name']);
    if ($valeurs['mail_reply_to'] !== '') { $mail->addReplyTo($valeurs['mail_reply_to']); }
}

function texteLegalPersonnalise(string $cle): string
{
    $valeurs = parametresTechniques();
    return is_string($valeurs[$cle] ?? null) ? $valeurs[$cle] : '';
}
