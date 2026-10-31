<?php

function chargerParametreSite(PDO $pdo, string $cle, array $defaut): array
{
    try {
        $requete = $pdo->prepare('SELECT configuration, version FROM parametre_site WHERE cle = ?');
        $requete->execute([$cle]);
        $ligne = $requete->fetch(PDO::FETCH_ASSOC);
        return ['disponible' => true, 'version' => $ligne ? (int) $ligne['version'] : 0,
            'valeurs' => $ligne ? json_decode($ligne['configuration'], true, 512, JSON_THROW_ON_ERROR) : $defaut];
    } catch (PDOException $erreur) {
        if (($erreur->errorInfo[1] ?? 0) !== 1146) { throw $erreur; }
        return ['disponible' => false, 'version' => 0, 'valeurs' => $defaut];
    }
}

function enregistrerParametreSite(PDO $pdo, string $cle, array $valeurs, array $defaut, int $version, int $idAdministrateur): bool
{
    $pdo->beginTransaction();
    try {
        $requete = $pdo->prepare('SELECT role, statut, email_verifie FROM `user` WHERE id = ? FOR UPDATE');
        $requete->execute([$idAdministrateur]);
        $administrateur = $requete->fetch(PDO::FETCH_ASSOC);
        if (!$administrateur || $administrateur['role'] !== 'admin' || $administrateur['statut'] !== 'actif' || !(int) $administrateur['email_verifie']) {
            throw new DomainException('Seul un administrateur actif peut modifier ces paramètres.');
        }
        $pdo->prepare('INSERT IGNORE INTO parametre_site (cle, configuration) VALUES (?, ?)')->execute([$cle, json_encode($defaut, JSON_THROW_ON_ERROR)]);
        $requete = $pdo->prepare('SELECT configuration, version FROM parametre_site WHERE cle = ? FOR UPDATE');
        $requete->execute([$cle]);
        $ancien = $requete->fetch(PDO::FETCH_ASSOC);
        if ((int) $ancien['version'] !== $version) { throw new DomainException('Les paramètres ont changé. Rechargez la page avant de recommencer.'); }
        $json = json_encode($valeurs, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (json_decode($ancien['configuration'], true, 512, JSON_THROW_ON_ERROR) === $valeurs) { $pdo->commit(); return false; }
        $pdo->prepare('INSERT INTO parametre_site_historique (cle, version, ancienne_configuration, nouvelle_configuration, id_modifie_par) VALUES (?, ?, ?, ?, ?)')
            ->execute([$cle, $version + 1, $ancien['configuration'], $json, $idAdministrateur]);
        $pdo->prepare('UPDATE parametre_site SET configuration = ?, version = version + 1, id_modifie_par = ?, date_modification = NOW() WHERE cle = ?')
            ->execute([$json, $idAdministrateur, $cle]);
        $pdo->commit();
        return true;
    } catch (Throwable $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $erreur;
    }
}

function historiqueParametreSite(PDO $pdo, string $cle): array
{
    $requete = $pdo->prepare('SELECT h.version, h.date_modification, u.pseudo FROM parametre_site_historique h LEFT JOIN `user` u ON u.id = h.id_modifie_par WHERE h.cle = ? ORDER BY h.version DESC LIMIT 20');
    $requete->execute([$cle]);
    return $requete->fetchAll(PDO::FETCH_ASSOC);
}
