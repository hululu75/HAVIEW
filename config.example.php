<?php
/**
 * Configuration pour Home Assistant
 * Copiez ce fichier en config.php et remplissez vos informations
 */

return [
    // URL de votre instance Home Assistant (sans slash final)
    // Si Home Assistant utilise HTTPS, utilisez https:// au lieu de http://
    // Exemples:
    //   'home_assistant_url' => 'http://homeassistant.local:8123',
    //   'home_assistant_url' => 'https://homeassistant.local:8123',
    //   'home_assistant_url' => 'http://192.168.1.100:8123',
    'home_assistant_url' => 'http://homeassistant.local:8123',

    // Token d'accès long terme (Long-Lived Access Token)
    // Générez-le depuis: Profile -> Security -> Long-Lived Access Tokens
    'access_token' => 'votre_token_ici',

    // Timeout pour les requêtes API (en secondes)
    'timeout' => 10,
];
