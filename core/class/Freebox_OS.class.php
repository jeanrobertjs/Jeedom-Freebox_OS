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
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../core/php/Freebox_OS.inc.php';

class Freebox_OS extends eqLogic
{
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */
	public static function deadCmd()
	{
		$return = array();
		foreach (eqLogic::byType('Freebox_OS') as $Freebox_OS) {
			foreach ($Freebox_OS->getCmd() as $cmd) {
				preg_match_all("/#([0-9]*)#/", $cmd->getConfiguration('infoName', ''), $matches);
				foreach ($matches[1] as $cmd_id) {
					if (!cmd::byId(str_replace('#', '', $cmd_id))) {
						$return[] = array('detail' => __('Freebox_OS', __FILE__) . ' ' . $Freebox_OS->getHumanName() . ' ' . __('dans la commande', __FILE__) . ' ' . $cmd->getName(), 'help' => __('Nom Information', __FILE__), 'who' => '#' . $cmd_id . '#');
					}
				}
				preg_match_all("/#([0-9]*)#/", $cmd->getConfiguration('calcul', ''), $matches);
				foreach ($matches[1] as $cmd_id) {
					if (!cmd::byId(str_replace('#', '', $cmd_id))) {
						$return[] = array('detail' => __('Freebox_OS', __FILE__) . ' ' . $Freebox_OS->getHumanName() . ' ' . __('dans la commande', __FILE__) . ' ' . $cmd->getName(), 'help' => __('Calcul', __FILE__), 'who' => '#' . $cmd_id . '#');
					}
				}
			}
		}
		return $return;
	}
	public static $_widgetPossibility = array('custom' => true);
	public static function cron()
	{
		$eqLogics = eqLogic::byType('Freebox_OS');
		$deamon_info = self::deamon_info();
		if ($deamon_info['state'] != 'ok' && config::byKey('deamonAutoMode', 'Freebox_OS') != 0) {
			log::add('Freebox_OS', 'debug', '================= Etat du Démon ' . $deamon_info['state'] . ' ==================');
			Freebox_OS::deamon_start();
			$Free_API = new Free_API();
			$Free_API->getFreeboxOpenSession();
			$deamon_info = self::deamon_info();
			log::add('Freebox_OS', 'debug', '================= Redémarrage du démon : ' . $deamon_info['state'] . ' ==================');
		}
		foreach ($eqLogics as $eqLogic) {
			$autorefresh = $eqLogic->getConfiguration('autorefresh', '*/5 * * * *');
			try {
				$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);

				if ($c->isDue() && $deamon_info['state'] == 'ok') {
					if ($eqLogic->getIsEnable()) {
						if (($eqLogic->getConfiguration('eq_group') == 'nodes' || $eqLogic->getConfiguration('eq_group') == 'tiles') && (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK' && config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == 1)) {
						} else {
							log::add('Freebox_OS', 'debug', '================= CRON pour l\'actualisation de : ' . $eqLogic->getName() . ' ==================');
							Free_Refresh::RefreshInformation($eqLogic->getId());
						}
					}
				}
				if ($deamon_info['state'] != 'ok' && config::byKey('deamonAutoMode', 'Freebox_OS') != 0) {
					log::add('Freebox_OS', 'debug', '================= PAS DE CRON pour d\'actualisation ' . $eqLogic->getName() . ' à cause du Démon : ' . $deamon_info['state'] . ' ==================');
				}
			} catch (Exception $exc) {
				//log::add('Freebox_OS', 'error', __('Expression cron non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $autorefresh . ' Ou problème dans le CRON');
			}
			if ($eqLogic->getLogicalId() == 'network' || $eqLogic->getLogicalId() == 'networkwifiguest' || $eqLogic->getLogicalId() == 'disk' || $eqLogic->getLogicalId() == 'homeadapters') {
				if ($eqLogic->getIsEnable()) {
					if ($deamon_info['state'] == 'ok') {
						Freebox_OS::cron_autorefresh_eqLogic($eqLogic, $deamon_info);
					}
				}
			}
		}
	}

