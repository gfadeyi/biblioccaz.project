<?php

function authentifierEquipeLimitee(PDO $pdo, string $identifiant, string $motDePasse, string $adresseIp): array
{
    if ($identifiant === '' || mb_strlen($identifiant) > 150 || strlen($motDePasse) > 1024 || preg_match('/[\r\n]/', $identifiant)) { throw new DomainException('Identifiants incorrects.'); }
    $cles = [hash('sha256', 'ip|' . $adresseIp), hash('sha256', 'identifiant|' . mb_strtolower($identifiant))];
    sort($cles, SORT_STRING);
    $pdo->beginTransaction();
    try {
        foreach ($cles as $cle) {
            $pdo->prepare('INSERT IGNORE INTO connexion_admin_limite (cle, debut_fenetre) VALUES (?, NOW())')->execute([$cle]);
            $requete = $pdo->prepare('SELECT echecs, bloque_jusqua > NOW() AS bloque, debut_fenetre < NOW() - INTERVAL 15 MINUTE AS ancienne FROM connexion_admin_limite WHERE cle = ? FOR UPDATE');
            $requete->execute([$cle]);
            $limite = $requete->fetch(PDO::FETCH_ASSOC);
            if ((int) $limite['bloque']) { throw new DomainException('Trop de tentatives. Réessayez après le blocage temporaire de 15 minutes.'); }
            if ((int) $limite['ancienne']) { $pdo->prepare('UPDATE connexion_admin_limite SET echecs = 0, bloque_jusqua = NULL, debut_fenetre = NOW() WHERE cle = ?')->execute([$cle]); }
        }
        $requete = $pdo->prepare('SELECT * FROM `user` WHERE pseudo = ? OR email = ?');
        $requete->execute([$identifiant, $identifiant]);
        $membre = $requete->fetch(PDO::FETCH_ASSOC);
        $empreinte = $membre['mot_de_passe'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $valide = password_verify($motDePasse, $empreinte);
        if (!$valide || !$membre || !in_array($membre['role'], ['admin', 'gestionnaire', 'moderateur'], true) || !(int) $membre['email_verifie'] || !in_array($membre['statut'], ['actif', 'valide_moderateur'], true)) {
            foreach ($cles as $cle) {
                $pdo->prepare('UPDATE connexion_admin_limite SET echecs = echecs + 1, bloque_jusqua = CASE WHEN echecs >= 5 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE) ELSE NULL END WHERE cle = ?')->execute([$cle]);
            }
            $pdo->commit();
            throw new DomainException('Identifiants incorrects ou compte non autorisé.');
        }
        foreach ($cles as $cle) { $pdo->prepare('DELETE FROM connexion_admin_limite WHERE cle = ?')->execute([$cle]); }
        $pdo->commit();
        return $membre;
    } catch (Throwable $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $erreur;
    }
}
