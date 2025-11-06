<?php
/**
 * Test statistics API avec méthode GET et paramètres URL
 * Basé sur la documentation Home Assistant officielle
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require 'config.php';

$entityId = 'sensor.wen_shi_du_chuan_gan_qi_yy_temperature';

// Tester différentes périodes
$now = new DateTime();
$periods = [
    '1 week' => '-1 week',
    '1 month' => '-1 month',
    '3 months' => '-3 months',
    '6 months' => '-6 months',
];

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test Statistics GET API</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .test { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        h2 { color: #667eea; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; max-height: 300px; overflow-y: auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #1890ff; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>📊 Test Statistics API (GET Method)</h1>
    <p><strong>Entity:</strong> $entityId</p>

    <div class='info'>
        <strong>ℹ️ Testing different statistics API endpoints:</strong><br>
        1. <code>/api/history/statistics_during_period</code> avec paramètres GET<br>
        2. Avec différentes valeurs de period (hour, day, 5minute)<br>
        3. Avec et sans fixed_period<br>
    </div>
";

foreach ($periods as $name => $modifier) {
    $start = clone $now;
    $start->modify($modifier);

    echo "<div class='test'>
        <h2>📅 Période: $name</h2>
        <p><strong>From:</strong> " . $start->format('Y-m-d H:i:s') . "</p>
        <p><strong>To:</strong> " . $now->format('Y-m-d H:i:s') . "</p>";

    // Format ISO pour les timestamps
    $startISO = $start->format('Y-m-d\TH:i:s');
    $endISO = $now->format('Y-m-d\TH:i:s');

    // Différents endpoints à tester avec GET
    $tests = [
        [
            'name' => 'statistics_during_period (GET, period=hour)',
            'url' => '/api/history/statistics_during_period?' . http_build_query([
                'start_time' => $startISO,
                'end_time' => $endISO,
                'statistic_ids' => $entityId,
                'period' => 'hour'
            ])
        ],
        [
            'name' => 'statistics_during_period (GET, period=day)',
            'url' => '/api/history/statistics_during_period?' . http_build_query([
                'start_time' => $startISO,
                'end_time' => $endISO,
                'statistic_ids' => $entityId,
                'period' => 'day'
            ])
        ],
        [
            'name' => 'statistics_during_period (GET, period=5minute)',
            'url' => '/api/history/statistics_during_period?' . http_build_query([
                'start_time' => $startISO,
                'end_time' => $endISO,
                'statistic_ids' => $entityId,
                'period' => '5minute'
            ])
        ],
        [
            'name' => 'statistics_during_period (GET, avec fixed_period)',
            'url' => '/api/history/statistics_during_period?' . http_build_query([
                'start_time' => $startISO,
                'statistic_ids' => $entityId,
                'period' => 'hour',
                'fixed_period' => 'true'
            ])
        ],
    ];

    foreach ($tests as $test) {
        echo "<h3>🔗 {$test['name']}</h3>";
        echo "<p><strong>URL:</strong> <code>" . htmlspecialchars($test['url']) . "</code></p>";

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

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        echo "<p><strong>HTTP Code:</strong> ";
        if ($httpCode == 200) {
            echo "<span class='success'>$httpCode ✓</span></p>";

            $data = json_decode($response, true);
            $count = 0;

            if (is_array($data)) {
                // Les statistics peuvent être dans différentes structures
                if (isset($data[$entityId])) {
                    $count = count($data[$entityId]);
                    echo "<p><strong>Points de données:</strong> <span class='success'>$count ✓</span></p>";

                    if ($count > 0) {
                        echo "<p class='success'>✅ TROUVÉ DES STATISTIQUES LONG TERME!</p>";

                        // Afficher le premier et dernier point
                        echo "<p><strong>Premier point:</strong></p>";
                        echo "<pre>" . json_encode($data[$entityId][0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

                        if ($count > 1) {
                            echo "<p><strong>Dernier point:</strong></p>";
                            echo "<pre>" . json_encode($data[$entityId][$count-1], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                        }

                        echo "<details><summary>Voir tous les points ($count)</summary>";
                        echo "<pre>" . json_encode($data[$entityId], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                        echo "</details>";
                    } else {
                        echo "<p class='warning'>⚠️ API répond 200 mais aucune donnée pour cet entité</p>";
                    }
                } else {
                    echo "<p class='warning'>⚠️ Structure de réponse inattendue</p>";
                    echo "<p><strong>Clés trouvées:</strong> " . implode(', ', array_keys($data)) . "</p>";
                    echo "<details><summary>Réponse complète</summary>";
                    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                    echo "</details>";
                }
            } else {
                echo "<p class='error'>❌ Réponse n'est pas un tableau JSON valide</p>";
                echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
            }
        } elseif ($httpCode == 404) {
            echo "<span class='error'>$httpCode ✗</span></p>";
            echo "<p class='error'>❌ Endpoint non trouvé - Les statistics ne sont peut-être pas activées</p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        } else {
            echo "<span class='error'>$httpCode ✗</span></p>";
            if ($curlError) {
                echo "<p class='error'>cURL Error: $curlError</p>";
            }
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        }

        echo "<hr>";
    }

    echo "</div>";
}

echo "
    <div class='info'>
        <h3>💡 Diagnostic:</h3>
        <ul>
            <li>Si tous retournent <strong>404</strong>: Les statistics long-terme ne sont pas activées dans votre Home Assistant</li>
            <li>Si retourne <strong>200 avec 0 points</strong>: L'API existe mais pas de données pour cet entité (peut-être pas dans recorder)</li>
            <li>Si retourne <strong>200 avec données</strong>: ✅ Les statistics long-terme fonctionnent!</li>
        </ul>
        <p><strong>Note:</strong> Pour activer les statistics, l'entité doit être configuré dans <code>recorder:</code> avec <code>state_class:</code> dans Home Assistant.</p>
    </div>

    <p><a href='history.php'>← Retour</a></p>
</body>
</html>";
?>
