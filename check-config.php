<?php
/**
 * 配置检查工具 - 检查 config.php 是否包含所需的传感器配置
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='zh-CN'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>配置检查工具</title>
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
        <h1>🔧 配置检查工具</h1>
        <p style='color: #666; margin-bottom: 30px;'>检查您的 config.php 配置文件是否正确设置</p>
";

// 检查 config.php 是否存在
if (!file_exists('config.php')) {
    echo "<div class='check-item error'>
            <strong>❌ config.php 文件不存在</strong>
            <p>请复制 config.example.php 到 config.php</p>
            <pre>cp config.example.php config.php</pre>
          </div>
          <a href='UPDATE_CONFIG.md' class='btn'>📖 查看更新说明</a>
    </div></body></html>";
    exit;
}

echo "<div class='check-item success'>
        <strong>✅ config.php 文件存在</strong>
      </div>";

// 加载配置
$config = require 'config.php';

// 检查基本配置
echo "<h2>📋 基本配置</h2>";

if (isset($config['home_assistant_url'])) {
    echo "<div class='check-item success'>
            <strong>✅ Home Assistant URL</strong>
            <p>" . htmlspecialchars($config['home_assistant_url']) . "</p>
          </div>";
} else {
    echo "<div class='check-item error'>
            <strong>❌ Home Assistant URL 未配置</strong>
          </div>";
}

if (isset($config['access_token'])) {
    $token = $config['access_token'];
    $tokenDisplay = substr($token, 0, 10) . '...' . substr($token, -10);
    echo "<div class='check-item success'>
            <strong>✅ Access Token</strong>
            <p>" . htmlspecialchars($tokenDisplay) . "</p>
          </div>";
} else {
    echo "<div class='check-item error'>
            <strong>❌ Access Token 未配置</strong>
          </div>";
}

// 检查 sensor_groups 配置
echo "<h2>🎯 传感器组配置</h2>";

if (!isset($config['sensor_groups'])) {
    echo "<div class='check-item error'>
            <strong>❌ 缺少 sensor_groups 配置</strong>
            <p>您的 config.php 需要更新到新格式</p>
          </div>

          <div class='solution'>
            <h3>💡 解决方案</h3>
            <p>请在 config.php 中添加以下配置（在最后的 <code>];</code> 之前）：</p>
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
            <p><a href='UPDATE_CONFIG.md' class='btn'>📖 查看完整更新说明</a></p>
          </div>";
} else {
    echo "<div class='check-item success'>
            <strong>✅ sensor_groups 配置存在</strong>
          </div>";

    $sensorGroups = $config['sensor_groups'];
    $groupCount = count($sensorGroups);

    echo "<div class='check-item success'>
            <strong>📊 找到 $groupCount 个传感器组</strong>
          </div>";

    foreach ($sensorGroups as $index => $group) {
        $groupNum = $index + 1;
        $groupId = $group['id'] ?? '未命名';
        $groupName = $group['name']['zh'] ?? $group['name']['fr'] ?? '未命名';
        $sensorCount = isset($group['sensors']) ? count($group['sensors']) : 0;

        echo "<div class='check-item success'>
                <strong>✅ 组 $groupNum: $groupName (ID: $groupId)</strong>
                <p>包含 $sensorCount 个传感器</p>";

        if (isset($group['sensors']) && !empty($group['sensors'])) {
            echo "<div class='entity-list'>";
            foreach ($group['sensors'] as $sensor) {
                $sensorType = $sensor['type'] ?? '未知';
                $entityId = $sensor['entity_id'] ?? '未配置';
                $icon = $sensor['icon'] ?? '📊';
                echo "<div class='entity-item'>$icon $sensorType: <code>$entityId</code></div>";
            }
            echo "</div>";
        }

        echo "</div>";
    }

    if (isset($config['default_sensor_group'])) {
        echo "<div class='check-item success'>
                <strong>✅ 默认传感器组</strong>
                <p>" . htmlspecialchars($config['default_sensor_group']) . "</p>
              </div>";
    }
}

// 测试连接
echo "<h2>🔌 连接测试</h2>";

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
                    <strong>✅ 成功连接到 Home Assistant</strong>
                  </div>";

            // 获取并显示所有传感器
            $states = $client->getStates();
            if (is_array($states) && !empty($states)) {
                echo "<div class='check-item success'>
                        <strong>✅ 成功获取实体列表</strong>
                        <p>共找到 " . count($states) . " 个实体</p>
                      </div>";

                // 查找 YY 相关的传感器
                $yySensors = [];
                foreach ($states as $state) {
                    $entityId = $state['entity_id'];
                    $friendlyName = $state['attributes']['friendly_name'] ?? $entityId;

                    if (stripos($friendlyName, 'YY') !== false ||
                        stripos($friendlyName, 'yy') !== false ||
                        stripos($entityId, 'yy') !== false) {
                        $yySensors[] = [
                            'entity_id' => $entityId,
                            'name' => $friendlyName,
                            'state' => $state['state'],
                        ];
                    }
                }

                if (!empty($yySensors)) {
                    echo "<div class='check-item success'>
                            <strong>✅ 找到 YY 相关的传感器</strong>
                            <p>共 " . count($yySensors) . " 个</p>
                            <div class='entity-list'>";

                    foreach ($yySensors as $sensor) {
                        echo "<div class='entity-item'>
                                <strong>" . htmlspecialchars($sensor['name']) . "</strong><br>
                                <code>" . htmlspecialchars($sensor['entity_id']) . "</code><br>
                                当前值: " . htmlspecialchars($sensor['state']) . "
                              </div>";
                    }

                    echo "</div></div>";
                } else {
                    echo "<div class='check-item warning'>
                            <strong>⚠️ 未找到 YY 相关的传感器</strong>
                            <p>请检查 Home Assistant 中是否存在包含 'YY' 的传感器</p>
                          </div>";
                }
            }
        } else {
            echo "<div class='check-item error'>
                    <strong>❌ 无法连接到 Home Assistant</strong>
                    <p>请检查 URL 和 token 是否正确</p>
                  </div>";
        }
    } catch (Exception $e) {
        echo "<div class='check-item error'>
                <strong>❌ 连接错误</strong>
                <p>" . htmlspecialchars($e->getMessage()) . "</p>
              </div>";
    }
}

echo "<hr style='margin: 40px 0; border: none; border-top: 2px solid #eee;'>
      <p style='text-align: center; color: #666;'>
        <a href='sensors.php' class='btn'>🏠 返回主页</a>
        <a href='UPDATE_CONFIG.md' class='btn' style='margin-left: 10px;'>📖 查看更新说明</a>
      </p>
    </div>
</body>
</html>";
?>
