# Scripts de Surveillance de Dossier

Deux scripts sont disponibles pour surveiller un dossier et envoyer une alerte email si aucun fichier n'est créé/modifié pendant 4 heures.

## Scripts Disponibles

- **monitor_folder.sh** - Script Bash (recommandé pour Linux)
- **monitor_folder.pl** - Script Perl (plus portable)

## Fonctionnalités

✅ Surveillance continue d'un dossier
✅ Détection de nouveaux fichiers ou modifications
✅ Envoi d'email après 4 heures d'inactivité
✅ Logs détaillés de l'activité
✅ Réinitialisation automatique de l'alerte après nouvelle activité

## Installation et Configuration

### Prérequis

**Pour le script Bash:**
```bash
# Installer un système d'envoi d'email
sudo apt-get install mailutils  # Ubuntu/Debian
# OU
sudo yum install mailx          # CentOS/RHEL
```

**Pour le script Perl:**
```bash
# Perl est généralement pré-installé sur Linux
perl --version

# Optionnel: installer le module Email::Sender
sudo cpan Email::Sender::Simple
```

### Rendre les scripts exécutables

```bash
chmod +x monitor_folder.sh
chmod +x monitor_folder.pl
```

## Utilisation

### Script Bash

```bash
# Utilisation basique
./monitor_folder.sh /chemin/vers/dossier admin@example.com

# Exemples concrets
./monitor_folder.sh /var/log/exports contact@monentreprise.fr
./monitor_folder.sh /home/user/documents admin@localhost
```

### Script Perl

```bash
# Utilisation basique
./monitor_folder.pl /chemin/vers/dossier admin@example.com

# Exemples concrets
./monitor_folder.pl /var/log/exports contact@monentreprise.fr
./monitor_folder.pl /home/user/documents admin@localhost
```

### Paramètres

1. **Premier argument** : Chemin du dossier à surveiller (obligatoire)
2. **Deuxième argument** : Adresse email de destination (obligatoire)

## Configuration Avancée

### Modifier les paramètres dans le script

Ouvrez le script avec un éditeur et modifiez les variables de configuration :

```bash
CHECK_INTERVAL=300      # Intervalle de vérification (5 minutes)
ALERT_THRESHOLD=14400   # Seuil d'alerte (4 heures = 14400 secondes)
LOG_FILE="/var/log/folder_monitor.log"  # Fichier de log
```

### Exemples de modification

**Pour 2 heures au lieu de 4 heures :**
```bash
ALERT_THRESHOLD=7200    # 2 heures = 7200 secondes
```

**Pour vérifier toutes les minutes :**
```bash
CHECK_INTERVAL=60       # 1 minute
```

## Exécution en Arrière-Plan

### Méthode 1 : Utiliser nohup

```bash
nohup ./monitor_folder.sh /chemin/vers/dossier admin@example.com > /dev/null 2>&1 &
```

### Méthode 2 : Créer un service systemd

Créez le fichier `/etc/systemd/system/folder-monitor.service` :

```ini
[Unit]
Description=Folder Monitor Service
After=network.target

[Service]
Type=simple
User=root
ExecStart=/home/user/test/monitor_folder.sh /var/log/exports admin@example.com
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Activez et démarrez le service :

```bash
sudo systemctl daemon-reload
sudo systemctl enable folder-monitor
sudo systemctl start folder-monitor
sudo systemctl status folder-monitor
```

### Méthode 3 : Utiliser cron avec un script wrapper

Cette méthode n'est pas recommandée car les scripts sont conçus pour tourner en continu.

## Fichiers de Log

Les logs sont écrits dans :
- **Bash** : `/var/log/folder_monitor.log`
- **Perl** : `/var/log/folder_monitor_perl.log`

Visualiser les logs en temps réel :

```bash
tail -f /var/log/folder_monitor.log
```

## Configuration SMTP (si nécessaire)

Si les méthodes d'envoi d'email par défaut ne fonctionnent pas, vous pouvez configurer un serveur SMTP.

### Pour le script Bash (avec curl)

Décommentez et configurez cette section dans le script :

```bash
curl --url 'smtp://smtp.gmail.com:587' \
     --ssl-reqd \
     --mail-from "votre-email@gmail.com" \
     --mail-rcpt "$EMAIL_TO" \
     --user 'votre-email@gmail.com:votre-mot-de-passe-application' \
     -T <(echo -e "From: $EMAIL_FROM\nTo: $EMAIL_TO\nSubject: $SUBJECT\n\n$message")
```

### Pour le script Perl

Installez le module SMTP :

```bash
sudo cpan Email::Sender::Transport::SMTP
```

## Dépannage

### Le script ne démarre pas

```bash
# Vérifier que le dossier existe
ls -la /chemin/vers/dossier

# Vérifier les permissions
chmod +x monitor_folder.sh
```

### Les emails ne sont pas envoyés

```bash
# Tester l'envoi d'email manuel
echo "Test" | mail -s "Test Email" admin@example.com

# Vérifier les logs du système
tail -f /var/log/mail.log
tail -f /var/log/syslog
```

### Arrêter le script

```bash
# Trouver le processus
ps aux | grep monitor_folder

# Arrêter le processus
kill <PID>

# Ou si c'est un service systemd
sudo systemctl stop folder-monitor
```

## Exemple Complet d'Utilisation

```bash
# 1. Créer le dossier à surveiller
mkdir -p /home/user/exports

# 2. Démarrer la surveillance
./monitor_folder.sh /home/user/exports admin@example.com &

# 3. Vérifier que le script tourne
ps aux | grep monitor_folder

# 4. Suivre les logs
tail -f /var/log/folder_monitor.log

# 5. Tester en créant un fichier
touch /home/user/exports/test.txt

# 6. Attendre 4 heures pour recevoir l'alerte
# (ou modifier ALERT_THRESHOLD pour tester plus rapidement)
```

## Sécurité

⚠️ **Important** :
- Ne stockez jamais de mots de passe en clair dans les scripts
- Utilisez des variables d'environnement ou des fichiers de configuration sécurisés
- Limitez les permissions du fichier de log : `chmod 600 /var/log/folder_monitor.log`
- Exécutez le script avec les permissions minimales nécessaires

## Support

Pour toute question ou problème, consultez les logs ou modifiez le script selon vos besoins.
