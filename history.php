<?php
/**
 * Page d'historique pour les capteurs de température et humidité
 */

// Charger la configuration
if (!file_exists('config.php')) {
    die('Erreur: Le fichier config.php n\'existe pas. Copiez config.example.php vers config.php et configurez vos paramètres.');
}

$config = require 'config.php';
require_once 'HomeAssistantClient.php';

// Initialiser le client Home Assistant
$client = new HomeAssistantClient(
    $config['home_assistant_url'],
    $config['access_token'],
    $config['timeout'] ?? 10
);

// Récupérer les états actuels pour trouver les capteurs
$states = [];
$error = null;

try {
    $states = $client->getStates();
    if (!is_array($states)) {
        $states = [];
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Filtrer pour trouver les capteurs de YY
$sensors = [];
if (!empty($states)) {
    foreach ($states as $state) {
        $entityId = $state['entity_id'];
        $friendlyName = $state['attributes']['friendly_name'] ?? $entityId;

        // Vérifier si c'est un capteur de "YY的房间"
        if (stripos($friendlyName, 'YY的房间') !== false || stripos($friendlyName, 'YY') !== false) {
            // Vérifier si c'est température ou humidité
            if (stripos($friendlyName, 'Température') !== false ||
                stripos($friendlyName, 'Temperature') !== false ||
                stripos($friendlyName, '温度') !== false ||
                stripos($entityId, 'temperature') !== false) {
                $sensors['temperature'] = [
                    'entity_id' => $entityId,
                    'name' => $friendlyName,
                    'current_value' => $state['state'],
                    'unit' => $state['attributes']['unit_of_measurement'] ?? ''
                ];
            } elseif (stripos($friendlyName, 'Humidité') !== false ||
                      stripos($friendlyName, 'Humidity') !== false ||
                      stripos($friendlyName, '湿度') !== false ||
                      stripos($entityId, 'humidity') !== false) {
                $sensors['humidity'] = [
                    'entity_id' => $entityId,
                    'name' => $friendlyName,
                    'current_value' => $state['state'],
                    'unit' => $state['attributes']['unit_of_measurement'] ?? ''
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>历史数据 - YY的房间</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
    <style>
        .period-selector {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .period-btn {
            padding: 12px 30px;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .period-btn:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

        .period-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .chart-wrapper {
            position: relative;
            height: 400px;
            margin-top: 20px;
        }

        .chart-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .current-value {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }

        .loading {
            text-align: center;
            padding: 50px;
            color: #999;
            font-size: 1.2em;
        }

        .error-box {
            background: #fee;
            border: 2px solid #e53e3e;
            padding: 20px;
            border-radius: 10px;
            color: #c53030;
            margin: 20px 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-box {
            background: #f8f8f8;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .stat-value {
            color: #333;
            font-size: 1.3em;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .chart-wrapper {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 历史数据 - YY的房间</h1>
            <p class="subtitle">温度和湿度历史记录</p>
        </header>

        <?php if ($error): ?>
            <div class="error-box">
                <strong>错误:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif (empty($sensors)): ?>
            <div class="error-box">
                <strong>未找到传感器</strong><br>
                没有找到YY的房间的温度或湿度传感器。
                <p style="margin-top: 15px;">
                    <a href="index.php" style="color: #667eea; font-weight: bold;">← 返回主页</a>
                </p>
            </div>
        <?php else: ?>
            <!-- 时间周期选择器 -->
            <div class="period-selector">
                <button class="period-btn active" data-period="day">最近一天</button>
                <button class="period-btn" data-period="week">最近一周</button>
                <button class="period-btn" data-period="month">最近一月</button>
                <button class="period-btn" data-period="year">最近一年</button>
            </div>

            <!-- 温度图表 -->
            <?php if (isset($sensors['temperature'])): ?>
            <div class="chart-container">
                <div class="chart-title">
                    🌡️ 温度 - <?= htmlspecialchars($sensors['temperature']['name']) ?>
                </div>
                <div class="current-value">
                    当前: <?= htmlspecialchars($sensors['temperature']['current_value']) ?> <?= htmlspecialchars($sensors['temperature']['unit']) ?>
                </div>
                <div id="temp-stats" class="stats-grid"></div>
                <div class="chart-wrapper">
                    <canvas id="temperatureChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

            <!-- 湿度图表 -->
            <?php if (isset($sensors['humidity'])): ?>
            <div class="chart-container">
                <div class="chart-title">
                    💧 湿度 - <?= htmlspecialchars($sensors['humidity']['name']) ?>
                </div>
                <div class="current-value">
                    当前: <?= htmlspecialchars($sensors['humidity']['current_value']) ?> <?= htmlspecialchars($sensors['humidity']['unit']) ?>
                </div>
                <div id="humidity-stats" class="stats-grid"></div>
                <div class="chart-wrapper">
                    <canvas id="humidityChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <footer>
            <p>最后更新: <?= date('Y-m-d H:i:s') ?></p>
            <p>
                <a href="sensors.php">🌡️ 当前数据</a> |
                <a href="index.php">📊 所有实体</a> |
                <a href="debug.php">🔍 诊断</a>
            </p>
        </footer>
    </div>

    <script>
        // Configuration des capteurs depuis PHP
        const sensors = <?= json_encode($sensors) ?>;
        let currentPeriod = 'day';
        let temperatureChart = null;
        let humidityChart = null;

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Event listeners pour les boutons de période
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    currentPeriod = this.dataset.period;
                    loadAllData();
                });
            });

            // Charger les données initiales
            loadAllData();
        });

        // Charger toutes les données
        async function loadAllData() {
            if (sensors.temperature) {
                await loadHistory('temperature', sensors.temperature.entity_id);
            }
            if (sensors.humidity) {
                await loadHistory('humidity', sensors.humidity.entity_id);
            }
        }

        // Charger l'historique pour un capteur
        async function loadHistory(type, entityId) {
            try {
                const response = await fetch(`api-history.php?entity_id=${encodeURIComponent(entityId)}&period=${currentPeriod}`);
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    const data = result.data;

                    // Calculer les statistiques
                    const values = data.map(d => d.value);
                    const stats = {
                        min: Math.min(...values).toFixed(1),
                        max: Math.max(...values).toFixed(1),
                        avg: (values.reduce((a, b) => a + b, 0) / values.length).toFixed(1),
                        count: data.length
                    };

                    // Afficher les statistiques
                    displayStats(type, stats, sensors[type].unit);

                    // Créer ou mettre à jour le graphique
                    createChart(type, data, sensors[type].name, sensors[type].unit);
                } else {
                    console.error('Pas de données pour', type);
                }
            } catch (error) {
                console.error('Erreur lors du chargement des données:', error);
            }
        }

        // Afficher les statistiques
        function displayStats(type, stats, unit) {
            const statsDiv = document.getElementById(`${type}-stats`);
            statsDiv.innerHTML = `
                <div class="stat-box">
                    <div class="stat-label">最小值</div>
                    <div class="stat-value">${stats.min} ${unit}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">最大值</div>
                    <div class="stat-value">${stats.max} ${unit}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">平均值</div>
                    <div class="stat-value">${stats.avg} ${unit}</div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">数据点</div>
                    <div class="stat-value">${stats.count}</div>
                </div>
            `;
        }

        // Créer ou mettre à jour un graphique
        function createChart(type, data, name, unit) {
            const canvasId = type === 'temperature' ? 'temperatureChart' : 'humidityChart';
            const ctx = document.getElementById(canvasId).getContext('2d');

            // Couleurs selon le type
            const colors = type === 'temperature'
                ? {
                    border: 'rgb(255, 99, 132)',
                    background: 'rgba(255, 99, 132, 0.1)'
                }
                : {
                    border: 'rgb(54, 162, 235)',
                    background: 'rgba(54, 162, 235, 0.1)'
                };

            // Préparer les données pour Chart.js
            const chartData = data.map(d => ({
                x: new Date(d.timestamp),
                y: d.value
            }));

            // Détruire le graphique existant
            if (type === 'temperature' && temperatureChart) {
                temperatureChart.destroy();
            } else if (type === 'humidity' && humidityChart) {
                humidityChart.destroy();
            }

            // Créer le nouveau graphique
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: [{
                        label: name,
                        data: chartData,
                        borderColor: colors.border,
                        backgroundColor: colors.background,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 2,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.parsed.y.toFixed(1)} ${unit}`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                displayFormats: {
                                    hour: 'HH:mm',
                                    day: 'MM-dd',
                                    week: 'MM-dd',
                                    month: 'yyyy-MM'
                                }
                            },
                            title: {
                                display: true,
                                text: '时间'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: unit
                            },
                            beginAtZero: false
                        }
                    }
                }
            });

            // Sauvegarder la référence
            if (type === 'temperature') {
                temperatureChart = chart;
            } else {
                humidityChart = chart;
            }
        }
    </script>
</body>
</html>
