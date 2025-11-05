#!/bin/bash

###############################################################################
# Script de surveillance de dossier
# Envoie un email si aucun fichier n'est créé/modifié pendant 4 heures
###############################################################################

# CONFIGURATION
FOLDER_TO_MONITOR="${1:-/path/to/monitor}"  # Dossier à surveiller (passé en argument ou par défaut)
CHECK_INTERVAL=300                           # Intervalle de vérification en secondes (5 minutes)
ALERT_THRESHOLD=14400                        # Seuil d'alerte en secondes (4 heures = 14400s)
EMAIL_TO="${2:-admin@example.com}"          # Email destinataire
EMAIL_FROM="monitor@$(hostname)"             # Email expéditeur
SUBJECT="[ALERTE] Aucun fichier généré dans $FOLDER_TO_MONITOR"
LOG_FILE="/var/log/folder_monitor.log"

# Couleurs pour les logs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

###############################################################################
# Fonction de log
###############################################################################
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

###############################################################################
# Fonction d'envoi d'email
###############################################################################
send_email() {
    local message="$1"

    # Méthode 1: Utilisation de mail (si disponible)
    if command -v mail &> /dev/null; then
        echo "$message" | mail -s "$SUBJECT" -r "$EMAIL_FROM" "$EMAIL_TO"
        log "Email envoyé via 'mail' à $EMAIL_TO"
        return 0
    fi

    # Méthode 2: Utilisation de sendmail (si disponible)
    if command -v sendmail &> /dev/null; then
        {
            echo "To: $EMAIL_TO"
            echo "From: $EMAIL_FROM"
            echo "Subject: $SUBJECT"
            echo ""
            echo "$message"
        } | sendmail -t
        log "Email envoyé via 'sendmail' à $EMAIL_TO"
        return 0
    fi

    # Méthode 3: Utilisation de curl avec un serveur SMTP
    # Décommentez et configurez si vous avez un serveur SMTP
    # curl --url 'smtp://smtp.example.com:587' \
    #      --ssl-reqd \
    #      --mail-from "$EMAIL_FROM" \
    #      --mail-rcpt "$EMAIL_TO" \
    #      --user 'username:password' \
    #      -T <(echo -e "From: $EMAIL_FROM\nTo: $EMAIL_TO\nSubject: $SUBJECT\n\n$message")

    log "${RED}ERREUR: Aucun système d'envoi d'email disponible (mail, sendmail)${NC}"
    return 1
}

###############################################################################
# Vérification des prérequis
###############################################################################
if [ ! -d "$FOLDER_TO_MONITOR" ]; then
    log "${RED}ERREUR: Le dossier $FOLDER_TO_MONITOR n'existe pas${NC}"
    exit 1
fi

log "${GREEN}Démarrage de la surveillance du dossier: $FOLDER_TO_MONITOR${NC}"
log "Seuil d'alerte: $ALERT_THRESHOLD secondes ($(($ALERT_THRESHOLD / 3600)) heures)"
log "Email d'alerte: $EMAIL_TO"

###############################################################################
# Boucle principale de surveillance
###############################################################################
last_modification_time=$(date +%s)
alert_sent=false

while true; do
    # Trouver le fichier le plus récemment modifié dans le dossier
    latest_file=$(find "$FOLDER_TO_MONITOR" -type f -printf '%T@ %p\n' 2>/dev/null | sort -n | tail -1)

    if [ -n "$latest_file" ]; then
        # Extraire le timestamp et le nom du fichier
        latest_timestamp=$(echo "$latest_file" | cut -d' ' -f1 | cut -d'.' -f1)
        latest_filename=$(echo "$latest_file" | cut -d' ' -f2-)

        current_time=$(date +%s)
        time_diff=$((current_time - latest_timestamp))

        # Si un nouveau fichier a été créé/modifié
        if [ "$latest_timestamp" -gt "$last_modification_time" ]; then
            log "${GREEN}Nouveau fichier détecté: $latest_filename${NC}"
            last_modification_time=$latest_timestamp
            alert_sent=false
        fi

        # Vérifier si le seuil d'alerte est dépassé
        if [ $time_diff -gt $ALERT_THRESHOLD ] && [ "$alert_sent" = false ]; then
            hours_ago=$(echo "scale=2; $time_diff / 3600" | bc)
            message="ALERTE: Aucun fichier n'a été créé ou modifié dans le dossier '$FOLDER_TO_MONITOR' depuis $hours_ago heures.

Dernier fichier détecté:
- Nom: $latest_filename
- Date: $(date -d @$latest_timestamp '+%Y-%m-%d %H:%M:%S')

Heure de l'alerte: $(date '+%Y-%m-%d %H:%M:%S')
Serveur: $(hostname)"

            log "${YELLOW}$message${NC}"
            send_email "$message"
            alert_sent=true
        fi

        # Log périodique (toutes les heures)
        if [ $((current_time % 3600)) -lt $CHECK_INTERVAL ]; then
            log "Surveillance active - Dernière modification il y a $(($time_diff / 60)) minutes"
        fi
    else
        log "${YELLOW}Aucun fichier trouvé dans $FOLDER_TO_MONITOR${NC}"
    fi

    # Attendre avant la prochaine vérification
    sleep $CHECK_INTERVAL
done
