<?php
/**
 * Dashboard Home Assistant en PHP
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

// Organiser les entités par domaine
$entitiesByDomain = [];
if (!empty($states)) {
    foreach ($states as $state) {
        $entityId = $state['entity_id'];
        $domain = explode('.', $entityId)[0];

        if (!isset($entitiesByDomain[$domain])) {
            $entitiesByDomain[$domain] = [];
        }

        $entitiesByDomain[$domain][] = $state;
    }

    // Trier les domaines par ordre alphabétique
    ksort($entitiesByDomain);
}

// Traduction des domaines
$domainNames = [
    'light' => 'Lumières',
    'switch' => 'Interrupteurs',
    'sensor' => 'Capteurs',
    'binary_sensor' => 'Capteurs binaires',
    'climate' => 'Climatisation',
    'cover' => 'Volets',
    'fan' => 'Ventilateurs',
    'lock' => 'Serrures',
    'media_player' => 'Lecteurs média',
    'weather' => 'Météo',
    'sun' => 'Soleil',
    'person' => 'Personnes',
    'device_tracker' => 'Suivi d\'appareils',
    'automation' => 'Automatisations',
    'script' => 'Scripts',
    'scene' => 'Scènes',
    'input_boolean' => 'Booléens',
    'input_number' => 'Nombres',
    'input_select' => 'Sélections',
    'input_text' => 'Textes',
];

// Fonction pour obtenir la classe CSS selon l'état
function getStateClass($state) {
    $value = strtolower($state);
    if (in_array($value, ['on', 'open', 'home', 'playing'])) {
        return 'state-on';
    } elseif (in_array($value, ['off', 'closed', 'away', 'idle', 'paused'])) {
        return 'state-off';
    } elseif (in_array($value, ['unavailable', 'unknown'])) {
        return 'state-unavailable';
    }
    return 'state-neutral';
}

// Fonction pour formater la dernière mise à jour
function formatLastUpdated($timestamp) {
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $diff = $now->diff($date);

    if ($diff->days > 0) {
        return $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
    } elseif ($diff->h > 0) {
        return $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
    } elseif ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
    } else {
        return 'À l\'instant';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Home Assistant</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🏠 Dashboard Home Assistant</h1>
            <?php if ($haConfig): ?>
                <p class="subtitle">
                    <?= htmlspecialchars($haConfig['location_name'] ?? 'Home Assistant') ?>
                    <span class="version">v<?= htmlspecialchars($haConfig['version'] ?? 'N/A') ?></span>
                </p>
            <?php endif; ?>
        </header>

        <?php if ($error): ?>
            <div class="error-message">
                <strong>Erreur:</strong> <?= htmlspecialchars($error) ?>
                <?php if (strpos($error, '401') !== false): ?>
                    <p style="margin-top: 15px;">
                        <a href="test-token.php" style="background: #667eea; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block; font-weight: bold;">
                            🔑 Tester et générer un nouveau token
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-value"><?= count($states) ?></div>
                    <div class="stat-label">Entités</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= count($entitiesByDomain) ?></div>
                    <div class="stat-label">Domaines</div>
                </div>
            </div>

            <div class="filters">
                <input type="text" id="searchBox" placeholder="Rechercher une entité..." class="search-box">
            </div>

            <?php foreach ($entitiesByDomain as $domain => $entities): ?>
                <div class="domain-section" data-domain="<?= htmlspecialchars($domain) ?>">
                    <h2 class="domain-title">
                        <?= htmlspecialchars($domainNames[$domain] ?? ucfirst($domain)) ?>
                        <span class="count">(<?= count($entities) ?>)</span>
                    </h2>

                    <div class="entities-grid">
                        <?php foreach ($entities as $entity): ?>
                            <div class="entity-card" data-entity-id="<?= htmlspecialchars($entity['entity_id']) ?>">
                                <div class="entity-header">
                                    <h3 class="entity-name">
                                        <?= htmlspecialchars($entity['attributes']['friendly_name'] ?? $entity['entity_id']) ?>
                                    </h3>
                                    <span class="entity-state <?= getStateClass($entity['state']) ?>">
                                        <?= htmlspecialchars($entity['state']) ?>
                                        <?php if (isset($entity['attributes']['unit_of_measurement'])): ?>
                                            <?= htmlspecialchars($entity['attributes']['unit_of_measurement']) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <div class="entity-details">
                                    <div class="entity-id"><?= htmlspecialchars($entity['entity_id']) ?></div>
                                    <div class="entity-updated">
                                        Mis à jour: <?= formatLastUpdated($entity['last_updated']) ?>
                                    </div>
                                </div>

                                <?php if (!empty($entity['attributes']) && count($entity['attributes']) > 1): ?>
                                    <details class="entity-attributes">
                                        <summary>Attributs (<?= count($entity['attributes']) ?>)</summary>
                                        <pre><?= htmlspecialchars(json_encode($entity['attributes'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                    </details>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($entitiesByDomain)): ?>
                <div class="info-message">
                    <strong>Aucune entité trouvée dans Home Assistant.</strong>
                    <p style="margin-top: 10px;">Causes possibles :</p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Votre instance Home Assistant n'a pas encore d'entités configurées</li>
                        <li>Le token d'accès n'a pas les bonnes permissions</li>
                        <li>Il y a un problème de connexion à l'API</li>
                    </ul>
                    <p style="margin-top: 15px;">
                        <a href="debug.php" style="background: #667eea; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block; font-weight: bold;">
                            🔍 Ouvrir la page de diagnostic
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <footer>
            <p>Dernière mise à jour: <?= date('d/m/Y H:i:s') ?></p>
            <p>
                <a href="?refresh=1">🔄 Rafraîchir</a> |
                <a href="sensors.php">🌡️ Capteurs YY</a> |
                <a href="debug.php">🔍 Diagnostic</a>
            </p>
        </footer>
    </div>

    <script>
        // Recherche en temps réel
        document.getElementById('searchBox')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const entityCards = document.querySelectorAll('.entity-card');

            entityCards.forEach(card => {
                const entityId = card.dataset.entityId.toLowerCase();
                const entityName = card.querySelector('.entity-name').textContent.toLowerCase();

                if (entityId.includes(searchTerm) || entityName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });

            // Masquer les sections vides
            document.querySelectorAll('.domain-section').forEach(section => {
                const visibleCards = section.querySelectorAll('.entity-card[style="display: block;"], .entity-card:not([style*="display"])');
                section.style.display = visibleCards.length > 0 ? 'block' : 'none';
            });
        });
    </script>
</body>
</html>
