<?php
require_once __DIR__ . '/notifications_lib.php';
require_once __DIR__ . '/notifications_membre_lib.php';

const ALERTE_SERIE_DELAI_HEURES = 24;
const ALERTES_SERIE_MAX = 100;

function alerteSerieActive(PDO $pdo, int $idUser, int $idSerie): bool
{
    try {
        $requete = $pdo->prepare("SELECT 1 FROM alerte_serie WHERE id_user = ? AND id_serie = ?");
        $requete->execute([$idUser, $idSerie]);
        return (bool) $requete->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function alertesSerieDisponibles(PDO $pdo): bool
{
    try {
        $pdo->query("SELECT 1 FROM alerte_serie LIMIT 0");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function abonnerAlerteSerie(PDO $pdo, int $idUser, int $idSerie): string
{
    try {
        $nombre = $pdo->prepare("SELECT COUNT(*) FROM alerte_serie WHERE id_user = ?");
        $nombre->execute([$idUser]);
        if ((int) $nombre->fetchColumn() >= ALERTES_SERIE_MAX && !alerteSerieActive($pdo, $idUser, $idSerie)) {
            return 'limite';
        }
        $pdo->prepare("INSERT IGNORE INTO alerte_serie (id_user, id_serie) VALUES (?, ?)")->execute([$idUser, $idSerie]);
        return 'ok';
    } catch (PDOException $e) {
        return 'indisponible';
    }
}

function desabonnerAlerteSerie(PDO $pdo, int $idUser, int $idSerie): bool
{
    try {
        $pdo->prepare("DELETE FROM alerte_serie WHERE id_user = ? AND id_serie = ?")->execute([$idUser, $idSerie]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function alertesSerieDe(PDO $pdo, int $idUser): array
{
    try {
        $requete = $pdo->prepare("
            SELECT a.id_serie, a.date_creation, s.nom, s.auteur,
                   (SELECT MIN(ls.id_livre) FROM livre_serie ls INNER JOIN livre l ON l.id_livre = ls.id_livre AND l.is_valide = 1 WHERE ls.id_serie = a.id_serie) AS id_livre_exemple
            FROM alerte_serie a
            INNER JOIN serie s ON s.id_serie = a.id_serie
            WHERE a.id_user = ?
            ORDER BY s.nom");
        $requete->execute([$idUser]);
        return $requete->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function notifierAlertesSerie(PDO $pdo, int $idLivre): int
{
    try {
        $livre = $pdo->prepare("SELECT titre FROM livre WHERE id_livre = ? AND is_valide = 1");
        $livre->execute([$idLivre]);
        $titre = $livre->fetchColumn();
        if ($titre === false) {
            return 0;
        }
        $dejaVendu = $pdo->prepare("SELECT 1 FROM commande_ligne WHERE id_livre = ? LIMIT 1");
        $dejaVendu->execute([$idLivre]);
        $dejaSignale = $pdo->prepare("SELECT 1 FROM alerte_serie_envoi WHERE id_livre = ? LIMIT 1");
        $dejaSignale->execute([$idLivre]);
        $nouveauTome = !$dejaVendu->fetchColumn() && !$dejaSignale->fetchColumn();
        $abonnes = $pdo->prepare("
            SELECT DISTINCT a.id_user, u.email, s.id_serie, s.nom
            FROM livre_serie ls
            INNER JOIN serie s ON s.id_serie = ls.id_serie
            INNER JOIN alerte_serie a ON a.id_serie = ls.id_serie
            INNER JOIN `user` u ON u.id = a.id_user AND u.statut NOT IN ('refuse_temporaire', 'refuse_definitif')
            WHERE ls.id_livre = ?
              AND NOT EXISTS (SELECT 1 FROM alerte_serie_envoi e WHERE e.id_user = a.id_user AND e.id_livre = ls.id_livre
                              AND e.date_envoi > NOW() - INTERVAL " . ALERTE_SERIE_DELAI_HEURES . " HOUR)
              AND NOT EXISTS (SELECT 1 FROM alerte_stock st WHERE st.id_user = a.id_user AND st.id_livre = ls.id_livre
                              AND st.date_envoi > NOW() - INTERVAL 10 MINUTE)");
        $abonnes->execute([$idLivre]);
        $destinataires = $abonnes->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return 0;
    }
    $marquer = $pdo->prepare("INSERT INTO alerte_serie_envoi (id_user, id_livre, date_envoi) VALUES (?, ?, NOW()) ON DUPLICATE KEY UPDATE date_envoi = NOW()");
    $lien = "detail_livre.php?id=$idLivre";
    $prevenus = [];
    foreach ($destinataires as $abonne) {
        $idUser = (int) $abonne['id_user'];
        if (isset($prevenus[$idUser])) {
            continue;
        }
        $prevenus[$idUser] = true;
        $message = $nouveauTome
            ? "Nouveau dans la série « {$abonne['nom']} » : « $titre » est disponible."
            : "Série « {$abonne['nom']} » : « $titre » est de nouveau en stock.";
        notifierMembre($pdo, $idUser, 'serie', $message, $lien);
        if ($abonne['email'] !== '') {
            notifierParMail($abonne['email'], $nouveauTome ? "Nouveau tome : « $titre »" : "« $titre » est de nouveau disponible",
                "Bonjour,\n\n$message\n\nVous recevez ce mail car vous suivez la série « {$abonne['nom']} ». Vous pouvez arrêter cette alerte depuis la page « Mes alertes » de votre compte.",
                SITE_URL . '/' . $lien, 'Voir le livre');
        }
        $marquer->execute([$idUser, $idLivre]);
    }
    if ($prevenus) {
        insertLog('STOCK', "Alerte série pour le livre #$idLivre : " . count($prevenus) . " lecteur(s) prévenu(s)");
    }
    return count($prevenus);
}
