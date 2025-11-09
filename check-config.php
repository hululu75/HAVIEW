<?php
/**
 * Configuration Check Tool - Verify config.php sensor configuration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Configuration Check Tool</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
        }
        .check-item {
            padding: 20px;
            margin: 15px 0;
            border-radius: 10px;
            border-left: 5px solid #ccc;
        }
        .check-item.success {
            background: #d4edda;
            border-color: #28a745;
        }
        .check-item.error {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .check-item.warning {
            background: #fff3cd;
            border-color: #ffc107;
        }
        .check-item strong {
            display: block;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin-top: 10px;
        }
        .code {
            background: #f5f5f5;
            padding: 3px 8px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 600;
        }
        .btn:hover {
            background: #5568d3;
        }
        .solution {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #1890ff;
        }
        .entity-list {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .entity-item {
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Configuration Check Tool</h1>
        <p style='color: #666; margin-bottom: 30px;'>Verify your config.php configuration file</p>
";

// Check if config.php exists
if (!file_exists('config.php')) {
    echo "<div class='check-item error'>
            <strong>config.php file not found</strong>
            <p>Please copy config.example.php to config.php</p>
            <pre>cp config.example.php config.php</pre>
          </div>
          <a href='sensors.php' class='btn'>Back to Main Page</a>
    </div></body></html>";
    exit;
}

echo "<div class='check-item success'>
        <strong>config.php file exists</strong>
      </div>";

// Load configuration
$config = require 'config.php';

// Basic configuration check
echo "<h2>Basic Configuration</h2>";

if (isset($config['home_assistant_url'])) {
    echo "<div class='check-item success'>
            <strong>Home Assistant URL</strong>
            <p>" . htmlspecialchars($config['home_assistant_url']) . "</p>
          </div>";
} else {
    echo "<div class='check-item error'>
            <strong>Home Assistant URL not configured</strong>
          </div>";
}

if (isset($config['access_token'])) {
    $token = $config['access_token'];
    $tokenDisplay = substr($token, 0, 10) . '...' . substr($token, -10);
    echo "<div class='check-item success'>
            <strong>Access Token</strong>
            <p>" . htmlspecialchars($tokenDisplay) . "</p>
          </div>";
} else {
    echo "<div class='check-item error'>
            <strong>Access Token not configured</strong>
          </div>";
}

// Check sensor_groups configuration
echo "<h2>Sensor Groups Configuration</h2>";

if (!isset($config['sensor_groups'])) {
    echo "<div class='check-item error'>
            <strong>Missing sensor_groups configuration</strong>
            <p>Your config.php needs to be updated to the new format</p>
          </div>

          <div class='solution'>
            <h3>Solution</h3>
            <p>Please add the following to your config.php (before the final <code>];</code>):</p>
            <pre>" . htmlspecialchars("
    'sensor_groups' => [
        [
            'id' => 'yy_room',
            'name' => [
                'fr' => 'Chambre de YY',
                'en' => 'YY\\'s Room',
                'zh' => 'YY的房间',
            ],
            'sensors' => [
                [
                    'type' => 'temperature',
                    'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_yy_temperature',
                    'icon' => '🌡️',
                    'name' => [
                        'fr' => 'Température',
                        'en' => 'Temperature',
                        'zh' => '温度',
                    ],
                ],
                [
                    'type' => 'humidity',
                    'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_yy_humidity',
                    'icon' => '💧',
                    'name' => [
                        'fr' => 'Humidité',
                        'en' => 'Humidity',
                        'zh' => '湿度',
                    ],
                ],
            ],
        ],
    ],

    'default_sensor_group' => 'yy_room',
") . "</pre>
          </div>";
} else {
    echo "<div class='check-item success'>
            <strong>sensor_groups configuration exists</strong>
          </div>";

    $sensorGroups = $config['sensor_groups'];
    $groupCount = count($sensorGroups);

    echo "<div class='check-item success'>
            <strong>Found $groupCount sensor group(s)</strong>
          </div>";

    foreach ($sensorGroups as $index => $group) {
        $groupNum = $index + 1;
        $groupId = $group['id'] ?? 'unnamed';
        $groupName = $group['name']['en'] ?? $group['name']['fr'] ?? 'unnamed';
        $sensorCount = isset($group['sensors']) ? count($group['sensors']) : 0;

        echo "<div class='check-item success'>
                <strong>Group $groupNum: $groupName (ID: $groupId)</strong>
                <p>Contains $sensorCount sensor(s)</p>";

        if (isset($group['sensors']) && !empty($group['sensors'])) {
            echo "<div class='entity-list'>";
            foreach ($group['sensors'] as $sensor) {
                $sensorType = $sensor['type'] ?? 'unknown';
                $entityId = $sensor['entity_id'] ?? 'not configured';
                $icon = $sensor['icon'] ?? '';
                echo "<div class='entity-item'>$icon $sensorType: <code>$entityId</code></div>";
            }
            echo "</div>";
        }

        echo "</div>";
    }

    if (isset($config['default_sensor_group'])) {
        echo "<div class='check-item success'>
                <strong>Default sensor group</strong>
                <p>" . htmlspecialchars($config['default_sensor_group']) . "</p>
              </div>";
    }
}

// Connection test
echo "<h2>Connection Test</h2>";

if (isset($config['home_assistant_url']) && isset($config['access_token'])) {
    require_once 'HomeAssistantClient.php';

    try {
        $client = new HomeAssistantClient(
            $config['home_assistant_url'],
            $config['access_token'],
            $config['timeout'] ?? 10
        );

        if ($client->checkConnection()) {
            echo "<div class='check-item success'>
                    <strong>Successfully connected to Home Assistant</strong>
                  </div>";

            // Get entities
            $states = $client->getStates();
            if (is_array($states) && !empty($states)) {
                echo "<div class='check-item success'>
                        <strong>Successfully retrieved entities list</strong>
                        <p>Found " . count($states) . " entities</p>
                      </div>";

                // Find configured sensors
                $configuredSensors = [];
                if (isset($config['sensor_groups'])) {
                    foreach ($config['sensor_groups'] as $group) {
                        if (isset($group['sensors'])) {
                            foreach ($group['sensors'] as $sensor) {
                                if (isset($sensor['entity_id'])) {
                                    $configuredSensors[] = $sensor['entity_id'];
                                }
                            }
                        }
                    }
                }

                // Check if configured sensors exist
                $statesById = [];
                foreach ($states as $state) {
                    $statesById[$state['entity_id']] = $state;
                }

                $foundCount = 0;
                $missingCount = 0;

                foreach ($configuredSensors as $entityId) {
                    if (isset($statesById[$entityId])) {
                        $foundCount++;
                    } else {
                        $missingCount++;
                    }
                }

                if ($foundCount > 0) {
                    echo "<div class='check-item success'>
                            <strong>Found $foundCount configured sensor(s)</strong>
                          </div>";
                }

                if ($missingCount > 0) {
                    echo "<div class='check-item warning'>
                            <strong>$missingCount configured sensor(s) not found in Home Assistant</strong>
                            <p>Please check your entity IDs</p>
                          </div>";
                }
            }
        } else {
            echo "<div class='check-item error'>
                    <strong>Cannot connect to Home Assistant</strong>
                    <p>Please check your URL and token</p>
                  </div>";
        }
    } catch (Exception $e) {
        echo "<div class='check-item error'>
                <strong>Connection error</strong>
                <p>" . htmlspecialchars($e->getMessage()) . "</p>
              </div>";
    }
}

echo "<hr style='margin: 40px 0; border: none; border-top: 2px solid #eee;'>
      <p style='text-align: center; color: #666;'>
        <a href='sensors.php' class='btn'>Back to Main Page</a>
        <a href='find-sensors.php' class='btn' style='margin-left: 10px;'>Find Sensors</a>
      </p>
    </div>
</body>
</html>";
?>
