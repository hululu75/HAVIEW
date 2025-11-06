<?php
/**
 * 测试长期统计数据 (Long-term Statistics)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require 'config.php';

$entityId = 'sensor.wen_shi_du_chuan_gan_qi_yy_temperature';

// 测试不同的时间范围
$now = new DateTime();
$periods = [
    '3 months' => '-3 months',
    '6 months' => '-6 months',
    '1 year' => '-1 year',
    '2 years' => '-2 years',
];

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Long-term Statistics</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        h2 { color: #667eea; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 300px; overflow-y: auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #1890ff; }
    </style>
</head>
<body>
    <h1>📊 Test Long-term Statistics API</h1>
    <p><strong>Entity:</strong> $entityId</p>

    <div class='info'>
        <strong>ℹ️ À propos des Long-term Statistics:</strong><br>
        Home Assistant stocke les statistiques long terme séparément de l'historique court terme.
        Ces statistiques contiennent les moyennes horaires/quotidiennes et peuvent être conservées pendant des années!
    </div>
";

foreach ($periods as $name => $modifier) {
    $start = clone $now;
    $start->modify($modifier);

    echo "<div class='test'>
        <h2>📅 Période: $name</h2>
        <p><strong>From:</strong> " . $start->format('Y-m-d H:i:s') . "</p>
        <p><strong>To:</strong> " . $now->format('Y-m-d H:i:s') . "</p>";

    // Tester plusieurs formats d'endpoint statistics
    $endpoints = [
        // Format 1
        [
            'name' => 'statistics_during_period (POST)',
            'method' => 'POST',
            'url' => '/api/history/statistics_during_period',
            'data' => [
                'start_time' => $start->format('Y-m-d\TH:i:s'),
                'end_time' => $now->format('Y-m-d\TH:i:s'),
                'statistic_ids' => [$entityId],
                'period' => 'hour'
            ]
        ],
        // Format 2
        [
            'name' => 'statistics with POST data',
            'method' => 'POST',
            'url' => '/api/history/statistics',
            'data' => [
                'start_time' => $start->format('Y-m-d\TH:i:s'),
                'statistic_ids' => [$entityId]
            ]
        ],
    ];

    foreach ($endpoints as $test) {
        echo "<h3>🔗 {$test['name']}</h3>";

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

        if ($test['method'] === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['data']));
            echo "<p><strong>POST Data:</strong></p>";
            echo "<pre>" . json_encode($test['data'], JSON_PRETTY_PRINT) . "</pre>";
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "<p><strong>HTTP Code:</strong> ";
        if ($httpCode == 200) {
            echo "<span class='success'>$httpCode ✓</span></p>";

            $data = json_decode($response, true);
            $count = 0;

            if (is_array($data)) {
                if (isset($data[$entityId])) {
                    $count = count($data[$entityId]);
                    echo "<p><strong>Points de données:</strong> <span class='success'>$count ✓</span></p>";

                    if ($count > 0) {
                        echo "<p class='success'>✅ TROUVÉ DES DONNÉES LONG TERME!</p>";

                        // Afficher le premier et dernier point
                        echo "<p><strong>Premier point:</strong></p>";
                        echo "<pre>" . json_encode($data[$entityId][0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

                        if ($count > 1) {
                            echo "<p><strong>Dernier point:</strong></p>";
                            echo "<pre>" . json_encode($data[$entityId][$count-1], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                        }

                        echo "<details><summary>Tous les points ($count)</summary>";
                        echo "<pre>" . json_encode($data[$entityId], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                        echo "</details>";
                    }
                } else {
                    echo "<p><strong>Structure de réponse:</strong> " . (is_array($data) ? 'Array avec ' . count($data) . ' clés' : gettype($data)) . "</p>";
                    echo "<pre>" . htmlspecialchars(substr(json_encode($data, JSON_PRETTY_PRINT), 0, 500)) . "</pre>";
                }
            }
        } else {
            echo "<span class='error'>$httpCode ✗</span></p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        }

        echo "<hr>";
    }

    echo "</div>";
}

echo "<p><a href='history.php'>← Retour</a></p>
</body>
</html>";
?>
