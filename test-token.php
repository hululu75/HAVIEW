<?php
/**
 * Outil de test et vérification du token Home Assistant
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Test du Token Home Assistant</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #667eea; margin-bottom: 20px; }
        h2 { color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-top: 30px; }
        .form-group { margin: 20px 0; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #333; }
        input[type="text"], input[type="url"], textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; font-family: 'Courier New', monospace; box-sizing: border-box; }
        input[type="text"]:focus, input[type="url"]:focus, textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        textarea { min-height: 100px; resize: vertical; }
        button { background: #667eea; color: white; padding: 12px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        button:hover { background: #5568d3; }
        .info { background: #e7f3ff; border-left: 4px solid #1890ff; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; border-radius: 5px; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; border-radius: 5px; color: #721c24; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; color: #856404; }
        pre { background: #f8f8f8; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #ddd; }
        .token-info { background: #f8f8f8; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .token-info strong { color: #667eea; }
        ol { line-height: 1.8; }
        ol li { margin: 10px 0; }
        .step { background: #667eea; color: white; border-radius: 50%; width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 10px; }
        a { color: #667eea; text-decoration: none; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔑 Test du Token Home Assistant</h1>

        <div class="info">
            <strong>Vous avez une erreur 401 (Unauthorized)</strong><br>
            Cela signifie que votre token d'accès est invalide, expiré ou mal configuré.<br>
            Utilisez cet outil pour tester votre token.
        </div>

        <h2>1. Guide : Comment générer un token valide</h2>

        <ol>
            <li>
                <strong>Ouvrez Home Assistant</strong> dans votre navigateur<br>
                <code>https://homeassistant.familyzhao.fr</code>
            </li>
            <li>
                <strong>Cliquez sur votre profil</strong> (icône en bas à gauche avec votre initiale)
            </li>
            <li>
                <strong>Scrollez vers le bas</strong> jusqu'à la section <strong>"Long-Lived Access Tokens"</strong>
            </li>
            <li>
                <strong>Cliquez sur "Create Token"</strong>
            </li>
            <li>
                <strong>Donnez un nom</strong> au token (ex: "Dashboard PHP")
            </li>
            <li>
                <strong>Copiez le token</strong> immédiatement (il ne sera affiché qu'une seule fois !)<br>
                ⚠️ <em>Important : Le token est long, assurez-vous de tout copier</em>
            </li>
        </ol>

        <h2>2. Testez votre token ici</h2>

        <form method="post">
            <div class="form-group">
                <label for="url">URL de Home Assistant:</label>
                <input type="url" id="url" name="url" value="https://homeassistant.familyzhao.fr" required placeholder="https://homeassistant.familyzhao.fr">
                <small style="color: #666;">Utilisez HTTPS si votre Home Assistant utilise SSL</small>
            </div>

            <div class="form-group">
                <label for="token">Token d'accès (Long-Lived Access Token):</label>
                <textarea id="token" name="token" required placeholder="Collez votre token ici..."><?= isset($_POST['token']) ? htmlspecialchars($_POST['token']) : '' ?></textarea>
                <small style="color: #666;">Le token ressemble à : eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...</small>
            </div>

            <button type="submit" name="test">🧪 Tester le token</button>
        </form>

        <?php
        if (isset($_POST['test'])) {
            $url = trim($_POST['url']);
            $token = trim($_POST['token']);

            echo "<h2>3. Résultat du test</h2>";

            // Vérifications de base
            echo "<div class='token-info'>";
            echo "<strong>Informations sur le token :</strong><br>";
            echo "Longueur : " . strlen($token) . " caractères<br>";
            echo "Commence par : " . htmlspecialchars(substr($token, 0, 20)) . "...<br>";
            echo "Se termine par : ..." . htmlspecialchars(substr($token, -20)) . "<br>";

            // Détecter les problèmes courants
            $issues = [];
            if (strlen($token) < 100) {
                $issues[] = "⚠️ Le token semble trop court (< 100 caractères)";
            }
            if (preg_match('/\s/', $token)) {
                $issues[] = "⚠️ Le token contient des espaces ou des retours à la ligne";
            }
            if ($token === 'votre_token_ici') {
                $issues[] = "❌ Vous devez remplacer 'votre_token_ici' par un vrai token";
            }

            if (!empty($issues)) {
                echo "</div><div class='warning'>";
                echo "<strong>Problèmes détectés :</strong><br>";
                foreach ($issues as $issue) {
                    echo $issue . "<br>";
                }
                echo "</div>";
            } else {
                echo "✓ Format du token semble correct<br>";
                echo "</div>";
            }

            // Test de connexion réel
            echo "<div class='token-info'>";
            echo "<strong>Test de connexion à Home Assistant...</strong><br><br>";

            $apiUrl = rtrim($url, '/') . '/api/';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $curlError = curl_error($ch);
            curl_close($ch);

            echo "URL testée : " . htmlspecialchars($apiUrl) . "<br>";
            if ($effectiveUrl != $apiUrl) {
                echo "URL finale : " . htmlspecialchars($effectiveUrl) . " (redirigé)<br>";
            }
            echo "Code HTTP : <strong>$httpCode</strong><br>";
            echo "</div>";

            if ($httpCode == 200) {
                echo "<div class='success'>";
                echo "<strong>✓ SUCCÈS ! Le token est valide !</strong><br><br>";
                echo "Réponse de Home Assistant :<br>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
                echo "<strong>Prochaines étapes :</strong><br>";
                echo "1. Copiez ce token dans votre fichier <code>config.php</code><br>";
                echo "2. Mettez à jour l'URL si elle a été redirigée (utilisez l'URL finale)<br>";
                echo "3. <a href='index.php'>Retournez au dashboard</a> et rafraîchissez la page<br>";
                echo "</div>";

                // Générer automatiquement le fichier config.php
                echo "<div class='info'>";
                echo "<strong>💾 Configuration à copier dans config.php :</strong><br><br>";
                echo "<pre><?php\nreturn [\n";
                echo "    'home_assistant_url' => '" . htmlspecialchars($effectiveUrl ? rtrim(str_replace('/api/', '', $effectiveUrl), '/') : $url) . "',\n";
                echo "    'access_token' => '" . htmlspecialchars($token) . "',\n";
                echo "    'timeout' => 10,\n";
                echo "];</pre>";
                echo "</div>";

            } elseif ($httpCode == 401) {
                echo "<div class='error'>";
                echo "<strong>✗ ÉCHEC : Token invalide (401 Unauthorized)</strong><br><br>";
                echo "Causes possibles :<br>";
                echo "• Le token est expiré<br>";
                echo "• Le token a été révoqué dans Home Assistant<br>";
                echo "• Le token n'est pas complet (vérifiez que vous avez tout copié)<br>";
                echo "• Il y a des espaces ou des retours à la ligne dans le token<br><br>";
                echo "<strong>Solution :</strong> Générez un nouveau token dans Home Assistant (voir le guide ci-dessus)<br>";
                echo "</div>";
            } elseif ($httpCode == 0) {
                echo "<div class='error'>";
                echo "<strong>✗ ÉCHEC : Impossible de contacter Home Assistant</strong><br><br>";
                echo "Erreur cURL : " . htmlspecialchars($curlError) . "<br><br>";
                echo "Causes possibles :<br>";
                echo "• L'URL est incorrecte<br>";
                echo "• Home Assistant n'est pas accessible depuis ce serveur<br>";
                echo "• Problème de réseau ou de firewall<br>";
                echo "</div>";
            } else {
                echo "<div class='warning'>";
                echo "<strong>⚠ Code HTTP inattendu : $httpCode</strong><br><br>";
                echo "Réponse :<br>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
                echo "</div>";
            }
        }
        ?>

        <h2>4. Retour</h2>
        <p>
            <a href="debug.php">← Page de diagnostic</a> |
            <a href="index.php">← Dashboard</a>
        </p>
    </div>
</body>
</html>
