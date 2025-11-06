#!/usr/bin/env php
<?php
/**
 * 交互式配置文件创建工具
 * Interactive Configuration File Creator
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n";
echo "╔═══════════════════════════════════════════════════╗\n";
echo "║   🏠 Home Assistant Dashboard Configuration      ║\n";
echo "║   配置文件创建向导                                  ║\n";
echo "╚═══════════════════════════════════════════════════╝\n";
echo "\n";

$configFile = __DIR__ . '/config.php';
$exampleFile = __DIR__ . '/config.example.php';

// 检查是否已有配置文件
if (file_exists($configFile)) {
    echo "⚠️  警告: config.php 已存在\n";
    echo "\n";
    echo "请选择操作:\n";
    echo "  1) 查看现有配置\n";
    echo "  2) 备份并创建新配置\n";
    echo "  3) 退出\n";
    echo "\n";
    echo "您的选择 (1-3): ";
    $choice = trim(fgets(STDIN));

    switch ($choice) {
        case '1':
            echo "\n📄 当前配置:\n";
            echo str_repeat("=", 50) . "\n";
            readfile($configFile);
            echo "\n";
            exit(0);

        case '2':
            $backupFile = $configFile . '.backup-' . date('YmdHis');
            if (copy($configFile, $backupFile)) {
                echo "✅ 已备份到: $backupFile\n\n";
            } else {
                die("❌ 备份失败\n");
            }
            break;

        case '3':
        default:
            echo "👋 已取消\n";
            exit(0);
    }
}

echo "📝 请输入您的配置信息:\n";
echo str_repeat("=", 50) . "\n\n";

// 1. Home Assistant URL
echo "1️⃣  Home Assistant URL\n";
echo "   示例: http://192.168.1.100:8123\n";
echo "   或: http://homeassistant.local:8123\n";
echo "   URL: ";
$haUrl = trim(fgets(STDIN));

if (empty($haUrl)) {
    $haUrl = 'http://homeassistant.local:8123';
    echo "   (使用默认值: $haUrl)\n";
}

// 移除末尾的斜杠
$haUrl = rtrim($haUrl, '/');

echo "\n";

// 2. Access Token
echo "2️⃣  Access Token (长期访问令牌)\n";
echo "   在 Home Assistant 中生成:\n";
echo "   个人资料 -> 安全 -> 长期访问令牌\n";
echo "   Token: ";
$accessToken = trim(fgets(STDIN));

if (empty($accessToken)) {
    die("❌ Access Token 不能为空\n");
}

echo "\n";
echo "🔍 正在测试连接...\n";

// 测试连接
$ch = curl_init($haUrl . '/api/');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ 连接成功!\n\n";
} else {
    echo "⚠️  警告: 连接测试失败 (HTTP $httpCode)\n";
    echo "   继续创建配置? (y/n): ";
    $continue = trim(fgets(STDIN));
    if (strtolower($continue) !== 'y') {
        die("👋 已取消\n");
    }
    echo "\n";
}

// 3. 查找传感器
echo "🔍 正在查找可用传感器...\n";

// 获取所有状态
$ch = curl_init($haUrl . '/api/states');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$statesJson = curl_exec($ch);
curl_close($ch);

$states = json_decode($statesJson, true);
$foundSensors = [
    'yy' => ['temperature' => null, 'humidity' => null],
    'cuisine' => ['temperature' => null, 'humidity' => null],
];

if (is_array($states)) {
    echo "✅ 找到 " . count($states) . " 个实体\n\n";

    // 查找 YY 和 Cuisine 传感器
    foreach ($states as $state) {
        $entityId = $state['entity_id'];
        $friendlyName = $state['attributes']['friendly_name'] ?? '';

        // YY 传感器
        if (stripos($entityId, 'yy') !== false || stripos($friendlyName, 'YY') !== false) {
            if (stripos($entityId, 'temperature') !== false) {
                $foundSensors['yy']['temperature'] = $entityId;
            } elseif (stripos($entityId, 'humidity') !== false) {
                $foundSensors['yy']['humidity'] = $entityId;
            }
        }

        // Cuisine 传感器
        if (stripos($entityId, 'cuisine') !== false || stripos($friendlyName, 'Cuisine') !== false ||
            stripos($friendlyName, '厨房') !== false) {
            if (stripos($entityId, 'temperature') !== false) {
                $foundSensors['cuisine']['temperature'] = $entityId;
            } elseif (stripos($entityId, 'humidity') !== false) {
                $foundSensors['cuisine']['humidity'] = $entityId;
            }
        }
    }

    echo "📊 发现的传感器:\n";
    echo str_repeat("-", 50) . "\n";

    // 显示 YY 传感器
    echo "\n🏠 YY的房间:\n";
    if ($foundSensors['yy']['temperature']) {
        echo "   ✅ 温度: {$foundSensors['yy']['temperature']}\n";
    } else {
        echo "   ❌ 温度: 未找到\n";
    }
    if ($foundSensors['yy']['humidity']) {
        echo "   ✅ 湿度: {$foundSensors['yy']['humidity']}\n";
    } else {
        echo "   ❌ 湿度: 未找到\n";
    }

    // 显示 Cuisine 传感器
    echo "\n🍳 厨房 (Cuisine):\n";
    if ($foundSensors['cuisine']['temperature']) {
        echo "   ✅ 温度: {$foundSensors['cuisine']['temperature']}\n";
    } else {
        echo "   ❌ 温度: 未找到\n";
    }
    if ($foundSensors['cuisine']['humidity']) {
        echo "   ✅ 湿度: {$foundSensors['cuisine']['humidity']}\n";
    } else {
        echo "   ❌ 湿度: 未找到\n";
    }

    echo "\n";
} else {
    echo "⚠️  无法获取传感器列表\n\n";
}

// 4. 手动输入传感器 ID（如果自动检测失败）
if (!$foundSensors['yy']['temperature'] || !$foundSensors['yy']['humidity']) {
    echo "⚠️  未能自动找到所有 YY 房间传感器\n";

    if (!$foundSensors['yy']['temperature']) {
        echo "YY 温度传感器 entity_id: ";
        $foundSensors['yy']['temperature'] = trim(fgets(STDIN));
    }

    if (!$foundSensors['yy']['humidity']) {
        echo "YY 湿度传感器 entity_id: ";
        $foundSensors['yy']['humidity'] = trim(fgets(STDIN));
    }
    echo "\n";
}

// 询问是否添加 Cuisine
echo "是否添加 Cuisine (厨房) 配置? (y/n): ";
$addCuisine = trim(fgets(STDIN));
$includeCuisine = (strtolower($addCuisine) === 'y');

if ($includeCuisine) {
    if (!$foundSensors['cuisine']['temperature']) {
        echo "Cuisine 温度传感器 entity_id: ";
        $foundSensors['cuisine']['temperature'] = trim(fgets(STDIN));
    }

    if (!$foundSensors['cuisine']['humidity']) {
        echo "Cuisine 湿度传感器 entity_id: ";
        $foundSensors['cuisine']['humidity'] = trim(fgets(STDIN));
    }
}

echo "\n";

// 5. 生成配置文件
echo "📝 正在生成 config.php...\n";

$configContent = <<<'PHP'
<?php
/**
 * Configuration pour Home Assistant Dashboard
 * 由 create-config.php 自动生成
 * Generated at: {TIMESTAMP}
 */

