<?php
/**
 * 调试版本 - 显示所有内部变量和逻辑
 */

// 开启错误显示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Charger le système de traduction
require_once 'languages.php';
$currentLang = getCurrentLanguage();

// Charger la configuration
if (!file_exists('config.php')) {
    die('Erreur: Le fichier config.php n\'existe pas.');
}

$config = require 'config.php';
require_once 'HomeAssistantClient.php';

// Initialiser le client Home Assistant
$client = new HomeAssistantClient(
    $config['home_assistant_url'],
    $config['access_token'],
    $config['timeout'] ?? 10
);

// 获取调试信息
$debugInfo = [];
$debugInfo['GET参数'] = $_GET;
$debugInfo['当前语言'] = $currentLang;
$debugInfo['请求的group'] = $_GET['group'] ?? 'null';
$debugInfo['默认group'] = $config['default_sensor_group'] ?? 'null';

// Obtenir le groupe de capteurs actuel depuis l'URL ou utiliser le défaut
$currentGroupId = $_GET['group'] ?? ($config['default_sensor_group'] ?? null);
$sensorGroups = $config['sensor_groups'] ?? [];
$currentGroup = null;

$debugInfo['最终使用的groupId'] = $currentGroupId;
$debugInfo['配置中的所有groups'] = array_map(function($g) {
    return [
        'id' => $g['id'],
        'name' => $g['name']['zh'] ?? $g['name']['fr'],
        'sensors_count' => count($g['sensors'] ?? [])
    ];
}, $sensorGroups);

// Trouver le groupe actuel
foreach ($sensorGroups as $group) {
    if ($group['id'] === $currentGroupId) {
        $currentGroup = $group;
        break;
    }
}

$debugInfo['找到的当前group'] = $currentGroup ? [
    'id' => $currentGroup['id'],
    'name' => $currentGroup['name'],
    'sensors' => $currentGroup['sensors']
] : 'null';

// Si le groupe n'est pas trouvé, utiliser le premier groupe disponible
if (!$currentGroup && !empty($sensorGroups)) {
    $currentGroup = $sensorGroups[0];
    $currentGroupId = $currentGroup['id'];
    $debugInfo['回退到第一个group'] = $currentGroup['id'];
}

// Gérer les erreurs
$error = null;
$states = [];
$sensorsData = [];

