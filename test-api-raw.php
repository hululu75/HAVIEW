<?php
/**
 * 测试直接调用 Home Assistant history API
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require 'config.php';

// 测试实体ID
$entityId = 'sensor.wen_shi_du_chuan_gan_qi_yy_temperature';

// 不同的时间段
$periods = [
    'day' => '-1 day',
    'week' => '-1 week',
    'month' => '-1 month',
    'year' => '-1 year'
];

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test API History Raw</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .period { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        h2 { color: #667eea; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 400px; overflow-y: auto; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>🧪 Test API History - Raw</h1>
    <p><strong>Entity ID:</strong> $entityId</p>
";

foreach ($periods as $periodName => $periodModifier) {
    echo "<div class='period'>
        <h2>📅 Période: $periodName ($periodModifier)</h2>";

    // Calculer les dates
    $now = new DateTime();
    $start = clone $now;
    $start->modify($periodModifier);

    // Format ISO 8601 avec timezone
    $startISO = $start->format('c'); // Format complet avec timezone
    $startSimple = $start->format('Y-m-d\TH:i:s');

    echo "<p><strong>Date début (ISO 8601):</strong> $startISO</p>";
    echo "<p><strong>Date début (simple):</strong> $startSimple</p>";
    echo "<p><strong>Date fin:</strong> " . $now->format('c') . "</p>";

    // Tester différents formats d'URL
    $urls = [
        "Format ISO complet" => "/api/history/period/$startISO?filter_entity_id=$entityId",
        "Format simple" => "/api/history/period/$startSimple?filter_entity_id=$entityId",
        "Sans timezone" => "/api/history/period/" . $start->format('Y-m-d\TH:i:s') . "?filter_entity_id=$entityId",
        "Avec minimal_response" => "/api/history/period/$startISO?filter_entity_id=$entityId&minimal_response=true",
    ];

    foreach ($urls as $formatName => $urlPath) {
        echo "<h3>🔗 Test: $formatName</h3>";
        echo "<p><code>" . htmlspecialchars($urlPath) . "</code></p>";

        $fullUrl = rtrim($config['home_assistant_url'], '/') . $urlPath;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['access_token'],
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "<p><strong>HTTP Code:</strong> ";
        if ($httpCode == 200) {
            echo "<span class='success'>$httpCode OK</span></p>";

            $data = json_decode($response, true);
            $count = 0;
            if (is_array($data) && isset($data[0])) {
                $count = count($data[0]);
            }

            echo "<p><strong>Nombre de points:</strong> <span class='" . ($count > 0 ? 'success' : 'error') . "'>$count</span></p>";

            if ($count > 0) {
                echo "<p><strong>Premier point:</strong></p>";
                echo "<pre>" . htmlspecialchars(json_encode($data[0][0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

                echo "<p><strong>Dernier point:</strong></p>";
                echo "<pre>" . htmlspecialchars(json_encode($data[0][$count-1], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
            } else {
                echo "<p class='error'>⚠️ Aucune donnée retournée</p>";
            }

            // Montrer un extrait de la réponse
            echo "<details><summary>Réponse complète (cliquer pour afficher)</summary>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 2000)) . (strlen($response) > 2000 ? "\n\n... (tronqué)" : "") . "</pre>";
            echo "</details>";

        } else {
            echo "<span class='error'>$httpCode ERREUR</span></p>";
            echo "<p><strong>Réponse:</strong></p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }

        echo "<hr>";
    }

    echo "</div>";
}

echo "<p><a href='history.php'>← Retour à l'historique</a></p>
</body>
</html>";
?>
