<?php
/**
 * API endpoint for fetching historical sensor data
 * Supports different time periods: day, week, month, year
 */

header('Content-Type: application/json');

// Load configuration
if (!file_exists('config.php')) {
    echo json_encode([
        'success' => false,
        'error' => 'Configuration file not found',
        'data' => [],
        'data_count' => 0
    ]);
    exit;
}

$config = require 'config.php';
require_once 'HomeAssistantClient.php';

// Get parameters
$entityId = $_GET['entity_id'] ?? null;
$period = $_GET['period'] ?? 'day';

if (!$entityId) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing entity_id parameter',
        'data' => [],
        'data_count' => 0
    ]);
    exit;
}

// Calculate start and end times based on period
$endTime = new DateTime('now');
$startTime = new DateTime('now');

switch ($period) {
    case 'day':
        $startTime->modify('-1 day');
        $statisticsPeriod = '5minute';
        break;
    case 'week':
        $startTime->modify('-7 days');
        $statisticsPeriod = 'hour';
        break;
    case 'month':
        $startTime->modify('-1 month');
        $statisticsPeriod = 'hour';
        break;
    case 'year':
        $startTime->modify('-1 year');
        $statisticsPeriod = 'day';
        break;
    default:
        $startTime->modify('-1 day');
        $statisticsPeriod = '5minute';
}

// Format times for Home Assistant API (ISO 8601)
$startTimeStr = $startTime->format('Y-m-d\TH:i:s') . '.000000+00:00';
$endTimeStr = $endTime->format('Y-m-d\TH:i:s') . '.000000+00:00';

try {
    // Initialize Home Assistant client
    $client = new HomeAssistantClient(
        $config['home_assistant_url'],
        $config['access_token'],
        $config['timeout'] ?? 10
    );

    // Try to get statistics first (more efficient for longer periods)
    $useStatistics = in_array($period, ['week', 'month', 'year']);

    if ($useStatistics) {
        $rawData = $client->getStatistics(
            $startTimeStr,
            $endTimeStr,
            [$entityId],
            $statisticsPeriod
        );

        // Process statistics data
        $processedData = [];

        if (isset($rawData[$entityId]) && is_array($rawData[$entityId])) {
            foreach ($rawData[$entityId] as $item) {
                if (isset($item['start']) && isset($item['mean'])) {
                    $processedData[] = [
                        'timestamp' => $item['start'],
                        'value' => round((float)$item['mean'], 2)
                    ];
                }
            }
        }
    } else {
        // Use regular history for short periods (day)
        $rawData = $client->getHistory($startTimeStr, $endTimeStr, $entityId);

        // Process history data
        $processedData = [];

        if (is_array($rawData) && !empty($rawData)) {
            // History returns an array of arrays, one per entity
            $historyData = $rawData[0] ?? [];

            foreach ($historyData as $item) {
                if (isset($item['last_updated']) && isset($item['state'])) {
                    // Skip non-numeric states
                    if (!is_numeric($item['state'])) {
                        continue;
                    }

                    $processedData[] = [
                        'timestamp' => $item['last_updated'],
                        'value' => round((float)$item['state'], 2)
                    ];
                }
            }
        }
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $processedData,
        'data_count' => count($processedData),
        'period' => $period,
        'entity_id' => $entityId,
        'start_time' => $startTimeStr,
        'end_time' => $endTimeStr
    ]);

} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'data' => [],
        'data_count' => 0
    ]);
}
