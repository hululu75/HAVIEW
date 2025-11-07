#!/usr/bin/env php
<?php
/**
 * 快速测试 config.php 是否正确加载
 */

echo "\n🧪 测试 Config.php\n";
echo str_repeat("=", 60) . "\n\n";

$configFile = __DIR__ . '/config.php';

if (!file_exists($configFile)) {
    die("❌ config.php 不存在\n");
}

echo "✅ config.php 存在\n\n";

$config = require $configFile;

echo "📋 配置内容:\n";
echo str_repeat("-", 60) . "\n";

echo "URL: " . ($config['home_assistant_url'] ?? '未设置') . "\n";
echo "Token: " . (isset($config['access_token']) ? substr($config['access_token'], 0, 20) . '...' : '未设置') . "\n";
echo "\n";

if (isset($config['sensor_groups'])) {
    echo "传感器组数量: " . count($config['sensor_groups']) . "\n\n";

    foreach ($config['sensor_groups'] as $i => $group) {
        echo "组 #" . ($i + 1) . ":\n";
        echo "  ID: " . ($group['id'] ?? '未设置') . "\n";
        echo "  名称(中文): " . ($group['name']['zh'] ?? '未设置') . "\n";
        echo "  名称(法语): " . ($group['name']['fr'] ?? '未设置') . "\n";
        echo "  传感器数量: " . (isset($group['sensors']) ? count($group['sensors']) : 0) . "\n";

        if (isset($group['sensors'])) {
            foreach ($group['sensors'] as $sensor) {
                echo "    - " . ($sensor['type'] ?? '?') . ": " . ($sensor['entity_id'] ?? '未设置') . "\n";
            }
        }
        echo "\n";
    }
} else {
    echo "❌ 没有 sensor_groups 配置\n";
}

echo str_repeat("=", 60) . "\n";
echo "\n✅ 测试完成\n\n";

// 生成测试 URL
echo "🔗 测试 URL:\n";
echo str_repeat("-", 60) . "\n";

if (isset($config['sensor_groups'])) {
    foreach ($config['sensor_groups'] as $group) {
        $groupId = $group['id'] ?? 'unknown';
        $groupName = $group['name']['zh'] ?? $group['name']['fr'] ?? '未知';
        echo "$groupName: sensors.php?group=$groupId&lang=zh\n";
    }
}

echo "\n";
