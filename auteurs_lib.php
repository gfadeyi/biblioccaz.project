<?php
require_once __DIR__ . '/livres_lib.php';
require_once __DIR__ . '/upload_lib.php';
require_once __DIR__ . '/categories_lib.php';

const AUTEUR_DELAI_NOUVELLE_DEMANDE_JOURS = 7;
const AUTEUR_OUVRAGES_MAX = 10;
const AUTEUR_OUVRAGES_AFFICHES_MIN = 3;
const AUTEUR_LONGUEURS = [
    'nom_plume'  => [2, 80],
    'biographie' => [50, 3000],
    'motivation' => [30, 2000],
    'titre'      => [1, 200],
    'editeur'    => [0, 120],
    'lien'       => [0, 255],
];
const AUTEUR_LIENS = [
    'lien_site'    => 'Site personnel',
    'lien_reseau'  => 'Réseau social',
    'lien_editeur' => 'Page chez l\'éditeur',
];
const AUTEUR_DOSSIER_PHOTOS = 'auteurs/';
const ROLES_CANDIDATURE_AUTEUR = ['client'];
const STATUTS_CANDIDATURE_AUTEUR = ['en_attente' => 'En attente', 'acceptee' => 'Acceptée', 'refusee' => 'Refusée'];

function candidaturesAuteurDisponibles(PDO $pdo): bool
{
    try {
        $pdo->prepare('SELECT 1 FROM candidature_auteur LIMIT 0')->execute();
        $pdo->prepare('SELECT 1 FROM fiche_auteur_ouvrage LIMIT 0')->execute();
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function isbnNormalise(string $isbn): string
{
    return strtoupper(str_replace([' ', '-'], '', trim($isbn)));
}

function isbnValide(string $isbn): bool
{
    $isbn = isbnNormalise($isbn);
    if (preg_match('/\A[0-9]{13}\z/', $isbn)) {
        $somme = 0;
        for ($i = 0; $i < 12; $i++) {
            $somme += (int) $isbn[$i] * ($i % 2 === 0 ? 1 : 3);
        }
        return (10 - $somme % 10) % 10 === (int) $isbn[12];
    }
    if (preg_match('/\A[0-9]{9}[0-9X]\z/', $isbn)) {
        $somme = 0;
        for ($i = 0; $i < 10; $i++) {
            $somme += ($isbn[$i] === 'X' ? 10 : (int) $isbn[$i]) * (10 - $i);
        }
        return $somme % 11 === 0;
    }
    return false;
}

function lienAuteurValide(string $lien): bool
{
    if (mb_strlen($lien) > AUTEUR_LONGUEURS['lien'][1] || !filter_var($lien, FILTER_VALIDATE_URL)) {
        return false;
    }
    return in_array(strtolower((string) parse_url($lien, PHP_URL_SCHEME)), ['http', 'https'], true)
        && (string) parse_url($lien, PHP_URL_HOST) !== '';
}

function texteSaisi(array $source, string $champ): string
{
    return is_string($source[$champ] ?? null) ? trim(str_replace("\r\n", "\n", $source[$champ])) : '';
}

function longueurAcceptee(string $texte, string $cle): bool
{
    [$minimum, $maximum] = AUTEUR_LONGUEURS[$cle];
    $longueur = mb_strlen($texte);
    return $longueur >= $minimum && $longueur <= $maximum;
}

function ouvragesSaisis(array $post): array
{
    $colonnes = [];
    foreach (['titre', 'editeur', 'annee', 'isbn'] as $champ) {
        $valeurs = $post['ouvrage_' . $champ] ?? [];
        $colonnes[$champ] = is_array($valeurs) ? array_values($valeurs) : [];
    }
    $nombre = max(array_map('count', $colonnes));
    $ouvrages = [];
    for ($i = 0; $i < min($nombre, AUTEUR_OUVRAGES_MAX + 1); $i++) {
        $ligne = [];
        foreach ($colonnes as $champ => $valeurs) {
            $ligne[$champ] = is_string($valeurs[$i] ?? null) ? trim($valeurs[$i]) : '';
        }
        if (implode('', $ligne) !== '') {
            $ouvrages[] = $ligne;
        }
    }
    return $ouvrages;
}

function erreursOuvrages(array $ouvrages): array
{
    if (!$ouvrages) {
        return ['Indiquez au moins un ouvrage publié.'];
    }
    if (count($ouvrages) > AUTEUR_OUVRAGES_MAX) {
        return ['Vous pouvez présenter ' . AUTEUR_OUVRAGES_MAX . ' ouvrages au plus.'];
    }
    $erreurs = [];
    $anneeCourante = (int) date('Y');
    foreach ($ouvrages as $index => $ouvrage) {
        $numero = $index + 1;
        if (!longueurAcceptee($ouvrage['titre'], 'titre')) {
            $erreurs[] = "Ouvrage $numero : le titre est obligatoire (200 caractères au plus).";
        }
        if (!longueurAcceptee($ouvrage['editeur'], 'editeur')) {
            $erreurs[] = "Ouvrage $numero : le nom de l'éditeur est trop long.";
        }
        if ($ouvrage['annee'] !== '' && (!ctype_digit($ouvrage['annee']) || (int) $ouvrage['annee'] < 1900 || (int) $ouvrage['annee'] > $anneeCourante)) {
            $erreurs[] = "Ouvrage $numero : l'année doit être comprise entre 1900 et $anneeCourante.";
        }
        if ($ouvrage['isbn'] !== '' && !isbnValide($ouvrage['isbn'])) {
            $erreurs[] = "Ouvrage $numero : l'ISBN n'est pas valide (10 ou 13 chiffres, clé de contrôle comprise).";
        }
    }
    return $erreurs;
}

function ouvragesNormalises(array $ouvrages): array
{
    return array_map(fn(array $ouvrage) => [
        'titre'   => $ouvrage['titre'],
        'editeur' => $ouvrage['editeur'] !== '' ? $ouvrage['editeur'] : null,
        'annee'   => $ouvrage['annee'] !== '' ? (int) $ouvrage['annee'] : null,
        'isbn'    => $ouvrage['isbn'] !== '' ? isbnNormalise($ouvrage['isbn']) : null,
    ], array_values($ouvrages));
}

function saisieFicheAuteur(array $post, bool $avecMotivation): array
{
    $valeurs = ['nom_plume' => preg_replace('/\s+/u', ' ', texteSaisi($post, 'nom_plume')), 'biographie' => texteSaisi($post, 'biographie')];
    if ($avecMotivation) {
        $valeurs['motivation'] = texteSaisi($post, 'motivation');
    }
    foreach (array_keys(AUTEUR_LIENS) as $champ) {
        $valeurs[$champ] = texteSaisi($post, $champ);
    }
    $ouvrages = ouvragesSaisis($post);

    $erreurs = [];
    if (!longueurAcceptee($valeurs['nom_plume'], 'nom_plume')) {
        $erreurs[] = 'Le nom de plume doit faire entre 2 et 80 caractères.';
    }
    if (!longueurAcceptee($valeurs['biographie'], 'biographie')) {
        $erreurs[] = 'La biographie doit faire entre 50 et 3 000 caractères.';
    }
    if ($avecMotivation && !longueurAcceptee($valeurs['motivation'], 'motivation')) {
        $erreurs[] = 'La motivation doit faire entre 30 et 2 000 caractères.';
    }
    foreach (AUTEUR_LIENS as $champ => $libelle) {
        if ($valeurs[$champ] !== '' && !lienAuteurValide($valeurs[$champ])) {
            $erreurs[] = "$libelle : indiquez une adresse complète commençant par http:// ou https://.";
        }
    }
    $erreurs = array_merge($erreurs, erreursOuvrages($ouvrages));

    return ['valeurs' => $valeurs, 'ouvrages' => $ouvrages, 'erreurs' => $erreurs];
}

function photoAuteurEnvoyee(): array
{
    if (empty($_FILES['photo']) || ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, ''];
    }
    return enregistrerImageEnvoyee($_FILES['photo'], AUTEUR_DOSSIER_PHOTOS);
}

function urlPhotoAuteur(?string $photo): ?string
{
    return $photo && is_file(__DIR__ . '/img/' . AUTEUR_DOSSIER_PHOTOS . $photo) ? urlImage(AUTEUR_DOSSIER_PHOTOS . $photo) : null;
}

function effacerPhotoAuteurInutilisee(PDO $pdo, ?string $photo): void
{
    if (!$photo || !preg_match('/\A[0-9]+_[0-9a-f]{8}\.(jpg|png|webp)\z/', $photo)) {
        return;
    }
    $stmt = $pdo->prepare('SELECT (SELECT COUNT(*) FROM fiche_auteur WHERE photo = ?) + (SELECT COUNT(*) FROM candidature_auteur WHERE photo = ?)');
    $stmt->execute([$photo, $photo]);
    if ((int) $stmt->fetchColumn() === 0 && is_file(__DIR__ . '/img/' . AUTEUR_DOSSIER_PHOTOS . $photo)) {
        unlink(__DIR__ . '/img/' . AUTEUR_DOSSIER_PHOTOS . $photo);
    }
}

function derniereCandidatureAuteur(PDO $pdo, int $idUser): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM candidature_auteur WHERE id_user = ? ORDER BY date_candidature DESC, id_candidature DESC LIMIT 1');
    $stmt->execute([$idUser]);
    return $stmt->fetch() ?: null;
}

