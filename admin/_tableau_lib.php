<?php
if (!function_exists('estEquipe')) { http_response_code(404); exit(); }

function compter(PDO $pdo, string $sql): float
{
    try {
        return (float) $pdo->query($sql)->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function tendance(float $actuel, float $precedent): array
{
    if ($precedent <= 0) {
        return $actuel > 0 ? ['hausse', 'arrow-up-right', 'Nouveau'] : ['stable', 'dash', 'Stable'];
    }
    $variation = (int) round(($actuel - $precedent) / $precedent * 100);
    if ($variation === 0) {
        return ['stable', 'dash', 'Stable'];
    }
    return $variation > 0 ? ['hausse', 'arrow-up-right', '+' . $variation . ' %'] : ['baisse', 'arrow-down-right', $variation . ' %'];
}

function depuis(string $date): string
{
    $secondes = time() - strtotime($date);
    if ($secondes < 3600) {
        return 'il y a ' . max(1, intdiv($secondes, 60)) . ' min';
    }
    if ($secondes < 86400) {
        return 'il y a ' . intdiv($secondes, 3600) . ' h';
    }
    if ($secondes < 7 * 86400) {
        $jours = intdiv($secondes, 86400);
        return 'il y a ' . $jours . ' jour' . ($jours > 1 ? 's' : '');
    }
    return 'le ' . date('d/m/Y', strtotime($date));
}
