#!/usr/bin/env php
<?php
/**
 * 修复 config.php 中的 emoji 格式错误
 * 自动修正常见的 emoji 显示问题
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔧 Config.php Emoji 修复工具\n";
echo "=" . str_repeat("=", 50) . "\n\n";

$configFile = __DIR__ . '/config.php';
$backupFile = __DIR__ . '/config.php.emoji-backup';

// 检查文件是否存在
if (!file_exists($configFile)) {
    die("❌ 错误: config.php 不存在\n");
}

// 备份原文件
echo "📦 创建备份: config.php.emoji-backup\n";
if (!copy($configFile, $backupFile)) {
    die("❌ 错误: 无法创建备份文件\n");
}

// 读取文件内容
$content = file_get_contents($configFile);
if ($content === false) {
    die("❌ 错误: 无法读取 config.php\n");
}

echo "📖 读取 config.php...\n\n";

// 定义 emoji 修复规则
$fixes = [
    // 修复损坏的温度 emoji
    "/'icon'\s*=>\s*'[^']*️\s*',/u" => "'icon' => '🌡️',",

    // 修复损坏的湿度 emoji
    "/'icon'\s*=>\s*'\[52;34H',/u" => "'icon' => '💧',",

    // 修复空的或损坏的 icon 值
    "/'icon'\s*=>\s*'[\s\x{FE0F}]*',/u" => "'icon' => '📊',",

    // 修复其他可能损坏的 emoji
    "/'icon'\s*=>\s*'[^\x{1F000}-\x{1F9FF}a-zA-Z0-9]*',/u" => "'icon' => '📊',",
];

$fixCount = 0;
$originalContent = $content;

echo "🔍 检测并修复 emoji 格式问题...\n\n";

foreach ($fixes as $pattern => $replacement) {
    $newContent = preg_replace($pattern, $replacement, $content);
    if ($newContent !== $content) {
        $count = preg_match_all($pattern, $content);
        if ($count > 0) {
            echo "  ✓ 修复 $count 个 emoji 问题\n";
            $fixCount += $count;
            $content = $newContent;
        }
    }
}

// 额外检查：查找明显错误的 entity_id
echo "\n🔍 检查 entity_id 配置...\n\n";

// 检查 Cuisine 是否使用了 YY 的传感器
if (preg_match("/'id'\s*=>\s*'cuisine'/i", $content)) {
    if (preg_match("/'entity_id'\s*=>\s*'sensor\.wen_shi_du_chuan_gan_qi_yy_/", $content)) {
        echo "⚠️  警告: 发现 Cuisine 配置使用了 YY 的传感器\n";
        echo "   请运行 find-sensors.php 查找正确的 Cuisine 传感器 ID\n\n";
    }
}

if ($fixCount > 0) {
    // 写入修复后的内容
    if (file_put_contents($configFile, $content) !== false) {
        echo "\n✅ 成功修复 $fixCount 个问题\n";
        echo "✅ 已保存到 config.php\n";
        echo "📦 原文件备份: config.php.emoji-backup\n\n";

        // 显示修改的差异
        echo "📋 修改摘要:\n";
        echo str_repeat("-", 50) . "\n";

        $originalLines = explode("\n", $originalContent);
        $newLines = explode("\n", $content);

        $changes = 0;
        for ($i = 0; $i < count($originalLines); $i++) {
            if (isset($newLines[$i]) && $originalLines[$i] !== $newLines[$i]) {
                $changes++;
                if ($changes <= 5) { // 只显示前5个变化
                    echo "\n行 " . ($i + 1) . ":\n";
                    echo "  旧: " . trim($originalLines[$i]) . "\n";
                    echo "  新: " . trim($newLines[$i]) . "\n";
                }
            }
        }

        if ($changes > 5) {
            echo "\n  ... 还有 " . ($changes - 5) . " 处修改\n";
        }

    } else {
        die("\n❌ 错误: 无法写入 config.php\n");
    }
} else {
    echo "✅ 未发现需要修复的 emoji 问题\n";
    echo "   您的 config.php 中的 emoji 格式正确\n\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎯 下一步:\n";
echo "  1. 运行: php find-sensors.php\n";
echo "     查找 Cuisine 相关的正确传感器 ID\n\n";
echo "  2. 访问: check-config.php\n";
echo "     验证配置是否正确\n\n";
echo "  3. 测试: sensors.php\n";
echo "     测试页面切换功能\n\n";

// 如果发现备份可以恢复
echo "💡 提示: 如果需要恢复原文件:\n";
echo "   cp config.php.emoji-backup config.php\n\n";