function dateNouvelleDemandeAuteur(array $candidature): ?string
{
    if ($candidature['statut'] !== 'refusee' || !$candidature['date_decision']) {
        return null;
    }
    $date = date('Y-m-d H:i:s', strtotime($candidature['date_decision'] . ' +' . AUTEUR_DELAI_NOUVELLE_DEMANDE_JOURS . ' days'));
    return $date > date('Y-m-d H:i:s') ? $date : null;
}

function etatCandidatureAuteur(PDO $pdo, array $membre): array
{
    if ($membre['role'] === 'auteur') {
        return ['etat' => 'auteur', 'candidature' => null, 'date_possible' => null];
    }
    if (!in_array($membre['role'], ROLES_CANDIDATURE_AUTEUR, true) || $membre['statut'] !== 'actif') {
        return ['etat' => 'interdite', 'candidature' => null, 'date_possible' => null];
    }
    $candidature = derniereCandidatureAuteur($pdo, (int) $membre['id']);
    if ($candidature && $candidature['statut'] === 'en_attente') {
        return ['etat' => 'en_attente', 'candidature' => $candidature, 'date_possible' => null];
    }
    $datePossible = $candidature ? dateNouvelleDemandeAuteur($candidature) : null;
    if ($datePossible) {
        return ['etat' => 'refusee_recente', 'candidature' => $candidature, 'date_possible' => $datePossible];
    }
    return ['etat' => 'ouverte', 'candidature' => $candidature, 'date_possible' => null];
}