try {
    // Vérifier la connexion
    if (!$client->checkConnection()) {
        throw new Exception('Impossible de se connecter à Home Assistant.');
    }

    // Récupérer les états des entités
    $states = $client->getStates();

    if (!is_array($states)) {
        $states = [];
    }

    $debugInfo['获取到的状态数'] = count($states);

    // Créer un index des états par entity_id
    $statesById = [];
    foreach ($states as $state) {
        $statesById[$state['entity_id']] = $state;
    }

    // Obtenir les données pour les capteurs configurés du groupe actuel
    if ($currentGroup && isset($currentGroup['sensors'])) {
        foreach ($currentGroup['sensors'] as $sensorConfig) {
            $entityId = $sensorConfig['entity_id'];
            if (isset($statesById[$entityId])) {
                $sensorsData[] = [
                    'config' => $sensorConfig,
                    'state' => $statesById[$entityId],
                ];
                $debugInfo['找到的传感器'][] = [
                    'entity_id' => $entityId,
                    'type' => $sensorConfig['type'],
                    'value' => $statesById[$entityId]['state']
                ];
            } else {
                $debugInfo['未找到的传感器'][] = $entityId;
            }
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
    $debugInfo['错误'] = $error;
}

?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>调试模式 - Sensors</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .debug-panel {
            background: #f8f8f8;
            border: 2px solid #ff9800;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            font-family: monospace;
        }
        .debug-panel h3 {
            margin-top: 0;
            color: #ff9800;
        }
        .debug-info {
            background: white;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .debug-key {
            font-weight: bold;
            color: #667eea;
        }
        .debug-value {
            color: #333;
        }
        .group-btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            border: 2px solid #667eea;
            font-weight: 600;
        }
        .group-btn.active {
            background: #667eea;
            color: white;
        }
        .sensor-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 10px;
            color: white;
            margin: 10px 0;
        }
        .temperature-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .humidity-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔍 调试模式 - 传感器页面</h1>
        </header>

        <!-- 调试面板 -->
        <div class="debug-panel">
            <h3>📋 调试信息</h3>
            <?php foreach ($debugInfo as $key => $value): ?>
                <div class="debug-info">
                    <span class="debug-key"><?= htmlspecialchars($key) ?>:</span>
                    <pre class="debug-value"><?= htmlspecialchars(print_r($value, true)) ?></pre>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 房间选择器 -->
        <div style="background: white; padding: 20px; border-radius: 10px; margin: 20px 0;">
            <h3>房间选择器</h3>
            <p>当前 URL: <code><?= htmlspecialchars($_SERVER['REQUEST_URI']) ?></code></p>
            <p>点击下面的按钮测试切换：</p>
            <div>
                <?php foreach ($sensorGroups as $group): ?>
                    <?php
                    $url = '?group=' . urlencode($group['id']) . '&lang=' . $currentLang;
                    $isActive = $group['id'] === $currentGroupId;
                    ?>
                    <a href="<?= $url ?>"
                       class="group-btn <?= $isActive ? 'active' : '' ?>"
                       onclick="console.log('点击:', '<?= $group['id'] ?>', 'URL:', '<?= $url ?>')">
                        <?= htmlspecialchars($group['name'][$currentLang] ?? $group['name']['fr']) ?>
                        <?php if ($isActive): ?>✓<?php endif; ?>
                    </a>
                    <br>
                    <small style="color: #666;">URL: debug-sensors.php<?= $url ?></small>
                    <br><br>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 传感器数据 -->
        <div style="background: white; padding: 20px; border-radius: 10px;">
            <h3>传感器数据</h3>
            <?php if ($error): ?>
                <div style="background: #fee; color: #c33; padding: 15px; border-radius: 5px;">
                    ❌ 错误: <?= htmlspecialchars($error) ?>
                </div>
            <?php elseif (empty($sensorsData)): ?>
                <div style="background: #ffc; padding: 15px; border-radius: 5px;">
                    ⚠️ 没有找到传感器数据
                </div>
            <?php else: ?>
                <?php foreach ($sensorsData as $sensorData): ?>
                    <?php
                    $sensorConfig = $sensorData['config'];
                    $state = $sensorData['state'];
                    $sensorType = $sensorConfig['type'];
                    $sensorName = $sensorConfig['name'][$currentLang] ?? $sensorConfig['name']['fr'];
                    $icon = $sensorConfig['icon'] ?? '📊';

                    $cardClass = match($sensorType) {
                        'temperature' => 'temperature-card',
                        'humidity' => 'humidity-card',
                        default => 'sensor-card',
                    };
                    ?>
                    <div class="sensor-card <?= $cardClass ?>">
                        <div style="font-size: 2em;"><?= $icon ?></div>
                        <h4><?= htmlspecialchars($sensorName) ?></h4>
                        <div style="font-size: 3em; font-weight: bold;">
                            <?= htmlspecialchars($state['state']) ?>
                            <?php if (isset($state['attributes']['unit_of_measurement'])): ?>
                                <span style="font-size: 0.5em;">
                                    <?= htmlspecialchars($state['attributes']['unit_of_measurement']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 0.9em; margin-top: 10px;">
                            Entity ID: <code><?= htmlspecialchars($sensorConfig['entity_id']) ?></code>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="margin-top: 20px; text-align: center;">
            <a href="sensors.php" style="color: #667eea; font-weight: bold;">← 返回正常页面</a>
        </div>
    </div>

    <script>
        console.log('调试信息:', <?= json_encode($debugInfo) ?>);
    </script>
</body>
</html>
