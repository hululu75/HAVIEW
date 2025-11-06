<?php
/**
 * Liste tous les capteurs YY pour trouver le bon ID d'humidité
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require 'config.php';
require_once 'HomeAssistantClient.php';

$client = new HomeAssistantClient(
    $config['home_assistant_url'],
    $config['access_token'],
    $config['timeout'] ?? 10
);

try {
    $states = $client->getStates();
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
}

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Capteurs YY</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        h1 { color: #667eea; }
        table { width: 100%; background: white; border-collapse: collapse; }
        th { background: #667eea; color: white; padding: 10px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f0f0f0; }
        .highlight { background: #ffffcc; }
    </style>
</head>
<body>
    <h1>🔍 Tous les capteurs contenant 'YY'</h1>
    <table>
        <tr>
            <th>Entity ID</th>
            <th>Nom convivial</th>
            <th>État actuel</th>
            <th>Unité</th>
        </tr>";

$yySensors = [];
foreach ($states as $state) {
    $entityId = $state['entity_id'];
    $friendlyName = $state['attributes']['friendly_name'] ?? $entityId;

    if (stripos($friendlyName, 'YY') !== false || stripos($entityId, 'yy') !== false) {
        $unit = $state['attributes']['unit_of_measurement'] ?? '';
        $value = $state['state'];

        // Mettre en surbrillance la température et l'humidité
        $highlight = '';
        if (stripos($friendlyName, '温度') !== false || stripos($entityId, 'temperature') !== false) {
            $highlight = 'class="highlight"';
        } elseif (stripos($friendlyName, '湿度') !== false || stripos($entityId, 'humidity') !== false) {
            $highlight = 'class="highlight"';
        }

        echo "<tr $highlight>
            <td><code>" . htmlspecialchars($entityId) . "</code></td>
            <td>" . htmlspecialchars($friendlyName) . "</td>
            <td><strong>" . htmlspecialchars($value) . "</strong></td>
            <td>" . htmlspecialchars($unit) . "</td>
        </tr>";

        $yySensors[] = [
            'entity_id' => $entityId,
            'name' => $friendlyName,
            'value' => $value,
            'unit' => $unit
        ];
    }
}

echo "</table>
    <p style='margin-top: 20px;'>
        <strong>Total:</strong> " . count($yySensors) . " capteurs trouvés<br>
        <strong>Note:</strong> Les lignes surlignées en jaune sont les capteurs de température et humidité
    </p>
    <p>
        <a href='history.php' style='color: #667eea; font-weight: bold;'>← Retour à l'historique</a>
    </p>
</body>
</html>";
?>