function insererCandidatureAuteur(PDO $pdo, int $idUser, array $valeurs, array $ouvrages, ?string $photo): int
{
    $pdo->prepare('INSERT INTO candidature_auteur (id_user, nom_plume, biographie, motivation, ouvrages, lien_site, lien_reseau, lien_editeur, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            $idUser, $valeurs['nom_plume'], $valeurs['biographie'], $valeurs['motivation'],
            json_encode(ouvragesNormalises($ouvrages), JSON_UNESCAPED_UNICODE),
            $valeurs['lien_site'] ?: null, $valeurs['lien_reseau'] ?: null, $valeurs['lien_editeur'] ?: null, $photo,
        ]);
    return (int) $pdo->lastInsertId();
}

function deposerCandidatureAuteur(PDO $pdo, int $idUser, array $valeurs, array $ouvrages, ?string $photo): int
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id, role, statut FROM user WHERE id = ? FOR UPDATE');
        $stmt->execute([$idUser]);
        $membre = $stmt->fetch();
        if (!$membre) {
            throw new DomainException('Compte introuvable.');
        }
        $etat = etatCandidatureAuteur($pdo, $membre);
        if ($etat['etat'] !== 'ouverte') {
            throw new DomainException(match ($etat['etat']) {
                'en_attente' => 'Une candidature est déjà en cours d\'étude.',
                'refusee_recente' => 'Une nouvelle demande sera possible à partir du ' . date('d/m/Y à H:i', strtotime($etat['date_possible'])) . '.',
                'auteur' => 'Votre compte est déjà un compte auteur.',
                default => 'Ce compte ne peut pas déposer de candidature auteur.',
            });
        }
        $idCandidature = insererCandidatureAuteur($pdo, $idUser, $valeurs, $ouvrages, $photo);
        $pdo->commit();
        return $idCandidature;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function ouvragesCandidature(array $candidature): array
{
    $ouvrages = json_decode((string) $candidature['ouvrages'], true);
    return is_array($ouvrages) ? $ouvrages : [];
}

