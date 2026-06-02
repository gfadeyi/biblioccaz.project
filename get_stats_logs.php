<?php
require_once 'config.php';

$logFile = __DIR__ . '/secure_data/logs.txt';
$response = [
    'totalConnexions' => 0,
    'sessionMoyenne' => 0,
    'connexionsParJour' => [],
    'listeLogs' => []
];

if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        $linesArr = array_reverse($lines);
        $totalSeconds = 0;
        $sessionCount = 0;
        
        foreach ($linesArr as $line) {
            $date = "Inconnue";
            if (preg_match('/^\[([^\]]+)\]/', $line, $m)) $date = $m[1];
            
            $type = "VISITE";
            if (strpos($line, '[CONNEXION]') !== false) $type = "CONNEXION";
            elseif (strpos($line, '[DÉCONNEXION]') !== false) $type = "DÉCONNEXION";
            elseif (strpos($line, '[MODERATION]') !== false) $type = "MODERATION";
            
            $user = "Invité";
            if (preg_match('/USER_ID:\s*([^\]]+)\]/', $line, $m)) {
                $val = trim($m[1]);
                if ($val !== 'ANONYME') $user = $val;
            }
            
            $desc = "Aucune description";
            $parts = explode(' - ', $line);
            if (count($parts) > 1) $desc = end($parts);

            if (count($response['listeLogs']) < 100) {
                $response['listeLogs'][] = [
                    'date' => $date,
                    'type' => $type,
                    'user' => $user,
                    'desc' => $desc,
                    'ip' => (preg_match('/IP:\s*([^\]]+)\]/', $line, $m) ? $m[1] : '...')
                ];
            }
            
            if ($type === 'CONNEXION') {
                $response['totalConnexions']++;
                $jour = explode(' ', $date)[0];
                $response['connexionsParJour'][$jour] = ($response['connexionsParJour'][$jour] ?? 0) + 1;
            }
            
            if ($type === 'DÉCONNEXION') {
                if (preg_match('/(\d+)\s*(seconde|secondes|s)/i', $line, $m)) {
                    $totalSeconds += intval($m[1]);
                    $sessionCount++;
                } elseif (preg_match('/Total:\s*(\d+)/i', $line, $m)) {
                    $totalSeconds += intval($m[1]);
                    $sessionCount++;
                }
            }
        }
        
        if ($sessionCount > 0) {
            $response['sessionMoyenne'] = round($totalSeconds / $sessionCount);
        }
    }
}
echo json_encode($response);