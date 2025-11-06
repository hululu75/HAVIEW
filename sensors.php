<?php
/**
 * Page filtrée pour afficher uniquement certains capteurs
 */

// Charger le système de traduction
require_once 'languages.php';
$currentLang = getCurrentLanguage();

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

// Gérer les erreurs
$error = null;
$states = [];
$haConfig = null;

try {
    // Vérifier la connexion
    if (!$client->checkConnection()) {
        throw new Exception('Impossible de se connecter à Home Assistant. Vérifiez votre configuration.');
    }

    // Récupérer les états des entités
    $states = $client->getStates();

    // S'assurer que $states est un tableau
    if (!is_array($states)) {
        $states = [];
    }

    // Récupérer la configuration Home Assistant
    $haConfig = $client->getConfig();

} catch (Exception $e) {
    $error = $e->getMessage();
    $states = [];
    $haConfig = null;
}

// Filtrer pour ne garder que les capteurs spécifiques
// On cherche les entités qui contiennent "YY的房间" et qui sont température ou humidité
$filteredEntities = [];

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
                $filteredEntities[] = $state;
            } elseif ((stripos($friendlyName, 'Humidité') !== false ||
                       stripos($friendlyName, 'Humidity') !== false ||
                       stripos($friendlyName, '湿度') !== false ||
                       stripos($entityId, 'humidity') !== false) &&
                      // Exclure les capteurs de batterie
                      stripos($friendlyName, 'batterie') === false &&
                      stripos($friendlyName, 'battery') === false &&
                      stripos($entityId, 'battery') === false &&
                      stripos($entityId, 'batterie') === false) {
                $filteredEntities[] = $state;
            }
        }
    }
}

// Fonction pour formater la dernière mise à jour
function formatLastUpdated($timestamp) {
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $diff = $now->diff($date);

    if ($diff->days > 0) {
        $unit = $diff->days > 1 ? t('days') : t('day');
        return $diff->days . ' ' . $unit;
    } elseif ($diff->h > 0) {
        $unit = $diff->h > 1 ? t('hours') : t('hour');
        return $diff->h . ' ' . $unit;
    } elseif ($diff->i > 0) {
        $unit = $diff->i > 1 ? t('minutes') : t('minute');
        return $diff->i . ' ' . $unit;
    } else {
        return t('just_now');
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capteurs YY - Home Assistant</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .language-selector {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .language-selector a {
            padding: 8px 15px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .language-selector a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .language-selector a.active {
            background: #667eea;
            color: white;
        }

        .sensor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .sensor-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            border-radius: 20px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .sensor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .sensor-icon {
            font-size: 3em;
            margin-bottom: 20px;
            text-align: center;
        }

        .sensor-name {
            font-size: 1.2em;
            margin-bottom: 20px;
            text-align: center;
            opacity: 0.9;
        }

        .sensor-value {
            font-size: 4em;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .sensor-unit {
            font-size: 0.5em;
            opacity: 0.8;
            margin-left: 5px;
        }

        .sensor-updated {
            text-align: center;
            opacity: 0.8;
            font-size: 0.9em;
            margin-top: 20px;
        }

        .temperature-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .humidity-card {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .sensor-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .click-hint {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            font-size: 0.9em;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sensor-card:hover .click-hint {
            opacity: 1;
        }

        .no-sensors {
            text-align: center;
            padding: 60px 20px;
            background: #f8f8f8;
            border-radius: 15px;
            margin-top: 30px;
        }

        .no-sensors-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="language-selector">
        <a href="?lang=zh" class="<?= $currentLang === 'zh' ? 'active' : '' ?>">中文</a>
        <a href="?lang=en" class="<?= $currentLang === 'en' ? 'active' : '' ?>">EN</a>
        <a href="?lang=fr" class="<?= $currentLang === 'fr' ? 'active' : '' ?>">FR</a>
    </div>

    <div class="container">
        <header>
            <h1>🌡️ <?= t('sensors_title') ?></h1>
            <p class="subtitle"><?= t('sensors_subtitle') ?></p>
        </header>

        <?php if ($error): ?>
            <div class="error-message">
                <strong><?= t('error') ?>:</strong> <?= htmlspecialchars($error) ?>
                <?php if (strpos($error, '401') !== false): ?>
                    <p style="margin-top: 15px;">
                        <a href="test-token.php" style="background: #667eea; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block; font-weight: bold;">
                            🔑 Tester et générer un nouveau token
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php if (!empty($filteredEntities)): ?>
                <div class="sensor-grid">
                    <?php foreach ($filteredEntities as $entity): ?>
                        <?php
                        $friendlyName = $entity['attributes']['friendly_name'] ?? $entity['entity_id'];
                        $isTemperature = stripos($friendlyName, 'Température') !== false ||
                                       stripos($friendlyName, 'Temperature') !== false ||
                                       stripos($friendlyName, '温度') !== false ||
                                       stripos($entity['entity_id'], 'temperature') !== false;
                        $cardClass = $isTemperature ? 'temperature-card' : 'humidity-card';
                        $icon = $isTemperature ? '🌡️' : '💧';
                        $sensorType = $isTemperature ? 'temperature' : 'humidity';
                        ?>
                        <a href="history.php?sensor=<?= $sensorType ?>" class="sensor-card-link">
                            <div class="sensor-card <?= $cardClass ?>">
                                <div class="sensor-icon"><?= $icon ?></div>
                                <div class="sensor-name"><?= htmlspecialchars($friendlyName) ?></div>
                                <div class="sensor-value">
                                    <?= htmlspecialchars($entity['state']) ?>
                                    <?php if (isset($entity['attributes']['unit_of_measurement'])): ?>
                                        <span class="sensor-unit"><?= htmlspecialchars($entity['attributes']['unit_of_measurement']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="sensor-updated">
                                    <?= t('updated_ago') ?> <?= formatLastUpdated($entity['last_updated']) ?>
                                </div>
                                <div class="click-hint">📈 <?= t('view_history') ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-sensors">
                    <div class="no-sensors-icon">🔍</div>
                    <h2><?= t('no_sensors_found') ?></h2>
                    <p style="margin-top: 15px; color: #666;">
                        <?= t('no_sensors_text') ?>
                    </p>
                    <p style="margin-top: 20px;">
                        <a href="index.php" style="color: #667eea; font-weight: bold; text-decoration: none;">
                            ← Voir toutes les entités
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <footer>
            <p><?= t('last_update') ?>: <?= date('d/m/Y H:i:s') ?></p>
        </footer>
    </div>

    <script>
        // Rafraîchissement automatique toutes les 30 secondes
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
