<?php

function changementEmailDisponible(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT id_user FROM changement_email LIMIT 1');
        return true;
    } catch (PDOException $erreur) {
        if ((int) ($erreur->errorInfo[1] ?? 0) === 1146) { return false; }
        throw $erreur;
    }
}

function compteAutoriseChangementEmail(array $membre): bool
{
    return (int) $membre['email_verifie'] === 1 && in_array($membre['statut'], ['actif', 'valide_moderateur'], true);
}

function demanderChangementEmail(PDO $pdo, int $idUser, string $nouvelleEmail, string $motDePasse): array
{
    $nouvelleEmail = trim($nouvelleEmail);
    if (strlen($nouvelleEmail) > 150 || !filter_var($nouvelleEmail, FILTER_VALIDATE_EMAIL)) { throw new DomainException('Indiquez une adresse email valide de 150 caractères maximum.'); }
    if ($motDePasse === '' || strlen($motDePasse) > 1024) { throw new DomainException('Indiquez votre mot de passe actuel.'); }
    $pdo->beginTransaction();
    try {
        $requete = $pdo->prepare('SELECT id,pseudo,email,mot_de_passe,statut,email_verifie FROM user WHERE id = ? FOR UPDATE');
        $requete->execute([$idUser]);
        $membre = $requete->fetch(PDO::FETCH_ASSOC);
        if (!$membre || !compteAutoriseChangementEmail($membre)) { throw new DomainException('Ce compte ne permet pas de changer d’adresse email.'); }
        $requete = $pdo->prepare('SELECT *, TIMESTAMPDIFF(SECOND, derniere_tentative, NOW()) AS delai, TIMESTAMPDIFF(SECOND, debut_fenetre, NOW()) AS age_fenetre FROM changement_email WHERE id_user = ? FOR UPDATE');
        $requete->execute([$idUser]);
        $precedente = $requete->fetch(PDO::FETCH_ASSOC);
        if ($precedente && (int) $precedente['delai'] < 60) { throw new DomainException('Patientez une minute entre deux demandes.'); }
        $nouvelleFenetre = !$precedente || (int) $precedente['age_fenetre'] >= 3600;
        if (!$nouvelleFenetre && (int) $precedente['tentatives'] >= 5) { throw new DomainException('Trop de tentatives. Réessayez dans une heure.'); }
        $tentatives = $nouvelleFenetre ? 1 : (int) $precedente['tentatives'] + 1;
        $pdo->prepare('INSERT INTO changement_email (id_user,derniere_tentative,debut_fenetre,tentatives) VALUES (?,NOW(),NOW(),?) ON DUPLICATE KEY UPDATE derniere_tentative=NOW(),debut_fenetre=IF(?,NOW(),debut_fenetre),tentatives=?')->execute([$idUser,$tentatives,$nouvelleFenetre ? 1 : 0,$tentatives]);
        if (!password_verify($motDePasse, $membre['mot_de_passe'])) {
            $pdo->commit();
            throw new DomainException('Le mot de passe actuel est incorrect.');
        }
        if (strcasecmp($membre['email'], $nouvelleEmail) === 0) {
            $pdo->commit();
            throw new DomainException('Cette adresse est déjà celle de votre compte.');
        }
        $requete = $pdo->prepare('SELECT id FROM user WHERE email = ? AND id <> ?');
        $requete->execute([$nouvelleEmail,$idUser]);
        if ($requete->fetchColumn()) {
            $pdo->commit();
            throw new DomainException('Cette adresse email est déjà utilisée.');
        }
        $jeton = bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE changement_email SET nouvelle_email=?,ancienne_email=?,jeton_hash=?,empreinte_mdp=?,date_expiration=DATE_ADD(NOW(),INTERVAL 1 HOUR) WHERE id_user=?')->execute([$nouvelleEmail,$membre['email'],hash('sha256',$jeton),hash('sha256',$membre['mot_de_passe']),$idUser]);
        $pdo->commit();
        return ['id_user'=>$idUser,'pseudo'=>$membre['pseudo'],'ancienne_email'=>$membre['email'],'nouvelle_email'=>$nouvelleEmail,'jeton'=>$jeton];
    } catch (Throwable $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $erreur;
    }
}