return [
    // Home Assistant URL
    'home_assistant_url' => '{HA_URL}',

    // Access Token
    'access_token' => '{ACCESS_TOKEN}',

    // Timeout (seconds)
    'timeout' => 10,

    // Sensor Groups Configuration
    'sensor_groups' => [
{SENSOR_GROUPS}
    ],

    // Default sensor group
    'default_sensor_group' => 'yy_room',
];

PHP;

// 构建传感器组
$sensorGroupsCode = '';

// YY 房间
$sensorGroupsCode .= "        // YY的房间\n";
$sensorGroupsCode .= "        [\n";
$sensorGroupsCode .= "            'id' => 'yy_room',\n";
$sensorGroupsCode .= "            'name' => [\n";
$sensorGroupsCode .= "                'fr' => 'Chambre de YY',\n";
$sensorGroupsCode .= "                'en' => 'YY\\'s Room',\n";
$sensorGroupsCode .= "                'zh' => 'YY的房间',\n";
$sensorGroupsCode .= "            ],\n";
$sensorGroupsCode .= "            'sensors' => [\n";

// YY 温度
$sensorGroupsCode .= "                [\n";
$sensorGroupsCode .= "                    'type' => 'temperature',\n";
$sensorGroupsCode .= "                    'entity_id' => '{$foundSensors['yy']['temperature']}',\n";
$sensorGroupsCode .= "                    'icon' => '🌡️',\n";
$sensorGroupsCode .= "                    'name' => [\n";
$sensorGroupsCode .= "                        'fr' => 'Température',\n";
$sensorGroupsCode .= "                        'en' => 'Temperature',\n";
$sensorGroupsCode .= "                        'zh' => '温度',\n";
$sensorGroupsCode .= "                    ],\n";
$sensorGroupsCode .= "                ],\n";

