<?php
/**
 * Test WebSocket API pour les statistics
 * Home Assistant a déplacé les statistics de REST vers WebSocket en 2022
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require 'config.php';

// Parse l'URL pour obtenir l'hôte
$parsedUrl = parse_url($config['home_assistant_url']);
$host = $parsedUrl['host'];
$scheme = ($parsedUrl['scheme'] === 'https') ? 'wss' : 'ws';
$port = $parsedUrl['port'] ?? (($parsedUrl['scheme'] === 'https') ? 443 : 8123);

$wsUrl = "$scheme://$host:$port/api/websocket";

$entityId = 'sensor.wen_shi_du_chuan_gan_qi_yy_temperature';

// Calculer les dates
$now = new DateTime();
$start = clone $now;
$start->modify('-1 month');

$startISO = $start->format('c');
$endISO = $now->format('c');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test WebSocket Statistics</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #1890ff; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #ff9800; }
        .error { background: #ffe0e0; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #f44336; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔌 Test WebSocket Statistics API</h1>

    <div class='info'>
        <strong>ℹ️ Information importante:</strong><br>
        Depuis Home Assistant Core 2022.12, les statistics ont été déplacées de l'API REST vers l'API WebSocket.<br>
        <br>
        <strong>Ancien endpoint (SUPPRIMÉ):</strong> <code>GET /api/history/statistics_during_period</code><br>
        <strong>Nouveau endpoint:</strong> <code>WebSocket: recorder/statistics_during_period</code><br>
    </div>

    <div class='warning'>
        <strong>⚠️ Problème:</strong><br>
        PHP n'a pas de support WebSocket natif. Pour utiliser les statistics long-terme, nous avons deux options:<br>
        <br>
        <strong>Option 1:</strong> Utiliser une bibliothèque PHP WebSocket (ex: Ratchet, Textalk WebSocket)<br>
        <strong>Option 2:</strong> Utiliser JavaScript côté client pour se connecter au WebSocket<br>
        <strong>Option 3:</strong> Continuer avec l'API history (limité à ~7-10 jours selon votre configuration)
    </div>

    <h2>📋 Configuration détectée:</h2>
    <pre>WebSocket URL: $wsUrl
Host: $host
Port: $port
Entity: $entityId
Période: " . $start->format('Y-m-d H:i:s') . " à " . $now->format('Y-m-d H:i:s') . "</pre>

    <h2>💡 Solution recommandée:</h2>
";

// Vérifier si l'extension WebSocket est disponible
if (extension_loaded('sockets')) {
    echo "<div class='info'>
        <p>✅ Extension PHP 'sockets' est disponible</p>
        <p>Nous pouvons implémenter un client WebSocket personnalisé.</p>
    </div>";
} else {
    echo "<div class='warning'>
        <p>⚠️ Extension PHP 'sockets' n'est pas disponible</p>
    </div>";
}

echo "
    <h3>Option A: JavaScript WebSocket Client (Recommandé)</h3>
    <p>Créer une page qui utilise JavaScript pour se connecter au WebSocket de Home Assistant et récupérer les statistics.</p>
    <pre>// Exemple de code JavaScript
const ws = new WebSocket('$wsUrl');
ws.onopen = () => {
    // S'authentifier
    ws.send(JSON.stringify({
        type: 'auth',
        access_token: 'YOUR_TOKEN'
    }));
};

ws.onmessage = (event) => {
    const data = JSON.parse(event.data);
    if (data.type === 'auth_ok') {
        // Demander les statistics
        ws.send(JSON.stringify({
            id: 1,
            type: 'recorder/statistics_during_period',
            start_time: '$startISO',
            end_time: '$endISO',
            statistic_ids: ['$entityId'],
            period: 'hour'
        }));
    }
};</pre>

    <h3>Option B: Bibliothèque PHP WebSocket</h3>
    <p>Installer une bibliothèque comme <code>textalk/websocket</code> via Composer:</p>
    <pre>composer require textalk/websocket</pre>

    <h3>Option C: Continuer avec l'API History actuelle</h3>
    <p>L'API <code>/api/history/period</code> fonctionne et retourne les données des derniers jours.<br>
    Limitations: Limité à la période de rétention configurée (~7-10 jours par défaut)</p>

    <div class='info'>
        <h3>🎯 Ma recommandation:</h3>
        <p><strong>Créer une page hybride:</strong></p>
        <ul>
            <li>Utiliser l'API History REST (actuelle) pour les périodes courtes (jour, semaine)</li>
            <li>Ajouter JavaScript WebSocket pour les statistics long-terme (mois, année)</li>
            <li>Désactiver les boutons mois/année si WebSocket échoue</li>
        </ul>
        <p>Cela permettra de garder votre dashboard simple tout en supportant les statistics quand disponibles.</p>
    </div>

    <p><a href='history.php' style='color: #667eea; font-weight: bold;'>← Retour</a></p>
</body>
</html>";
?>
