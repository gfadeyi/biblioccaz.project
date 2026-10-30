<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}

const MARQUEUR_DOSSIER = '.biblioccaz-a-envoyer';
const SCRIPTS_INSTALLATION = ['sql/biblioccaz.sql', 'sql/donnees_demo.sql', 'sql/donnees_demo_catalogue.sql'];
const MOT_DE_PASSE_NEUTRALISE = '!compte-de-demonstration-sans-connexion';
const DOSSIERS_EXCLUS = ['docs/', 'sql/', 'outils/', 'nbproject/', 'sauvegardes/', 'secure_data/', 'vendor/', 'img/', '.git/'];
const FICHIERS_EXCLUS = ['.gitignore', '.gitattributes', 'composer.json', 'composer.lock', 'secrets.php', 'secrets.example.php', 'reset_password'];
const EXTENSIONS_EXCLUES = ['md', 'sql', 'bak', 'old', 'orig', 'log', 'txt', 'zip', 'gz', '7z', 'db', 'ini', 'env', 'lock'];
const DOSSIERS_ENVOI_VIDES = ['img/annonces', 'img/dons', 'img/profils', 'img/livres', 'secure_data'];
const MOTIFS_INTERDITS = ['~(^|/)secrets\.php$~i', '~\.(sql|md|bak|log|db)$~i', '~(^|/)(docs|sql|sauvegardes|outils|nbproject)/~i', '~(^|/)\.(?!htaccess$)[^/]+(/|$)~', '~^secure_data/(?!\.htaccess$)~i'];

$racineProjet = dirname(__DIR__);
$options = getopt('', ['destination:', 'chemin-site:', 'avec-couvertures-editeur', 'aide']);

if (isset($options['aide'])) {
    echo "Usage : php outils/preparer_envoi.php [--destination=DOSSIER] [--chemin-site=/] [--avec-couvertures-editeur]" . PHP_EOL;
    exit(0);
}

function arreter(string $message): never
{
    fwrite(STDERR, 'ERREUR : ' . $message . PHP_EOL);
    exit(1);
}

function cheminNormalise(string $chemin): string
{
    return rtrim(str_replace('\\', '/', $chemin), '/');
}

function supprimerDossier(string $dossier): void
{
    $elements = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dossier, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($elements as $element) {
        $element->isDir() && !$element->isLink() ? rmdir($element->getPathname()) : unlink($element->getPathname());
    }
    rmdir($dossier);
}

function copierFichier(string $source, string $cible): void
{
    if (!is_dir(dirname($cible)) && !mkdir(dirname($cible), 0755, true)) {
        arreter("Impossible de créer le dossier de $cible");
    }
    if (!copy($source, $cible)) {
        arreter("Copie impossible : $source");
    }
}

function listeGit(string $racine, array $arguments): array
{
    $processus = proc_open(array_merge(['git', '-C', $racine, 'ls-files', '-z'], $arguments), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tubes);
    if (!is_resource($processus)) {
        arreter('Git est introuvable.');
    }
    $sortie = stream_get_contents($tubes[1]);
    $erreurs = stream_get_contents($tubes[2]);
    fclose($tubes[1]);
    fclose($tubes[2]);
    if (proc_close($processus) !== 0) {
        arreter('git ls-files a échoué : ' . trim($erreurs));
    }
    return array_values(array_filter(explode("\0", $sortie)));
}

function fichiersDuDepot(string $racine): array
{
    return [listeGit($racine, ['--cached', '--others', '--exclude-standard']), listeGit($racine, ['--others', '--exclude-standard'])];
}

function fichierAEnvoyer(string $chemin): bool
{
    foreach (DOSSIERS_EXCLUS as $dossier) {
        if (str_starts_with($chemin, $dossier)) {
            return false;
        }
    }
    $nom = basename($chemin);
    $extension = strtolower(pathinfo($nom, PATHINFO_EXTENSION));
    return !in_array($nom, FICHIERS_EXCLUS, true) && !in_array($extension, EXTENSIONS_EXCLUES, true) && !str_ends_with($nom, '~');
}

function decouperRequetes(string $sql): array
{
    $requetes = [];
    $courante = '';
    $guillemet = null;
    $longueur = strlen($sql);
    for ($i = 0; $i < $longueur; $i++) {
        $caractere = $sql[$i];
        if ($guillemet !== null) {
            $courante .= $caractere;
            if ($caractere === '\\' && $guillemet !== '`') {
                $courante .= $sql[++$i] ?? '';
            } elseif ($caractere === $guillemet) {
                if (($sql[$i + 1] ?? '') === $guillemet) {
                    $courante .= $sql[++$i];
                } else {
                    $guillemet = null;
                }
            }
            continue;
        }
        if ($caractere === "'" || $caractere === '"' || $caractere === '`') {
            $guillemet = $caractere;
        } elseif ($caractere === '-' && ($sql[$i + 1] ?? '') === '-' && ctype_space($sql[$i + 2] ?? "\n")) {
            $fin = strpos($sql, "\n", $i);
            $i = $fin === false ? $longueur : $fin;
            continue;
        } elseif ($caractere === '/' && ($sql[$i + 1] ?? '') === '*') {
            $fin = strpos($sql, '*/', $i + 2);
            $i = $fin === false ? $longueur : $fin + 1;
            continue;
        } elseif ($caractere === ';') {
            if (trim($courante) !== '') {
                $requetes[] = trim($courante);
            }
            $courante = '';
            continue;
        }
        $courante .= $caractere;
    }
    if ($guillemet !== null) {
        arreter('Chaîne SQL non fermée dans un script d\'installation.');
    }
    if (trim($courante) !== '') {
        $requetes[] = trim($courante);
    }
    return $requetes;
}

