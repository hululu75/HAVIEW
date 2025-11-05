# Dashboard Home Assistant en PHP

Un dashboard web simple et élégant pour afficher les données de votre instance Home Assistant.

## Fonctionnalités

- 🏠 Affichage de toutes les entités Home Assistant
- 🔍 Recherche en temps réel des entités
- 📊 Organisation par domaines (lumières, capteurs, interrupteurs, etc.)
- 🎨 Interface moderne et responsive
- 📱 Compatible mobile
- 🔄 Rafraîchissement manuel
- 📋 Affichage détaillé des attributs de chaque entité

## Prérequis

- PHP 7.4 ou supérieur
- Extension PHP cURL activée
- Serveur web (Apache, Nginx, ou PHP built-in server)
- Une instance Home Assistant accessible

## Installation

### 1. Cloner ou télécharger le projet

```bash
git clone <url-du-repo>
cd <nom-du-dossier>
```

### 2. Configurer l'accès à Home Assistant

Copiez le fichier de configuration exemple :

```bash
cp config.example.php config.php
```

Éditez `config.php` et remplissez vos informations :

```php
return [
    'home_assistant_url' => 'http://votre-home-assistant:8123',
    'access_token' => 'votre_token_ici',
    'timeout' => 10,
];
```

### 3. Générer un token d'accès dans Home Assistant

1. Connectez-vous à votre Home Assistant
2. Cliquez sur votre profil (en bas à gauche)
3. Faites défiler jusqu'à **"Long-Lived Access Tokens"**
4. Cliquez sur **"Create Token"**
5. Donnez-lui un nom (ex: "PHP Dashboard")
6. Copiez le token généré dans votre `config.php`

### 4. Lancer le serveur

#### Option A : Serveur PHP intégré (pour le développement)

```bash
php -S localhost:8000
```

Puis ouvrez votre navigateur sur : http://localhost:8000

#### Option B : Apache/Nginx

Configurez votre serveur web pour pointer vers le dossier du projet et accédez-y via votre navigateur.

## Structure du projet

```
.
├── index.php                  # Page principale du dashboard
├── HomeAssistantClient.php    # Classe client pour l'API Home Assistant
├── style.css                  # Styles CSS
├── config.example.php         # Exemple de configuration
├── config.php                 # Votre configuration (à créer, ignoré par git)
├── .gitignore                 # Fichiers à ignorer par git
└── README.md                  # Ce fichier
```

## Utilisation

### Affichage des entités

Le dashboard affiche automatiquement toutes vos entités Home Assistant organisées par domaines :
- Lumières
- Interrupteurs
- Capteurs
- Climatisation
- Volets
- Et bien d'autres...

### Recherche

Utilisez la barre de recherche en haut pour filtrer les entités par :
- Nom convivial (friendly name)
- ID d'entité (ex: light.salon)

### Attributs détaillés

Cliquez sur "Attributs" sous chaque entité pour voir tous ses attributs au format JSON.

### Rafraîchissement

Cliquez sur le lien "🔄 Rafraîchir" en bas de page pour recharger les données.

## API Home Assistant

Le client PHP implémente les endpoints suivants :

### Méthodes disponibles

```php
// Récupérer tous les états
$states = $client->getStates();

// Récupérer l'état d'une entité spécifique
$state = $client->getState('light.salon');

// Récupérer la configuration
$config = $client->getConfig();

// Récupérer les services disponibles
$services = $client->getServices();

// Appeler un service
$client->callService('light', 'turn_on', [
    'entity_id' => 'light.salon',
    'brightness' => 255
]);

// Récupérer l'historique
$history = $client->getHistory();

// Vérifier la connexion
$isConnected = $client->checkConnection();
```

## Personnalisation

### Modifier les styles

Éditez le fichier `style.css` pour personnaliser l'apparence du dashboard.

### Ajouter des fonctionnalités

Le fichier `HomeAssistantClient.php` peut être étendu pour ajouter d'autres appels API Home Assistant. Consultez la [documentation officielle de l'API](https://developers.home-assistant.io/docs/api/rest/).

## Sécurité

⚠️ **Important** :
- Ne commitez JAMAIS votre fichier `config.php` avec votre token
- Le token donne un accès complet à votre Home Assistant
- Utilisez HTTPS en production
- Limitez l'accès au dashboard (authentification web, VPN, etc.)

## Dépannage

### Erreur de connexion

- Vérifiez que l'URL de Home Assistant est correcte
- Vérifiez que le token est valide
- Vérifiez que Home Assistant est accessible depuis votre serveur PHP
- Vérifiez que l'extension cURL est activée : `php -m | grep curl`

### Erreur 401 Unauthorized

- Votre token est invalide ou a expiré
- Générez un nouveau token dans Home Assistant

### Page blanche

- Activez l'affichage des erreurs PHP :
  ```php
  ini_set('display_errors', 1);
  error_reporting(E_ALL);
  ```
- Vérifiez les logs PHP

## Contribuer

Les contributions sont les bienvenues ! N'hésitez pas à :
- Signaler des bugs
- Proposer de nouvelles fonctionnalités
- Soumettre des pull requests

## Licence

Ce projet est libre d'utilisation.

## Ressources

- [Home Assistant](https://www.home-assistant.io/)
- [Documentation API Home Assistant](https://developers.home-assistant.io/docs/api/rest/)
- [PHP cURL](https://www.php.net/manual/fr/book.curl.php)
