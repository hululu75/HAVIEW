<?php
/**
 * 网页版配置向导
 * Web-based Configuration Wizard
 */

$error = null;
$success = null;
$configFile = __DIR__ . '/config.php';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $haUrl = trim($_POST['ha_url'] ?? '');
    $token = trim($_POST['token'] ?? '');
    $yyTemp = trim($_POST['yy_temp'] ?? '');
    $yyHum = trim($_POST['yy_hum'] ?? '');
    $cuisineTemp = trim($_POST['cuisine_temp'] ?? '');
    $cuisineHum = trim($_POST['cuisine_hum'] ?? '');
    $addCuisine = isset($_POST['add_cuisine']);

    if (empty($haUrl) || empty($token) || empty($yyTemp) || empty($yyHum)) {
        $error = '请填写所有必需字段';
    } else {
        // 移除 URL 末尾斜杠
        $haUrl = rtrim($haUrl, '/');

        // 生成配置
        $config = [
            'home_assistant_url' => $haUrl,
            'access_token' => $token,
            'timeout' => 10,
            'sensor_groups' => [
                [
                    'id' => 'yy_room',
                    'name' => [
                        'fr' => 'Chambre de YY',
                        'en' => 'YY\'s Room',
                        'zh' => 'YY的房间',
                    ],
                    'sensors' => [
                        [
                            'type' => 'temperature',
                            'entity_id' => $yyTemp,
                            'icon' => '🌡️',
                            'name' => [
                                'fr' => 'Température',
                                'en' => 'Temperature',
                                'zh' => '温度',
                            ],
                        ],
                        [
                            'type' => 'humidity',
                            'entity_id' => $yyHum,
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
        ];

        // 添加 Cuisine 配置
        if ($addCuisine && !empty($cuisineTemp) && !empty($cuisineHum)) {
            $config['sensor_groups'][] = [
                'id' => 'cuisine',
                'name' => [
                    'fr' => 'Cuisine',
                    'en' => 'Kitchen',
                    'zh' => '厨房',
                ],
                'sensors' => [
                    [
                        'type' => 'temperature',
                        'entity_id' => $cuisineTemp,
                        'icon' => '🌡️',
                        'name' => [
                            'fr' => 'Température',
                            'en' => 'Temperature',
                            'zh' => '温度',
                        ],
                    ],
                    [
                        'type' => 'humidity',
                        'entity_id' => $cuisineHum,
                        'icon' => '💧',
                        'name' => [
                            'fr' => 'Humidité',
                            'en' => 'Humidity',
                            'zh' => '湿度',
                        ],
                    ],
                ],
            ];
        }

        // 备份现有配置
        if (file_exists($configFile)) {
            $backup = $configFile . '.backup-' . date('YmdHis');
            copy($configFile, $backup);
        }

        // 写入配置文件
        $configCode = "<?php\n/**\n * Configuration généré par setup.php\n * Generated at: " . date('Y-m-d H:i:s') . "\n */\n\nreturn " . var_export($config, true) . ";\n";

        if (file_put_contents($configFile, $configCode)) {
            $success = true;
        } else {
            $error = '无法写入配置文件。请检查文件权限。';
        }
    }
}

// 检查现有配置
$hasConfig = file_exists($configFile);
$currentConfig = null;

if ($hasConfig) {
    $currentConfig = require $configFile;
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>配置向导 - Home Assistant Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .setup-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .setup-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .setup-header h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-hint {
            font-size: 0.9em;
            color: #666;
            margin-top: 5px;
        }

        .section-title {
            font-size: 1.3em;
            color: #667eea;
            margin: 30px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: 600;
        }

        .cuisine-fields {
            display: none;
            margin-left: 30px;
            padding: 20px;
            background: #f8f8f8;
            border-radius: 8px;
            margin-top: 15px;
        }

        .cuisine-fields.active {
            display: block;
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
            margin-top: 30px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }

        .alert-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }

        .success-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .success-actions a {
            flex: 1;
            min-width: 200px;
            padding: 15px;
            background: white;
            border: 2px solid #667eea;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: all 0.3s;
        }

        .success-actions a:hover {
            background: #667eea;
            color: white;
        }

        .current-config {
            background: #f8f8f8;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .current-config h3 {
            margin-top: 0;
            color: #667eea;
        }

        .config-item {
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }

        .auto-detect-btn {
            padding: 10px 20px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
        }

        .auto-detect-btn:hover {
            background: #45a049;
        }

        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1>🏠 配置向导</h1>
            <p>Home Assistant Dashboard Configuration</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <h3>✅ 配置创建成功！</h3>
                <p>config.php 已成功创建并保存。</p>

                <div class="success-actions">
                    <a href="sensors.php">📊 查看传感器页面</a>
                    <a href="check-config.php">🔍 验证配置</a>
                    <a href="find-sensors.php">🔎 查找传感器</a>
                </div>
            </div>

            <div style="margin-top: 20px; text-align: center;">
                <a href="setup.php" style="color: #667eea;">← 返回配置向导</a>
            </div>
        <?php else: ?>

            <?php if ($hasConfig && $currentConfig): ?>
                <div class="current-config">
                    <h3>📄 当前配置</h3>
                    <div class="config-item">
                        <strong>URL:</strong> <?= htmlspecialchars($currentConfig['home_assistant_url'] ?? '未设置') ?>
                    </div>
                    <div class="config-item">
                        <strong>传感器组:</strong>
                        <?php
                        if (isset($currentConfig['sensor_groups'])) {
                            foreach ($currentConfig['sensor_groups'] as $group) {
                                echo htmlspecialchars($group['name']['zh'] ?? $group['name']['fr'] ?? '未知') . ' ';
                            }
                        } else {
                            echo '无';
                        }
                        ?>
                    </div>
                    <p style="color: #666; margin-top: 10px;">提交新配置将覆盖现有配置（会自动备份）</p>
                </div>
            <?php endif; ?>

            <form method="POST" id="setupForm">
                <div class="section-title">🔗 连接信息</div>

                <div class="form-group">
                    <label for="ha_url">Home Assistant URL *</label>
                    <input type="text"
                           id="ha_url"
                           name="ha_url"
                           placeholder="http://192.168.1.100:8123"
                           value="<?= htmlspecialchars($currentConfig['home_assistant_url'] ?? '') ?>"
                           required>
                    <div class="form-hint">例如: http://homeassistant.local:8123 或 http://192.168.1.100:8123</div>
                </div>

                <div class="form-group">
                    <label for="token">Access Token (长期访问令牌) *</label>
                    <input type="password"
                           id="token"
                           name="token"
                           placeholder="eyJ0eXAiOiJKV1QiLCJhbG..."
                           required>
                    <div class="form-hint">
                        在 Home Assistant 中生成: 个人资料 → 安全 → 长期访问令牌
                    </div>
                </div>

                <div class="section-title">🏠 YY的房间 传感器</div>

                <div class="form-group">
                    <label for="yy_temp">温度传感器 Entity ID *</label>
                    <input type="text"
                           id="yy_temp"
                           name="yy_temp"
                           placeholder="sensor.wen_shi_du_chuan_gan_qi_yy_temperature"
                           required>
                    <div class="form-hint">例如: sensor.wen_shi_du_chuan_gan_qi_yy_temperature</div>
                </div>

                <div class="form-group">
                    <label for="yy_hum">湿度传感器 Entity ID *</label>
                    <input type="text"
                           id="yy_hum"
                           name="yy_hum"
                           placeholder="sensor.wen_shi_du_chuan_gan_qi_yy_humidity"
                           required>
                    <div class="form-hint">例如: sensor.wen_shi_du_chuan_gan_qi_yy_humidity</div>
                </div>

                <div class="section-title">🍳 厨房 (Cuisine) 传感器</div>

                <div class="checkbox-group">
                    <input type="checkbox" id="add_cuisine" name="add_cuisine" onchange="toggleCuisine()">
                    <label for="add_cuisine">添加厨房传感器配置</label>
                </div>

                <div class="cuisine-fields" id="cuisineFields">
                    <div class="form-group">
                        <label for="cuisine_temp">温度传感器 Entity ID</label>
                        <input type="text"
                               id="cuisine_temp"
                               name="cuisine_temp"
                               placeholder="sensor.wen_shi_du_chuan_gan_qi_cuisine_temperature">
                    </div>

                    <div class="form-group">
                        <label for="cuisine_hum">湿度传感器 Entity ID</label>
                        <input type="text"
                               id="cuisine_hum"
                               name="cuisine_hum"
                               placeholder="sensor.wen_shi_du_chuan_gan_qi_cuisine_humidity">
                    </div>
                </div>

                <button type="submit" class="btn-submit">💾 保存配置</button>
            </form>

            <div style="margin-top: 30px; text-align: center; color: #666;">
                <p>💡 提示: 不确定传感器 ID？</p>
                <a href="find-sensors.php" target="_blank" style="color: #667eea; font-weight: 600;">
                    点击这里查找所有可用传感器 →
                </a>
            </div>

        <?php endif; ?>
    </div>

    <script>
        function toggleCuisine() {
            const checkbox = document.getElementById('add_cuisine');
            const fields = document.getElementById('cuisineFields');
            fields.classList.toggle('active', checkbox.checked);
        }

        // 恢复复选框状态
        if (document.getElementById('add_cuisine').checked) {
            toggleCuisine();
        }
    </script>
</body>
</html>
