<?php
/**
 * Sensor Discovery Tool - Find all available sensors
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!file_exists('config.php')) {
    die('Error: config.php does not exist');
}

$config = require 'config.php';
require_once 'HomeAssistantClient.php';

$client = new HomeAssistantClient(
    $config['home_assistant_url'],
    $config['access_token'],
    $config['timeout'] ?? 10
);

// Search filter from URL
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensor Discovery Tool</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .search-box {
            margin: 20px 0;
        }
        .search-box input {
            width: 100%;
            max-width: 500px;
            padding: 12px 20px;
            font-size: 16px;
            border: 2px solid #667eea;
            border-radius: 10px;
            box-sizing: border-box;
        }
        .sensor-group {
            margin: 30px 0;
        }
        .sensor-group h2 {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .sensor-card {
            background: #f8f9fa;
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 5px solid #667eea;
        }
        .sensor-card.temperature {
            border-left-color: #f5576c;
            background: linear-gradient(90deg, #fff 0%, #ffe5e9 100%);
        }
        .sensor-card.humidity {
            border-left-color: #00f2fe;
            background: linear-gradient(90deg, #fff 0%, #e5f9fe 100%);
        }
        .entity-id {
            font-family: monospace;
            background: white;
            padding: 8px 12px;
            border-radius: 5px;
            display: inline-block;
            margin: 5px 0;
            font-size: 14px;
        }
        .sensor-info {
            margin-top: 10px;
        }
        .sensor-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        .friendly-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            margin-left: 10px;
        }
        .copy-btn:hover {
            background: #5568d3;
        }
        .stats {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .back-btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 600;
        }
        .back-btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sensor Discovery Tool</h1>
        <p class="subtitle">Find all available sensors in your Home Assistant instance</p>

        <div class="search-box">
            <form method="GET">
                <input type="text" 
                       name="search" 
                       placeholder="Search by name or entity ID..." 
                       value="<?= htmlspecialchars($searchQuery) ?>">
            </form>
        </div>

        <?php
        try {
            if (!$client->checkConnection()) {
                throw new Exception('Cannot connect to Home Assistant');
            }

            $states = $client->getStates();

            if (!is_array($states) || empty($states)) {
                echo "<p>No entities found</p>";
                exit;
            }

            // Categorize sensors
            $temperatureSensors = [];
            $humiditySensors = [];
            $otherSensors = [];

            foreach ($states as $state) {
                $entityId = $state['entity_id'];
                $friendlyName = $state['attributes']['friendly_name'] ?? $entityId;

                // Apply search filter
                if (!empty($searchQuery)) {
                    if (stripos($entityId, $searchQuery) === false && 
                        stripos($friendlyName, $searchQuery) === false) {
                        continue;
                    }
                }

                // Only show sensor entities
                if (strpos($entityId, 'sensor.') !== 0) {
                    continue;
                }

                $sensorData = [
                    'entity_id' => $entityId,
                    'friendly_name' => $friendlyName,
                    'state' => $state['state'],
                    'unit' => $state['attributes']['unit_of_measurement'] ?? '',
                ];

                // Categorize by type
                if (stripos($entityId, 'temperature') !== false || 
                    stripos($friendlyName, 'temperature') !== false ||
                    stripos($friendlyName, 'temp') !== false) {
                    $temperatureSensors[] = $sensorData;
                } elseif (stripos($entityId, 'humidity') !== false ||
                          stripos($entityId, 'humidite') !== false ||
                          stripos($friendlyName, 'humidity') !== false ||
                          stripos($friendlyName, 'humidité') !== false) {
                    $humiditySensors[] = $sensorData;
                } else {
                    $otherSensors[] = $sensorData;
                }
            }

            // Display statistics
            $totalFound = count($temperatureSensors) + count($humiditySensors) + count($otherSensors);
            echo "<div class='stats'>";
            echo "<strong>Found " . $totalFound . " sensor(s)</strong>";
            if (!empty($searchQuery)) {
                echo " matching \"" . htmlspecialchars($searchQuery) . "\"";
            }
            echo "<br>";
            echo "Temperature: " . count($temperatureSensors) . " | ";
            echo "Humidity: " . count($humiditySensors) . " | ";
            echo "Other: " . count($otherSensors);
            echo "</div>";

            // Display temperature sensors
            if (!empty($temperatureSensors)) {
                echo "<div class='sensor-group'>";
                echo "<h2>Temperature Sensors</h2>";
                foreach ($temperatureSensors as $sensor) {
                    echo "<div class='sensor-card temperature'>";
                    echo "<div class='friendly-name'>🌡️ " . htmlspecialchars($sensor['friendly_name']) . "</div>";
                    echo "<div class='entity-id'>" . htmlspecialchars($sensor['entity_id']);
                    echo "<button class='copy-btn' onclick='copyToClipboard(\"" . htmlspecialchars($sensor['entity_id']) . "\")'>Copy</button>";
                    echo "</div>";
                    echo "<div class='sensor-info'>";
                    echo "<div class='sensor-value'>" . htmlspecialchars($sensor['state']) . " " . htmlspecialchars($sensor['unit']) . "</div>";
                    echo "</div>";
                    echo "</div>";
                }
                echo "</div>";
            }

            // Display humidity sensors
            if (!empty($humiditySensors)) {
                echo "<div class='sensor-group'>";
                echo "<h2>Humidity Sensors</h2>";
                foreach ($humiditySensors as $sensor) {
                    echo "<div class='sensor-card humidity'>";
                    echo "<div class='friendly-name'>💧 " . htmlspecialchars($sensor['friendly_name']) . "</div>";
                    echo "<div class='entity-id'>" . htmlspecialchars($sensor['entity_id']);
                    echo "<button class='copy-btn' onclick='copyToClipboard(\"" . htmlspecialchars($sensor['entity_id']) . "\")'>Copy</button>";
                    echo "</div>";
                    echo "<div class='sensor-info'>";
                    echo "<div class='sensor-value'>" . htmlspecialchars($sensor['state']) . " " . htmlspecialchars($sensor['unit']) . "</div>";
                    echo "</div>";
                    echo "</div>";
                }
                echo "</div>";
            }

            // Display other sensors
            if (!empty($otherSensors)) {
                echo "<div class='sensor-group'>";
                echo "<h2>Other Sensors</h2>";
                foreach ($otherSensors as $sensor) {
                    echo "<div class='sensor-card'>";
                    echo "<div class='friendly-name'>📊 " . htmlspecialchars($sensor['friendly_name']) . "</div>";
                    echo "<div class='entity-id'>" . htmlspecialchars($sensor['entity_id']);
                    echo "<button class='copy-btn' onclick='copyToClipboard(\"" . htmlspecialchars($sensor['entity_id']) . "\")'>Copy</button>";
                    echo "</div>";
                    echo "<div class='sensor-info'>";
                    echo "<div class='sensor-value'>" . htmlspecialchars($sensor['state']) . " " . htmlspecialchars($sensor['unit']) . "</div>";
                    echo "</div>";
                    echo "</div>";
                }
                echo "</div>";
            }

        } catch (Exception $e) {
            echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; color: #721c24;'>";
            echo "<strong>Error:</strong> " . htmlspecialchars($e->getMessage());
            echo "</div>";
        }
        ?>

        <hr style="margin: 40px 0; border: none; border-top: 2px solid #eee;">
        <p style="text-align: center;">
            <a href="sensors.php" class="back-btn">Back to Main Page</a>
            <a href="check-config.php" class="back-btn" style="margin-left: 10px;">Check Configuration</a>
        </p>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Copied to clipboard: ' + text);
            }, function(err) {
                alert('Failed to copy: ' + err);
            });
        }
    </script>
</body>
</html>
