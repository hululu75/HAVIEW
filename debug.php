<?php
/**
 * Page de debug pour diagnostiquer les problèmes de connexion à Home Assistant
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Charger la configuration
if (!file_exists('config.php')) {
    die('Erreur: Le fichier config.php n\'existe pas. Copiez config.example.php vers config.php et configurez vos paramètres.');
}

$config = require 'config.php';
require_once 'HomeAssistantClient.php';

echo "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Debug Home Assistant</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #ddd; }
        .info { background: #e7f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        table td { padding: 8px; border-bottom: 1px solid #eee; }
        table td:first-child { font-weight: bold; width: 200px; }
    </style>
</head>
<body>
    <h1>🔍 Page de Debug Home Assistant</h1>
";

// Section 1: Configuration
echo "<div class='section'>
    <h2>1. Configuration</h2>
    <table>
        <tr>
            <td>URL Home Assistant:</td>
            <td>" . htmlspecialchars($config['home_assistant_url']) . "</td>
        </tr>
        <tr>
            <td>Token configuré:</td>
            <td>" . (strlen($config['access_token']) > 0 ? "<span class='success'>Oui (" . strlen($config['access_token']) . " caractères)</span>" : "<span class='error'>Non</span>") . "</td>
        </tr>
        <tr>
            <td>Timeout:</td>
            <td>" . ($config['timeout'] ?? 10) . " secondes</td>
        </tr>
    </table>
</div>";

// Section 2: Extensions PHP
echo "<div class='section'>
    <h2>2. Extensions PHP requises</h2>
    <table>
        <tr>
            <td>Extension cURL:</td>
            <td>" . (extension_loaded('curl') ? "<span class='success'>✓ Activée</span>" : "<span class='error'>✗ Non disponible</span>") . "</td>
        </tr>
        <tr>
            <td>Extension JSON:</td>
            <td>" . (extension_loaded('json') ? "<span class='success'>✓ Activée</span>" : "<span class='error'>✗ Non disponible</span>") . "</td>
        </tr>
        <tr>
            <td>Version PHP:</td>
            <td>" . PHP_VERSION . "</td>
        </tr>
    </table>
</div>";

// Section 3: Test de connexion
echo "<div class='section'>
    <h2>3. Test de connexion à Home Assistant</h2>";

$client = new HomeAssistantClient(
    $config['home_assistant_url'],
    $config['access_token'],
    $config['timeout'] ?? 10
);

try {
    // Test de connexion basique
    echo "<p>Test de l'endpoint <code>/api/</code>...</p>";

    $url = rtrim($config['home_assistant_url'], '/') . '/api/';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Suivre les redirections
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['access_token'],
        'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo "<table>
        <tr>
            <td>URL demandée:</td>
            <td>" . htmlspecialchars($url) . "</td>
        </tr>
        <tr>
            <td>URL finale:</td>
            <td>" . htmlspecialchars($effectiveUrl) . ($effectiveUrl != $url ? " <span class='warning'>(redirigé)</span>" : "") . "</td>
        </tr>
        <tr>
            <td>Code HTTP:</td>
            <td>" . ($httpCode == 200 ? "<span class='success'>$httpCode OK</span>" : "<span class='error'>$httpCode</span>") . "</td>
        </tr>
        <tr>
            <td>Erreur cURL:</td>
            <td>" . ($curlError ? "<span class='error'>$curlError</span>" : "<span class='success'>Aucune</span>") . "</td>
        </tr>
    </table>";

    if ($httpCode == 200) {
        echo "<p class='success'>✓ Connexion réussie!</p>";
        echo "<p><strong>Réponse:</strong></p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    } else {
        echo "<p class='error'>✗ Échec de connexion</p>";
        if ($httpCode == 401) {
            echo "<div class='info'>
                ⚠️ <strong>Erreur 401: Token invalide ou expiré.</strong><br><br>
                Le token d'accès n'est pas valide. Cela peut être dû à :<br>
                • Token expiré ou révoqué<br>
                • Token mal copié (espaces, caractères manquants)<br>
                • Token jamais généré<br><br>
                <a href='test-token.php' style='background: #667eea; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-block; font-weight: bold; margin-top: 10px;'>
                    🔑 Tester et générer un nouveau token
                </a>
            </div>";
        } elseif ($httpCode == 0) {
            echo "<div class='info'>⚠️ Impossible de joindre le serveur. Vérifiez l'URL et que Home Assistant est accessible.</div>";
        }
        echo "<p><strong>Réponse:</strong></p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }

} catch (Exception $e) {
    echo "<p class='error'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Section 4: Test de récupération des états
echo "<div class='section'>
    <h2>4. Test de récupération des états</h2>";

try {
    echo "<p>Appel de <code>/api/states</code>...</p>";

    $url = rtrim($config['home_assistant_url'], '/') . '/api/states';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Suivre les redirections
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $config['access_token'],
        'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $curlError = curl_error($ch);
    curl_close($ch);

    echo "<table>
        <tr>
            <td>URL demandée:</td>
            <td>" . htmlspecialchars($url) . "</td>
        </tr>
        <tr>
            <td>URL finale:</td>
            <td>" . htmlspecialchars($effectiveUrl) . ($effectiveUrl != $url ? " <span class='warning'>(redirigé)</span>" : "") . "</td>
        </tr>
        <tr>
            <td>Code HTTP:</td>
            <td>" . ($httpCode == 200 ? "<span class='success'>$httpCode OK</span>" : "<span class='error'>$httpCode</span>") . "</td>
        </tr>
        <tr>
            <td>Taille réponse:</td>
            <td>" . strlen($response) . " octets</td>
        </tr>
    </table>";

    if ($httpCode == 200) {
        $states = json_decode($response, true);

        echo "<p class='success'>✓ Récupération réussie!</p>";
        echo "<table>
            <tr>
                <td>Nombre d'entités:</td>
                <td><strong>" . (is_array($states) ? count($states) : 0) . "</strong></td>
            </tr>
            <tr>
                <td>Type de réponse:</td>
                <td>" . (is_array($states) ? "<span class='success'>Tableau PHP</span>" : "<span class='error'>" . gettype($states) . "</span>") . "</td>
            </tr>
        </table>";

        if (is_array($states) && count($states) > 0) {
            echo "<p><strong>Première entité (exemple):</strong></p>";
            echo "<pre>" . htmlspecialchars(json_encode($states[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

            echo "<p><strong>Toutes les entités:</strong></p>";
            echo "<pre style='max-height: 400px; overflow-y: auto;'>" . htmlspecialchars(json_encode($states, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        } elseif (is_array($states) && count($states) == 0) {
            echo "<div class='info'>⚠️ Le tableau est vide. Home Assistant ne retourne aucune entité. Vérifiez que vous avez des entités configurées dans Home Assistant.</div>";
        } else {
            echo "<p class='warning'>Réponse brute:</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    } else {
        echo "<p class='error'>✗ Échec de récupération</p>";
        echo "<p><strong>Réponse:</strong></p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }

} catch (Exception $e) {
    echo "<p class='error'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// Section 5: Test de récupération de la config
echo "<div class='section'>
    <h2>5. Test de récupération de la configuration</h2>";

try {
    $haConfig = $client->getConfig();

    if (!empty($haConfig)) {
        echo "<p class='success'>✓ Configuration récupérée!</p>";
        echo "<pre>" . htmlspecialchars(json_encode($haConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
    } else {
        echo "<p class='warning'>Configuration vide</p>";
    }

} catch (Exception $e) {
    echo "<p class='error'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

echo "<div class='section'>
    <h2>6. Retour au dashboard</h2>
    <p><a href='index.php' style='color: #667eea; text-decoration: none; font-weight: bold;'>← Retour au dashboard</a></p>
</div>";

echo "</body></html>";
?>