	public static function cron_autorefresh_eqLogic($eqLogic, $deamon_info)
	{
		$_crondailyEq = null;
		$_crondailyTil = null;
		$autorefresh_eqLogic = $eqLogic->getConfiguration('autorefresh_eqLogic');
		try {
			if ($autorefresh_eqLogic != null) {
				switch ($eqLogic->getLogicalId()) {
					case 'network':
					case 'networkwifiguest':
						if (config::byKey('TYPE_FREEBOX_MODE', 'Freebox_OS') == 'router') {
							$_crondailyEq = $eqLogic->getLogicalId();
						}
						break;
					case 'disk':
						$_crondailyEq = $eqLogic->getLogicalId();
						break;
					case 'homeadapters':
						$_crondailyTil = 'homeadapters_SP';
						break;
				}
				if ($_crondailyEq != null or $_crondailyTil != null) {
					try {
						$cron = new Cron\CronExpression($autorefresh_eqLogic, new Cron\FieldFactory);
						if ($cron->isDue()) {
							log::add('Freebox_OS', 'debug', '================= CRON ACTUALISATION AJOUT NOUVELLE COMMANDE pour l\'équipement  : ' . $eqLogic->getName() . ' ==================');
							if ($_crondailyEq != null) {
								Free_CreateEq::createEq($_crondailyEq, false);
							}
							if ($_crondailyTil != null) {
								Free_CreateTil::createTil($_crondailyTil, false);
							}
							log::add('Freebox_OS', 'debug', '================= FIN CRON ACTUALISATION AJOUT NOUVELLE COMMANDE pour l\'équipement  : ' . $eqLogic->getName() . ' ==================');
						}
					} catch (Exception $e) {
						log::add('Freebox_OS', 'error', __('Expression Cron Actualisation Ajout nouvelle commande  non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $autorefresh_eqLogic);
					}
				}
				$_crondailyEq = null;
				$_crondailyTil = null;
			}
		} catch (Exception $exc) {
			log::add('Freebox_OS', 'error', __('Erreur Cron Actualisation Ajout nouvelle commande ', __FILE__) . $eqLogic->getHumanName());
		}
	}

	public static function deamon_info()
	{
		$return = array();
		$return['log'] = 'Freebox_OS';
		if (trim(config::byKey('FREEBOX_SERVER_IP', 'Freebox_OS')) != '' && config::byKey('FREEBOX_SERVER_APP_TOKEN', 'Freebox_OS') != '' && trim(config::byKey('FREEBOX_SERVER_APP_ID', 'Freebox_OS')) != '') {
			$return['launchable'] = 'ok';
		} else {
			$return['launchable'] = 'nok';
		}
		$return['state'] = 'ok';
		$session_token = cache::byKey('Freebox_OS::SessionToken');
		if (!is_object($session_token) || $session_token->getValue('') == '') {
			$return['state'] = 'nok';
			return $return;
		}
		$cron = cron::byClassAndFunction('Freebox_OS', 'RefreshToken');
		if (!is_object($cron)) {
			$return['state'] = 'nok';
			return $return;
		}
		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
		if (!is_object($cron)) {
			$return['state'] = 'nok';
			return $return;
		}
		return $return;
	}
	public static function deamon_start($_debug = false)
	{
		//log::remove('Freebox_OS');
		$deamon_info = self::deamon_info();
		self::deamon_stop();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		if ($deamon_info['state'] == 'ok') return;
		$cron = cron::byClassAndFunction('Freebox_OS', 'RefreshToken');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron RefreshToken introuvable', __FILE__));
		}
		if (is_object($cron)) {
			$cron->run();
		}

		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron FreeboxPUT introuvable', __FILE__));
		}
		if (is_object($cron)) {
			$cron->run();
		}
		if (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK') {
			if (config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == 1) {
				$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxGET');
				if (!is_object($cron)) {
					throw new Exception(__('Tache cron FreeboxGET introuvable', __FILE__));
				}
				if (is_object($cron)) {
					$cron->run();
				}
			}
		}
	}
	public static function deamon_stop()
	{
		$cron = cron::byClassAndFunction('Freebox_OS', 'RefreshToken');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron RefreshToken introuvable', __FILE__));
		}
		if (is_object($cron)) {
			$cron->halt();
		}
		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron FreeboxPUT introuvable', __FILE__));
		}
		if (is_object($cron)) {
			$cron->stop();
			sleep(1);
			if ($cron->running()) {
				$cron->halt();
			}
		}
		cache::delete("Freebox_OS::actionlist");

		if (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK') {
			if (config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == '1') {
				$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxGET');
				if (!is_object($cron)) {
					throw new Exception(__('Tache cron FreeboxGET introuvable', __FILE__));
				}
				if (is_object($cron)) {
					$cron->stop();
					sleep(1);
					if ($cron->running()) {
						$cron->halt();
					}
				}
			}
		}

		$Free_API = new Free_API();
		$Free_API->close_session();
	}
	public static function FreeboxPUT()
	{
		$action = cache::byKey("Freebox_OS::actionlist")->getValue();
		if (!is_array($action)) {
			//log::add('Freebox_OS', 'debug', '[testNotArray]' . $action);
			return;
		}
		if (!isset($action[0])) {
			return;
		}
		if ($action[0] == '') {
			return;
		}
		log::add('Freebox_OS', 'debug', '********************  Action pour l\'action : ' . $action[0]['Name'] . '(' . $action[0]['LogicalId'] . ') ' . 'de l\'équipement : ' . $action[0]['NameEqLogic']);
		Free_Update::UpdateAction($action[0]['LogicalId'], $action[0]['SubType'], $action[0]['Name'], $action[0]['Value'], $action[0]['Config'], $action[0]['EqLogic'], $action[0]['Options'], $action[0]['This']);
		$action = cache::byKey("Freebox_OS::actionlist")->getValue();
		array_shift($action);
		cache::set("Freebox_OS::actionlist", $action);
	}
	public static function FreeboxGET()
	{
		try {
			//log::add('Freebox_OS', 'debug', '********************  CRON UPDATE TILES/NODE ******************** ');
			//if (config::byKey('FREEBOX_TILES_CmdbyCmd', 'Freebox_OS') == 1) {
			//	Free_Refresh::RefreshInformation('Tiles_global_CmdbyCmd');
			//} else {
			Free_Refresh::RefreshInformation('Tiles_global');
			//	}
			sleep(15);
		} catch (Exception $exc) {
			log::add('Freebox_OS', 'error', __('********************  ERREUR CRON UPDATE TILES/NODE ', __FILE__));
		}
	}
	public static function resetConfig()
	{
		config::save('FREEBOX_SERVER_IP', "mafreebox.freebox.fr", 'Freebox_OS');
		//config::save('FREEBOX_SERVER_APP_VERSION', "v5.0.0", 'Freebox_OS');
		config::save('FREEBOX_SERVER_APP_NAME', "Plugin Freebox OS", 'Freebox_OS');
		config::save('FREEBOX_SERVER_APP_ID', "plugin.freebox.jeedom", 'Freebox_OS');
		config::save('FREEBOX_SERVER_DEVICE_NAME', config::byKey("name"), 'Freebox_OS');
		config::save('FREEBOX_API', "v9", 'Freebox_OS');
		config::save('FREEBOX_REBOOT_DEAMON', FALSE, 'Freebox_OS');
	}

	public static function EqLogic_ID($Name, $_logicalId)
	{
		$EqLogic = self::byLogicalId($_logicalId, 'Freebox_OS');
		log::add('Freebox_OS', 'debug', '>> ================ >> Name : ' . $Name . ' -- LogicalID : ' . $_logicalId);
		return $EqLogic;
	}
	public static function AddEqLogic($Name, $_logicalId, $category = null, $tiles, $eq_type, $eq_action = null, $logicalID_equip = null, $_autorefresh = null, $_Room = null, $Player = null, $type2 = null, $eq_group = 'system', $type_save = false)
	{
		$EqLogic = self::byLogicalId($_logicalId, 'Freebox_OS');
		log::add('Freebox_OS', 'debug', '>> ================ >> Name : ' . $Name . ' -- LogicalID : ' . $_logicalId . ' -- catégorie : ' . $category . ' -- Equipement Type : ' . $eq_type . ' -- Logical ID Equip : ' . $logicalID_equip . ' -- Cron : ' . $_autorefresh . ' -- Objet : ' . $_Room);
		if (!is_object($EqLogic)) {
			$EqLogic = new Freebox_OS();
			$EqLogic->setLogicalId($_logicalId);
			if ($_Room == null) {
				$defaultRoom = intval(config::byKey('defaultParentObject', "Freebox_OS", '', true));
			} else {
				// Fonction NON désactiver A TRAITER => Pose des soucis chez certain utilisateurs (Voir Fil d'actualité du Plugin)
				$defaultRoom = intval($_Room);
			}

			if ($defaultRoom != null) {
				$EqLogic->setObject_id($defaultRoom);
			}
			$EqLogic->setEqType_name('Freebox_OS');
			$EqLogic->setIsEnable(1);
			$EqLogic->setIsVisible(0);
			$EqLogic->setName($Name);
			if ($category != null) {
				$EqLogic->setcategory($category, 1);
			}

			if ($_autorefresh != null) {
				$EqLogic->setConfiguration('autorefresh', $_autorefresh);
			} else {
				$EqLogic->setConfiguration('autorefresh', '*/5 * * * *');
			}
			if ($tiles == true) {
				$EqLogic->setConfiguration('type', $eq_type);
				$EqLogic->setConfiguration('action', $eq_action);
				if ($EqLogic->getConfiguration('type', $eq_type) == 'parental' || $EqLogic->getConfiguration('type', $eq_type) == 'player') {
					$EqLogic->setConfiguration('action', $logicalID_equip);
				}
				if ($Player != null) {
					$EqLogic->setConfiguration('player', $Player);
				}
			}
			if ($eq_group != null) {
				$EqLogic->setConfiguration('eq_group', $eq_group);
			}

			if ($_logicalId == 'network' || $_logicalId == 'networkwifiguest' || $_logicalId == 'disk' || $_logicalId == 'homeadapters') {
				$EqLogic->setConfiguration('autorefresh_eqLogic', '2 1 * * *');
			}
			try {
				$EqLogic->save();
			} catch (Exception $e) {
				$EqLogic->setName($EqLogic->getName() . ' doublon ' . rand(0, 9999));
				$EqLogic->save();
			}
		}
		$EqLogic->setConfiguration('logicalID', $_logicalId);
		if ($_autorefresh == null) {
			if ($tiles == true && ($EqLogic->getConfiguration('type', $eq_type) != 'parental' && $EqLogic->getConfiguration('type', $eq_type) != 'player' && $EqLogic->getConfiguration('type', $eq_type) != 'alarm_remote')) {
				$EqLogic->setConfiguration('autorefresh', '* * * * *');
			} elseif ($tiles == true && ($EqLogic->getConfiguration('type', $eq_type) == 'alarm_remote')) {
				$EqLogic->setConfiguration('autorefresh', '*/5 * * * *');
			} elseif ($EqLogic->getLogicalId() == 'disk') {
				$EqLogic->setConfiguration('autorefresh', '1 * * * *');
			} else {
				$EqLogic->setConfiguration('autorefresh', '*/5 * * * *');
			}
		}
		if ($tiles == true) {
			if ($eq_type != 'pir' && $eq_type != 'kfb' && $eq_type != 'dws' && $eq_type != 'alarm' && $eq_type != 'basic_shutter' && $eq_type != 'shutter'  && $eq_type != 'opener' && $eq_type != 'plug') {
				$EqLogic->setConfiguration('type', $eq_type);
			} else {
				$EqLogic->setConfiguration('type2', $eq_type);
				if ($eq_type === 'pir') {
					$EqLogic->setConfiguration('info', 'mouv_sensor');
				}
			}
			if ($eq_action != null) {
				$EqLogic->setConfiguration('action', $eq_action);
			}
			if ($EqLogic->getConfiguration('type', $eq_type) == 'parental' || $EqLogic->getConfiguration('type', $eq_type) == 'player' || $EqLogic->getConfiguration('type', $eq_type) == 'VM') {
				$EqLogic->setConfiguration('action', $logicalID_equip);
			}
		}
		if ($type_save == false) {
			$EqLogic->save();
		} else {
			$EqLogic->save(true);
		}

		return $EqLogic;
	}

	public static function templateWidget()
	{
		return Free_Template::getTemplate();
	}

	public function AddCommand($Name, $_logicalId, $Type = 'info', $SubType = 'binary', $Template = null, $unite = null, $generic_type = null, $IsVisible = 1, $link_I = 'default', $link_logicalId,  $invertBinary_display = '0', $icon, $forceLineB = '0', $valuemin = 'default', $valuemax = 'default', $_order = null, $IsHistorized = '0', $forceIcone_widget = false, $repeatevent = 'never', $_logicalId_slider = null, $_iconname = null, $_home_config_eq = null, $_calculValueOffset = null, $_historizeRound = null, $_noiconname = null, $invertSlide = null, $request = null, $_eq_type_home = null, $forceLineA = null, $listValue = null, $updatenetwork = false, $name_connectivity_type = null, $listValue_Update = null, $_display_parameters = null, $invertBinary_config = '0')
	{
		if ($listValue_Update == true) {
			log::add('Freebox_OS', 'debug', '│ Name : ' . $Name . ' -- LogicalID : ' . $_logicalId . ' -- Mise à jour de la liste de choix avec les valeurs : ' . $listValue);
		} else if ($updatenetwork != false) {
		} else {
			log::add('Freebox_OS', 'debug', '│ Name : ' . $Name . ' -- Type : ' . $Type . ' -- LogicalID : ' . $_logicalId . ' -- Template Widget / Ligne : ' . $Template . '/' . $forceLineB . '-- Type de générique : ' . $generic_type . ' -- Inverser Affichage : ' . $invertBinary_display . ' -- Inverser Valeur Binaire : ' . $invertBinary_config . ' -- Icône : ' . $icon . ' -- Min/Max : ' . $valuemin . '/' . $valuemax . ' -- Calcul/Arrondi : ' . $_calculValueOffset . '/' . $_historizeRound . ' -- Ordre : ' . $_order);
		}
		$Cmd = $this->getCmd($Type, $_logicalId);
		if (!is_object($Cmd)) {
			$VerifName = $Name;
			$Cmd = new Freebox_OSCmd();
			$Cmd->setId(null);
			$Cmd->setLogicalId($_logicalId);
			$Cmd->setEqLogic_id($this->getId());
			$count = 0;
			if ($name_connectivity_type != null) {
				if (is_object(cmd::byEqLogicIdCmdName($this->getId(), $VerifName))) {
					$VerifName = $VerifName . ' (' . $name_connectivity_type . ')';
				}
				if (is_object(cmd::byEqLogicIdCmdName($this->getId(), $VerifName))) {
					$VerifName = $VerifName . ' (' . $name_connectivity_type . ' - ' . $_logicalId . ')';
				}
			}
			while (is_object(cmd::byEqLogicIdCmdName($this->getId(), $VerifName))) {
				$count++;
				$VerifName = $Name . '(' . $count . ')';
			}
			$Cmd->setName($VerifName);

			$Cmd->setType($Type);
			$Cmd->setSubType($SubType);
			$Cmd->save();

			if ($Template != null) {
				$Cmd->setTemplate('dashboard', $Template);
				$Cmd->setTemplate('mobile', $Template);
			}
			if ($unite != null && $SubType == 'numeric') {
				$Cmd->setUnite($unite);
			}
			$Cmd->setIsVisible($IsVisible);
			$Cmd->setIsHistorized($IsHistorized);
			if ($invertBinary_display != null && $SubType == 'binary') {
				$Cmd->setdisplay('invertBinary', 1);
			}
			if ($invertSlide != null) {
				if ($Type == 'action') {
					$Cmd->setConfiguration('invertslide', 1);
				} else {
					$Cmd->setDisplay('invertBinary', 1);
				}
			}
			if ($icon != null) {
				$Cmd->setdisplay('icon', '<i class="' . $icon . '"></i>');
			}
			if ($forceLineB != null) {
				$Cmd->setdisplay('forceReturnLineBefore', 1);
			}
			if ($forceLineA != null) {
				$Cmd->setdisplay('forceReturnLineAfter', 1);
			}
			if ($_iconname != null) {
				$Cmd->setdisplay('showIconAndNamedashboard', 1);
				$Cmd->setdisplay('showIconAndNamemobile', 1);
				//$Cmd->setdisplay('title_disable', true);
			}
			if ($_display_parameters != null) {
				$Cmd->setdisplay('parameters', $_display_parameters);
				$Cmd->save();
			}
			if ($_noiconname != null) {
				$Cmd->setdisplay('showNameOndashboard', 0);
				$Cmd->setdisplay('showNameOnmobile', 0);
			}
			if ($_calculValueOffset != null) {
				$Cmd->setConfiguration('calculValueOffset', $_calculValueOffset);
			}
			if ($_historizeRound != null) {
				$Cmd->setConfiguration('historizeRound', $_historizeRound);
			}

			if ($request != null) {
				$Cmd->setConfiguration('request', $request);
			}

			$Cmd->save();
			if ($_order != null) {
				$Cmd->setOrder($_order);
			}
		}

		if ($_home_config_eq != null) { // Compatibilité Homebridge
			if ($_home_config_eq == 'SetModeAbsent') {
				$this->setConfiguration($_home_config_eq, $Cmd->getId() . "|" . $Name);
				$this->setConfiguration('SetModePresent', "NOT");
				$this->setConfiguration('ModeAbsent', $Name);
				log::add('Freebox_OS', 'debug', '│ Paramétrage du Mode Homebridge Set Mode : SetModePresent => NOT' . ' -- Paramétrage du Mode Homebridge Set Mode : ' . $_home_config_eq);
			} else if ($_home_config_eq == 'SetModeNuit') {
				$this->setConfiguration($_home_config_eq, $Cmd->getId() . "|" . $Name);
				$this->setConfiguration('ModeNuit', $Name);
				log::add('Freebox_OS', 'debug', '│ Paramétrage du Mode Homebridge Set Mode : ' . $_home_config_eq);
			} else if ($_home_config_eq == 'mouv_sensor') {
				$this->setConfiguration('info', $_home_config_eq);
				log::add('Freebox_OS', 'debug', '│ Paramétrage : ' . $_home_config_eq);
				if ($invertBinary_display != '0' && $SubType == 'binary') {
					$Cmd->setdisplay('invertBinary', 1);
				} else {
					$Cmd->setdisplay('invertBinary', 0);
				}
				if ($invertBinary_config != '0' && $SubType == 'binary') {
					$Cmd->setConfiguration('invertBinary', 1);
				}
				$Cmd->setConfiguration('info', $_home_config_eq);
			}
		}
		if ($_eq_type_home != null) { // Node
			$Cmd->setConfiguration('TypeNode', $_eq_type_home);
		}
		$this->save(true);
		if ($generic_type != null) {
			$Cmd->setGeneric_type($generic_type);
		}
		if ($_logicalId_slider != null) { // logical Id spécial Slider
			$Cmd->setConfiguration('logicalId_slider', $link_I);
		}
		if ($Type == 'info') {
			if ($repeatevent === true || $repeatevent === 'never') {
				//log::add('Freebox_OS', 'debug', '│ ====================> NE PAS Répéter les valeurs identiques pour l\'info avec le nom : ' . $Name . ' (' . $repeatevent . ')');
				$Cmd->setConfiguration('repeatEventManagement', 'never');
			} else {
				$Cmd->setConfiguration('repeatEventManagement', 'always');
				//log::add('Freebox_OS', 'debug', '│ ====================> Répéter les valeurs identiques pour l\'info avec le nom : ' . $Name . ' (' . $repeatevent . ')');
			}
		}

		if ($valuemin != 'default') {
			$Cmd->setConfiguration('minValue', $valuemin);
		}
		if ($valuemax != 'default') {
			$Cmd->setConfiguration('maxValue', $valuemax);
		}
		if (is_object($link_I) && $Type == 'action') {
			$Cmd->setValue($link_I->getId());
		}
		if ($link_logicalId != 'default') {
			$Cmd->setConfiguration('logicalId', $link_logicalId);
		}

		// Mise à jour des noms de la commande pour le Network
		if ($updatenetwork != false) {
			if ($updatenetwork['updatename'] == true) {
				if ($Name != $Cmd->getName()) {
					log::add('Freebox_OS', 'debug', '│===========> Nom différent sur la Freebox : ' . $Name . ' -- Nom de la commande Jeedom : ' . $Cmd->getName());
					if ($name_connectivity_type != 'Wifi Ethernet ?') {
						$Name_verif = $Name . ' (' . $name_connectivity_type . ')';
					} else {
						$Name_verif = $Name;
					}
					$Name_wifi = $Name . ' (Wifi)';
					$Name_ethernet = $Name . ' (Ethernet)';
					if ($Name_verif == $Cmd->getName() || $Name_wifi == $Cmd->getName() || $Name_ethernet == $Cmd->getName()) {
					} else {
						if ($name_connectivity_type != null) {
							if (is_object(cmd::byEqLogicIdCmdName($this->getId(), $Name))) {
								$Name = $Name_verif;
							}
						}
						if (is_object(cmd::byEqLogicIdCmdName($this->getId(), $Name_verif))) {
							$Name_verif = $Name_verif . ' - (' . $_logicalId . ')';
						}
						if ($Name_verif != $Cmd->getName()) {
							$Cmd->setName($Name_verif);
						}
					}
				}
			}
			if ($updatenetwork['UpdateVisible'] == true) {
				if ($updatenetwork['IsVisible_option'] == 0) {
					//log::add('Freebox_OS', 'debug', '│===============================> PAS VISIBLE : ' . $updatenetwork['IsVisible_option']);
					$Cmd->setIsVisible(0);
					$Cmd->save();
				} else {
					$Cmd->setIsVisible(1);
					$Cmd->save();
				}
				//log::add('Freebox_OS', 'debug', '│===============================> TEST 2000: ' . $updatenetwork['IsVisible_option']);
			}
			$Cmd->setConfiguration('host_type', $updatenetwork['host_type']);
			if ($repeatevent == $updatenetwork['repeatevent'] && $Type == 'info') {
				$Cmd->setConfiguration('repeatEventManagement', 'never');
			}
			$Cmd->setConfiguration('IPV4', $updatenetwork['IPV4']);
			$Cmd->setConfiguration('IPV6', $updatenetwork['IPV6']);
			$Cmd->setConfiguration('mac_address', $updatenetwork['mac_address']);
			if ($updatenetwork['order'] != null) {
				$Cmd->setOrder($updatenetwork['order']);
			}
		}
		if ($forceIcone_widget == true) {
			if ($icon != null) {
				$Cmd->setdisplay('icon', '<i class="' . $icon . '"></i>');
			}
			if ($Template != null) {
				$Cmd->setTemplate('dashboard', $Template);
				$Cmd->setTemplate('mobile', $Template);
			}
			$Cmd->setIsVisible($IsVisible);

			if ($forceLineB != null) {
				$Cmd->setdisplay('forceReturnLineBefore', 1);
			}
			if ($forceLineA != null) {
				$Cmd->setdisplay('forceReturnLineAfter', 1);
			}

			if ($_iconname != null) {
				$Cmd->setdisplay('showIconAndNamedashboard', 1);
			}
		}
		if ($listValue != null) {
			$Cmd->setConfiguration('listValue', $listValue);
		}

		$Cmd->save();

		// Création de la commande refresh
		$createRefreshCmd  = true;
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = cmd::byEqLogicIdCmdName($this->getId(), __('Rafraichir', __FILE__));
			if (is_object($refresh)) {
				$createRefreshCmd = false;
			}
		}
		if ($createRefreshCmd) {
			if (!is_object($refresh)) {
				$refresh = new Freebox_OSCmd();
				$refresh->setLogicalId('refresh');
				$refresh->setIsVisible(1);
				$refresh->setName(__('Rafraichir', __FILE__));
			}
			$refresh->setType('action');
			$refresh->setSubType('other');
			$refresh->setEqLogic_id($this->getId());
			$refresh->save();
		}
		return $Cmd;
	}
	/*     * *********************Méthodes d'instance************************* */
	public function preInsert()
	{
	}

	public function postInsert()
	{
	}

	public function preSave()
	{
		switch ($this->getLogicalId()) {
			case 'AirPlay':
				$Free_API = new Free_API();
				$parametre["enabled"] = $this->getIsEnable();
				$parametre["password"] = $this->getConfiguration('password');
				$Free_API->airmedia('config', $parametre, null);
				break;
		}
	}

	public function postSave()
	{
		if ($this->getConfiguration('type') == 'alarm_control') {
			log::add('Freebox_OS', 'debug', '******************** Mise à jour des paramètrages spécifiques pour Homebridge : ' . $this->getConfiguration('type') . ' **************************************** ');
			foreach ($this->getCmd('action') as $Cmd) {
				if (is_object($Cmd)) {
					switch ($Cmd->getLogicalId()) {
						case "1":
							$_home_config_eq = 'SetModeAbsent';
							$_home_mode = 'ModeAbsent';
							break;
						case "2":
							$_home_config_eq = 'SetModeNuit';
							$_home_mode = 'ModeNuit';
							break;
					}
					if (isset($_home_config_eq)) {
						if ($_home_config_eq != null) {
							log::add('Freebox_OS', 'debug', '│──────────> Mode : ' . $_home_config_eq . '(Commande : ' . $Cmd->getName() . ')');
							$this->setConfiguration($_home_mode, $Cmd->getName());
							$this->save(true);
							$this->setConfiguration($_home_config_eq, $Cmd->getId() . "|" . $Cmd->getName());
							$this->save(true);
							if ($_home_config_eq == 'SetModeAbsent') {
								$this->setConfiguration('SetModePresent', "NOT");
							} else {
								$this->setConfiguration($_home_config_eq, $Cmd->getId() . "|" . $Cmd->getName());
							}

							$_home_config_eq = null;
						}
					}
				}
			}
			log::add('Freebox_OS', 'debug', '**************************************************************************************************** ');
		}
		if ($this->getIsEnable()) {
			if (($this->getConfiguration('eq_group') == 'nodes' || $this->getConfiguration('eq_group') == 'tiles') && (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK' && config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == 1)) {
			} else {
				Free_Refresh::RefreshInformation($this->getId());
			}
		}

		$createRefreshCmd = true;
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = cmd::byEqLogicIdCmdName($this->getId(), __('Rafraichir', __FILE__));
			if (is_object($refresh)) {
				$createRefreshCmd = false;
			}
		}
		if ($createRefreshCmd) {
			if (!is_object($refresh)) {
				$refresh = new Freebox_OSCmd();
				$refresh->setLogicalId('refresh');
				$refresh->setIsVisible(1);
				$refresh->setName(__('Rafraichir', __FILE__));
			}
			$refresh->setType('action');
			$refresh->setSubType('other');
			$refresh->setEqLogic_id($this->getId());
			$refresh->save();
		}
	}

	public function preUpdate()
	{
		if (!$this->getIsEnable()) return;
		if (config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == 1 && $this->getConfiguration('eq_group') == 'tiles') {
			log::add('Freebox_OS', 'debug', '================= CRON : Pas de vérification car Cron global titles actif');
		} else {
			if ($this->getConfiguration('autorefresh') == '') {
				log::add('Freebox_OS', 'error', '================= CRON : Temps de rafraichissement est vide pour l\'équipement : ' . $this->getName() . ' ' . $this->getConfiguration('autorefresh'));
				throw new Exception(__('Le champ "Temps de rafraichissement (cron)" ne peut être vide : ' . $this->getName(), __FILE__));
			}
		}
	}

	public function postUpdate()
	{
	}

	public function preRemove()
	{
	}

	public function postRemove()
	{
	}
	public static function getConfigForCommunity()
	{
		$box = "Box [" . config::byKey('TYPE_FREEBOX', 'Freebox_OS') . ' / ' . $box_name = config::byKey('TYPE_FREEBOX_NAME', 'Freebox_OS') . ']';
		$box_mode = "Mode [" . config::byKey('TYPE_FREEBOX_MODE', 'Freebox_OS') . ']';
		$IP = "IP Box [" . config::byKey('FREEBOX_SERVER_IP', 'Freebox_OS') . ']';
		$ligne1 = $box . ' ; ' . $box_mode . ' ; ' . $IP;

		$Name = "Nom [" . config::byKey('FREEBOX_SERVER_DEVICE_NAME', 'Freebox_OS') . ']';
		$API = "API [" . config::byKey('FREEBOX_API', 'Freebox_OS') . ']';
		$tiles = "Freebox Compatible Tiles [" . config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') . ']';
		$tiles_cron = "Cron Tiles [" . config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') . ']';
		$ligne2 = $Name . ' ; ' . $API . ' ; ' . $tiles . ' ; ' . $tiles_cron;

		$SEARCH_EQ = "EQ [" . config::byKey('SEARCH_EQ', 'Freebox_OS') . ']';
		$SEARCH_TILES = "Tiles [" . config::byKey('SEARCH_TILES', 'Freebox_OS') . ']';
		$SEARCH_PARENTAL = "Parental [" . config::byKey('SEARCH_PARENTAL', 'Freebox_OS') . ']';
		$ligne3 = 'Scans : ' . $SEARCH_EQ . ' ; ' . $SEARCH_TILES . ' ; ' . $SEARCH_PARENTAL;

		$FreeboxInfo = $ligne1 . '<br>' . $ligne2 . '<br>' . $ligne3;
		return $FreeboxInfo;
	}

	/*     * **********************Getteur Setteur*************************** */

	public static function RefreshToken()
	{
		log::add('Freebox_OS', 'debug', '=================   REFRESH TOKEN    ==================');
		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
		if (is_object($cron)) {
			log::add('Freebox_OS', 'debug', '>───────── ARRET CRON FreeboxPUT');
			$cron->stop();
		}
		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxGET');
		if (is_object($cron)) {
			log::add('Freebox_OS', 'debug', '>───────── ARRET CRON FreeboxGET');
			$cron->stop();
		}
		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxAPI');
		if (is_object($cron)) {
			log::add('Freebox_OS', 'debug', '>───────── ARRET CRON FreeboxAPI');
			$cron->stop();
		}
		sleep(1);
		$Free_API = new Free_API();
		$Free_API->close_session();
		if ($Free_API->getFreeboxOpenSession() === false) {
			self::deamon_stop();
			log::add('Freebox_OS', 'debug', '[REFRESH TOKEN] : FALSE / ' . $Free_API->getFreeboxOpenSession());
		}
		sleep(1);
		$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxPUT');
		if (!is_object($cron)) {
			throw new Exception(__('Tache cron FreeboxPUT introuvable', __FILE__));
		} else {
			log::add('Freebox_OS', 'debug', '>───────── REDEMARRAGE CRON FreeboxPUT');
			$cron->run();
		}
		if (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK') {
			if (config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS') == 1) {
				$cron = cron::byClassAndFunction('Freebox_OS', 'FreeboxGET');
				if (!is_object($cron)) {
					throw new Exception(__('Tache cron FreeboxGET introuvable', __FILE__));
				} else {
					log::add('Freebox_OS', 'debug', '>───────── REDEMARRAGE CRON FreeboxGET');
					$cron->run();
				}
			}
		}
		log::add('Freebox_OS', 'debug', '================= FIN REFRESH TOKEN  ==================');
	}
	public static function getlogicalinfo()
	{
		return array(
			'4GID' => '4G',
			'4GName' => '4G',
			'airmediaID' => 'airmedia',
			'airmediaName' => 'Air Média',
			'connexionID' => 'connexion',
			'connexionName' => 'Freebox Débits',
			'diskID' => 'disk',
			'diskName' => 'Disque Dur',
			'downloadsID' => 'downloads',
			'downloadsName' => 'Téléchargements',
			'freeplugID' => 'freeplug',
			'freeplugName' => 'Freeplug',
			'homeadaptersID' => 'homeadapters',
			'homeadaptersName' => 'Home Adapters',
			'LCDID' => 'LCD',
			'LCDName' => 'Afficheur LCD',
			'managementID' => 'management',
			'managementName' => 'Gestion réseau',
			'networkID' => 'network',
			'networkName' => 'Appareils connectés',
			'netshareID' => 'netshare',
			'netshareName' => 'Partage Windows - Mac',
			'networkwifiguestID' => 'networkwifiguest',
			'networkwifiguestName' => 'Appareils connectés Wifi Invité',
			'notificationID' => 'notification',
			'notificationName' => 'Notification',
			'phoneID' => 'phone',
			'phoneName' => 'Téléphone',
			'playerID' => 'player',
			'playerName' => 'Player',
			'systemID' => 'system',
			'systemName' => 'Système',
			'VMID' => 'VM',
			'VMName' => 'VM',
			'wifiID' => 'wifi',
			'wifiName' => 'Wifi',
			'wifiguestID' => 'wifiguest',
			'wifiguestName' => 'Wifi Invité',
			'wifimmac_filter' => 'Wifi Filtrage Adresse Mac',
			'wifiWPSID' => 'wifiWPS',
			'wifiWPSName' => 'Wifi WPS',
			'wifiAPID' => 'wifiAP',
			'wifiAPName' => 'Wifi Access Points'
		);
	}
	public static function FreeboxAPI()
	{
		log::add('Freebox_OS', 'info', '================= DEBUT TEST VERSION API DE LA FREEBOX ==================');
		log::add('Freebox_OS', 'info', '================= Il est possible d\'avoir le message suivant dans les messages : API NON COMPATIBLE : Version d\'API inconnue ==================');
		$Free_API = new Free_API();
		$result = $Free_API->universal_get('universalAPI', null, null, 'api_version', true, true, true);
		log::add('Freebox_OS', 'info', '│──────────> Type Box : ' . $result['box_model_name']);
		log::add('Freebox_OS', 'info', '│──────────> API URL : ' . $result['api_base_url']);
		log::add('Freebox_OS', 'info', '│──────────> Port https : ' . $result['https_port']);
		log::add('Freebox_OS', 'info', '│──────────> Nom Box : ' . $result['device_name']);
		log::add('Freebox_OS', 'info', '│──────────> Https disponible : ' . $result['https_available']);
		log::add('Freebox_OS', 'info', '│──────────> Modele de box : ' . $result['box_model']);
		log::add('Freebox_OS', 'info', '│──────────> Type de box : ' . $result['device_type']);
		log::add('Freebox_OS', 'info', '│──────────> API domaine : ' . $result['api_domain']);
		log::add('Freebox_OS', 'info', '│──────────> API version : ' . $result['api_version']);
		$API_version = 'v'  . $result['api_version'];
		$API_version = strstr($API_version, '.', true);
		log::add('Freebox_OS', 'info', '│──────────> Version actuelle dans la base : ' . config::byKey('FREEBOX_API', 'Freebox_OS'));
		config::save('FREEBOX_API', $API_version, 'Freebox_OS');
		log::add('Freebox_OS', 'info', '│──────────> Nouvelle Version actuelle dans la base : ' . config::byKey('FREEBOX_API', 'Freebox_OS'));
		log::add('Freebox_OS', 'info', '================= FIN TEST VERSION API DE LA FREEBOX ==================');
		return $API_version;
	}
	public static function updateLogicalID($eq_version, $_update = false)
	{
		$eqLogics = eqLogic::byType('Freebox_OS');
		$logicalinfo = Freebox_OS::getlogicalinfo();
		if ($eq_version == 2) {
			if (config::byKey('TYPE_FREEBOX_TILES', 'Freebox_OS') == 'OK') {
				if (!is_object(config::byKey('FREEBOX_TILES_CRON', 'Freebox_OS'))) {
					config::save('FREEBOX_TILES_CRON', init(1), 'Freebox_OS');
					Free_CreateTil::createTil('SetSettingTiles');
				}
			}
		}
		foreach ($eqLogics as $eqLogic) {
			if ($eqLogic->getConfiguration('type') === 'alarm_control') {
				$type_eq = 'alarm_control';
			} else if ($eqLogic->getConfiguration('type') === 'camera') {
				$type_eq = 'camera';
			} else if ($eqLogic->getConfiguration('type') === 'freeplug') {
				$type_eq = 'freeplug';
			} else if ($eqLogic->getConfiguration('type') === 'parental') {
				$type_eq = 'parental_controls';
			} else if ($eqLogic->getConfiguration('type') === 'player') {
				$type_eq = 'player';
			} else if ($eqLogic->getConfiguration('type') === 'VM') {
				$type_eq = 'VM';
			} else {
				$type_eq = $eqLogic->getLogicalId();
			}
			if ($eqLogic->getConfiguration('VersionLogicalID', 0) == $eq_version) continue;

			log::add('Freebox_OS', 'debug', '│ Fonction updateLogicalID : Update eqLogic : ' . $eqLogic->getLogicalId() . ' - ' . $eqLogic->getName());
			switch ($type_eq) {
				case 'airmedia':
					$eqLogic->setLogicalId($logicalinfo['airmediaID']);
					//$eqLogic->setName($logicalinfo['airmediaName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'alarm_control':
					// Update spécifique pour l'alarme
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'tiles');
					//$eqLogic->save();
					break;
				case 'camera':
					// Update spécifique pour les caméras
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'nodes');
					break;
				case 'connexion':
					$eqLogic->setLogicalId($logicalinfo['connexionID']);
					//$eqLogic->setName($logicalinfo['connexionName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'disk':
					$eqLogic->setLogicalId($logicalinfo['diskID']);
					//$eqLogic->setName($logicalinfo['diskName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'downloads':
					$eqLogic->setLogicalId($logicalinfo['downloadsID']);
					//$eqLogic->setName($logicalinfo['downloadsName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'freeplug':
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'homeadapters':
					$eqLogic->setLogicalId($logicalinfo['homeadaptersID']);
					//$eqLogic->setName($logicalinfo['homeadaptersName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'tiles_SP');
					break;
				case 'parental_controls':
					//Pour les contrôles parentaux
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'parental_controls');
					break;
				case 'phone':
					$eqLogic->setLogicalId($logicalinfo['phoneID']);
					//$eqLogic->setName($logicalinfo['phoneName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'player':
					//Pour les players
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'management':
					$eqLogic->setLogicalId($logicalinfo['managementID']);
					//$eqLogic->setName($logicalinfo['networkName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'network':
					$eqLogic->setLogicalId($logicalinfo['networkID']);
					//$eqLogic->setName($logicalinfo['networkName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'netshare':
					$eqLogic->setLogicalId($logicalinfo['netshareID']);
					//$eqLogic->setName($logicalinfo['netshareName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'networkwifiguest':
					$eqLogic->setLogicalId($logicalinfo['networkwifiguestID']);
					//$eqLogic->setName($logicalinfo['networkwifiguestName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'LCD':
					$eqLogic->setLogicalId($logicalinfo['LCDID']);
					//$eqLogic->setName($logicalinfo['LCDName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'system':
					$eqLogic->setLogicalId($logicalinfo['systemID']);
					//$eqLogic->setName($logicalinfo['systemName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'VM':
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				case 'wifi':
					$eqLogic->setLogicalId($logicalinfo['wifiID']);
					//$eqLogic->setName($logicalinfo['wifiName']);
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					$eqLogic->setConfiguration('eq_group', 'system');
					break;
				default:
					$eqLogic->setConfiguration('eq_group', 'tiles');
					$eqLogic->setConfiguration('VersionLogicalID', $eq_version);
					break;
			}
			$eqLogic->save(true);
			log::add('Freebox_OS', 'debug', '│ Fonction passe en version en V' . $eq_version  . ' - Pour : ' . $eqLogic->getLogicalId() . ' - ' . $eqLogic->getName());
			//if (!$_update) $eqLogic->setName($eqName);

		}
	}
}

