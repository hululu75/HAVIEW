<?php
/**
 * 传感器调试工具 - 查找所有 YY 相关的传感器
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!file_exists('config.php')) {
    die('错误: config.php 不存在');
}

$config = require 'config.php';
require_once 'HomeAssistantClient.php';

$client = new HomeAssistantClient(
    $config['home_assistant_url'],
    $config['access_token'],
    $config['timeout'] ?? 10
);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>传感器调试工具</title>
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
        .sensor-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin: 15px 0;
            transition: all 0.3s;
        }
        .sensor-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        .sensor-card.temperature {
            border-left: 5px solid #f5576c;
        }
        .sensor-card.humidity {
            border-left: 5px solid #00f2fe;
        }
        .sensor-card.other {
            border-left: 5px solid #667eea;
        }
        .sensor-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .sensor-icon {
            font-size: 2em;
        }
        .sensor-name {
            font-size: 1.3em;
            font-weight: 600;
            color: #333;
        }
        .sensor-entity-id {
            font-family: monospace;
            background: #f5f5f5;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 0.9em;
            color: #666;
            word-break: break-all;
        }
        .sensor-state {
            font-size: 1.5em;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        .copy-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            margin-top: 10px;
        }
        .copy-btn:hover {
            background: #5568d3;
        }
        .section {
            margin: 30px 0;
        }
        .section-title {
            font-size: 1.5em;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .info-box {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #1890ff;
        }
        .error-box {
            background: #ffe0e0;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
        }
        .config-preview {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 0.9em;
            overflow-x: auto;
            white-space: pre-wrap;
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
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
        }
        .stat-label {
            margin-top: 5px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 传感器调试工具</h1>
        <p class="subtitle">查找所有 YY 相关的传感器</p>

<?php
try {
    $states = $client->getStates();

    if (!is_array($states) || empty($states)) {
        echo "<div class='error-box'>
                <strong>❌ 无法获取传感器列表</strong>
                <p>请检查 Home Assistant 连接</p>
              </div>";
        exit;
    }

    // 查找所有 YY 相关的传感器
    $yySensors = [];
    $temperatureSensors = [];
    $humiditySensors = [];
    $otherSensors = [];

    foreach ($states as $state) {
        $entityId = $state['entity_id'];
        $friendlyName = $state['attributes']['friendly_name'] ?? $entityId;

        // 查找包含 YY 的传感器
        if (stripos($friendlyName, 'YY') !== false ||
            stripos($friendlyName, 'yy') !== false ||
            stripos($entityId, 'yy') !== false ||
            stripos($friendlyName, '温湿度') !== false) {

            $sensor = [
                'entity_id' => $entityId,
                'name' => $friendlyName,
                'state' => $state['state'],
                'unit' => $state['attributes']['unit_of_measurement'] ?? '',
                'device_class' => $state['attributes']['device_class'] ?? '',
            ];

            // 分类
            if (stripos($friendlyName, 'Temperature') !== false ||
                stripos($friendlyName, 'Température') !== false ||
                stripos($friendlyName, '温度') !== false ||
                stripos($entityId, 'temperature') !== false ||
                $sensor['device_class'] === 'temperature') {
                $temperatureSensors[] = $sensor;
            } elseif (stripos($friendlyName, 'Humidity') !== false ||
                      stripos($friendlyName, 'Humidité') !== false ||
                      stripos($friendlyName, '湿度') !== false ||
                      stripos($entityId, 'humidity') !== false ||
                      $sensor['device_class'] === 'humidity') {
                $humiditySensors[] = $sensor;
            } else {
                $otherSensors[] = $sensor;
            }

            $yySensors[] = $sensor;
        }
    }

    // 显示统计
    echo "<div class='stats'>
            <div class='stat-card'>
                <div class='stat-value'>" . count($yySensors) . "</div>
                <div class='stat-label'>YY 相关传感器</div>
            </div>
            <div class='stat-card'>
                <div class='stat-value'>" . count($temperatureSensors) . "</div>
                <div class='stat-label'>温度传感器</div>
            </div>
            <div class='stat-card'>
                <div class='stat-value'>" . count($humiditySensors) . "</div>
                <div class='stat-label'>湿度传感器</div>
            </div>
            <div class='stat-card'>
                <div class='stat-value'>" . count($otherSensors) . "</div>
                <div class='stat-label'>其他传感器</div>
            </div>
          </div>";

    if (empty($yySensors)) {
        echo "<div class='error-box'>
                <strong>❌ 未找到任何 YY 相关的传感器</strong>
                <p>请检查 Home Assistant 中是否存在包含 'YY' 的传感器</p>
              </div>";
    } else {
        // 显示温度传感器
        if (!empty($temperatureSensors)) {
            echo "<div class='section'>
                    <div class='section-title'>🌡️ 温度传感器 (" . count($temperatureSensors) . ")</div>";

            foreach ($temperatureSensors as $sensor) {
                echo "<div class='sensor-card temperature'>
                        <div class='sensor-header'>
                            <div class='sensor-icon'>🌡️</div>
                            <div class='sensor-name'>" . htmlspecialchars($sensor['name']) . "</div>
                        </div>
                        <div class='sensor-entity-id'>" . htmlspecialchars($sensor['entity_id']) . "</div>
                        <div class='sensor-state'>" . htmlspecialchars($sensor['state']) . " " . htmlspecialchars($sensor['unit']) . "</div>
                        <button class='copy-btn' onclick='copyToClipboard(\"" . htmlspecialchars($sensor['entity_id']) . "\")'>📋 复制 Entity ID</button>
                      </div>";
            }

            echo "</div>";
        }

        // 显示湿度传感器
        if (!empty($humiditySensors)) {
            echo "<div class='section'>
                    <div class='section-title'>💧 湿度传感器 (" . count($humiditySensors) . ")</div>";

            foreach ($humiditySensors as $sensor) {
                echo "<div class='sensor-card humidity'>
                        <div class='sensor-header'>
                            <div class='sensor-icon'>💧</div>
                            <div class='sensor-name'>" . htmlspecialchars($sensor['name']) . "</div>
                        </div>
                        <div class='sensor-entity-id'>" . htmlspecialchars($sensor['entity_id']) . "</div>
                        <div class='sensor-state'>" . htmlspecialchars($sensor['state']) . " " . htmlspecialchars($sensor['unit']) . "</div>
                        <button class='copy-btn' onclick='copyToClipboard(\"" . htmlspecialchars($sensor['entity_id']) . "\")'>📋 复制 Entity ID</button>
                      </div>";
            }

            echo "</div>";
        } else {
            echo "<div class='error-box'>
                    <strong>❌ 未找到湿度传感器</strong>
                    <p>在 YY 相关的传感器中没有找到湿度传感器。请检查：</p>
                    <ul>
                        <li>Home Assistant 中是否存在湿度传感器</li>
                        <li>传感器名称是否包含 'YY'、'湿度'、'Humidity' 等关键词</li>
                        <li>传感器是否在线且有数据</li>
                    </ul>
                  </div>";
        }

        // 显示其他传感器
        if (!empty($otherSensors)) {
            echo "<div class='section'>
                    <div class='section-title'>📊 其他 YY 传感器 (" . count($otherSensors) . ")</div>";

            foreach ($otherSensors as $sensor) {
                echo "<div class='sensor-card other'>
                        <div class='sensor-header'>
                            <div class='sensor-icon'>📊</div>
                            <div class='sensor-name'>" . htmlspecialchars($sensor['name']) . "</div>
                        </div>
                        <div class='sensor-entity-id'>" . htmlspecialchars($sensor['entity_id']) . "</div>
                        <div class='sensor-state'>" . htmlspecialchars($sensor['state']) . " " . htmlspecialchars($sensor['unit']) . "</div>
                        <button class='copy-btn' onclick='copyToClipboard(\"" . htmlspecialchars($sensor['entity_id']) . "\")'>📋 复制 Entity ID</button>
                      </div>";
            }

            echo "</div>";
        }

        // 生成配置预览
        echo "<div class='section'>
                <div class='section-title'>⚙️ 建议的 config.php 配置</div>
                <div class='info-box'>
                    <strong>💡 提示</strong>
                    <p>根据找到的传感器，这是建议的配置。请复制以下内容到您的 config.php 中。</p>
                </div>
                <div class='config-preview'>";

        echo "'sensor_groups' => [\n";
        echo "    [\n";
        echo "        'id' => 'yy_room',\n";
        echo "        'name' => [\n";
        echo "            'fr' => 'Chambre de YY',\n";
        echo "            'en' => 'YY\\'s Room',\n";
        echo "            'zh' => 'YY的房间',\n";
        echo "        ],\n";
        echo "        'sensors' => [\n";

        // 添加温度传感器
        if (!empty($temperatureSensors)) {
            $tempSensor = $temperatureSensors[0]; // 使用第一个温度传感器
            echo "            [\n";
            echo "                'type' => 'temperature',\n";
            echo "                'entity_id' => '" . $tempSensor['entity_id'] . "',\n";
            echo "                'icon' => '🌡️',\n";
            echo "                'name' => [\n";
            echo "                    'fr' => 'Température',\n";
            echo "                    'en' => 'Temperature',\n";
            echo "                    'zh' => '温度',\n";
            echo "                ],\n";
            echo "            ],\n";
        }

        // 添加湿度传感器
        if (!empty($humiditySensors)) {
            $humSensor = $humiditySensors[0]; // 使用第一个湿度传感器
            echo "            [\n";
            echo "                'type' => 'humidity',\n";
            echo "                'entity_id' => '" . $humSensor['entity_id'] . "',\n";
            echo "                'icon' => '💧',\n";
            echo "                'name' => [\n";
            echo "                    'fr' => 'Humidité',\n";
            echo "                    'en' => 'Humidity',\n";
            echo "                    'zh' => '湿度',\n";
            echo "                ],\n";
            echo "            ],\n";
        } else {
            echo "            // ⚠️ 未找到湿度传感器\n";
            echo "            // 请手动添加湿度传感器配置\n";
        }

        echo "        ],\n";
        echo "    ],\n";
        echo "],\n\n";
        echo "'default_sensor_group' => 'yy_room',";

        echo "</div>
              <button class='copy-btn' onclick='copyConfig()'>📋 复制完整配置</button>
              </div>";
    }

} catch (Exception $e) {
    echo "<div class='error-box'>
            <strong>❌ 错误</strong>
            <p>" . htmlspecialchars($e->getMessage()) . "</p>
          </div>";
}
?>

        <p style="text-align: center; margin-top: 40px;">
            <a href="sensors.php" class="btn">🏠 返回主页</a>
            <a href="check-config.php" class="btn" style="margin-left: 10px;">🔧 配置检查</a>
        </p>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('✅ 已复制: ' + text);
            }).catch(err => {
                prompt('请手动复制:', text);
            });
        }

        function copyConfig() {
            const config = document.querySelector('.config-preview').textContent;
            navigator.clipboard.writeText(config).then(() => {
                alert('✅ 配置已复制到剪贴板！\n\n请粘贴到您的 config.php 文件中');
            }).catch(err => {
                prompt('请手动复制配置:', config);
            });
        }
    </script>
</body>
</html>
