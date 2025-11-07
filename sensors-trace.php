<?php
/**
 * 完整追踪版本 - 记录每一步执行
 */

// 开启所有错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 追踪日志
$trace = [];
$trace[] = "========== 开始执行 ==========";
$trace[] = "时间: " . date('Y-m-d H:i:s');
$trace[] = "请求 URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A');
$trace[] = "";

// 1. 记录所有输入
$trace[] = "1. 输入参数:";
$trace[] = "   \$_GET: " . json_encode($_GET);
$trace[] = "   \$_POST: " . json_encode($_POST);
$trace[] = "   \$_COOKIE: " . json_encode($_COOKIE);
$trace[] = "";

// 2. 加载语言系统
$trace[] = "2. 加载语言系统 (languages.php)";
require_once 'languages.php';
$trace[] = "   ✓ languages.php 加载完成";

// 3. 获取当前语言
$trace[] = "3. 获取当前语言";
$trace[] = "   调用 getCurrentLanguage()...";
$currentLang = getCurrentLanguage();
$trace[] = "   返回: $currentLang";
$trace[] = "   Cookie 'language': " . ($_COOKIE['language'] ?? 'null');
$trace[] = "";

// 4. 加载配置
$trace[] = "4. 加载配置文件";
if (!file_exists('config.php')) {
    die('❌ config.php 不存在');
}
$config = require 'config.php';
$trace[] = "   ✓ config.php 加载完成";
$trace[] = "   URL: " . $config['home_assistant_url'];
$trace[] = "   默认组: " . ($config['default_sensor_group'] ?? 'null');
$trace[] = "   组数量: " . (isset($config['sensor_groups']) ? count($config['sensor_groups']) : 0);
if (isset($config['sensor_groups'])) {
    foreach ($config['sensor_groups'] as $i => $g) {
        $trace[] = "     组[$i]: id='{$g['id']}', name='{$g['name']['zh']}'";
    }
}
$trace[] = "";

// 5. 确定当前组
$trace[] = "5. 确定当前组";
$trace[] = "   \$_GET['group']: " . ($_GET['group'] ?? 'null');
$trace[] = "   default_sensor_group: " . ($config['default_sensor_group'] ?? 'null');

$currentGroupId = $_GET['group'] ?? ($config['default_sensor_group'] ?? null);
$trace[] = "   计算的 \$currentGroupId: " . ($currentGroupId ?? 'null');

$sensorGroups = $config['sensor_groups'] ?? [];
$currentGroup = null;

$trace[] = "   开始查找匹配的组...";
foreach ($sensorGroups as $i => $group) {
    $trace[] = "     检查组[$i]: '{$group['id']}' === '$currentGroupId' ?";
    if ($group['id'] === $currentGroupId) {
        $currentGroup = $group;
        $trace[] = "       ✓ 匹配! 使用这个组";
        break;
    } else {
        $trace[] = "       ✗ 不匹配";
    }
}

if (!$currentGroup && !empty($sensorGroups)) {
    $trace[] = "   ⚠️ 未找到匹配组，使用第一个组";
    $currentGroup = $sensorGroups[0];
    $currentGroupId = $currentGroup['id'];
    $trace[] = "   回退到: " . $currentGroupId;
}

if ($currentGroup) {
    $trace[] = "   最终选择的组:";
    $trace[] = "     ID: " . $currentGroup['id'];
    $trace[] = "     名称: " . ($currentGroup['name'][$currentLang] ?? $currentGroup['name']['fr']);
    $trace[] = "     传感器数: " . count($currentGroup['sensors'] ?? []);
} else {
    $trace[] = "   ❌ 错误: 未找到任何组!";
}
$trace[] = "";

// 6. 连接 Home Assistant
require_once 'HomeAssistantClient.php';
$trace[] = "6. 连接 Home Assistant";
$client = new HomeAssistantClient(
    $config['home_assistant_url'],
    $config['access_token'],
    $config['timeout'] ?? 10
);

$error = null;
$states = [];
$sensorsData = [];

