<?php

const NOTIFICATIONS_TYPES = [
    'don_valide' => 'carton',
    'don_refuse' => 'carton',
    'don_reserve' => 'carton',
    'don_libere' => 'carton',
    'don_annule' => 'carton',
    'don_recupere' => 'seconde-vie',
    'evenement_complet' => 'ticket',
    'evenement_place' => 'ticket',
    'evenement_proposition' => 'ticket',
    'retour' => 'colis',
    'service_client' => 'enveloppe',
    'information' => 'clochette',
];
const NOTIFICATIONS_CONSERVATION_JOURS = 90;
const NOTIFICATIONS_APERCU = 5;

function lienNotificationValide(?string $lien): ?string
{
    if ($lien === null || $lien === '') {
        return null;
    }
    return preg_match('/^[a-z0-9_]+\.php(\?[A-Za-z0-9_=&%.\-]*)?(#[A-Za-z0-9_\-]+)?$/', $lien) ? $lien : null;
}

function notifierMembre(PDO $pdo, int $idUser, string $type, string $message, ?string $lien = null): bool
{
    if ($idUser <= 0) {
        return false;
    }
    $type = isset(NOTIFICATIONS_TYPES[$type]) ? $type : 'information';
    try {
        $pdo->prepare("INSERT INTO notification (id_user, type, message, lien) VALUES (?, ?, ?, ?)")
            ->execute([$idUser, $type, mb_substr(trim($message), 0, 255), lienNotificationValide($lien)]);
        $pdo->prepare("DELETE FROM notification WHERE id_user = ? AND lu = 1 AND date_creation < DATE_SUB(NOW(), INTERVAL " . NOTIFICATIONS_CONSERVATION_JOURS . " DAY)")
            ->execute([$idUser]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function nombreNotificationsNonLues(PDO $pdo, int $idUser): int
{
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notification WHERE id_user = ? AND lu = 0");
        $stmt->execute([$idUser]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function notificationsMembre(PDO $pdo, int $idUser, int $limite = 50): array
{
    try {
        $stmt = $pdo->prepare("SELECT id_notification, type, message, lien, lu, date_creation FROM notification WHERE id_user = ? ORDER BY date_creation DESC, id_notification DESC LIMIT " . max(1, min(200, $limite)));
        $stmt->execute([$idUser]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function ouvrirNotification(PDO $pdo, int $idUser, int $idNotification): ?string
{
    try {
        $stmt = $pdo->prepare("SELECT lien FROM notification WHERE id_notification = ? AND id_user = ?");
        $stmt->execute([$idNotification, $idUser]);
        $lien = $stmt->fetchColumn();
        if ($lien === false) {
            return null;
        }
        $pdo->prepare("UPDATE notification SET lu = 1, date_lecture = COALESCE(date_lecture, NOW()) WHERE id_notification = ? AND id_user = ?")
            ->execute([$idNotification, $idUser]);
        return lienNotificationValide($lien) ?? 'notifications.php';
    } catch (PDOException $e) {
        return null;
    }
}

function marquerNotificationsLues(PDO $pdo, int $idUser): int
{
    try {
        $stmt = $pdo->prepare("UPDATE notification SET lu = 1, date_lecture = NOW() WHERE id_user = ? AND lu = 0");
        $stmt->execute([$idUser]);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        return 0;
    }
}

function iconeNotification(string $type): string
{
    return NOTIFICATIONS_TYPES[$type] ?? 'clochette';
}

function dateNotification(string $date): string
{
    $ecart = time() - strtotime($date);
    if ($ecart < 60) {
        return "à l'instant";
    }
    if ($ecart < 3600) {
        return 'il y a ' . intdiv($ecart, 60) . ' min';
    }
    if ($ecart < 86400) {
        return 'il y a ' . intdiv($ecart, 3600) . ' h';
    }
    return 'le ' . date('d/m/Y', strtotime($date));
}