function construireInstallation(string $racine, array &$bilan): string
{
    $sortie = ["SET NAMES utf8mb4"];
    foreach (SCRIPTS_INSTALLATION as $script) {
        $contenu = file_get_contents($racine . '/' . $script);
        if ($contenu === false || !mb_check_encoding($contenu, 'UTF-8')) {
            arreter("$script est illisible ou n'est pas en UTF-8.");
        }
        foreach (decouperRequetes($contenu) as $requete) {
            $debut = strtoupper(preg_replace('/\s+/', ' ', substr($requete, 0, 60)));
            if (str_starts_with($debut, 'SET NAMES')) {
                continue;
            }
            if (preg_match('/^(CREATE (DATABASE|SCHEMA)|DROP (DATABASE|SCHEMA)|USE |CREATE USER|DROP USER|ALTER USER|GRANT |REVOKE |FLUSH |SET PASSWORD)/', $debut)) {
                $bilan['requetes_retirees'][] = "$script : " . strtok($requete, "\n");
                continue;
            }
            $requete = preg_replace("~'\\\$2[aby]\\\$\d{2}\\\$[./A-Za-z0-9]{53}'~", "'" . MOT_DE_PASSE_NEUTRALISE . "'", $requete, -1, $remplacements);
            $bilan['mots_de_passe_neutralises'] += $remplacements;
            $sortie[] = $requete;
        }
    }
    $sql = implode(";\n\n", $sortie) . ";\n";
    if (preg_match('/\$2[aby]\$\d{2}\$/', $sql)) {
        arreter('Une empreinte de mot de passe subsiste dans le script d\'installation.');
    }
    if (preg_match('/(Ã.|â€|├|┬)/u', $sql)) {
        arreter('Caractères mal encodés détectés dans le script d\'installation.');
    }
    return $sql;
}

function htaccessProduction(string $racine, string $cheminSite): string
{
    $contenu = file_get_contents($racine . '/.htaccess');
    $contenu = preg_replace('~^ErrorDocument 404 .*$~m', 'ErrorDocument 404 ' . $cheminSite . 'erreur_404.php', $contenu, 1, $erreurDocument);
    $redirection = "RewriteEngine On\nRewriteCond %{REQUEST_URI} !^/\\.well-known/acme-challenge/\nRewriteCond %{HTTPS} !=on\nRewriteCond %{HTTP:X-Forwarded-Proto} !=https\nRewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]\n";
    $contenu = preg_replace('~^RewriteEngine On\R~m', $redirection, $contenu, 1, $moteur);
    if ($erreurDocument !== 1 || $moteur !== 1) {
        arreter('Structure du .htaccess inattendue : adaptation production impossible.');
    }
    return $contenu;
}

function imagesReferencees(string $racine, string $sql, bool $avecCouverturesEditeur, array &$bilan): array
{
    preg_match_all("~'([^'\\\\]+\\.(?:jpe?g|png|webp|gif))'~i", $sql, $correspondances);
    $images = [];
    foreach (array_unique($correspondances[1]) as $image) {
        if (str_contains($image, '..') || !is_file($racine . '/img/' . $image)) {
            continue;
        }
        if (!$avecCouverturesEditeur && !str_ends_with($image, '-generee.png')) {
            $bilan['couvertures_ecartees'][] = $image;
            continue;
        }
        $images[] = 'img/' . $image;
    }
    return $images;
}

$destination = cheminNormalise($options['destination'] ?? (getenv('USERPROFILE') ?: getenv('HOME')) . '/biblioccaz-a-envoyer');
$cheminSite = '/' . trim($options['chemin-site'] ?? '/', '/') . '/';
$cheminSite = $cheminSite === '//' ? '/' : $cheminSite;
$avecCouverturesEditeur = isset($options['avec-couvertures-editeur']);

if ($destination === '' || str_starts_with($destination . '/', cheminNormalise($racineProjet) . '/')) {
    arreter('La destination doit se trouver hors du dossier du projet.');
}
if (file_exists($destination)) {
    if (!is_file($destination . '/' . MARQUEUR_DOSSIER)) {
        arreter("$destination existe déjà et n'a pas été créé par ce script : choisis un autre dossier.");
    }
    supprimerDossier($destination);
}
if (!is_file($racineProjet . '/vendor/autoload.php')) {
    arreter('vendor/ est absent : lance composer install avant de préparer l\'envoi.');
}