try {
    $trace[] = "   检查连接...";
    if (!$client->checkConnection()) {
        throw new Exception('连接失败');
    }
    $trace[] = "   ✓ 连接成功";

    $trace[] = "   获取所有状态...";
    $states = $client->getStates();
    $trace[] = "   ✓ 获取到 " . count($states) . " 个实体";

    // 创建索引
    $statesById = [];
    foreach ($states as $state) {
        $statesById[$state['entity_id']] = $state;
    }

    // 获取当前组的传感器数据
    $trace[] = "7. 获取传感器数据";
    if ($currentGroup && isset($currentGroup['sensors'])) {
        $trace[] = "   遍历配置的传感器...";
        foreach ($currentGroup['sensors'] as $i => $sensorConfig) {
            $entityId = $sensorConfig['entity_id'];
            $sensorType = $sensorConfig['type'];
            $trace[] = "     传感器[$i]: type=$sensorType, entity_id=$entityId";

            if (isset($statesById[$entityId])) {
                $value = $statesById[$entityId]['state'];
                $trace[] = "       ✓ 找到数据: $value";
                $sensorsData[] = [
                    'config' => $sensorConfig,
                    'state' => $statesById[$entityId],
                ];
            } else {
                $trace[] = "       ✗ 未找到数据";
            }
        }
    } else {
        $trace[] = "   ⚠️ 当前组为空或没有传感器配置";
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    $trace[] = "   ❌ 错误: " . $error;
}

$trace[] = "";
$trace[] = "========== 执行完成 ==========";
$trace[] = "最终状态:";
$trace[] = "  当前组 ID: " . ($currentGroupId ?? 'null');
$trace[] = "  找到传感器数: " . count($sensorsData);
foreach ($sensorsData as $i => $sd) {
    $trace[] = "    [$i] " . $sd['config']['type'] . ": " . $sd['state']['state'];
}

// 输出追踪日志
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>完整追踪 - Sensors</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .trace { background: #2d2d2d; padding: 20px; border-radius: 10px; white-space: pre-wrap; }
        .highlight { background: #3a3a3a; padding: 2px 5px; border-left: 3px solid #4CAF50; }
        .error { color: #f44336; }
        .success { color: #4CAF50; }
        .warning { color: #ff9800; }
        .section { color: #2196F3; font-weight: bold; margin-top: 15px; }
        .sensor-card {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 10px;
            color: white;
            margin: 10px;
            min-width: 200px;
        }
        .temp { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .hum { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        h1 { color: #4CAF50; }
        .nav { margin: 20px 0; }
        .nav a {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
        }
    </style>
</head>
<body>
    <h1>🔍 完整执行追踪</h1>

    <div class="nav">
        <a href="?group=yy_room&lang=zh">切换到 YY 房间</a>
        <a href="?group=cuisine&lang=zh">切换到 厨房</a>
    </div>

    <div class="trace">
<?php
foreach ($trace as $line) {
    if (strpos($line, '✓') !== false) {
        echo '<span class="success">' . htmlspecialchars($line) . '</span>' . "\n";
    } elseif (strpos($line, '✗') !== false || strpos($line, '❌') !== false) {
        echo '<span class="error">' . htmlspecialchars($line) . '</span>' . "\n";
    } elseif (strpos($line, '⚠️') !== false) {
        echo '<span class="warning">' . htmlspecialchars($line) . '</span>' . "\n";
    } elseif (preg_match('/^\d+\./', $line) || strpos($line, '====') !== false) {
        echo '<span class="section">' . htmlspecialchars($line) . '</span>' . "\n";
    } else {
        echo htmlspecialchars($line) . "\n";
    }
}
?>
    </div>

    <h2 style="color: #4CAF50; margin-top: 30px;">📊 显示的传感器数据</h2>

    <?php if ($error): ?>
        <div style="background: #f44336; color: white; padding: 15px; border-radius: 5px;">
            ❌ 错误: <?= htmlspecialchars($error) ?>
        </div>
    <?php elseif (empty($sensorsData)): ?>
        <div style="background: #ff9800; color: white; padding: 15px; border-radius: 5px;">
            ⚠️ 没有找到传感器数据
        </div>
    <?php else: ?>
        <?php foreach ($sensorsData as $sd): ?>
            <?php
            $type = $sd['config']['type'];
            $name = $sd['config']['name']['zh'];
            $icon = $sd['config']['icon'];
            $value = $sd['state']['state'];
            $unit = $sd['state']['attributes']['unit_of_measurement'] ?? '';
            $entityId = $sd['config']['entity_id'];
            $cardClass = ($type === 'temperature') ? 'temp' : (($type === 'humidity') ? 'hum' : '');
            ?>
            <div class="sensor-card <?= $cardClass ?>">
                <div style="font-size: 3em;"><?= $icon ?></div>
                <div style="font-size: 1.5em; margin: 10px 0;"><?= htmlspecialchars($name) ?></div>
                <div style="font-size: 3em; font-weight: bold;">
                    <?= htmlspecialchars($value) ?>
                    <span style="font-size: 0.5em;"><?= htmlspecialchars($unit) ?></span>
                </div>
                <div style="font-size: 0.8em; margin-top: 10px; opacity: 0.8;">
                    <?= htmlspecialchars($entityId) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="margin-top: 30px; text-align: center;">
        <a href="sensors.php" style="color: #4CAF50;">返回正常页面</a>
    </div>

    <script>
        console.log("追踪日志:", <?= json_encode($trace) ?>);
        console.log("当前组ID:", <?= json_encode($currentGroupId) ?>);
        console.log("传感器数据:", <?= json_encode($sensorsData) ?>);
    </script>
</body>
</html>
