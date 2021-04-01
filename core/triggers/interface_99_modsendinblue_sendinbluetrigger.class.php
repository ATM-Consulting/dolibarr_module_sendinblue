<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		core/triggers/interface_99_modMyodule_sendinbluetrigger.class.php
 * 	\ingroup	sendinblue
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modMymodule_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */

/**
 * Trigger class
 */
class Interfacesendinbluetrigger extends DolibarrTriggers
{
	public $family = 'sendinblue';
	public $description = "Triggers of this module are empty functions. They have no effect. They are provided for tutorial purpose only.";

	/**
	 * Version of the trigger
	 * @var string
	 */
	public $version = self::VERSION_DOLIBARR;

	/**
	 * @var string Image of the trigger
	 */
	public $picto = 'sendinblue@sendinblue';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->sendinblue->enabled)) return 0;     // Module not active, we do nothing

		if (in_array($action, array('CONTACT_ENABLEDISABLE', 'CONTACT_DELETE'))) {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			define('INC_FROM_DOLIBARR', true);
			dol_include_once('/sendinblue/class/dolsendinblue.class.php');
			$sendinblue = new DolSendinBlue($this->db);
			$sendinblue->delete_user(array('email' => $object->email));
		}

		if ($action == 'CONTACT_MODIFY') {
			if (strcmp($object->email, $object->oldcopy->email) != 0) {
				//TODO Faire pop un message d'alerte pour prévenir que l'adresse antérieure est abonnée
			}
		}

		if ($action == 'MAILING_DELETE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			dol_include_once('/sendinblue/class/dolsendinblue.class.php');
			$sendinblue = new DolSendinBlue($this->db);
			$result = $sendinblue->fetch_by_mailing($object->id);
			if ($result > 0) $result = $sendinblue->delete($user);
			if ($result < 0) {
				$this->error = $sendinblue->error;
				$this->errors = $sendinblue->errors;
				return -1;
			}
		}

		return 0;
	}
}