$bilan = ['requetes_retirees' => [], 'mots_de_passe_neutralises' => 0, 'couvertures_ecartees' => []];
$sqlInstallation = construireInstallation($racineProjet, $bilan);

[$fichiersProjet, $nonSuivis] = fichiersDuDepot($racineProjet);
$aEnvoyer = array_values(array_filter($fichiersProjet, 'fichierAEnvoyer'));
$aEnvoyer = array_values(array_diff($aEnvoyer, ['.htaccess']));
foreach (['img/.htaccess', 'img/profils/.htaccess', 'img/icones.svg', 'img/default.png', 'secure_data/.htaccess'] as $fichierProtege) {
    if (is_file($racineProjet . '/' . $fichierProtege)) {
        $aEnvoyer[] = $fichierProtege;
    }
}
foreach ($fichiersProjet as $fichier) {
    if (str_starts_with($fichier, 'img/') && dirname($fichier) === 'img') {
        $aEnvoyer[] = $fichier;
    }
}
foreach (glob($racineProjet . '/img/captcha/*.{png,jpg,jpeg,webp}', GLOB_BRACE) as $imageCaptcha) {
    $aEnvoyer[] = 'img/captcha/' . basename($imageCaptcha);
}
$aEnvoyer = array_merge($aEnvoyer, imagesReferencees($racineProjet, $sqlInstallation, $avecCouverturesEditeur, $bilan));

$vendor = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($racineProjet . '/vendor', FilesystemIterator::SKIP_DOTS));
foreach ($vendor as $fichierVendor) {
    if ($fichierVendor->isFile()) {
        $aEnvoyer[] = 'vendor/' . str_replace('\\', '/', substr($fichierVendor->getPathname(), strlen($racineProjet . '/vendor/')));
    }
}
$aEnvoyer = array_values(array_unique($aEnvoyer));
sort($aEnvoyer);

foreach ($aEnvoyer as $fichier) {
    foreach (MOTIFS_INTERDITS as $motif) {
        if (!str_starts_with($fichier, 'vendor/') && preg_match($motif, $fichier)) {
            arreter("Fichier interdit dans la sélection : $fichier");
        }
    }
}

mkdir($destination . '/site', 0755, true);
mkdir($destination . '/base', 0755, true);
file_put_contents($destination . '/' . MARQUEUR_DOSSIER, date('c') . PHP_EOL);

$manifeste = [];
foreach ($aEnvoyer as $fichier) {
    copierFichier($racineProjet . '/' . $fichier, $destination . '/site/' . $fichier);
    $manifeste[] = hash_file('sha256', $racineProjet . '/' . $fichier) . '  ' . $fichier;
}
file_put_contents($destination . '/site/.htaccess', htaccessProduction($racineProjet, $cheminSite));
$manifeste[] = hash_file('sha256', $destination . '/site/.htaccess') . '  .htaccess (version production)';
foreach (DOSSIERS_ENVOI_VIDES as $dossierVide) {
    if (!is_dir($destination . '/site/' . $dossierVide)) {
        mkdir($destination . '/site/' . $dossierVide, 0755, true);
    }
}

file_put_contents($destination . '/base/installation_biblioccaz.sql', $sqlInstallation);
copierFichier($racineProjet . '/secrets.example.php', $destination . '/secrets-modele.php');
sort($manifeste);
file_put_contents($destination . '/fichiers-du-site.txt', implode(PHP_EOL, $manifeste) . PHP_EOL);

$nonSuivisEnvoyes = array_values(array_intersect($nonSuivis, $aEnvoyer));
echo 'Dossier prêt : ' . $destination . PHP_EOL;
echo '  site/ : ' . (count($aEnvoyer) + 1) . ' fichiers à envoyer à la racine du site' . PHP_EOL;
echo '  base/installation_biblioccaz.sql : ' . number_format(strlen($sqlInstallation) / 1024, 0, ',', ' ') . ' Ko, ' . substr_count($sqlInstallation, ";\n\n") . ' requêtes' . PHP_EOL;
echo '  Requêtes retirées : ' . count($bilan['requetes_retirees']) . PHP_EOL;
foreach ($bilan['requetes_retirees'] as $requeteRetiree) {
    echo '    - ' . $requeteRetiree . PHP_EOL;
}
echo '  Mots de passe de démonstration neutralisés : ' . $bilan['mots_de_passe_neutralises'] . PHP_EOL;
echo '  Couvertures d\'éditeur écartées : ' . count($bilan['couvertures_ecartees']) . ($avecCouverturesEditeur ? '' : ' (--avec-couvertures-editeur pour les inclure)') . PHP_EOL;
echo '  ErrorDocument 404 : ' . $cheminSite . 'erreur_404.php' . PHP_EOL;
if ($nonSuivisEnvoyes) {
    echo '  Attention, fichiers non suivis par Git inclus :' . PHP_EOL;
    foreach ($nonSuivisEnvoyes as $fichier) {
        echo '    - ' . $fichier . PHP_EOL;
    }
}
