<?php
/**
 * Page d'historique avec support WebSocket pour les statistics long-terme
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

        // Exclure les capteurs de batterie
        if (stripos($friendlyName, 'batterie') !== false ||
            stripos($friendlyName, 'battery') !== false ||
            stripos($entityId, 'battery') !== false) {
            continue;
        }

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
                    'unit' => $state['attributes']['unit_of_measurement'] ?? ''
                ];
            } elseif (stripos($friendlyName, 'Humidité') !== false ||
                      stripos($friendlyName, 'Humidity') !== false ||
                      stripos($friendlyName, '湿度') !== false ||
                      stripos($entityId, 'humidity') !== false) {
                $sensors['humidity'] = [
                    'entity_id' => $entityId,
                    'name' => $friendlyName,
                    'unit' => $state['attributes']['unit_of_measurement'] ?? ''
                ];
            }
        }
    }
}

// Parse l'URL pour le WebSocket
$parsedUrl = parse_url($config['home_assistant_url']);
$host = $parsedUrl['host'];
$scheme = ($parsedUrl['scheme'] === 'https') ? 'wss' : 'ws';
$port = $parsedUrl['port'] ?? (($parsedUrl['scheme'] === 'https') ? 443 : 8123);
$wsUrl = "$scheme://$host:$port/api/websocket";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique - YY的房间</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        h1 {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .sensor-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .sensor-tab {
            flex: 1;
            padding: 15px;
            background: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .sensor-tab:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .sensor-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .period-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .period-btn {
            padding: 12px 24px;
            background: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .period-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .period-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .period-btn:disabled {
            background: #e0e0e0;
            color: #999;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .period-btn:disabled:hover {
            transform: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .chart-container {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .stat-value {
            color: #667eea;
            font-size: 1.8em;
            font-weight: bold;
        }

        .api-method {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #1890ff;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: white;
            font-size: 1.2em;
        }

        .error {
            background: #ffe0e0;
            color: #d32f2f;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #d32f2f;
        }

        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #ff9800;
        }

        .info {
            background: #e7f3ff;
            color: #014361;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #1890ff;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }

        #debugInfo {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-family: monospace;
            font-size: 0.9em;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📈 Historique - YY的房间</h1>
            <p>Visualisation des données historiques avec support WebSocket</p>
            <a href="sensors.php" class="back-link">← Retour aux capteurs</a>
        </div>

        <?php if ($error): ?>
            <div class="error">
                <strong>Erreur:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php elseif (empty($sensors)): ?>
            <div class="warning">
                <strong>Attention:</strong> Aucun capteur trouvé pour YY的房间
            </div>
        <?php else: ?>
            <div class="sensor-tabs">
                <?php if (isset($sensors['temperature'])): ?>
                    <button class="sensor-tab active" data-sensor="temperature">
                        🌡️ <?= htmlspecialchars($sensors['temperature']['name']) ?>
                    </button>
                <?php endif; ?>
                <?php if (isset($sensors['humidity'])): ?>
                    <button class="sensor-tab" data-sensor="humidity">
                        💧 <?= htmlspecialchars($sensors['humidity']['name']) ?>
                    </button>
                <?php endif; ?>
            </div>

            <div class="api-method" id="apiMethodInfo">
                <strong>📡 Méthode API:</strong> <span id="apiMethodText">Chargement...</span>
            </div>

            <div id="wsStatusMessage" style="display: none;"></div>

            <div class="period-selector">
                <button class="period-btn active" data-period="day">📅 Jour</button>
                <button class="period-btn" data-period="week">📅 Semaine</button>
                <button class="period-btn" data-period="month">📅 Mois</button>
                <button class="period-btn" data-period="year">📅 Année</button>
            </div>

            <div class="stats-container" id="statsContainer"></div>

            <div class="chart-container">
                <canvas id="historyChart"></canvas>
            </div>

            <div id="debugInfo" style="display: none;">
                <strong>Debug Info:</strong><br>
                <div id="debugContent"></div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Configuration
        const config = {
            haUrl: <?= json_encode($config['home_assistant_url']) ?>,
            accessToken: <?= json_encode($config['access_token']) ?>,
            wsUrl: <?= json_encode($wsUrl) ?>,
            sensors: <?= json_encode($sensors) ?>
        };

        let currentSensor = 'temperature';
        let currentPeriod = 'day';
        let chart = null;
        let ws = null;
        let wsAuthenticated = false;
        let wsMessageId = 1;
        let wsConnectionTimeout = null;
        let wsAvailable = false;

        // Initialisation
        document.addEventListener('DOMContentLoaded', () => {
            setupEventListeners();
            initWebSocket();
            loadData();
        });

        function setupEventListeners() {
            // Tabs de capteurs
            document.querySelectorAll('.sensor-tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    document.querySelectorAll('.sensor-tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    currentSensor = tab.dataset.sensor;
                    loadData();
                });
            });

            // Boutons de période
            document.querySelectorAll('.period-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentPeriod = btn.dataset.period;
                    loadData();
                });
            });
        }

        function initWebSocket() {
            // Timeout de 5 secondes pour la connexion WebSocket
            wsConnectionTimeout = setTimeout(() => {
                if (!wsAuthenticated) {
                    debug('Timeout de connexion WebSocket - désactivation des périodes longues', true);
                    disableLongTermPeriods('WebSocket indisponible ou timeout de connexion');
                }
            }, 5000);

            try {
                debug('Tentative de connexion WebSocket à: ' + config.wsUrl);
                ws = new WebSocket(config.wsUrl);

                ws.onopen = () => {
                    debug('WebSocket connecté, en attente d\'authentification...');
                };

                ws.onmessage = (event) => {
                    const data = JSON.parse(event.data);
                    debug('WebSocket message reçu: ' + data.type);

                    if (data.type === 'auth_required') {
                        debug('Authentification requise, envoi du token...');
                        ws.send(JSON.stringify({
                            type: 'auth',
                            access_token: config.accessToken
                        }));
                    } else if (data.type === 'auth_ok') {
                        debug('✅ Authentification WebSocket réussie!');
                        wsAuthenticated = true;
                        wsAvailable = true;
                        clearTimeout(wsConnectionTimeout);
                        showWebSocketStatus(true);
                    } else if (data.type === 'auth_invalid') {
                        debug('❌ Authentification WebSocket échouée: ' + data.message, true);
                        wsAuthenticated = false;
                        clearTimeout(wsConnectionTimeout);
                        disableLongTermPeriods('Authentification WebSocket échouée');
                    } else if (data.type === 'result') {
                        handleStatisticsResult(data);
                    }
                };

                ws.onerror = (error) => {
                    debug('❌ Erreur WebSocket', true);
                    clearTimeout(wsConnectionTimeout);
                    disableLongTermPeriods('Erreur de connexion WebSocket');
                };

                ws.onclose = () => {
                    debug('WebSocket déconnecté');
                    wsAuthenticated = false;
                    if (wsAvailable) {
                        // Si c'était disponible avant, afficher un message
                        showWebSocketStatus(false);
                    }
                };
            } catch (error) {
                debug('❌ Erreur lors de la création du WebSocket: ' + error, true);
                clearTimeout(wsConnectionTimeout);
                disableLongTermPeriods('WebSocket non supporté ou erreur de connexion');
            }
        }

        function disableLongTermPeriods(reason) {
            debug('Désactivation des périodes longues: ' + reason);

            // Désactiver les boutons mois et année
            document.querySelectorAll('.period-btn').forEach(btn => {
                if (btn.dataset.period === 'month' || btn.dataset.period === 'year') {
                    btn.disabled = true;
                    btn.title = 'WebSocket requis pour les statistics long-terme';
                }
            });

            // Afficher un message d'information
            const msgDiv = document.getElementById('wsStatusMessage');
            if (msgDiv) {
                msgDiv.className = 'warning';
                msgDiv.style.display = 'block';
                msgDiv.innerHTML = `
                    <strong>⚠️ Statistics long-terme indisponibles</strong><br>
                    ${reason}<br>
                    <small>Les périodes "Mois" et "Année" sont désactivées. Utilisez "Jour" ou "Semaine" pour voir l'historique récent.</small>
                `;
            }

            // Si on était sur mois ou année, basculer sur jour
            if (currentPeriod === 'month' || currentPeriod === 'year') {
                currentPeriod = 'day';
                document.querySelectorAll('.period-btn').forEach(btn => {
                    btn.classList.remove('active');
                    if (btn.dataset.period === 'day') {
                        btn.classList.add('active');
                    }
                });
                loadData();
            }
        }

        function showWebSocketStatus(connected) {
            const msgDiv = document.getElementById('wsStatusMessage');
            if (msgDiv) {
                if (connected) {
                    msgDiv.className = 'success';
                    msgDiv.style.display = 'block';
                    msgDiv.innerHTML = `
                        <strong>✅ WebSocket connecté</strong><br>
                        Les statistics long-terme sont disponibles pour les périodes "Mois" et "Année"
                    `;
                    // Masquer le message après 5 secondes
                    setTimeout(() => {
                        msgDiv.style.display = 'none';
                    }, 5000);
                } else {
                    msgDiv.className = 'info';
                    msgDiv.style.display = 'block';
                    msgDiv.innerHTML = `
                        <strong>ℹ️ WebSocket déconnecté</strong><br>
                        Reconnexion automatique en cours...
                    `;
                }
            }
        }

        function loadData() {
            const sensor = config.sensors[currentSensor];
            if (!sensor) return;

            // Utiliser WebSocket pour mois et année, REST API pour jour et semaine
            if ((currentPeriod === 'month' || currentPeriod === 'year') && wsAuthenticated) {
                updateApiMethodInfo('WebSocket (recorder/statistics_during_period)');
                loadStatisticsViaWebSocket(sensor.entity_id);
            } else {
                updateApiMethodInfo('REST API (/api/history/period)');
                loadHistoryViaRest(sensor.entity_id);
            }
        }

        function loadStatisticsViaWebSocket(entityId) {
            if (!ws || !wsAuthenticated) {
                debug('WebSocket non disponible, basculement sur REST API', true);
                loadHistoryViaRest(entityId);
                return;
            }

            const now = new Date();
            const start = new Date(now);

            switch (currentPeriod) {
                case 'month':
                    start.setMonth(start.getMonth() - 1);
                    break;
                case 'year':
                    start.setFullYear(start.getFullYear() - 1);
                    break;
            }

            const message = {
                id: wsMessageId++,
                type: 'recorder/statistics_during_period',
                start_time: start.toISOString(),
                end_time: now.toISOString(),
                statistic_ids: [entityId],
                period: currentPeriod === 'year' ? 'day' : 'hour',
                types: ['mean', 'min', 'max']
            };

            debug('Envoi de la requête WebSocket: ' + JSON.stringify(message));
            ws.send(JSON.stringify(message));
        }

        function handleStatisticsResult(data) {
            debug('Résultat statistics reçu');

            if (!data.success) {
                debug('Erreur dans la réponse: ' + JSON.stringify(data), true);
                loadHistoryViaRest(config.sensors[currentSensor].entity_id);
                return;
            }

            const result = data.result;
            const sensor = config.sensors[currentSensor];
            const entityId = sensor.entity_id;

            if (!result || !result[entityId] || result[entityId].length === 0) {
                debug('Aucune donnée statistics, basculement sur REST API', true);
                loadHistoryViaRest(entityId);
                return;
            }

            const stats = result[entityId];
            const chartData = stats.map(item => ({
                timestamp: item.start || item.end,
                value: item.mean || item.state
            }));

            displayChart(chartData, sensor);
            displayStats(chartData, sensor);
        }

        function loadHistoryViaRest(entityId) {
            fetch(`api-history.php?entity_id=${entityId}&period=${currentPeriod}`)
                .then(response => response.json())
                .then(data => {
                    debug('Données REST reçues: ' + data.data_count + ' points');

                    if (data.success && data.data && data.data.length > 0) {
                        displayChart(data.data, config.sensors[currentSensor]);
                        displayStats(data.data, config.sensors[currentSensor]);
                    } else {
                        debug('Aucune donnée disponible pour cette période', true);
                        displayChart([], config.sensors[currentSensor]);
                        displayStats([], config.sensors[currentSensor]);
                    }
                })
                .catch(error => {
                    debug('Erreur lors du chargement: ' + error, true);
                });
        }

        function displayChart(data, sensor) {
            const ctx = document.getElementById('historyChart').getContext('2d');

            if (chart) {
                chart.destroy();
            }

            const labels = data.map(item => {
                const date = new Date(item.timestamp);
                return date.toLocaleString('fr-FR', {
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            });

            const values = data.map(item => item.value);

            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: sensor.name + ' (' + sensor.unit + ')',
                        data: values,
                        borderColor: currentSensor === 'temperature' ? '#f44336' : '#2196F3',
                        backgroundColor: currentSensor === 'temperature' ? 'rgba(244, 67, 54, 0.1)' : 'rgba(33, 150, 243, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            });
        }

        function displayStats(data, sensor) {
            const container = document.getElementById('statsContainer');
            if (!container) return;

            if (data.length === 0) {
                container.innerHTML = '<div class="warning">Aucune donnée disponible pour cette période</div>';
                return;
            }

            const values = data.map(item => item.value);
            const min = Math.min(...values).toFixed(1);
            const max = Math.max(...values).toFixed(1);
            const avg = (values.reduce((a, b) => a + b, 0) / values.length).toFixed(1);
            const count = values.length;

            container.innerHTML = `
                <div class="stat-card">
                    <div class="stat-label">Minimum</div>
                    <div class="stat-value">${min} ${sensor.unit}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Maximum</div>
                    <div class="stat-value">${max} ${sensor.unit}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Moyenne</div>
                    <div class="stat-value">${avg} ${sensor.unit}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Points de données</div>
                    <div class="stat-value">${count}</div>
                </div>
            `;
        }

        function updateApiMethodInfo(method) {
            const elem = document.getElementById('apiMethodText');
            if (elem) {
                elem.textContent = method;
            }
        }

        function debug(message, isError = false) {
            console.log(message);
            const debugDiv = document.getElementById('debugContent');
            if (debugDiv) {
                const time = new Date().toLocaleTimeString();
                const color = isError ? 'red' : 'inherit';
                debugDiv.innerHTML += `<div style="color: ${color}">[${time}] ${message}</div>`;
                document.getElementById('debugInfo').style.display = 'block';
            }
        }
    </script>
</body>
</html>