function slugAuteurLibre(PDO $pdo, string $nomPlume, int $idUser): string
{
    $base = rtrim(substr(creerSlug($nomPlume, 'auteur'), 0, 90), '-');
    $stmt = $pdo->prepare('SELECT 1 FROM fiche_auteur WHERE slug = ? AND id_user <> ?');
    for ($suffixe = 1; ; $suffixe++) {
        $slug = $suffixe === 1 ? $base : $base . '-' . $suffixe;
        $stmt->execute([$slug, $idUser]);
        if (!$stmt->fetchColumn()) {
            return $slug;
        }
    }
}

function ecrireFicheAuteur(PDO $pdo, int $idUser, array $valeurs, array $ouvrages, ?string $photo): void
{
    $stmt = $pdo->prepare('SELECT nom_plume, slug FROM fiche_auteur WHERE id_user = ? FOR UPDATE');
    $stmt->execute([$idUser]);
    $actuelle = $stmt->fetch();
    $slug = $actuelle && $actuelle['nom_plume'] === $valeurs['nom_plume'] ? $actuelle['slug'] : slugAuteurLibre($pdo, $valeurs['nom_plume'], $idUser);
    $liens = [$valeurs['lien_site'] ?: null, $valeurs['lien_reseau'] ?: null, $valeurs['lien_editeur'] ?: null];
    if ($actuelle) {
        $pdo->prepare('UPDATE fiche_auteur SET nom_plume = ?, slug = ?, biographie = ?, lien_site = ?, lien_reseau = ?, lien_editeur = ?, photo = ?, date_modification = NOW() WHERE id_user = ?')
            ->execute(array_merge([$valeurs['nom_plume'], $slug, $valeurs['biographie']], $liens, [$photo, $idUser]));
    } else {
        $pdo->prepare('INSERT INTO fiche_auteur (id_user, nom_plume, slug, biographie, lien_site, lien_reseau, lien_editeur, photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute(array_merge([$idUser, $valeurs['nom_plume'], $slug, $valeurs['biographie']], $liens, [$photo]));
    }
    $pdo->prepare('DELETE FROM fiche_auteur_ouvrage WHERE id_user = ?')->execute([$idUser]);
    $insertion = $pdo->prepare('INSERT INTO fiche_auteur_ouvrage (id_user, titre, editeur, annee, isbn, ordre) VALUES (?, ?, ?, ?, ?, ?)');
    foreach (array_values($ouvrages) as $ordre => $ouvrage) {
        $insertion->execute([$idUser, $ouvrage['titre'], $ouvrage['editeur'], $ouvrage['annee'], $ouvrage['isbn'], $ordre]);
    }
}

function enregistrerFicheAuteur(PDO $pdo, int $idUser, array $valeurs, array $ouvrages, ?string $nouvellePhoto, bool $retirerPhoto): array
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id, role FROM user WHERE id = ? FOR UPDATE');
        $stmt->execute([$idUser]);
        $membre = $stmt->fetch();
        if (!$membre || $membre['role'] !== 'auteur') {
            throw new DomainException('Seul un compte auteur peut modifier une page auteur.');
        }
        $stmt = $pdo->prepare('SELECT photo FROM fiche_auteur WHERE id_user = ?');
        $stmt->execute([$idUser]);
        $existante = $stmt->fetch();
        $anciennePhoto = $existante['photo'] ?? null;
        $photo = $nouvellePhoto ?? ($retirerPhoto ? null : $anciennePhoto);
        $creation = !$existante;
        ecrireFicheAuteur($pdo, $idUser, $valeurs, ouvragesNormalises($ouvrages), $photo);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    if ($anciennePhoto && $anciennePhoto !== $photo) {
        effacerPhotoAuteurInutilisee($pdo, $anciennePhoto);
    }
    return ['creation' => $creation];
}

