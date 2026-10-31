<?php

function archivageCatalogueDisponible(PDO $pdo): bool
{
    try {
        $pdo->prepare('SELECT id_livre FROM livre_archive LIMIT 0')->execute();
        return true;
    } catch (PDOException $erreur) {
        if (($erreur->errorInfo[1] ?? 0) !== 1146) { throw $erreur; }
        return false;
    }
}

function livreEstArchive(PDO $pdo, int $idLivre): bool
{
    if (!archivageCatalogueDisponible($pdo)) { return false; }
    $requete = $pdo->prepare('SELECT actif FROM livre_archive WHERE id_livre = ?');
    $requete->execute([$idLivre]);
    return (bool) $requete->fetchColumn();
}

function changerArchivageLivre(PDO $pdo, int $idLivre, int $idActeur, bool $archiver): array
{
    try {
        $pdo->beginTransaction();
        $requete = $pdo->prepare('SELECT role, statut, email_verifie FROM `user` WHERE id = ? FOR UPDATE');
        $requete->execute([$idActeur]);
        $acteur = $requete->fetch(PDO::FETCH_ASSOC);
        if (!$acteur || !in_array($acteur['statut'], ['actif', 'valide_moderateur'], true) || !(int) $acteur['email_verifie'] || !rolePossedeDroit($pdo, $acteur['role'], 'catalogue', DROITS)) {
            throw new DomainException('Vous ne pouvez pas archiver ce livre.');
        }
        $requete = $pdo->prepare('SELECT is_valide FROM livre WHERE id_livre = ? FOR UPDATE');
        $requete->execute([$idLivre]);
        $livre = $requete->fetch(PDO::FETCH_ASSOC);
        if (!$livre) { throw new DomainException('Livre introuvable.'); }
        $requete = $pdo->prepare('SELECT * FROM livre_archive WHERE id_livre = ? FOR UPDATE');
        $requete->execute([$idLivre]);
        $archive = $requete->fetch(PDO::FETCH_ASSOC);
        if ($archiver === (bool) ($archive['actif'] ?? false)) { throw new DomainException('Ce livre est déjà dans cet état.'); }
        if ($archiver) {
            $pdo->prepare('INSERT INTO livre_archive (id_livre, ancienne_validation, id_archive_par) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE ancienne_validation = VALUES(ancienne_validation), actif = 1, id_archive_par = VALUES(id_archive_par), date_archivage = NOW(), date_reactivation = NULL')
                ->execute([$idLivre, (int) $livre['is_valide'], $idActeur]);
            $pdo->prepare('UPDATE livre SET is_valide = 0 WHERE id_livre = ?')->execute([$idLivre]);
        } else {
            $pdo->prepare('UPDATE livre SET is_valide = ? WHERE id_livre = ?')->execute([(int) $archive['ancienne_validation'], $idLivre]);
            $pdo->prepare('UPDATE livre_archive SET actif = 0, date_reactivation = NOW() WHERE id_livre = ?')->execute([$idLivre]);
        }
        $pdo->commit();
        insertLog('CATALOGUE', ($archiver ? 'Archivage' : 'Réactivation') . ' du livre #' . $idLivre . ', historique conservé');
        return ['success', $archiver ? 'Fiche archivée. Les exemplaires et l’historique sont conservés.' : 'Fiche réactivée avec son statut de validation précédent.'];
    } catch (DomainException $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['warning', $erreur->getMessage()];
    } catch (Throwable $erreur) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('Archivage catalogue : ' . $erreur->getMessage());
        return ['danger', 'Aucune modification. Vérifiez que le suivi des archives est installé.'];
    }
}