function annulerChangementEmail(PDO $pdo, int $idUser, ?string $empreinte = null): void
{
    $requete = $pdo->prepare('UPDATE changement_email SET nouvelle_email=NULL,ancienne_email=NULL,jeton_hash=NULL,empreinte_mdp=NULL,date_expiration=NULL WHERE id_user=?' . ($empreinte !== null ? ' AND jeton_hash=?' : ''));
    $requete->execute($empreinte !== null ? [$idUser,$empreinte] : [$idUser]);
}

function demandeChangementEmailEnCours(PDO $pdo, int $idUser): ?array
{
    $requete = $pdo->prepare('SELECT nouvelle_email,date_expiration FROM changement_email WHERE id_user=? AND jeton_hash IS NOT NULL AND date_expiration>NOW()');
    $requete->execute([$idUser]);
    return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
}

function demandeEmailParJeton(PDO $pdo, string $jeton): ?array
{
    if (!preg_match('/\A[a-f0-9]{64}\z/', $jeton)) { return null; }
    $requete = $pdo->prepare('SELECT id_user,nouvelle_email,date_expiration FROM changement_email WHERE jeton_hash=? AND date_expiration>NOW()');
    $requete->execute([hash('sha256',$jeton)]);
    return $requete->fetch(PDO::FETCH_ASSOC) ?: null;
}

function confirmerChangementEmail(PDO $pdo, string $jeton, ?int $idConnecte = null): array
{
    $demande = demandeEmailParJeton($pdo,$jeton);
    if (!$demande || ($idConnecte !== null && $idConnecte !== (int) $demande['id_user'])) { throw new DomainException('Ce lien est invalide, expiré ou ne correspond pas au compte connecté.'); }
    $pdo->beginTransaction();
    try {
        $requete = $pdo->prepare('SELECT id,pseudo,email,mot_de_passe,statut,email_verifie FROM user WHERE id=? FOR UPDATE');
        $requete->execute([$demande['id_user']]);
        $membre = $requete->fetch(PDO::FETCH_ASSOC);
        $requete = $pdo->prepare('SELECT *, (date_expiration>NOW()) AS valide FROM changement_email WHERE id_user=? FOR UPDATE');
        $requete->execute([$demande['id_user']]);
        $actuelle = $requete->fetch(PDO::FETCH_ASSOC);
        if (!$membre || !$actuelle || !$actuelle['valide'] || !compteAutoriseChangementEmail($membre) || !is_string($actuelle['jeton_hash']) || !hash_equals($actuelle['jeton_hash'],hash('sha256',$jeton)) || !hash_equals($actuelle['ancienne_email'],$membre['email']) || !hash_equals($actuelle['empreinte_mdp'],hash('sha256',$membre['mot_de_passe']))) { throw new DomainException('Ce lien est invalide ou les informations du compte ont changé. Demandez un nouveau lien.'); }
        $requete = $pdo->prepare('SELECT id FROM user WHERE email=? AND id<>?');
        $requete->execute([$actuelle['nouvelle_email'],$membre['id']]);
        if ($requete->fetchColumn()) { throw new DomainException('Cette adresse email est désormais utilisée par un autre compte.'); }
        $pdo->prepare('UPDATE user SET email=?,email_verifie=1,email_token=NULL,reset_token_hash=NULL,reset_expire=NULL WHERE id=?')->execute([$actuelle['nouvelle_email'],$membre['id']]);
        annulerChangementEmail($pdo,(int) $membre['id']);
        $pdo->prepare('UPDATE session_utilisateur SET date_revocation=NOW(),id_revoque_par=? WHERE id_user=? AND date_revocation IS NULL')->execute([$membre['id'],$membre['id']]);
        $pdo->commit();
        return ['id_user'=>(int) $membre['id'],'pseudo'=>$membre['pseudo'],'ancienne_email'=>$membre['email'],'nouvelle_email'=>$actuelle['nouvelle_email']];
    } catch (PDOException $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        if ((int) ($erreur->errorInfo[1] ?? 0) === 1062) { throw new DomainException('Cette adresse email est désormais utilisée par un autre compte.'); }
        throw $erreur;
    } catch (Throwable $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $erreur;
    }
}
