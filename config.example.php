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

    // Configuration des capteurs à afficher
    // Vous pouvez définir plusieurs groupes de capteurs
    // Chaque groupe peut contenir plusieurs capteurs avec leur type
    'sensor_groups' => [
        // Groupe 1: YY的房间
        [
            'id' => 'yy_room',
            'name' => [
                'fr' => 'Chambre de YY',
                'en' => 'YY\'s Room',
                'zh' => 'YY的房间',
            ],
            'sensors' => [
                [
                    'type' => 'temperature',  // temperature, humidity, etc.
                    'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_yy_temperature',
                    'icon' => '🌡️',
                    'name' => [
                        'fr' => 'Température',
                        'en' => 'Temperature',
                        'zh' => '温度',
                    ],
                ],
                [
                    'type' => 'humidity',
                    'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_yy_humidity',
                    'icon' => '💧',
                    'name' => [
                        'fr' => 'Humidité',
                        'en' => 'Humidity',
                        'zh' => '湿度',
                    ],
                ],
            ],
        ],

        // Exemple: Ajoutez d'autres groupes ici
        // Décommentez et modifiez selon vos besoins
        /*
        [
            'id' => 'living_room',
            'name' => [
                'fr' => 'Salon',
                'en' => 'Living Room',
                'zh' => '客厅',
            ],
            'sensors' => [
                [
                    'type' => 'temperature',
                    'entity_id' => 'sensor.living_room_temperature',
                    'icon' => '🌡️',
                    'name' => [
                        'fr' => 'Température Salon',
                        'en' => 'Living Room Temperature',
                        'zh' => '客厅温度',
                    ],
                ],
            ],
        ],
        */
    ],

    // Groupe de capteurs actif par défaut (id du groupe)
    'default_sensor_group' => 'yy_room',
];