class Freebox_OSCmd extends cmd
{
	public function dontRemoveCmd()
	{
		if ($this->getLogicalId() == 'refresh') {
			return true;
		}
		return false;
	}
	public function execute($_options = array())
	{
		//log::add('Freebox_OS', 'debug', '********************  Action pour l\'action : ' . $this->getName());
		$array = cache::byKey("Freebox_OS::actionlist")->getValue();
		if (!is_array($array)) {
			$array = [];
		}
		$update = array(
			'This' => $this,
			'LogicalId' => $this->getLogicalId(),
			'SubType' => $this->getSubType(),
			'Name' => $this->getName(),
			'Value' => $this->getvalue(),
			'Config' => $this->getConfiguration('logicalId'),
			'EqLogic' => $this->getEqLogic(),
			'NameEqLogic' => $this->getEqLogic()->getName(),
			'Options' => $_options,
		);

		array_push($array, $update);
		cache::set("Freebox_OS::actionlist", $array);
		//Free_Update::UpdateAction($this->getLogicalId(), $this->getSubType(), $this->getName(), $this->getvalue(), $this->getConfiguration('logicalId'), $this->getEqLogic(), $_options, $this);
	}

	public function getWidgetTemplateCode($_version = 'dashboard', $_clean = true, $_widgetName = '')
	{
		$data = null;
		if ($_version != 'scenario') return parent::getWidgetTemplateCode($_version, $_clean, $_widgetName);
		list($Cmd, $arguments) = explode('?', $this->getConfiguration('request'), 2);
		if ($Cmd == 'wol')
			$data = getTemplate('core', 'scenario', 'cmd.WakeonLAN', 'Freebox_OS');
		if ($Cmd == 'add_del_mac')
			$data = getTemplate('core', 'scenario', 'cmd.mac_filter', 'Freebox_OS');
		if ($Cmd == 'add_del_dhcp')
			$data = getTemplate('core', 'scenario', 'cmd.dhcp', 'Freebox_OS');
		if ($Cmd == 'redir')
			$data = getTemplate('core', 'scenario', 'cmd.port_forwarding', 'Freebox_OS');
		if (!is_null($data)) {
			if (version_compare(jeedom::version(), '4.2.0', '>=')) {
				if (!is_array($data)) return array('template' => $data, 'isCoreWidget' => false);
			} else return $data;
		}
		return parent::getWidgetTemplateCode($_version, $_clean, $_widgetName);
	}
}
