<?php
/**
 * 测试不同的 statistics API 端点格式
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require 'config.php';

$entityId = 'sensor.wen_shi_du_chuan_gan_qi_yy_temperature';

// 计算时间
$now = new DateTime();
$start = clone $now;
$start->modify('-1 month');

$startISO = $start->format('Y-m-d\TH:i:s');
$endISO = $now->format('Y-m-d\TH:i:s');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Statistics API</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        h2 { color: #667eea; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 300px; overflow-y: auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🧪 Test Statistics API Endpoints</h1>
    <p><strong>Entity:</strong> $entityId</p>
    <p><strong>Start:</strong> $startISO</p>
    <p><strong>End:</strong> $endISO</p>
";

// Différents endpoints à tester
$endpoints = [
    // Format 1: statistics_during_period
    [
        'name' => 'statistics_during_period (POST)',
        'method' => 'POST',
        'url' => '/api/history/statistics_during_period',
        'data' => [
            'start_time' => $startISO,
            'end_time' => $endISO,
            'statistic_ids' => [$entityId],
            'period' => 'hour'
        ]
    ],

    // Format 2: statistics avec GET
    [
        'name' => 'statistics/<timestamp> GET',
        'method' => 'GET',
        'url' => '/api/history/statistics/' . urlencode($startISO) . '?statistic_ids=' . urlencode($entityId) . '&period=hour&end_time=' . urlencode($endISO),
        'data' => null
    ],

    // Format 3: history/period avec statistics
    [
        'name' => 'history/period pour statistics',
        'method' => 'GET',
        'url' => '/api/history/period/' . urlencode($startISO) . '?filter_entity_id=' . urlencode($entityId) . '&end_time=' . urlencode($endISO) . '&significant_changes_only=false',
        'data' => null
    ],

    // Format 4: Sans end_time
    [
        'name' => 'statistics/<timestamp> sans end_time',
        'method' => 'GET',
        'url' => '/api/history/statistics/' . urlencode($startISO) . '?statistic_ids=' . urlencode($entityId) . '&period=hour',
        'data' => null
    ],
];

foreach ($endpoints as $test) {
    echo "<div class='test'>
        <h2>📡 {$test['name']}</h2>
        <p><strong>Method:</strong> {$test['method']}</p>
        <p><strong>URL:</strong> <code>{$test['url']}</code></p>";

    if ($test['data']) {
        echo "<p><strong>POST Data:</strong></p><pre>" . json_encode($test['data'], JSON_PRETTY_PRINT) . "</pre>";
    }

    $fullUrl = rtrim($config['home_assistant_url'], '/') . $test['url'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['access_token'],
        'Content-Type: application/json',
    ]);

    if ($test['method'] === 'POST' && $test['data']) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data']));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<p><strong>HTTP Code:</strong> ";
    if ($httpCode == 200) {
        echo "<span class='success'>$httpCode ✓ OK</span></p>";

        $data = json_decode($response, true);

        // Compter les points de données
        $count = 0;
        if (is_array($data)) {
            if (isset($data[$entityId])) {
                $count = count($data[$entityId]);
            } elseif (isset($data[0])) {
                $count = count($data[0]);
            }
        }

        echo "<p><strong>Points de données:</strong> <span class='" . ($count > 0 ? 'success' : 'error') . "'>$count</span></p>";

        if ($count > 0) {
            echo "<p class='success'>✅ SUCCÈS - Cette méthode fonctionne!</p>";

            // Montrer un exemple
            if (isset($data[$entityId]) && isset($data[$entityId][0])) {
                echo "<p><strong>Premier point:</strong></p>";
                echo "<pre>" . json_encode($data[$entityId][0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            } elseif (isset($data[0]) && isset($data[0][0])) {
                echo "<p><strong>Premier point:</strong></p>";
                echo "<pre>" . json_encode($data[0][0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            }
        }

        echo "<details><summary>Réponse complète</summary>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 2000)) . (strlen($response) > 2000 ? "\n\n... (tronqué)" : "") . "</pre>";
        echo "</details>";

    } else {
        echo "<span class='error'>$httpCode ✗ ERREUR</span></p>";
        echo "<p><strong>Réponse:</strong></p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
    }

    echo "</div>";
}

echo "<p><a href='history.php'>← Retour</a></p>
</body>
</html>";
?>
