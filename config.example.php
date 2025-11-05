<?php
/**
 * Configuration pour Home Assistant
 * Copiez ce fichier en config.php et remplissez vos informations
 */

return [
    // URL de votre instance Home Assistant (sans slash final)
    'home_assistant_url' => 'http://homeassistant.local:8123',

    // Token d'accès long terme (Long-Lived Access Token)
    // Générez-le depuis: Profile -> Security -> Long-Lived Access Tokens
    'access_token' => 'votre_token_ici',

    // Timeout pour les requêtes API (en secondes)
    'timeout' => 10,
];
