<?php
/**
 * Page de test pour l'API d'historique
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Charger la configuration
if (!file_exists('config.php')) {
    die('Erreur: Le fichier config.php n\'existe pas.');
}

$config = require 'config.php';
require_once 'HomeAssistantClient.php';

// Initialiser le client
$client = new HomeAssistantClient(
    $config['home_assistant_url'],
    $config['access_token'],
    $config['timeout'] ?? 10
);

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test API Historique</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        h2 { color: #667eea; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>🧪 Test de l'API Historique</h1>
";

// Étape 1: Récupérer tous les états
echo "<div class='section'>
    <h2>1. Récupération des capteurs</h2>";

try {
    $states = $client->getStates();
    echo "<p class='success'>✓ " . count($states) . " entités trouvées</p>";

    // Filtrer pour trouver les capteurs YY
    $sensors = [];
    foreach ($states as $state) {
        $entityId = $state['entity_id'];
        $friendlyName = $state['attributes']['friendly_name'] ?? $entityId;

        if (stripos($friendlyName, 'YY') !== false) {
            if (stripos($friendlyName, 'Température') !== false ||
                stripos($friendlyName, 'Temperature') !== false ||
                stripos($friendlyName, '温度') !== false ||
                stripos($entityId, 'temperature') !== false) {
                $sensors['temperature'] = $entityId;
                echo "<p><strong>Capteur température trouvé:</strong><br>";
                echo "ID: " . htmlspecialchars($entityId) . "<br>";
                echo "Nom: " . htmlspecialchars($friendlyName) . "</p>";
            } elseif (stripos($friendlyName, 'Humidité') !== false ||
                      stripos($friendlyName, 'Humidity') !== false ||
                      stripos($friendlyName, '湿度') !== false ||
                      stripos($entityId, 'humidity') !== false) {
                $sensors['humidity'] = $entityId;
                echo "<p><strong>Capteur humidité trouvé:</strong><br>";
                echo "ID: " . htmlspecialchars($entityId) . "<br>";
                echo "Nom: " . htmlspecialchars($friendlyName) . "</p>";
            }
        }
    }

    if (empty($sensors)) {
        echo "<p class='error'>✗ Aucun capteur YY trouvé</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>✗ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Étape 2: Tester l'API d'historique pour chaque capteur
if (!empty($sensors)) {
    foreach ($sensors as $type => $entityId) {
        echo "<div class='section'>
            <h2>2. Test historique pour $type</h2>
            <p><strong>Entity ID:</strong> " . htmlspecialchars($entityId) . "</p>";

        // Test avec différentes périodes
        $periods = ['day' => '1 jour', 'week' => '1 semaine'];

        foreach ($periods as $period => $label) {
            echo "<h3>Période: $label</h3>";

            // Calculer les dates
            $now = new DateTime();
            $start = clone $now;

            switch ($period) {
                case 'day':
                    $start->modify('-1 day');
                    break;
                case 'week':
                    $start->modify('-1 week');
                    break;
            }

            echo "<p><strong>Période:</strong> " . $start->format('Y-m-d H:i:s') . " → " . $now->format('Y-m-d H:i:s') . "</p>";

            try {
                // Appeler l'API
                $history = $client->getHistory(
                    $start->format('Y-m-d\TH:i:s'),
                    null,
                    $entityId
                );

                echo "<p><strong>Réponse brute de l'API:</strong></p>";
                echo "<pre>" . htmlspecialchars(json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

                // Analyser les données
                if (!empty($history) && isset($history[0])) {
                    $dataPoints = 0;
                    $validPoints = 0;

                    foreach ($history[0] as $entry) {
                        $dataPoints++;
                        if (isset($entry['state']) && is_numeric($entry['state'])) {
                            $validPoints++;
                        }
                    }

                    echo "<p class='success'>✓ $dataPoints points de données, $validPoints valides</p>";

                    if ($validPoints > 0) {
                        // Afficher les 3 premiers points
                        echo "<p><strong>Premiers points de données:</strong></p>";
                        echo "<pre>";
                        $count = 0;
                        foreach ($history[0] as $entry) {
                            if ($count >= 3) break;
                            if (isset($entry['state']) && is_numeric($entry['state'])) {
                                echo "Temps: " . $entry['last_changed'] . " | Valeur: " . $entry['state'] . "\n";
                                $count++;
                            }
                        }
                        echo "</pre>";
                    } else {
                        echo "<p class='error'>✗ Aucun point de données valide</p>";
                    }
                } else {
                    echo "<p class='error'>✗ Aucune donnée retournée par l'API</p>";
                }

            } catch (Exception $e) {
                echo "<p class='error'>✗ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
            }

            echo "<hr>";
        }

        echo "</div>";
    }
}

// Étape 3: Tester l'endpoint api-history.php
if (!empty($sensors)) {
    echo "<div class='section'>
        <h2>3. Test de l'endpoint api-history.php</h2>";

    foreach ($sensors as $type => $entityId) {
        $url = "api-history.php?entity_id=" . urlencode($entityId) . "&period=day";
        echo "<h3>Test pour $type</h3>";
        echo "<p><strong>URL:</strong> <a href='$url' target='_blank'>$url</a></p>";

        // Lire le fichier directement
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/" . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "<p><strong>Code HTTP:</strong> $httpCode</p>";
        echo "<p><strong>Réponse:</strong></p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";

        // Décoder la réponse
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "<p class='success'>✓ API fonctionne - " . count($data['data']) . " points de données</p>";
        } else {
            echo "<p class='error'>✗ Problème avec l'API</p>";
        }
    }

    echo "</div>";
}

echo "<div class='section'>
    <h2>Navigation</h2>
    <p>
        <a href='history.php'>← Retour à la page historique</a> |
        <a href='index.php'>Dashboard principal</a>
    </p>
</div>";

echo "</body></html>";
?>
