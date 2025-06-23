<?php
// Instellingen
$maxRequests = 50;            // Maximaal aantal verzoeken binnen timeWindow
$timeWindow = 60;             // In seconden
$blockDuration = 600;         // In seconden (bijv. 600 = 10 minuten)

$logFile = __DIR__ . '/ip_log.json';
$htaccessFile = __DIR__ . '/.htaccess';
$ip = $_SERVER['REMOTE_ADDR'];
$time = time();

// 1. Lees de log
$log = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];

// 2. Verwijder oude verzoeken en check op deblokkering
$changed = false;
$blockedIPs = [];

foreach ($log as $loggedIp => $data) {
    // Initialiseer structuur indien nodig
    if (!isset($data['requests'])) $data['requests'] = [];
    if (!isset($data['blocked_until'])) $data['blocked_until'] = 0;

    // Verwijder oude requests
    $data['requests'] = array_filter($data['requests'], function($t) use ($time, $blockDuration) {
        return ($time - $t) <= $blockDuration;
    });

    // Indien geblokkeerd maar tijd is verstreken => deblokkeren
    if ($data['blocked_until'] > 0 && $time >= $data['blocked_until']) {
        $data['blocked_until'] = 0;
        removeFromHtaccess($loggedIp, $htaccessFile);
        $changed = true;
    }

    // Update
    $log[$loggedIp] = $data;

    // Houd lijst van nog geblokkeerde IP’s bij
    if ($data['blocked_until'] > $time) {
        $blockedIPs[] = $loggedIp;
    }
}

// 3. Voeg huidige request toe
if (!isset($log[$ip])) {
    $log[$ip] = ['requests' => [], 'blocked_until' => 0];
}
$log[$ip]['requests'][] = $time;

// 4. Check of IP moet worden geblokkeerd
$recent = array_filter($log[$ip]['requests'], function($t) use ($time, $timeWindow) {
    return ($time - $t) <= $timeWindow;
});

if (count($recent) > $maxRequests) {
    if ($log[$ip]['blocked_until'] == 0) {
        $log[$ip]['blocked_until'] = $time + $blockDuration;
        addToHtaccess($ip, $htaccessFile);
        $changed = true;
    }

    // Blokkeer toegang
    header('HTTP/1.1 429 Too Many Requests');
    echo "Je hebt tijdelijk te veel verzoeken gedaan. Probeer het over " . ceil(($log[$ip]['blocked_until'] - $time)/60) . " minuut/minuten opnieuw.";
    file_put_contents($logFile, json_encode($log));
    exit;
}

// 5. Opslaan log indien nodig
if ($changed || !empty($log[$ip]['requests'])) {
    file_put_contents($logFile, json_encode($log));
}


// --- Functies ---
function addToHtaccess($ip, $file) {
    $denyLine = "Deny from $ip";
    $htaccess = file_get_contents($file);

    // Alleen toevoegen als het IP er nog niet in staat
    if (strpos($htaccess, $denyLine) === false) {
        file_put_contents($file, "\n# Blocked by flood protection\n$denyLine", FILE_APPEND);
    }        
}

function removeFromHtaccess($ip, $file) {
    if (!file_exists($file)) return;
    $lines = file($file);
    $newLines = [];
    foreach ($lines as $line) {
        if (trim($line) !== "Deny from $ip") {
            $newLines[] = $line;
        }
    }
    file_put_contents($file, implode("", $newLines));
}
?>
