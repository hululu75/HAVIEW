#!/usr/bin/env php
<?php
/**
 * 快速诊断并修复配置问题
 */

echo "\n🔍 Home Assistant Dashboard 诊断工具\n";
echo str_repeat("=", 60) . "\n\n";

$configFile = __DIR__ . '/config.php';
$exampleFile = __DIR__ . '/config.example.php';

// 检查 config.php
echo "1. 检查配置文件...\n";
if (file_exists($configFile)) {
    echo "   ✅ config.php 存在\n";

    // 读取配置
    $config = require $configFile;

    echo "\n2. 检查传感器组配置...\n";
    if (isset($config['sensor_groups'])) {
        $groups = $config['sensor_groups'];
        echo "   ✅ 找到 " . count($groups) . " 个传感器组:\n";

        foreach ($groups as $group) {
            $groupId = $group['id'] ?? '未知';
            $groupName = $group['name']['zh'] ?? $group['name']['fr'] ?? '未知';
            $sensorCount = isset($group['sensors']) ? count($group['sensors']) : 0;
            echo "      • $groupName ($groupId) - $sensorCount 个传感器\n";

            if (isset($group['sensors'])) {
                foreach ($group['sensors'] as $sensor) {
                    $type = $sensor['type'] ?? '?';
                    $entityId = $sensor['entity_id'] ?? '未设置';
                    $icon = $sensor['icon'] ?? '?';
                    echo "        - $type: $entityId $icon\n";
                }
            }
        }

        // 检查是否有 Cuisine
        $hasCuisine = false;
        foreach ($groups as $group) {
            if (($group['id'] ?? '') === 'cuisine') {
                $hasCuisine = true;
                break;
            }
        }

        if (!$hasCuisine) {
            echo "\n   ⚠️  警告: 没有找到 Cuisine (厨房) 配置\n";
            echo "   💡 运行: php create-config.php 添加厨房配置\n";
        }

    } else {
        echo "   ❌ 没有找到 sensor_groups 配置\n";
    }

    echo "\n3. 检查连接信息...\n";
    if (isset($config['home_assistant_url'])) {
        echo "   ✅ URL: {$config['home_assistant_url']}\n";
    } else {
        echo "   ❌ 未设置 Home Assistant URL\n";
    }

    if (isset($config['access_token']) && $config['access_token'] !== 'votre_token_ici') {
        echo "   ✅ Token: " . substr($config['access_token'], 0, 20) . "...\n";
    } else {
        echo "   ❌ 未设置有效的 Access Token\n";
    }

} else {
    echo "   ❌ config.php 不存在\n";
    echo "\n💡 解决方案:\n";
    echo "   运行以下命令创建配置文件:\n";
    echo "   php create-config.php\n";
    echo "\n   或者手动复制:\n";
    echo "   cp config.example.php config.php\n";
    echo "   然后编辑 config.php 填写您的信息\n";
    exit(1);
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✨ 诊断完成\n\n";

// 提供快速操作选项
echo "📋 可用操作:\n";
echo "  1. 运行 create-config.php - 重新创建配置（推荐）\n";
echo "  2. 运行 find-sensors.php - 查找所有传感器\n";
echo "  3. 访问 sensors.php - 测试页面\n";
echo "  4. 访问 check-config.php - 详细配置检查\n";
echo "\n";
