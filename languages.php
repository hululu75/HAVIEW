<?php
/**
 * Fichier de traductions multilingues
 * Supporte: Français (fr), English (en), 中文 (zh)
 */

$translations = [
    'fr' => [
        // Navigation
        'back_to_sensors' => '← Retour aux capteurs',
        'view_history' => 'Cliquez pour voir l\'historique',

        // Page titles
        'sensors_title' => 'Capteurs',
        'sensors_subtitle' => 'Données en temps réel',
        'history_title' => 'Historique',
        'history_subtitle' => 'Visualisation des données historiques',

        // Sensor types
        'temperature' => 'Température',
        'humidity' => 'Humidité',

        // Time periods
        'period_day' => 'Jour',
        'period_week' => 'Semaine',
        'period_month' => 'Mois',
        'period_year' => 'Année',

        // Statistics
        'minimum' => 'Minimum',
        'maximum' => 'Maximum',
        'average' => 'Moyenne',
        'data_points' => 'Points de données',

        // Status messages
        'updated_ago' => 'Mis à jour il y a',
        'last_update' => 'Dernière mise à jour',
        'no_sensors_found' => 'Aucun capteur trouvé',
        'no_sensors_text' => 'Aucun capteur de température ou d\'humidité trouvé pour ce groupe.',
        'error' => 'Erreur',
        'warning' => 'Attention',
        'no_data' => 'Aucune donnée disponible pour cette période',

        // Time units
        'day' => 'jour',
        'days' => 'jours',
        'hour' => 'heure',
        'hours' => 'heures',
        'minute' => 'minute',
        'minutes' => 'minutes',
        'just_now' => 'À l\'instant',
    ],

    'en' => [
        // Navigation
        'back_to_sensors' => '← Back to sensors',
        'view_history' => 'Click to view history',

        // Page titles
        'sensors_title' => 'Sensors',
        'sensors_subtitle' => 'Real-time Data',
        'history_title' => 'History',
        'history_subtitle' => 'Historical Data Visualization',

        // Sensor types
        'temperature' => 'Temperature',
        'humidity' => 'Humidity',

        // Time periods
        'period_day' => 'Day',
        'period_week' => 'Week',
        'period_month' => 'Month',
        'period_year' => 'Year',

        // Statistics
        'minimum' => 'Minimum',
        'maximum' => 'Maximum',
        'average' => 'Average',
        'data_points' => 'Data Points',

        // Status messages
        'updated_ago' => 'Updated',
        'last_update' => 'Last update',
        'no_sensors_found' => 'No sensors found',
        'no_sensors_text' => 'No temperature or humidity sensors found for this group.',
        'error' => 'Error',
        'warning' => 'Warning',
        'no_data' => 'No data available for this period',

        // Time units
        'day' => 'day',
        'days' => 'days',
        'hour' => 'hour',
        'hours' => 'hours',
        'minute' => 'minute',
        'minutes' => 'minutes',
        'just_now' => 'Just now',
    ],

    'zh' => [
        // Navigation
        'back_to_sensors' => '← 返回传感器',
        'view_history' => '点击查看历史数据',

        // Page titles
        'sensors_title' => '传感器',
        'sensors_subtitle' => '实时数据',
        'history_title' => '历史数据',
        'history_subtitle' => '历史数据可视化',

        // Sensor types
        'temperature' => '温度',
        'humidity' => '湿度',

        // Time periods
        'period_day' => '天',
        'period_week' => '周',
        'period_month' => '月',
        'period_year' => '年',

        // Statistics
        'minimum' => '最小值',
        'maximum' => '最大值',
        'average' => '平均值',
        'data_points' => '数据点',

        // Status messages
        'updated_ago' => '更新于',
        'last_update' => '最后更新',
        'no_sensors_found' => '未找到传感器',
        'no_sensors_text' => '未找到此分组的温度或湿度传感器。',
        'error' => '错误',
        'warning' => '警告',
        'no_data' => '此期间没有可用数据',

        // Time units
        'day' => '天',
        'days' => '天',
        'hour' => '小时',
        'hours' => '小时',
        'minute' => '分钟',
        'minutes' => '分钟',
        'just_now' => '刚刚',
    ],
];

/**
 * Obtenir la langue actuelle depuis les cookies ou la langue par défaut
 */
function getCurrentLanguage() {
    if (isset($_COOKIE['language']) && isset($GLOBALS['translations'][$_COOKIE['language']])) {
        return $_COOKIE['language'];
    }
    return 'fr'; // Langue par défaut
}

/**
 * Définir la langue
 */
function setLanguage($lang) {
    if (isset($GLOBALS['translations'][$lang])) {
        setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/'); // 1 an
        return true;
    }
    return false;
}

/**
 * Obtenir une traduction
 */
function t($key, $default = null) {
    global $translations;
    $lang = getCurrentLanguage();

    if (isset($translations[$lang][$key])) {
        return $translations[$lang][$key];
    }

    // Fallback to French if key not found
    if ($lang !== 'fr' && isset($translations['fr'][$key])) {
        return $translations['fr'][$key];
    }

    return $default ?? $key;
}

// Gérer le changement de langue via URL
if (isset($_GET['lang'])) {
    $requestedLang = $_GET['lang'];
    if (setLanguage($requestedLang)) {
        // Rediriger en conservant les autres paramètres (sauf lang)
        $params = $_GET;
        unset($params['lang']); // Retirer lang car il est maintenant dans le cookie

        $queryString = http_build_query($params);
        $redirect = strtok($_SERVER['REQUEST_URI'], '?');

        if ($queryString) {
            $redirect .= '?' . $queryString;
        }

        header('Location: ' . $redirect);
        exit;
    }
}

return $translations;