function deciderCandidatureAuteur(PDO $pdo, int $idCandidature, int $idDecideur, string $decision, string $message): array
{
    if (!in_array($decision, ['accepter', 'refuser'], true)) {
        throw new DomainException('Décision inconnue.');
    }
    if ($decision === 'refuser' && $message === '') {
        throw new DomainException('Expliquez le refus au candidat : le message est obligatoire.');
    }
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM candidature_auteur WHERE id_candidature = ? FOR UPDATE');
        $stmt->execute([$idCandidature]);
        $candidature = $stmt->fetch();
        if (!$candidature || $candidature['statut'] !== 'en_attente') {
            throw new DomainException('Cette candidature a déjà été traitée.');
        }
        $stmt = $pdo->prepare('SELECT id, pseudo, prenom, email, role, statut, email_verifie FROM user WHERE id = ? FOR UPDATE');
        $stmt->execute([$candidature['id_user']]);
        $candidat = $stmt->fetch();
        if (!$candidat) {
            throw new DomainException('Le compte du candidat n\'existe plus.');
        }
        if ($decision === 'accepter') {
            if ($candidat['role'] !== 'client' || $candidat['statut'] !== 'actif') {
                throw new DomainException('Ce compte n\'est plus un compte lecteur actif : la candidature ne peut pas être acceptée.');
            }
            if (!(int) $candidat['email_verifie']) {
                throw new DomainException('Le candidat n\'a pas encore confirmé son adresse email.');
            }
            $pdo->prepare("UPDATE user SET role = 'auteur' WHERE id = ?")->execute([$candidat['id']]);
            ecrireFicheAuteur($pdo, (int) $candidat['id'], $candidature, ouvragesCandidature($candidature), $candidature['photo']);
        }
        $pdo->prepare('UPDATE candidature_auteur SET statut = ?, date_decision = NOW(), id_decideur = ?, message_decision = ? WHERE id_candidature = ?')
            ->execute([$decision === 'accepter' ? 'acceptee' : 'refusee', $idDecideur, $message !== '' ? mb_substr($message, 0, 1000) : null, $idCandidature]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    $candidature['statut'] = $decision === 'accepter' ? 'acceptee' : 'refusee';
    return ['candidature' => $candidature, 'candidat' => $candidat];
}

function texteMailDecisionAuteur(array $candidat, array $candidature, string $message): array
{
    $complement = $message !== '' ? "\n\nMessage de l'équipe :\n" . $message : '';
    if ($candidature['statut'] === 'acceptee') {
        return ["Votre candidature auteur est acceptée",
            "Bonjour " . $candidat['prenom'] . ",\n\nBonne nouvelle : l'équipe BIBLIOccaz a accepté votre candidature d'auteur. Votre page publique « " . $candidature['nom_plume'] . " » est en ligne.\n\nReconnectez-vous pour accéder à votre espace créateur : vous pourrez y proposer vos livres et vos rencontres, et modifier votre page." . $complement,
            SITE_URL . '/login.php', 'Me reconnecter'];
    }
    $datePossible = date('d/m/Y', strtotime('+' . AUTEUR_DELAI_NOUVELLE_DEMANDE_JOURS . ' days'));
    return ["Votre candidature auteur",
        "Bonjour " . $candidat['prenom'] . ",\n\nMerci pour l'intérêt que vous portez à BIBLIOccaz. Après étude, l'équipe n'a pas retenu votre candidature d'auteur pour le moment." . $complement . "\n\nVotre compte lecteur reste actif. Vous pourrez déposer une nouvelle candidature à partir du $datePossible.",
        SITE_URL . '/profil.php', 'Mon compte'];
}

function conditionFichePublique(string $aliasFiche = 'f', string $aliasMembre = 'u'): string
{
    return "$aliasFiche.est_masquee = 0 AND $aliasMembre.role = 'auteur' AND $aliasMembre.statut NOT IN ('" . implode("', '", STATUTS_COMPTE_SUSPENDU) . "')";
}

function ficheAuteurDe(PDO $pdo, int $idUser): ?array
{
    try {
        $stmt = $pdo->prepare('SELECT f.*, ' . conditionFichePublique() . ' AS est_publique FROM fiche_auteur f INNER JOIN user u ON u.id = f.id_user WHERE f.id_user = ?');
        $stmt->execute([$idUser]);
        $fiche = $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
    if (!$fiche) {
        return null;
    }
    $fiche['ouvrages'] = ouvragesFiche($pdo, $idUser);
    return $fiche;
}

function ouvragesFiche(PDO $pdo, int $idUser): array
{
    $stmt = $pdo->prepare('SELECT titre, editeur, annee, isbn FROM fiche_auteur_ouvrage WHERE id_user = ? ORDER BY ordre, id_ouvrage');
    $stmt->execute([$idUser]);
    return $stmt->fetchAll();
}

function fichePubliqueParSlug(PDO $pdo, string $slug): ?array
{
    if ($slug === '' || strlen($slug) > 100) {
        return null;
    }
    try {
        $stmt = $pdo->prepare('SELECT f.* FROM fiche_auteur f INNER JOIN user u ON u.id = f.id_user WHERE f.slug = ? AND ' . conditionFichePublique());
        $stmt->execute([$slug]);
        $fiche = $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
    if (!$fiche) {
        return null;
    }
    $fiche['ouvrages'] = ouvragesFiche($pdo, (int) $fiche['id_user']);
    return $fiche;
}

function slugFichePublique(PDO $pdo, ?int $idUser): ?string
{
    if (!$idUser) {
        return null;
    }
    try {
        $stmt = $pdo->prepare('SELECT f.slug FROM fiche_auteur f INNER JOIN user u ON u.id = f.id_user WHERE f.id_user = ? AND ' . conditionFichePublique());
        $stmt->execute([$idUser]);
        return $stmt->fetchColumn() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

function fichesPubliques(PDO $pdo, string $recherche = ''): array
{
    $sql = 'SELECT f.id_user, f.nom_plume, f.slug, f.biographie, f.photo,
                   (SELECT COUNT(*) FROM fiche_auteur_ouvrage o WHERE o.id_user = f.id_user) AS nb_ouvrages
            FROM fiche_auteur f INNER JOIN user u ON u.id = f.id_user
            WHERE ' . conditionFichePublique();
    $parametres = [];
    if ($recherche !== '') {
        $sql .= " AND (f.nom_plume LIKE ? ESCAPE '!' OR f.biographie LIKE ? ESCAPE '!'
                  OR EXISTS (SELECT 1 FROM fiche_auteur_ouvrage o WHERE o.id_user = f.id_user AND o.titre LIKE ? ESCAPE '!'))";
        $motif = '%' . strtr($recherche, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $parametres = [$motif, $motif, $motif];
    }
    try {
        $stmt = $pdo->prepare($sql . ' ORDER BY f.nom_plume');
        $stmt->execute($parametres);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function livresCatalogueOuvrages(PDO $pdo, array $ouvrages): array
{
    $isbns = array_values(array_unique(array_filter(array_column($ouvrages, 'isbn'))));
    if (!$isbns) {
        return [];
    }
    $stmt = $pdo->prepare("SELECT REPLACE(REPLACE(isbn, '-', ''), ' ', '') AS isbn_normalise, MIN(id_livre) AS id_livre
        FROM livre WHERE is_valide = 1 AND REPLACE(REPLACE(isbn, '-', ''), ' ', '') IN (" . implode(', ', array_fill(0, count($isbns), '?')) . ")
        GROUP BY isbn_normalise");
    $stmt->execute($isbns);
    $correspondances = [];
    foreach ($stmt->fetchAll() as $ligne) {
        $correspondances[strtoupper($ligne['isbn_normalise'])] = (int) $ligne['id_livre'];
    }
    return $correspondances;
}

function livresAuteurEnVente(PDO $pdo, int $idUser): array
{
    $stmt = $pdo->prepare(sqlCartesLivres('AND l.id_user_auteur = ?') . ' ORDER BY l.titre LIMIT 24');
    $stmt->execute([$idUser]);
    return $stmt->fetchAll();
}

function evenementsAuteur(PDO $pdo, int $idUser): array
{
    try {
        $stmt = $pdo->prepare("SELECT id_evenement, titre, date_debut, date_fin, lieu, image, type
            FROM evenement WHERE id_createur = ? AND statut = 'publie' ORDER BY date_debut DESC LIMIT 30");
        $stmt->execute([$idUser]);
        $evenements = $stmt->fetchAll();
    } catch (PDOException $e) {
        return ['a_venir' => [], 'passes' => []];
    }
    $maintenant = date('Y-m-d H:i:s');
    $aVenir = array_reverse(array_values(array_filter($evenements, fn($e) => $e['date_fin'] >= $maintenant)));
    $passes = array_slice(array_values(array_filter($evenements, fn($e) => $e['date_fin'] < $maintenant)), 0, 6);
    return ['a_venir' => $aVenir, 'passes' => $passes];
}

function masquerFicheAuteur(PDO $pdo, int $idUser, int $idEquipe, bool $masquer): bool
{
    $stmt = $pdo->prepare('UPDATE fiche_auteur SET est_masquee = ?, id_masquee_par = ?, date_masquage = ? WHERE id_user = ? AND est_masquee = ?');
    $stmt->execute([$masquer ? 1 : 0, $masquer ? $idEquipe : null, $masquer ? date('Y-m-d H:i:s') : null, $idUser, $masquer ? 0 : 1]);
    return $stmt->rowCount() === 1;
}

function extraitBiographie(string $biographie, int $longueur = 180): string
{
    $texte = preg_replace('/\s+/u', ' ', trim($biographie));
    return mb_strlen($texte) > $longueur ? rtrim(mb_substr($texte, 0, $longueur - 1)) . '…' : $texte;
}

function valeursFormulaireAuteur(?array $source, bool $avecMotivation): array
{
    $valeurs = ['nom_plume' => '', 'biographie' => ''];
    if ($avecMotivation) {
        $valeurs['motivation'] = '';
    }
    foreach (array_keys(AUTEUR_LIENS) as $champ) {
        $valeurs[$champ] = '';
    }
    foreach ($valeurs as $champ => $vide) {
        $valeurs[$champ] = (string) ($source[$champ] ?? $vide);
    }
    return $valeurs;
}
