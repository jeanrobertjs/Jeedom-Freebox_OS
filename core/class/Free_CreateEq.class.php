<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class Free_CreateEq
{
    public static function createEq($create = 'default')
    {
        $logicalinfo = Freebox_OS::getlogicalinfo();
        if (version_compare(jeedom::version(), "4", "<")) {
            $templatecore_V4 = null;
        } else {
            $templatecore_V4  = 'core::';
        };
        switch ($create) {
            case 'airmedia':
                Free_CreateEq::createEq_airmedia($logicalinfo, $templatecore_V4);
                break;
            case 'connexion':
                Free_CreateEq::createEq_connexion($logicalinfo, $templatecore_V4);
                Free_CreateEq::createEq_connexion_4G($logicalinfo, $templatecore_V4);
                Free_CreateEq::createEq_connexion_FTTH($logicalinfo, $templatecore_V4);
                Free_CreateEq::createEq_connexion_xdsl($logicalinfo, $templatecore_V4);
                break;
            case 'disk':
                Free_CreateEq::createEq_disk_SP($logicalinfo, $templatecore_V4);
                break;
            case 'downloads':
                Free_CreateEq::createEq_download($logicalinfo, $templatecore_V4);
                break;
            case 'parental':
                Free_CreateEq::createEq_parental($logicalinfo, $templatecore_V4);
                break;
            case 'network':
                Free_CreateEq::createEq_network_SP($logicalinfo, $templatecore_V4, 'LAN');
                break;
            case 'networkwifiguest':
                Free_CreateEq::createEq_network_SP($logicalinfo, $templatecore_V4, 'WIFIGUEST');
                break;
            case 'phone':
                Free_CreateEq::createEq_phone($logicalinfo, $templatecore_V4);
                break;
            case 'system':
                Free_CreateEq::createEq_system_SP($logicalinfo, $templatecore_V4);
                break;
            case 'wifi':
                Free_CreateEq::createEq_wifi($logicalinfo, $templatecore_V4);
                break;
            default:
                log::add('Freebox_OS', 'debug', '================= ORDRE DE LA CREATION DES EQUIPEMENTS STANDARDS  ==================');
                log::add('Freebox_OS', 'debug', '================= ' . $logicalinfo['airmediaName']);
                log::add('Freebox_OS', 'debug', '================= ' . $logicalinfo['connexionName'] . ' / 4G' . ' / Fibre' . ' / xdsl');
                log::add('Freebox_OS', 'debug', '================= ' . $logicalinfo['diskName']);
                log::add('Freebox_OS', 'debug', '================= ' . $logicalinfo['networkName']);
                log::add('Freebox_OS', 'debug', '================= ' . $logicalinfo['networkwifiguestName']);
                log::add('Freebox_OS', 'debug', '================= ' . $logicalinfo['phoneName']);
                log::add('Freebox_OS', 'debug', '================= ' . $logicalinfo['systemName']);
                log::add('Freebox_OS', 'debug', '================= ' . $logicalinfo['wifiName']);
                log::add('Freebox_OS', 'debug', '================= ENSEMBLE DES PLAYERS SOUS TENSION');
                log::add('Freebox_OS', 'debug', '====================================================================================');
                Free_CreateEq::createEq_airmedia($logicalinfo, $templatecore_V4);
                Free_CreateEq::createEq_connexion($logicalinfo, $templatecore_V4);
                Free_CreateEq::createEq_connexion_4G($logicalinfo, $templatecore_V4);
                Free_CreateEq::createEq_connexion_FTTH($logicalinfo, $templatecore_V4);
                Free_CreateEq::createEq_connexion_xdsl($logicalinfo, $templatecore_V4);
                Free_CreateEq::createEq_disk($logicalinfo, $templatecore_V4);
                Free_CreateEq::createEq_download($logicalinfo, $templatecore_V4);
                Free_CreateEq::createEq_network($logicalinfo, $templatecore_V4, 'LAN');
                Free_CreateEq::createEq_network($logicalinfo, $templatecore_V4, 'WIFIGUEST');
                Free_CreateEq::createEq_phone($logicalinfo, $templatecore_V4);
                Free_CreateEq::createEq_system($logicalinfo, $templatecore_V4);
                Free_CreateEq::createEq_wifi($logicalinfo, $templatecore_V4);
                // TEST
                // Free_CreateEq::createEq_notification($logicalinfo, $templatecore_V4);
                break;
        }
    }
    private static function createEq_airmedia($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Ajout des commandes : ' . $logicalinfo['airmediaName']);
        $updateicon = false;
        if (version_compare(jeedom::version(), "4", "<")) {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V3 ');
            $iconAirPlayOn = 'fas fa-play';
            $iconAirPlayOff = 'fas fa-stop';
        } else {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V4');
            $iconAirPlayOn = 'fas fa-play icon_green';
            $iconAirPlayOff = 'fas fa-stop icon_red';
        };
        $Airmedia = Freebox_OS::AddEqLogic($logicalinfo['airmediaName'], $logicalinfo['airmediaID'], 'multimedia', false, null, null, null, '*/5 * * * *');
        $Airmedia->AddCommand('Choix Player AirMedia', 'ActualAirmedia', 'info', 'string', 'Freebox_OS::AirMedia_Recever', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default', 1, '0', false, true);
        $Airmedia->AddCommand('Start', 'airmediastart', 'action', 'message', 'Freebox_OS::AirMedia_Start', null, null, 1, 'default', 'default', 0, $iconAirPlayOn, 0, 'default', 'default', 2, '0', $updateicon, false, null, true);
        $Airmedia->AddCommand('Stop', 'airmediastop', 'action', 'message', 'Freebox_OS::AirMedia_Stop', null, null, 1, 'default', 'default', 0, $iconAirPlayOff, 0, 'default', 'default', 3, '0', $updateicon, false, null, true);
        log::add('Freebox_OS', 'debug', '└─────────');
    }
    private static function createEq_airmedia_sp($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Création équipement spécifique : ' . $logicalinfo['airmediaName']);
        $Free_API = new Free_API();
        $Free_API->universal_get('airmedia', null, null, null);
        log::add('Freebox_OS', 'debug', '└─────────');
    }

    private static function createEq_connexion($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Ajout des commandes : ' . $logicalinfo['connexionName']);
        $updateicon = false;
        if (version_compare(jeedom::version(), "4", "<")) {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V3 ');
        } else {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V4');
        };
        $Free_API = new Free_API();
        $result = $Free_API->universal_get('connexion', null, null, 'ftth');
        if ($result['sfp_present'] == null) {
            $_bandwidth_value_down = '#value# / 1000000';
            $_bandwidth_down_unit = 'Mb/s';
            $_bandwidth_value_up = '#value#  / 1000000';
            $_bandwidth_up_unit = 'Mb/s';
        } else {
            $_bandwidth_value_down = '#value#  / 1000000000';
            $_bandwidth_down_unit = 'Gb/s';
            $_bandwidth_value_up = '#value#  / 1000000';
            $_bandwidth_up_unit = 'Mb/s';
        }
        $Connexion = Freebox_OS::AddEqLogic($logicalinfo['connexionName'], $logicalinfo['connexionID'], 'default', false, null, null, '*/15 * * * *');
        $Connexion->AddCommand('Débit descendant', 'rate_down', 'info', 'numeric', $templatecore_V4 . 'badge', 'Ko/s', null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  1, '0', $updateicon, true, null, null, null, '#value# / 1024', '2');
        $Connexion->AddCommand('Débit montant', 'rate_up', 'info', 'numeric', $templatecore_V4 . 'badge', 'Ko/s', null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  2, '0', $updateicon, true, null, null, null, '#value# / 1024', '2');
        $Connexion->AddCommand('Débit descendant (max)', 'bandwidth_down', 'info', 'numeric', $templatecore_V4 . 'badge', $_bandwidth_down_unit, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  3, '0', $updateicon, true, null, null, null, $_bandwidth_value_down, '2');
        $Connexion->AddCommand('Débit montant (max)', 'bandwidth_up', 'info', 'numeric', $templatecore_V4 . 'badge', $_bandwidth_up_unit, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  4, '0', $updateicon, true, null, null, null, $_bandwidth_value_up, '2');
        $Connexion->AddCommand('Type de connexion', 'media', 'info', 'string', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  5, '0', $updateicon, true);
        $Connexion->AddCommand('Etat de la connexion', 'state', 'info', 'string', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  6, '0', $updateicon, true);
        $Connexion->AddCommand('IPv4', 'ipv4', 'info', 'string', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  7, '0', $updateicon, true);
        $Connexion->AddCommand('IPv6', 'ipv6', 'info', 'string', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  8, '0', $updateicon, true);
        $Connexion->AddCommand('Réponse Ping', 'ping', 'info', 'binary', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  9, '0', $updateicon, true);
        $Connexion->AddCommand('Proxy Wake on Lan', 'wol', 'info', 'binary', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  10, '0', $updateicon, true);
        log::add('Freebox_OS', 'debug', '└─────────');
    }
    private static function createEq_connexion_FTTH($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Ajout des commandes Spécifique Fibre : ' . $logicalinfo['connexionName']);
        $Free_API = new Free_API();
        $result = $Free_API->universal_get('connexion', null, null, 'ftth');
        if ($result['sfp_present'] == null) {
            $_modul = 'Module Fibre Non Présent';
        } else {
            $_modul = 'Module Fibre Présent';
        }
        log::add('Freebox_OS', 'debug', '│ ' . $_modul);

        if ($result != false && $result['sfp_present'] == 1) {

            $updateicon = false;
            if (version_compare(jeedom::version(), "4", "<")) {
                log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V3 ');
            } else {
                log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V4');
            };
            $Connexion = Freebox_OS::AddEqLogic($logicalinfo['connexionName'], $logicalinfo['connexionID'], 'default', false, null, null, '*/15 * * * *');
            $Connexion->AddCommand('Type de connexion Fibre', 'link_type', 'info', 'string', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  20, '0', $updateicon, true);
            $Connexion->AddCommand('Module Fibre présent', 'sfp_present', 'info', 'binary', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  21, '0', $updateicon, true);
            $Connexion->AddCommand('Signal Fibre présent', 'sfp_has_signal', 'info', 'binary', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  22, '0', $updateicon, true);
            $Connexion->AddCommand('Etat Alimentation', 'sfp_alim_ok', 'info', 'binary', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  23, '0', $updateicon, true);
            $Connexion->AddCommand('Puissance transmise', 'sfp_pwr_tx', 'info', 'numeric', $templatecore_V4 . 'badge', 'dBm', null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  24, '0', $updateicon, true, null, null, null, '#value# / 100', '2');
            $Connexion->AddCommand('Puissance reçue', 'sfp_pwr_rx', 'info', 'numeric', $templatecore_V4 . 'badge', 'dBm', null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  25, '0', $updateicon, true, null, null, null, '#value# / 100', '2');
        } else {
            log::add('Freebox_OS', 'debug', '│ Pas de commande spécifique à ajouter');
        }
        log::add('Freebox_OS', 'debug', '└─────────');
    }
    private static function createEq_connexion_4G($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Ajout des commandes Spécifique 4G : ' . $logicalinfo['connexionName']);
        $Free_API = new Free_API();
        $result = $Free_API->universal_get('connexion', null, null, 'lte/config');
        if ($result != false && $result != 'Aucun module 4G détecté') {
            $_modul = 'Module 4G Présent';
            log::add('Freebox_OS', 'debug', '│ ' . $_modul);
            $Connexion = Freebox_OS::AddEqLogic($logicalinfo['connexionName'], $logicalinfo['connexionID'], 'default', false, null, null, '*/15 * * * *');
            $Connexion->AddCommand('Débit xDSL Descendant', 'tx_used_rate_xdsl', 'info', 'numeric', $templatecore_V4 . 'badge', 'ko/s', null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  20, '0', null, true, null, null, null, '#value# / 1000', '2');
            $Connexion->AddCommand('Débit xDSL Montant', 'rx_used_rate_xdsl', 'info', 'numeric', $templatecore_V4 . 'badge', 'ko/s', null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  21, '0', null, true, null, null, null, '#value# / 1000', '2');
            $Connexion->AddCommand('Débit xDSL Descendant (max)', 'tx_max_rate_xdsl', 'info', 'numeric', $templatecore_V4 . 'badge', 'ko/s', null, 0, 'default', 'default', 0, null, 0, 'default', 'default',  22, '0', null, true, null, null, null, '#value# / 1000', '2');
            $Connexion->AddCommand('Débit xDSL Montant (max)', 'rx_max_rate_xdsl', 'info', 'numeric', $templatecore_V4 . 'badge', 'ko/s', null, 0, 'default', 'default', 0, null, 0, 'default', 'default',  23, '0', null, true, null, null, null, '#value# / 1000', '2');
            $Connexion->AddCommand('Débit 4G Descendant', 'tx_use_rate_lte', 'info', 'numeric', $templatecore_V4 . 'badge', 'ko/s', null, 0, 'default', 'default', 0, null, 0, 'default', 'default',  24, '0', null, true, null, null, null, '#value# / 1000', '2');
            $Connexion->AddCommand('Débit 4G Montant', 'rx_used_rate_lte', 'info', 'numeric', $templatecore_V4 . 'badge', 'ko/s', null, 0, 'default', 'default', 0, null, 0, 'default', 'default',  25, '0', null, true, null, null, null, '#value# / 1000', '2');
            $Connexion->AddCommand('Débit 4G Descendant (max)', 'tx_max_rate_lte', 'info', 'numeric', $templatecore_V4 . 'badge', 'ko/s', null, 0, 'default', 'default', 0, null, 0, 'default', 'default',  26, '0', null, true, null, null, null, '#value# / 1000', '2');
            $Connexion->AddCommand('Débit 4G Montant (max)', 'rx_max_rate_lte', 'info', 'numeric', $templatecore_V4 . 'badge', 'ko/s', null, 0, 'default', 'default', 0, null, 0, 'default', 'default',  27, '0', null, true, null, null, null, '#value# / 1000', '2');
            $Connexion->AddCommand('Etat de la connexion xDSL 4G', 'state', 'info', 'string', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  28, '0', null, true);
        } else {
            $_modul = 'Module 4G Non Présent';
            log::add('Freebox_OS', 'debug', '│ ' . $_modul);
        }

        log::add('Freebox_OS', 'debug', '└─────────');
    }
    private static function createEq_connexion_xdsl($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Ajout des commandes Spécifique xdsl : ' . $logicalinfo['connexionName']);
        $Free_API = new Free_API();
        $result = $Free_API->universal_get('connexion', null, null, 'xdsl');
        if ($result != false && $result != 'Aucun module 4G détecté') {
            $_modul = 'Module xdsl Présent';
            log::add('Freebox_OS', 'debug', '│ ' . $_modul);
            $Connexion = Freebox_OS::AddEqLogic($logicalinfo['connexionName'], $logicalinfo['connexionID'], 'default', false, null, null, '*/15 * * * *');
            $Connexion->AddCommand('Type de modulation', 'modulation', 'info', 'string', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  40, '0', null, true);
            $Connexion->AddCommand('Protocole', 'protocol', 'info', 'string', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  41, '0', null, true);
        } else {
            $_modul = 'Module xdsl Non Présent';
            log::add('Freebox_OS', 'debug', '│ ' . $_modul);
        }

        log::add('Freebox_OS', 'debug', '└─────────');
    }
    private static function createEq_disk($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Création équipement : ' . $logicalinfo['diskName']);
        Freebox_OS::AddEqLogic($logicalinfo['diskName'], $logicalinfo['diskID'], 'default', false, null, null, null, '5 */12 * * *');
        log::add('Freebox_OS', 'debug', '└─────────');
    }

    private static function createEq_disk_SP($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Création équipement spécifique : ' . $logicalinfo['diskName']);
        $Free_API = new Free_API();
        $Free_API->disk();
        log::add('Freebox_OS', 'debug', '└─────────');
    }

    private static function createEq_download($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Ajout des commandes : ' . $logicalinfo['downloadsName']);
        $updateicon = false;
        if (version_compare(jeedom::version(), "4", "<")) {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V3 ');
            $iconDownloadsOn = 'fas fa-play';
            $iconDownloadsOff = 'fas fa-stop';
            $iconRSSnb = 'fas fa-rss';
            $iconRSSread = 'fas fa-rss-square';
            $iconconn_ready = 'fas fa-ethernet';
            $iconthrottling_is_scheduled = 'far fa-calendar-alt';
        } else {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V4');
            $iconDownloadsOn = 'fas fa-play icon_green';
            $iconDownloadsOff = 'fas fa-stop icon_red';
            $iconRSSnb = 'fas fa-rss icon_green';
            $iconRSSread = 'fas fa-rss-square icon_orange';
            $iconconn_ready = 'fas fa-ethernet icon_green';
            $iconthrottling_is_scheduled = 'far fa-calendar-alt icon_green';
        };
        $downloads = Freebox_OS::AddEqLogic($logicalinfo['downloadsName'], $logicalinfo['downloadsID'], 'multimedia', false, null, null, null, '5 */12 * * *');
        $downloads->AddCommand('Nb de tâche(s)', 'nb_tasks', 'info', 'numeric', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  1, '0', $updateicon, true);
        $downloads->AddCommand('Nb de tâche(s) active', 'nb_tasks_active', 'info', 'numeric', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  2, '0', $updateicon, true);
        $downloads->AddCommand('Nb de tâche(s) en extraction', 'nb_tasks_extracting', 'info', 'numeric', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  3, '0', $updateicon, true);
        $downloads->AddCommand('Nb de tâche(s) en réparation', 'nb_tasks_repairing', 'info', 'numeric', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  4, '0', $updateicon, true);
        $downloads->AddCommand('Nb de tâche(s) en vérification', 'nb_tasks_checking', 'info', 'numeric', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  5, '0', $updateicon, true);
        $downloads->AddCommand('Nb de tâche(s) en attente', 'nb_tasks_queued', 'info', 'numeric', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  6, '0', $updateicon, true);
        $downloads->AddCommand('Nb de tâche(s) en erreur', 'nb_tasks_error', 'info', 'numeric', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  7, '0', $updateicon, true);
        $downloads->AddCommand('Nb de tâche(s) stoppée(s)', 'nb_tasks_stopped', 'info', 'numeric', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  8, '0', $updateicon, true);
        $downloads->AddCommand('Nb de tâche(s) terminée(s)', 'nb_tasks_done', 'info', 'numeric', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  9, '0', $updateicon, true);
        $downloads->AddCommand('Téléchargement en cours', 'nb_tasks_downloading', 'info', 'numeric', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default', 10, '0', $updateicon, true);
        $downloads->AddCommand('Vitesse réception', 'rx_rate', 'info', 'numeric', $templatecore_V4 . 'badge', 'Ko/s', null, 1, 'default', 'default', 0, null, 0, 'default', 'default', 11, '0', $updateicon, true, null, null, null, '#value# / 1000', '2');
        $downloads->AddCommand('Vitesse émission', 'tx_rate', 'info', 'numeric', $templatecore_V4 . 'badge', 'Ko/s', null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  12, '0', $updateicon, true, null, null, null, '#value# / 1000', '2');
        $downloads->AddCommand('Start Téléchargement', 'start_dl', 'action', 'other', null, null, null, 1, 'default', 'default', 0, $iconDownloadsOn, 0, 'default', 'default',  13, '0', $updateicon, false);
        $downloads->AddCommand('Stop Téléchargement', 'stop_dl', 'action', 'other', null, null, null, 1, 'default', 'default', 0, $iconDownloadsOff, 0, 'default', 'default',  14, '0', $updateicon, false);
        $downloads->AddCommand('Nb de flux RSS', 'nb_rss', 'info', 'numeric', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, $iconRSSnb, 0, 'default', 'default',  15, '0', $updateicon, false, null, true);
        $downloads->AddCommand('Nb de flux RSS Non Lu', 'nb_rss_items_unread', 'info', 'numeric', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, $iconRSSread, 0, 'default', 'default',  16, '0', $updateicon, false, null, true);
        $downloads->AddCommand('Etat connexion', 'conn_ready', 'info', 'binary', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, $iconconn_ready, 0, 'default', 'default',  17, '0', $updateicon, true);
        $downloads->AddCommand('Etat Planning', 'throttling_is_scheduled', 'info', 'binary', $templatecore_V4 . 'line', null, null, 0, 'default', 'default', 0, $iconthrottling_is_scheduled, 0, 'default', 'default',  18, '0', $updateicon, true);

        log::add('Freebox_OS', 'debug', '└─────────');
    }

    private static function createEq_parental($logicalinfo, $templatecore_V4)
    {
        $Free_API = new Free_API();
        foreach ($Free_API->universal_get('parentalprofile') as $Equipement) {
            log::add('Freebox_OS', 'debug', '┌───────── Ajout des commandes : Contrôle parental');
            if (version_compare(jeedom::version(), "4", "<")) {
                log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V3 ');
                $Templateparent = null;
                $iconparent_allowed = 'fas fa-user-check';
                $iconparent_denied = 'fas fa-user-lock';
                $iconparent_temp = 'fas fa-user-clock';
            } else {
                log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V4');
                $Templateparent = 'Freebox_OS::Parental';
                $iconparent_allowed = 'fas fa-user-check icon_green';
                $iconparent_denied = 'fas fa-user-lock icon_red';
                $iconparent_temp = 'fas fa-user-clock icon_blue';
            };

            $category = 'default';
            $Equipement['name'] = preg_replace('/\'+/', ' ', $Equipement['name']); // Suppression '

            $parental = Freebox_OS::AddEqLogic($Equipement['name'], 'parental_' . $Equipement['id'], $category, true, 'parental', null, $Equipement['id'], '*/5 * * * *');
            $StatusParental = $parental->AddCommand('Etat', $Equipement['id'], "info", 'string', $Templateparent, null, null, 1, '', '', '', '', 0, 'default', 'default', 1, 1, false, true, null, true);
            $parental->AddCommand('Autoriser', 'allowed', 'action', 'other', null, null, null, 1, $StatusParental, 'parentalStatus', 0, $iconparent_allowed, 0, 'default', 'default', 2, '0', false, false, null, true);
            $parental->AddCommand('Bloquer', 'denied', 'action', 'other', null, null, null, 1, $StatusParental, 'parentalStatus', 0, $iconparent_denied, 0, 'default', 'default', 3, '0', false, false, null, true);
            $parental->AddCommand('Autoriser-Bloquer Temporairement', 'tempDenied', 'action', 'select', null, null, null, 1, $StatusParental, 'parentalStatus', '', $iconparent_temp, 0, 'default', 'default', 4, '0', false, false, '', true);
            log::add('Freebox_OS', 'debug', '└─────────');
        }
    }
    private static function createEq_phone($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Ajout des commandes : ' . $logicalinfo['phoneName']);
        $updateicon = false;
        if (version_compare(jeedom::version(), "4", "<")) {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V3 ');
            $iconmissed = 'icon techno-phone1';
            $iconaccepted = 'icon techno-phone3';
            $iconoutgoing = 'ficon techno-phone2';
            $iconDell_call = 'fas fa-magic';
            $iconRead_call = 'fab fa-readme';
        } else {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V4');
            $iconmissed = 'icon techno-phone1 icon_red';
            $iconaccepted = 'icon techno-phone3 icon_blue';
            $iconoutgoing = 'icon techno-phone2 icon_green';
            $iconDell_call = 'fas fa-magic icon_red';
            $iconRead_call = 'fab fa-readme icon_blue';
        };
        $phone = Freebox_OS::AddEqLogic($logicalinfo['phoneName'], $logicalinfo['phoneID'], 'default', false, null, null, null, '*/30 * * * *');
        $phone->AddCommand('Nb Manqués', 'nbmissed', 'info', 'numeric', $templatecore_V4 . 'badge', null, null, 1, 'default', 'default', 0, $iconmissed, 1, 'default', 'default',  1, '0', $updateicon, true, false, true, null, null, null, null);
        $phone->AddCommand('Liste Manqués', 'listmissed', 'info', 'string', null, null, null, 1, 'default', 'default', 0, $iconmissed, 1, 'default', 'default',  2, '0', $updateicon, true, false, null, null, null, null, 'NONAME');
        $phone->AddCommand('Nb Reçus', 'nbaccepted', 'info', 'numeric', $templatecore_V4 . 'badge', null, null, 1, 'default', 'default', 0, $iconaccepted, 1, 'default', 'default',  3, '0', $updateicon, true, false, true, null, null, null, null);
        $phone->AddCommand('Liste Reçus', 'listaccepted', 'info', 'string', null, null, null, 1, 'default', 'default', 0, $iconaccepted, 1, 'default', 'default',  4, '0', $updateicon, true, false, null, null, null, null, 'NONAME');
        $phone->AddCommand('Nb Emis', 'nboutgoing', 'info', 'numeric', $templatecore_V4 . 'badge', null, null, 1, 'default', 'default', 0, $iconoutgoing, 1, 'default', 'default',  5, '0', $updateicon, true, false, true, null, null, null, null);
        $phone->AddCommand('Liste Emis', 'listoutgoing', 'info', 'string', null, null, null, 1, 'default', 'default', 0, $iconoutgoing, 1, 'default', 'default',  6, '0', $updateicon, true, false, null, null, null, null, 'NONAME');
        $phone->AddCommand('Vider le journal d appels', 'phone_dell_call', 'action', 'other', 'default', null, null,  1, 'default', 'default', 0, $iconDell_call, 1, 'default', 'default', 9, '0', $updateicon, false, null, true);
        $phone->AddCommand('Tout marquer comme lu', 'phone_read_call', 'action', 'other', 'default', null, null,  1, 'default', 'default', 0, $iconRead_call, 0, 'default', 'default', 10, '0', $updateicon, false, null, true);

        log::add('Freebox_OS', 'debug', '└─────────');
    }

    private static function createEq_network($logicalinfo, $templatecore_V4, $_network = 'LAN')
    {
        if ($_network == 'LAN') {
            $_networkname = $logicalinfo['networkName'];
            $_networkID = $logicalinfo['networkID'];
        } else if ($_network == 'WIFIGUEST') {
            $_networkname = $logicalinfo['networkwifiguestName'];
            $_networkID = $logicalinfo['networkwifiguestID'];
        }
        log::add('Freebox_OS', 'debug', '┌───────── Création équipement : ' . $_networkname);
        Freebox_OS::AddEqLogic($_networkname, $_networkID, 'default', false, null, null, null, '*/5 * * * *');
        log::add('Freebox_OS', 'debug', '└─────────');
    }
    private static function createEq_network_interface($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Création équipement : ' . $logicalinfo['wifiguestName']);
        $Free_API = new Free_API();
        $Free_API->universal_get('network', null, null, 'interfaces');
        log::add('Freebox_OS', 'debug', '└─────────');
    }

    private static function createEq_network_SP($logicalinfo, $templatecore_V4, $_network = 'LAN')
    {
        if ($_network == 'LAN') {
            $_networkname = $logicalinfo['networkName'];
            $_networkID = $logicalinfo['networkID'];
            $_networkinterface = 'pub';
        } else if ($_network == 'WIFIGUEST') {
            $_networkname = $logicalinfo['networkwifiguestName'];
            $_networkID = $logicalinfo['networkwifiguestID'];
            $_networkinterface = 'wifiGuest';
        }
        $updateWidget = true;
        log::add('Freebox_OS', 'debug', '┌───────── Ajout des commandes spécifiques : ' . $_networkname);
        $Free_API = new Free_API();
        $network = Freebox_OS::AddEqLogic($_networkname, $_networkID, 'default', false, null, null, null, '*/5 * * * *');
        log::add('Freebox_OS', 'debug', '>───────── Commande trouvée pour le réseau');
        foreach ($Free_API->universal_get('network', null, null, $_networkinterface) as $Equipement) {
            if ($Equipement['primary_name'] != '') {
                $Command = $network->AddCommand($Equipement['primary_name'], $Equipement['id'], 'info', 'binary', 'Freebox_OS::Network', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default', null, '0', $updateWidget, true);
                $Command->setConfiguration('host_type', $Equipement['host_type']);
                if (isset($Equipement['l3connectivities'])) {
                    foreach ($Equipement['l3connectivities'] as $Ip) {
                        if ($Ip['active']) {
                            if ($Ip['af'] == 'ipv4') {
                                $Command->setConfiguration('IPV4', $Ip['addr']);
                            } else {
                                $Command->setConfiguration('IPV6', $Ip['addr']);
                            }
                        }
                    }
                }
                if (isset($Equipement['l2ident'])) {
                    $ident = $Equipement['l2ident'];
                    if ($ident['type'] == 'mac_address') {
                        $Command->setConfiguration('mac_address', $ident['id']);
                    }
                }
                if ($Command->execCmd() != $Equipement['active']) {
                    $Command->setCollectDate(date('Y-m-d H:i:s'));
                    $Command->setConfiguration('doNotRepeatEvent', 1);
                    $Command->event($Equipement['active']);
                }
                $Command->save();
            }
        }
        log::add('Freebox_OS', 'debug', '└─────────');
    }

    private static function createEq_notification($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Création équipement : ' . $logicalinfo['notificationName']);
        $Free_API = new Free_API();
        $Free_API->universal_get('notification', null, null);
        log::add('Freebox_OS', 'debug', '└─────────');
    }
    private static function createEq_system($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Ajout des commandes : ' . $logicalinfo['systemName']);
        $updateicon = false;
        if (version_compare(jeedom::version(), "4", "<")) {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V3 ');
            $iconReboot = 'fas fa-sync';
        } else {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V4');
            $iconReboot = 'fas fa-sync icon_red';
        };
        $system = Freebox_OS::AddEqLogic($logicalinfo['systemName'], $logicalinfo['systemID'], 'default', false, null, null, null, '*/30 * * * *');
        $system->AddCommand('Reboot', 'reboot', 'action', 'other',  $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, $iconReboot, 0, 'default', 'default',  31, '0', true, false, null, true);
        $system->AddCommand('Freebox firmware version', 'firmware_version', 'info', 'string', $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default', 1, '0', $updateicon, true);
        $system->AddCommand('Mac', 'mac', 'info', 'string',  $templatecore_V4 . 'line', null, null, 0, 'default', 'default', 0, null, 0, 'default', 'default',  2, '0', $updateicon, true);
        $system->AddCommand('Allumée depuis', 'uptime', 'info', 'string',  $templatecore_V4 . 'line', null, null, 1, 'default', 'default', 0, null, 0, 'default', 'default',  3, '0', $updateicon, true);
        $system->AddCommand('Board name', 'board_name', 'info', 'string',  $templatecore_V4 . 'line', null, null, 0, 'default', 'default', 0, null, 0, 'default', 'default',  4, '0', $updateicon, true);
        $system->AddCommand('Serial', 'serial', 'info', 'string',  $templatecore_V4 . 'line', null, null, 0, 'default', 'default', 0, null, 0, 'default', 'default',  5, '0', $updateicon, true);
        //$system->AddCommand('Redirection de ports', 'port_forwarding', 'action', 'message', null, null, null, 0, 'default', 'default', 0, null, 0, 'default', 'default', 'default', 6, '0', $updateicon);
        log::add('Freebox_OS', 'debug', '└─────────');
    }

    private static function createEq_system_SP($logicalinfo, $templatecore_V4)
    {
        $Free_API = new Free_API();
        log::add('Freebox_OS', 'debug', '┌───────── Ajout des commandes spécifiques : ' . $logicalinfo['systemName']);
        $system = Freebox_OS::AddEqLogic($logicalinfo['systemName'], $logicalinfo['systemID'], 'default', false, null, null, null, '*/30 * * * *');
        if (version_compare(jeedom::version(), "4", "<")) {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V3');
            $Template4G = null;
            $templatecore_V4 = null;
            $icontemp = 'fas fa-thermometer-half';
            $iconfan = 'fas fa-fan';
            $icon4Gon = 'fas fa-broadcast-tower';
            $icon4Goff = 'fas fa-broadcast-tower';
        } else {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V4');
            $Template4G = 'Freebox_OS::4G';
            $templatecore_V4  = 'core::';
            $icontemp = 'fas fa-thermometer-half icon_blue';
            $iconfan = 'fas fa-fan icon_blue';
            $icon4Gon = 'fas fa-broadcast-tower icon_green';
            $icon4Goff = 'fas fa-broadcast-tower icon_red';
        };
        $boucle_num = 1; // 1 = sensors - 2 = fans - 3 = extension
        $_order = 6;
        while ($boucle_num <= 3) {
            if ($boucle_num == 1) {
                $boucle_update = 'sensors';
            } else if ($boucle_num == 2) {
                $boucle_update = 'fans';
            } else if ($boucle_num == 3) {
                $boucle_update = 'expansions';
            }
            log::add('Freebox_OS', 'debug', '│──────────> Boucle pour Update : ' . $boucle_update);
            foreach ($Free_API->universal_get('system', null, $boucle_update) as $Equipement) {
                $icon = null;
                $_max = 'default';
                $_min = 'default';
                $_unit = null;
                $_name = $Equipement['name'];
                $_id = $Equipement['id'];
                $_value = $Equipement['value'];
                $_type = 'numeric';
                $IsVisible = 1;
                $_iconname = true;
                if (strpos($_id, 'temp') !== FALSE) {
                    $_unit = '°C';
                    $_max = 100;
                    $_min = '0';
                    $icon = $icontemp;
                    $link_logicalId = 'sensors';
                } else if (strpos($_id, 'fan') !== FALSE) {
                    $_unit = 'tr/min';
                    $_max = 5000;
                    $_min = '0';
                    $icon = $iconfan;
                    $link_logicalId = 'fans';
                } else if ($boucle_num = 3) {
                    $_iconname = null;
                    $_type = 'binary';
                    $_id = $Equipement['slot'];
                    $_name = 'Slot ' . $Equipement['slot'] . ' - ' . $Equipement['type'];
                    $IsVisible = '0';
                    $_value = $Equipement['present'];
                    $link_logicalId = 'expansions';
                }
                log::add('Freebox_OS', 'debug', '│ Name : ' . $_name . ' -- id : ' . $_id . ' -- value : ' . $_value . ' -- unité : ' . $_unit . ' -- type : ' . $_type);
                if ($_name != '') {

                    $system->AddCommand($_name, $_id, 'info', $_type, $templatecore_V4 . 'line', $_unit, null, $IsVisible, 'default', $link_logicalId, 0, $icon, 0, $_min, $_max, $_order, 0, false, true, null, $_iconname);

                    $system->checkAndUpdateCmd($_id, $_value);
                    if ($Equipement['type'] == 'dsl_lte') {
                        // Début ajout 4G
                        $_4G = $system->AddCommand('Etat 4G ', '4GStatut', "info", 'binary', null . 'line', null, null, 0, '', '', '', '', 1, 'default', 'default', 32, '0', false, 'never', null, true);
                        $system->AddCommand('4G On', '4GOn', 'action', 'other', $Template4G, null, 'ENERGY_ON', 1, $_4G, '4GStatut', 0, $icon4Gon, 1, 'default', 'default', 33, '0', false, false, null, true);
                        $system->AddCommand('4G Off', '4GOff', 'action', 'other', $Template4G, null, 'ENERGY_OFF', 1, $_4G, '4GStatut', 0, $icon4Goff, 0, 'default', 'default', 34, '0', false, false, null, true);
                    }
                    $_order++;
                }
            }
            $boucle_num++;
        }
        log::add('Freebox_OS', 'debug', '└─────────');
    }

    private static function createEq_wifi($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Ajout des commandes : ' . $logicalinfo['wifiName']);
        $updateicon = false;
        if (version_compare(jeedom::version(), "4", "<")) {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V3 ');
            $TemplateWifiOnOFF = 'default';
            $iconWifiOn = 'fas fa-wifi';
            $iconWifiOff = 'fas fa-times';
            $iconWifiPlanningOn = 'fas fa-calendar-alt';
            $iconWifiPlanningOff = 'fas fa-calendar-times';
        } else {
            log::add('Freebox_OS', 'debug', '│ Application des Widgets ou Icônes pour le core V4');
            $TemplateWifiOnOFF = 'Freebox_OS::Wifi';
            $TemplateWifiPlanningOnOFF = 'Freebox_OS::Planning Wifi';
            $iconWifiOn = 'fas fa-wifi icon_green';
            $iconWifiOff = 'fas fa-times icon_red';
            $iconWifiPlanningOn = 'fas fa-calendar-alt icon_green';
            $iconWifiPlanningOff = 'fas fa-calendar-times icon_red';
        };
        $Wifi = Freebox_OS::AddEqLogic($logicalinfo['wifiName'], $logicalinfo['wifiID'], 'default', false, null, null, null, '*/5 * * * *');
        $StatusWifi = $Wifi->AddCommand('Etat wifi', 'wifiStatut', "info", 'binary', null, null, 'ENERGY_STATE', 0, '', '', '', '', 0, 'default', 'default', 1, 1, $updateicon, true);
        $Wifi->AddCommand('Wifi On', 'wifiOn', 'action', 'other', $TemplateWifiOnOFF, null, 'ENERGY_ON', 1, $StatusWifi, 'wifiStatut', 0, $iconWifiOn, 0, 'default', 'default', 4, '0', $updateicon, false);
        $Wifi->AddCommand('Wifi Off', 'wifiOff', 'action', 'other', $TemplateWifiOnOFF, null, 'ENERGY_OFF', 1, $StatusWifi, 'wifiStatut', 0, $iconWifiOff, 0, 'default', 'default', 5, '0', $updateicon, false);
        // Planification Wifi
        $PlanningWifi = $Wifi->AddCommand('Etat Planning', 'wifiPlanning', "info", 'binary', null, null, null, 0, '', '', '', '', 0, 'default', 'default', '0', 2, $updateicon, true);
        $Wifi->AddCommand('Wifi Planning On', 'wifiPlanningOn', 'action', 'other', $TemplateWifiPlanningOnOFF, null, 'ENERGY_ON', 1, $PlanningWifi, 'wifiPlanning', 0, $iconWifiPlanningOn, 0, 'default', 'default', 6, '0', $updateicon, false);
        $Wifi->AddCommand('Wifi Planning Off', 'wifiPlanningOff', 'action', 'other', $TemplateWifiPlanningOnOFF, null, 'ENERGY_OFF', 1, $PlanningWifi, 'wifiPlanning', 0, $iconWifiPlanningOff, 0, 'default', 'default', 7, '0', $updateicon, false);
        log::add('Freebox_OS', 'debug', '└─────────');
    }

    private static function createEq_wifi_wps($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Création équipement : ' . $logicalinfo['wifiWPSName']);
        $Free_API = new Free_API();
        $Free_API->universal_get('wifi', null, null, 'wps/config');
        log::add('Freebox_OS', 'debug', '└─────────');
    }
    private static function createEq_mac_filter($logicalinfo, $templatecore_V4)
    {
        log::add('Freebox_OS', 'debug', '┌───────── Création équipement : ' . $logicalinfo['wifiWPSName']);
        $Free_API = new Free_API();
        $Free_API->universal_get('wifi', null, null, 'mac_filter');
        log::add('Freebox_OS', 'debug', '└─────────');
    }
}
