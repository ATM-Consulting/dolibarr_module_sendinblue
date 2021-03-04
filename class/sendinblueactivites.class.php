<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014	   Florian Henry        <florian.henry@open-concept.pro>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file /sendinblue/class/sendinblueactivites.php
 * \ingroup sendinblue
 * \brief Page to define emailing targets link with sendinblue
 */

// Put here all includes required by your class file
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
// require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

/**
 * Put here description of your class
 */
class SendinBlueActivites extends CommonObject
{
	var $db; // !< To store db handler
	var $error; // !< To return error code (or message)
	var $errors = array(); // !< To return several error codes (or messages)
	var $element = 'sendinblueactivites'; // !< Id that identify managed objects
	var $table_element = 'sendinblue_activites'; // !< Name of table without prefix where object is stored
	var $id;
	var $entity;
	var $fk_mailing;
	var $sendinblue_id;
	var $email;
	var $activites;
	var $fk_user_author;
	var $datec = '';
	var $fk_user_mod;
	var $tms = '';
	var $lines = array();

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	function __construct($db) {
		$this->db = $db;
		return 1;
	}

	/**
	 * Create object into database
	 *
	 * @param User $user User that creates
	 * @param int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->entity))
			$this->entity = trim($this->entity);
		if (isset($this->fk_mailing))
			$this->fk_mailing = trim($this->fk_mailing);
		if (isset($this->sendinblue_id))
			$this->sendinblue_id = trim($this->sendinblue_id);
		if (isset($this->email))
			$this->email = trim($this->email);
		if (isset($this->fk_user_author))
			$this->fk_user_author = trim($this->fk_user_author);
		if (isset($this->fk_user_mod))
			$this->fk_user_mod = trim($this->fk_user_mod);

			// Check parameters
			// Put here code to add a control on parameters values
		if (is_array($this->activites)) {
			$activites = serialize($this->activites);
		} else {
			$activites = trim($this->activites);
		}

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "sendinblue_activites(";

		$sql .= "entity,";
		$sql .= "fk_mailing,";
		$sql .= "sendinblue_id,";
		$sql .= "email,";
		$sql .= "activites,";
		$sql .= "fk_user_author,";
		$sql .= "datec,";
		$sql .= "fk_user_mod";

		$sql .= ") VALUES (";

		$sql .= " " . $conf->entity . ",";
		$sql .= " " . (! isset($this->fk_mailing) ? 'NULL' : "'" . $this->fk_mailing . "'") . ",";
		$sql .= " " . (! isset($this->sendinblue_id) ? 'NULL' : "'" . $this->db->escape($this->sendinblue_id) . "'") . ",";
		$sql .= " " . (! isset($this->email) ? 'NULL' : "'" . $this->db->escape($this->email) . "'") . ",";
		$sql .= " " . (empty($activites) ? 'NULL' : "'" . $this->db->escape($activites) . "'") . ",";
		$sql .= " " . $user->id . ",";
		$sql .= " '" . $this->db->idate(dol_now()) . "',";
		$sql .= " " . $user->id;

		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "sendinblue_activites");

			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $id Id object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($id) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_mailing,";
		$sql .= " t.sendinblue_id,";
		$sql .= " t.email,";
		$sql .= " t.activites,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "sendinblue_activites as t";
		$sql .= " WHERE t.rowid = " . $id;

		dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->entity = $obj->entity;
				$this->fk_mailing = $obj->fk_mailing;
				$this->sendinblue_id = $obj->sendinblue_id;
				$this->email = $obj->email;
				$this->activites = unserialize($obj->activites);
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_by_mailing_email() {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_mailing,";
		$sql .= " t.sendinblue_id,";
		$sql .= " t.email,";
		$sql .= " t.activites,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "sendinblue_activites as t";

		$sql .= " WHERE t.email = '" . $this->db->escape($this->email) . "'";
		if (! empty($this->fk_mailing)) {
			$sql .= " AND t.fk_mailing=" . $this->fk_mailing;
		}

		dol_syslog(get_class($this) . "::fetch_by_mailing_email sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->entity = $obj->entity;
				$this->fk_mailing = $obj->fk_mailing;
				$this->sendinblue_id = $obj->sendinblue_id;
				$this->email = $obj->email;
				$this->activites = unserialize($obj->activites);
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_by_mailing_email " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function fetchEmailContactActivites($email = '') {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_mailing,";
		$sql .= " t.sendinblue_id,";
		$sql .= " t.email,";
		$sql .= " t.activites,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "sendinblue_activites as t";

		$sql .= " WHERE t.email = '" . $this->db->escape($email) . "'";
		$sql .= " ORDER BY t.datec DESC";

		dol_syslog(get_class($this) . "::fetchEmailContactActivites sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$this->lines = array();

				while ( $obj = $this->db->fetch_object($resql) ) {

					$line = new SendinBlueActivitesLineDb();

					$line->id = $obj->rowid;

					$line->entity = $obj->entity;
					$line->fk_mailing = $obj->fk_mailing;
					$line->sendinblue_id = $obj->sendinblue_id;
					$line->email = $obj->email;
					$line->activites = unserialize($obj->activites);
					$line->fk_user_author = $obj->fk_user_author;
					$line->datec = $this->db->jdate($obj->datec);
					$line->fk_user_mod = $obj->fk_user_mod;
					$line->tms = $this->db->jdate($obj->tms);

					$this->lines[] = $line;
				}
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetchEmailContactActivites " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function getEmailcontactActivitesClick($sortorder = 'ASC', $sortfield = 't.rowid', $limit = 0, $offset = 0, $filter = array()) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_mailing,";
		$sql .= " t.sendinblue_id,";
		$sql .= " t.email,";
		$sql .= " t.activites,";
		$sql .= " t.fk_user_author,";
		$sql .= " ml.date_creat as datec,";
		$sql .= " ml.date_valid,";
		$sql .= " t.fk_user_mod,";
		$sql .= " ml.titre,";
		$sql .= " soc.nom as socname,";
		$sql .= " socp.rowid as contact_id,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "sendinblue_activites as t";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "sendinblue as m ON m.fk_mailing=t.fk_mailing";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "mailing as ml ON t.fk_mailing=ml.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socp ON t.email=socp.email";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as soc ON socp.fk_soc=soc.rowid";

		$sql .= " WHERE t.activites LIKE '%Click%'";
		
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if ($key == 'ml.titre' || $key == 't.email' || $key == 'soc.nom') {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}elseif ($key == 'socp.rowid'){
					$sql .=' AND '.$key.' = '.$value;
					
				}  elseif ($key != 'link') {
					$sql .= ' AND ' . $key . ' IN (' . $value . ')';
				}
			}
		}
		if (! empty($sortfield)) {
			$sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
		}

		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::getEmailcontactActivitesClick sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				
				$this->contactemail_clickactivity = array();

				while ( $obj = $this->db->fetch_object($resql) ) {

					$line = new SendinBlueActivitesLineDb();
				
					$addline = false;
					$activites = ($obj->activites);
					if (!empty($activites) && count($activites) > 0) {

						
						
								if (array_key_exists('link', $filter) && ! empty($filter['link'])) {
									if (strpos($act['url'], $filter['link']) !== false) {
										$addline = true;
									}
								} else {
									$addline = true;
								}
								$line->activites[] = $act;
							
						
					}
					if ($addline) {
						$line->id = $obj->rowid;

						$line->entity = $obj->entity;
						$line->fk_mailing = $obj->fk_mailing;
						$line->sendinblue_id = $obj->sendinblue_id;
						$line->email = $obj->email;

						$line->fk_user_author = $obj->fk_user_author;
						$line->datec = $this->db->jdate($obj->datec);
						$line->fk_user_mod = $obj->fk_user_mod;
						$line->tms = $this->db->jdate($obj->tms);
						$line->campaign_title = $obj->title;
						$line->socname = $obj->socname;
						$line->contact_id = $obj->contact_id;

						$this->contactemail_clickactivity[] = $line;
					}
				}
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::getEmailcontactActivitesClick " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param User $user User that modifies
	 * @param int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function update($user = 0, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->fk_mailing))
			$this->fk_mailing = trim($this->fk_mailing);
		if (isset($this->sendinblue_id))
			$this->sendinblue_id = trim($this->sendinblue_id);
		if (isset($this->email))
			$this->email = trim($this->email);
		if (isset($this->fk_user_mod))
			$this->fk_user_mod = trim($this->fk_user_mod);

			// Check parameters
			// Put here code to add a control on parameters values
		if (is_array($this->activites)) {
			$activites = serialize($this->activites);
		} else {
			$activites = trim($this->activites);
		}

		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "sendinblue_activites SET";

		$sql .= " fk_mailing=" . (isset($this->fk_mailing) ? $this->fk_mailing : "null") . ",";
		$sql .= " sendinblue_id=" . (isset($this->sendinblue_id) ? "'" . $this->db->escape($this->sendinblue_id) . "'" : "null") . ",";
		$sql .= " email=" . (isset($this->email) ? "'" . $this->db->escape($this->email) . "'" : "null") . ",";
		$sql .= " activites=" . (! empty($activites) ? "'" . $this->db->escape($activites) . "'" : "null") . ",";
		$sql .= " fk_user_mod=" . $user->id;

		$sql .= " WHERE rowid=" . $this->id;

		$this->db->begin();

		dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user User that deletes
	 * @param int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		$this->db->begin();

		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}

		if (! $error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "sendinblue_activites";
			$sql .= " WHERE rowid=" . $this->id;

			dol_syslog(get_class($this) . "::delete sql=" . $sql);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}





function getEmailcontactActivitesOpen($sortorder = 'ASC', $sortfield = 't.rowid', $limit = 0, $offset = 0, $filter = array()) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_mailing,";
		$sql .= " t.sendinblue_id,";
		$sql .= " t.email,";
		$sql .= " t.activites,";
		$sql .= " t.fk_user_author,";
		$sql .= " ml.date_creat as datec,";
		$sql .= " ml.date_valid,";
		$sql .= " t.fk_user_mod,";
		$sql .= " ml.titre,";
		$sql .= " soc.nom as socname,";
		$sql .= " socp.rowid as contact_id,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "sendinblue_activites as t";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "sendinblue as m ON m.fk_mailing=t.fk_mailing";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "mailing as ml ON t.fk_mailing=ml.rowid";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople as socp ON t.email=socp.email";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as soc ON socp.fk_soc=soc.rowid";

		$sql .= " WHERE t.activites LIKE '%Open%'";
		
		if (count($filter) > 0) {
			foreach ( $filter as $key => $value ) {
				if ($key == 'ml.titre' || $key == 't.email' || $key == 'soc.nom') {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				} elseif ($key == 'socp.rowid'){
					$sql .=' AND '.$key.' = '.$value;
					
				} elseif ($key != 'link') {
					$sql .= ' AND ' . $key . ' IN (' . $value . ')';
				}
			}
		}
		if (! empty($sortfield)) {
			$sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
		}

		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this) . "::getEmailcontactActivitesOpen sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				
				$this->contactemail_clickactivity = array();

				while ( $obj = $this->db->fetch_object($resql) ) {

					$line = new SendinBlueActivitesLineDb();
				
					$addline = false;
					$activites = ($obj->activites);
					if (!empty($activites) && count($activites) > 0) {

						
						
								if (array_key_exists('link', $filter) && ! empty($filter['link'])) {
									if (strpos($act['url'], $filter['link']) !== false) {
										$addline = true;
									}
								} else {
									$addline = true;
								}
								$line->activites[] = $act;
							
						
					}
					if ($addline) {
						$line->id = $obj->rowid;

						$line->entity = $obj->entity;
						$line->fk_mailing = $obj->fk_mailing;
						$line->sendinblue_id = $obj->sendinblue_id;
						$line->email = $obj->email;

						$line->fk_user_author = $obj->fk_user_author;
						$line->datec = $this->db->jdate($obj->datec);
						$line->fk_user_mod = $obj->fk_user_mod;
						$line->tms = $this->db->jdate($obj->tms);
						$line->campaign_title = $obj->title;
						$line->socname = $obj->socname;
						$line->contact_id = $obj->contact_id;

						$this->contactemail_clickactivity[] = $line;
					}
				}
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::getEmailcontactActivitesClick " . $this->error, LOG_ERR);
			return - 1;
		}
		}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $fromid Id of object to clone
	 * @return int New id of clone
	 */
	function createFromClone($fromid) {
		global $user, $langs;

		$error = 0;

		$object = new SendinBlueactivites($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;

		// Clear fields
		// ...

		// Create clone
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error ++;
		}

		if (! $error) {
		}

		// End
		if (! $error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return - 1;
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	function initAsSpecimen() {
		$this->id = 0;

		$this->entity = '';
		$this->fk_mailing = '';
		$this->sendinblue_id = '';
		$this->email = '';
		$this->activites = '';
		$this->fk_user_author = '';
		$this->datec = '';
		$this->fk_user_mod = '';
		$this->tms = '';
	}

	/**
	 * Save contact activites
	 *
	 * @param user $user Users
	 * @param array $targetactivites
	 * @param string $email
	 * @return int <0 if KO, >0 if OK
	 */
	public function saveEmailContactActivites($user, $targetactivites = null, $email = '') {
		$error = 0;

		if (is_array($targetactivites) && count($targetactivites) > 0 && ! empty($email)) {

			foreach ( $targetactivites as $activites ) {
				// Find length of current activite to know of we have more inforamtion and then update it

				// if (empty($this->fk_mailing))
				$this->fk_mailing = $activites->fk_mailing;

				$this->email = $email;

				$result = $this->fetch_by_mailing_email();
				if ($result < 0)
					$error ++;

				if (empty($error)) {
					// There no existing record for this campagin so save data
					if (empty($this->id)) {
						dol_syslog(get_class($this) . "::saveEmailContactActivites create Ativites for " . $email, LOG_DEBUG);

						$this->activites = $activites->activites;
						$result = $this->create($user);
						if ($result < 0)
							$error ++;
					} else {
						// There is already an activity check if we need to update
						if (count($this->activites) <= count($activites->activites)) {
							dol_syslog(get_class($this) . "::saveEmailContactActivites update Ativites for " . $email, LOG_DEBUG);
							$this->activites = $activites->activites;
							$result = $this->update($user);
							if ($result < 0)
								$error ++;
						}
					}
				}
			}
		}

		if (empty($error)) {
			return 1;
		} else {
			return - 1;
		}
	}
}
class SendinBlueActivitesLineDb
{
	public $id;
	public $entity;
	public $fk_mailing;
	public $sendinblue_id;
	public $email;
	public $activites;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';
	public $campaign_title;
	public $socname;
	public $contact_id;

	/**
	 * Constructor
	 */
	function __construct() {
		return 0;
	}
}