#!/usr/bin/perl

###############################################################################
# Script de surveillance de dossier (Perl)
# Envoie un email si aucun fichier n'est créé/modifié pendant 4 heures
###############################################################################

use strict;
use warnings;
use File::Find;
use POSIX qw(strftime);
use Time::Local;

# CONFIGURATION
my $FOLDER_TO_MONITOR = $ARGV[0] || '/path/to/monitor';
my $CHECK_INTERVAL = 300;        # Intervalle de vérification en secondes (5 minutes)
my $ALERT_THRESHOLD = 14400;     # Seuil d'alerte en secondes (4 heures)
my $EMAIL_TO = $ARGV[1] || 'admin@example.com';
my $EMAIL_FROM = "monitor\@" . `hostname`;
chomp($EMAIL_FROM);
my $SUBJECT = "[ALERTE] Aucun fichier généré dans $FOLDER_TO_MONITOR";
my $LOG_FILE = '/var/log/folder_monitor_perl.log';

# Variables globales
my $last_modification_time = time();
my $alert_sent = 0;

###############################################################################
# Fonction de log
###############################################################################
sub log_message {
    my ($message) = @_;
    my $timestamp = strftime("%Y-%m-%d %H:%M:%S", localtime);
    my $log_line = "[$timestamp] $message\n";

    print $log_line;

    if (open(my $fh, '>>', $LOG_FILE)) {
        print $fh $log_line;
        close($fh);
    }
}

###############################################################################
# Fonction d'envoi d'email
###############################################################################
sub send_email {
    my ($message) = @_;

    # Méthode 1: Utilisation de sendmail
    if (-x '/usr/sbin/sendmail' || -x '/usr/bin/sendmail') {
        my $sendmail = '/usr/sbin/sendmail';
        $sendmail = '/usr/bin/sendmail' unless -x $sendmail;

        if (open(my $mail, '|-', "$sendmail -t")) {
            print $mail "To: $EMAIL_TO\n";
            print $mail "From: $EMAIL_FROM\n";
            print $mail "Subject: $SUBJECT\n";
            print $mail "\n";
            print $mail $message;
            close($mail);

            log_message("Email envoyé via sendmail à $EMAIL_TO");
            return 1;
        }
    }

    # Méthode 2: Utilisation du module Email::Sender (si disponible)
    eval {
        require Email::Sender::Simple;
        require Email::Simple;
        require Email::Simple::Creator;

        my $email = Email::Simple->create(
            header => [
                To      => $EMAIL_TO,
                From    => $EMAIL_FROM,
                Subject => $SUBJECT,
            ],
            body => $message,
        );

        Email::Sender::Simple::sendmail($email);
        log_message("Email envoyé via Email::Sender à $EMAIL_TO");
        return 1;
    };

    if ($@) {
        log_message("ERREUR: Impossible d'envoyer l'email - $@");
        return 0;
    }

    log_message("ERREUR: Aucun système d'envoi d'email disponible");
    return 0;
}

###############################################################################
# Fonction pour trouver le fichier le plus récent
###############################################################################
sub find_latest_file {
    my ($directory) = @_;
    my $latest_time = 0;
    my $latest_file = '';

    my $wanted = sub {
        return unless -f $_;
        my $mtime = (stat($_))[9];
        if ($mtime > $latest_time) {
            $latest_time = $mtime;
            $latest_file = $File::Find::name;
        }
    };

    eval {
        find($wanted, $directory);
    };

    if ($@) {
        log_message("ERREUR lors de la recherche de fichiers: $@");
        return (0, '');
    }

    return ($latest_time, $latest_file);
}

###############################################################################
# Fonction pour formater la durée
###############################################################################
sub format_duration {
    my ($seconds) = @_;
    my $hours = int($seconds / 3600);
    my $minutes = int(($seconds % 3600) / 60);

    if ($hours > 0) {
        return sprintf("%.2f heures", $seconds / 3600);
    } else {
        return sprintf("%d minutes", $minutes);
    }
}

###############################################################################
# Vérification des prérequis
###############################################################################
unless (-d $FOLDER_TO_MONITOR) {
    log_message("ERREUR: Le dossier $FOLDER_TO_MONITOR n'existe pas");
    exit 1;
}

log_message("Démarrage de la surveillance du dossier: $FOLDER_TO_MONITOR");
log_message("Seuil d'alerte: $ALERT_THRESHOLD secondes (" . int($ALERT_THRESHOLD / 3600) . " heures)");
log_message("Email d'alerte: $EMAIL_TO");

###############################################################################
# Boucle principale de surveillance
###############################################################################
while (1) {
    my ($latest_timestamp, $latest_filename) = find_latest_file($FOLDER_TO_MONITOR);

    if ($latest_timestamp > 0) {
        my $current_time = time();
        my $time_diff = $current_time - $latest_timestamp;

        # Si un nouveau fichier a été créé/modifié
        if ($latest_timestamp > $last_modification_time) {
            log_message("Nouveau fichier détecté: $latest_filename");
            $last_modification_time = $latest_timestamp;
            $alert_sent = 0;
        }

        # Vérifier si le seuil d'alerte est dépassé
        if ($time_diff > $ALERT_THRESHOLD && !$alert_sent) {
            my $duration = format_duration($time_diff);
            my $latest_date = strftime("%Y-%m-%d %H:%M:%S", localtime($latest_timestamp));
            my $current_date = strftime("%Y-%m-%d %H:%M:%S", localtime($current_time));
            my $hostname = `hostname`;
            chomp($hostname);

            my $message = <<EOF;
ALERTE: Aucun fichier n'a été créé ou modifié dans le dossier '$FOLDER_TO_MONITOR' depuis $duration.

Dernier fichier détecté:
- Nom: $latest_filename
- Date: $latest_date

Heure de l'alerte: $current_date
Serveur: $hostname
EOF

            log_message("ALERTE: $message");
            send_email($message);
            $alert_sent = 1;
        }

        # Log périodique (toutes les heures)
        if (($current_time % 3600) < $CHECK_INTERVAL) {
            my $minutes_ago = int($time_diff / 60);
            log_message("Surveillance active - Dernière modification il y a $minutes_ago minutes");
        }
    } else {
        log_message("Aucun fichier trouvé dans $FOLDER_TO_MONITOR");
    }

    # Attendre avant la prochaine vérification
    sleep($CHECK_INTERVAL);
}
