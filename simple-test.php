<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>简单测试</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .box { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        a { display: inline-block; padding: 10px 20px; background: #667eea; color: white;
            text-decoration: none; border-radius: 5px; margin: 5px; }
        .info { background: #e3f2fd; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🧪 房间切换测试</h1>

    <?php
    $config = require 'config.php';
    $requestedGroup = $_GET['group'] ?? 'none';
    $currentLang = $_GET['lang'] ?? 'zh';
    ?>

    <div class="info">
        <strong>当前 URL:</strong> <?= htmlspecialchars($_SERVER['REQUEST_URI']) ?><br>
        <strong>请求的房间:</strong> <?= htmlspecialchars($requestedGroup) ?><br>
        <strong>语言:</strong> <?= htmlspecialchars($currentLang) ?>
    </div>

    <div class="box">
        <h3>配置的房间:</h3>
        <?php foreach ($config['sensor_groups'] as $group): ?>
            <div>
                ✓ <?= htmlspecialchars($group['name']['zh']) ?> (ID: <?= htmlspecialchars($group['id']) ?>)
            </div>
        <?php endforeach; ?>
    </div>

    <div class="box">
        <h3>点击测试切换:</h3>
        <?php foreach ($config['sensor_groups'] as $group): ?>
            <?php
            $url = '?group=' . urlencode($group['id']) . '&lang=' . $currentLang;
            $isActive = ($group['id'] === $requestedGroup);
            ?>
            <a href="<?= $url ?>" style="<?= $isActive ? 'background:#f5576c;' : '' ?>">
                <?= htmlspecialchars($group['name']['zh']) ?>
                <?= $isActive ? '✓' : '' ?>
            </a>
        <?php endforeach; ?>
        <div style="margin-top:10px; font-size:12px; color:#666;">
            点击按钮后，URL应该变化并且"请求的房间"应该更新
        </div>
    </div>

    <div class="box">
        <h3>显示的传感器:</h3>
        <?php
        require_once 'HomeAssistantClient.php';
        $client = new HomeAssistantClient(
            $config['home_assistant_url'],
            $config['access_token'],
            $config['timeout'] ?? 10
        );

        // 找到当前组
        $currentGroup = null;
        foreach ($config['sensor_groups'] as $group) {
            if ($group['id'] === $requestedGroup) {
                $currentGroup = $group;
                break;
            }
        }

        if ($currentGroup) {
            echo "<strong>当前房间: " . htmlspecialchars($currentGroup['name']['zh']) . "</strong><br>";

            try {
                $states = $client->getStates();
                $statesById = [];
                foreach ($states as $state) {
                    $statesById[$state['entity_id']] = $state;
                }

                foreach ($currentGroup['sensors'] as $sensor) {
                    $entityId = $sensor['entity_id'];
                    $type = $sensor['type'];

                    if (isset($statesById[$entityId])) {
                        $value = $statesById[$entityId]['state'];
                        $unit = $statesById[$entityId]['attributes']['unit_of_measurement'] ?? '';
                        echo "<div style='margin:5px 0;'>
                                {$sensor['icon']} {$sensor['name']['zh']}:
                                <strong>$value $unit</strong>
                                <small style='color:#666;'>($entityId)</small>
                              </div>";
                    } else {
                        echo "<div style='color:red;'>❌ 未找到: $entityId</div>";
                    }
                }
            } catch (Exception $e) {
                echo "<div style='color:red;'>错误: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            echo "<div style='color:#999;'>未选择房间（请点击上面的按钮）</div>";
        }
        ?>
    </div>

    <hr>
    <div style="text-align:center;">
        <a href="sensors.php">返回 sensors.php</a>
        <a href="debug-sensors.php">查看调试模式</a>
    </div>
</body>
</html>