// YY 湿度
$sensorGroupsCode .= "                [\n";
$sensorGroupsCode .= "                    'type' => 'humidity',\n";
$sensorGroupsCode .= "                    'entity_id' => '{$foundSensors['yy']['humidity']}',\n";
$sensorGroupsCode .= "                    'icon' => '💧',\n";
$sensorGroupsCode .= "                    'name' => [\n";
$sensorGroupsCode .= "                        'fr' => 'Humidité',\n";
$sensorGroupsCode .= "                        'en' => 'Humidity',\n";
$sensorGroupsCode .= "                        'zh' => '湿度',\n";
$sensorGroupsCode .= "                    ],\n";
$sensorGroupsCode .= "                ],\n";

$sensorGroupsCode .= "            ],\n";
$sensorGroupsCode .= "        ],\n";

// Cuisine（如果用户选择添加）
if ($includeCuisine && $foundSensors['cuisine']['temperature'] && $foundSensors['cuisine']['humidity']) {
    $sensorGroupsCode .= "\n        // 厨房 (Cuisine)\n";
    $sensorGroupsCode .= "        [\n";
    $sensorGroupsCode .= "            'id' => 'cuisine',\n";
    $sensorGroupsCode .= "            'name' => [\n";
    $sensorGroupsCode .= "                'fr' => 'Cuisine',\n";
    $sensorGroupsCode .= "                'en' => 'Kitchen',\n";
    $sensorGroupsCode .= "                'zh' => '厨房',\n";
    $sensorGroupsCode .= "            ],\n";
    $sensorGroupsCode .= "            'sensors' => [\n";

    // Cuisine 温度
    $sensorGroupsCode .= "                [\n";
    $sensorGroupsCode .= "                    'type' => 'temperature',\n";
    $sensorGroupsCode .= "                    'entity_id' => '{$foundSensors['cuisine']['temperature']}',\n";
    $sensorGroupsCode .= "                    'icon' => '🌡️',\n";
    $sensorGroupsCode .= "                    'name' => [\n";
    $sensorGroupsCode .= "                        'fr' => 'Température',\n";
    $sensorGroupsCode .= "                        'en' => 'Temperature',\n";
    $sensorGroupsCode .= "                        'zh' => '温度',\n";
    $sensorGroupsCode .= "                    ],\n";
    $sensorGroupsCode .= "                ],\n";

    // Cuisine 湿度
    $sensorGroupsCode .= "                [\n";
    $sensorGroupsCode .= "                    'type' => 'humidity',\n";
    $sensorGroupsCode .= "                    'entity_id' => '{$foundSensors['cuisine']['humidity']}',\n";
    $sensorGroupsCode .= "                    'icon' => '💧',\n";
    $sensorGroupsCode .= "                    'name' => [\n";
    $sensorGroupsCode .= "                        'fr' => 'Humidité',\n";
    $sensorGroupsCode .= "                        'en' => 'Humidity',\n";
    $sensorGroupsCode .= "                        'zh' => '湿度',\n";
    $sensorGroupsCode .= "                    ],\n";
    $sensorGroupsCode .= "                ],\n";

    $sensorGroupsCode .= "            ],\n";
    $sensorGroupsCode .= "        ],\n";
}

// 替换占位符
$configContent = str_replace('{TIMESTAMP}', date('Y-m-d H:i:s'), $configContent);
$configContent = str_replace('{HA_URL}', $haUrl, $configContent);
$configContent = str_replace('{ACCESS_TOKEN}', $accessToken, $configContent);
$configContent = str_replace('{SENSOR_GROUPS}', $sensorGroupsCode, $configContent);

// 写入文件
if (file_put_contents($configFile, $configContent) !== false) {
    echo "✅ config.php 创建成功!\n\n";

    echo "📋 配置摘要:\n";
    echo str_repeat("=", 50) . "\n";
    echo "Home Assistant: $haUrl\n";
    echo "传感器组:\n";
    echo "  • YY的房间 (yy_room)\n";
    if ($includeCuisine) {
        echo "  • 厨房 (cuisine)\n";
    }
    echo "\n";

    echo "🎯 下一步:\n";
    echo "  1. 访问: http://your-server/sensors.php\n";
    echo "  2. 测试页面切换功能\n";
    echo "  3. 查看历史数据: history.php\n";
    echo "\n";

    echo "💡 提示:\n";
    echo "  • 可以编辑 config.php 添加更多传感器\n";
    echo "  • 运行 find-sensors.php 查看所有可用传感器\n";
    echo "  • 运行 check-config.php 验证配置\n";
    echo "\n";

} else {
    die("❌ 无法写入 config.php\n");
}

echo "✨ 完成!\n\n";
