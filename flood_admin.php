<?php
// Simpele adminbeveiliging
$adminUser = 'GithubUser';
$adminPass = '!@GithubUserWachtwoord#';

if (!isset($_SERVER['PHP_AUTH_USER']) || 
    $_SERVER['PHP_AUTH_USER'] !== $adminUser || 
    $_SERVER['PHP_AUTH_PW'] !== $adminPass) {
    header('WWW-Authenticate: Basic realm="Flood admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Toegang geweigerd';
    exit;
}


$logFile = __DIR__ . '/ip_log.json';
$htaccessFile = __DIR__ . '/.htaccess';
$time = time();

// IP verwijderen?
if (isset($_GET['unblock'])) {
    $ipToUnblock = $_GET['unblock'];
    if (file_exists($logFile)) {
        $log = json_decode(file_get_contents($logFile), true);
        if (isset($log[$ipToUnblock])) {
            $log[$ipToUnblock]['blocked_until'] = 0;
            $log[$ipToUnblock]['requests'] = [];
            removeFromHtaccess($ipToUnblock, $htaccessFile);
            file_put_contents($logFile, json_encode($log));
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        }
    }
}

// Log ophalen
$blocked = [];
if (file_exists($logFile)) {
    $log = json_decode(file_get_contents($logFile), true);
    foreach ($log as $ip => $data) {
        if (isset($data['blocked_until']) && $data['blocked_until'] > $time) {
            $blocked[$ip] = $data['blocked_until'] - $time;
        }
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
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Geblokkeerde IP’s</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 2em; }
        table { border-collapse: collapse; width: 100%; background: white; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background: #eee; }
        a { color: red; text-decoration: none; }
        .center { text-align: center; margin-top: 2em; }
    </style>
</head>
<body>

<h2>Geblokkeerde IP-adressen</h2>

<?php if (count($blocked) === 0): ?>
    <p class="center">Er zijn momenteel geen geblokkeerde IP’s.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>IP-adres</th>
                <th>Resterende bloktijd (min)</th>
                <th>Actie</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($blocked as $ip => $seconds): ?>
            <tr>
                <td><?= htmlspecialchars($ip) ?></td>
                <td><?= ceil($seconds / 60) ?> min</td>
                <td><a href="?unblock=<?= urlencode($ip) ?>" onclick="return confirm('Deblokkeer <?= $ip ?>?')">🧹 Deblokkeer</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>
