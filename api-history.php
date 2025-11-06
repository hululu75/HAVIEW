<?php
/**
 * API pour récupérer l'historique des capteurs
 */

header('Content-Type: application/json');

// Charger la configuration
if (!file_exists('config.php')) {
    http_response_code(500);
    echo json_encode(['error' => 'Configuration manquante']);
    exit;
}

$config = require 'config.php';
require_once 'HomeAssistantClient.php';

// Initialiser le client Home Assistant
$client = new HomeAssistantClient(
    $config['home_assistant_url'],
    $config['access_token'],
    $config['timeout'] ?? 10
);

// Récupérer les paramètres
$period = $_GET['period'] ?? 'day';
$entityId = $_GET['entity_id'] ?? '';

if (empty($entityId)) {
    http_response_code(400);
    echo json_encode(['error' => 'entity_id requis']);
    exit;
}

// Calculer la période
$now = new DateTime();
$start = clone $now;

switch ($period) {
    case 'day':
        $start->modify('-1 day');
        break;
    case 'week':
        $start->modify('-1 week');
        break;
    case 'month':
        $start->modify('-1 month');
        break;
    case 'year':
        $start->modify('-1 year');
        break;
    default:
        $start->modify('-1 day');
}

try {
    // Traiter les données
    $data = [];

    // Pour les périodes longues (month, year), utiliser les statistiques
    // Pour les périodes courtes (day, week), utiliser l'historique détaillé
    if ($period === 'month' || $period === 'year') {
        // Utiliser l'API statistics pour les données long terme
        $statsPeriod = $period === 'year' ? 'day' : 'hour';

        $stats = $client->getStatistics(
            $start->format('Y-m-d\TH:i:s'),
            $now->format('Y-m-d\TH:i:s'),
            [$entityId],
            $statsPeriod
        );

        // Traiter les statistiques
        if (!empty($stats) && isset($stats[$entityId])) {
            foreach ($stats[$entityId] as $entry) {
                if (isset($entry['mean']) && is_numeric($entry['mean'])) {
                    $data[] = [
                        'timestamp' => $entry['start'],
                        'value' => (float)$entry['mean']
                    ];
                }
            }
        }
    } else {
        // Utiliser l'API history pour les données court terme
        $history = $client->getHistory(
            $start->format('Y-m-d\TH:i:s'),
            null,
            $entityId
        );

        if (!empty($history) && isset($history[0])) {
            foreach ($history[0] as $entry) {
                if (isset($entry['state']) && is_numeric($entry['state'])) {
                    $data[] = [
                        'timestamp' => $entry['last_changed'],
                        'value' => (float)$entry['state']
                    ];
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'entity_id' => $entityId,
        'period' => $period,
        'api_used' => ($period === 'month' || $period === 'year') ? 'statistics' : 'history',
        'start' => $start->format('Y-m-d H:i:s'),
        'end' => $now->format('Y-m-d H:i:s'),
        'data' => $data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
