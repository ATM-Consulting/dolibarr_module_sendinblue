<?php
/* Copyright (C) 2013 Florian Henry  <florian.henry@open-concept.pro>
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
 * \file /sendinblue/sendinblue/sendinblue.class.php
 * \ingroup sendinblue
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
// require_once 'MCAPI.class.php';
require_once "SendinBlue.class.php";

/**
 * Put here description of your class
 */
class DolSendinBlue extends CommonObject
{
	var $db; // !< To store db handler
	var $error; // !< To return error code (or message)
	var $errors = array(); // !< To return several error codes (or messages)
	var $element = 'sendinblue'; // !< Id that identify managed objects
	var $table_element = 'sendinblue'; // !< Name of table without prefix where object is stored
	var $sendinblue; // API Object
	var $email_lines = array();
	var $listdest_lines = array();
	var $listsegment_lines = array();
	var $listcampaign_lines = array();
	var $listlist_lines = array();
	var $email_activity = array();
	var $contactemail_activity = array();
	var $id;
	var $entity;
	var $fk_mailing;

	var $sendinblue_id;
	var $sendinblue_webid;
	var $sendinblue_listid;
	var $sendinblue_segmentid;
	var $sendinblue_sender_name;
	var $fk_user_author;
	var $datec = '';
	var $fk_user_mod;
	var $tms = '';
	var $currentmailing;
	var $lines = array();

	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 */
	function __construct($db) {
		$this->db = $db;
		return 1;
	}

	/**
	 * Create object into database
	 *
	 * @param User $user that creates
	 * @param int $notrigger triggers after, 1=disable triggers
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
		if (isset($this->sendinblue_webid))
			$this->sendinblue_webid = trim($this->sendinblue_webid);
		if (isset($this->sendinblue_listid))
			$this->sendinblue_listid = trim($this->sendinblue_listid);
		if (isset($this->sendinblue_segmentid))
			$this->sendinblue_segmentid = trim($this->sendinblue_segmentid);
		if (isset($this->sendinblue_sender_name))
			$this->sendinblue_sender_name = trim($this->sendinblue_sender_name);

			// Check parameters
			// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "sendinblue(";

		$sql .= "entity,";
		$sql .= "fk_mailing,";
		$sql .= "sendinblue_id,";
		$sql .= "sendinblue_webid,";
		$sql .= "sendinblue_listid,";
		$sql .= "sendinblue_segmentid,";
		$sql .= "sendinblue_sender_name,";
		$sql .= "fk_user_author,";
		$sql .= "datec,";
		$sql .= "fk_user_mod";
		$sql .= ") VALUES (";
		$sql .= " " . $conf->entity . ",";
		$sql .= " " . (! isset($this->fk_mailing) ? 'NULL' : "'" . $this->fk_mailing . "'") . ",";
		$sql .= " " . (! isset($this->sendinblue_id) ? 'NULL' : "'" . $this->sendinblue_id . "'") . ",";
		$sql .= " " . (! isset($this->sendinblue_webid) ? 'NULL' : "'" . $this->sendinblue_webid . "'") . ",";
		$sql .= " " . (! isset($this->sendinblue_listid) ? 'NULL' : "'" . $this->db->escape($this->sendinblue_listid) . "'") . ",";
		$sql .= " " . (! isset($this->sendinblue_segmentid) ? 'NULL' : "'" . $this->db->escape($this->sendinblue_segmentid) . "'") . ",";
		$sql .= " " . (! isset($this->sendinblue_sender_name) ? 'NULL' : "'" . $this->db->escape($this->sendinblue_sender_name) . "'") . ",";
		$sql .= " '" . $user->id . "',";
		$sql .= " " . $this->db->idate(dol_now()) . ",";
		$sql .= " '" . $user->id . "'";
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "sendinblue");

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
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($id) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_mailing,";
		$sql .= " t.sendinblue_id,";
		$sql .= " t.sendinblue_webid,";
		$sql .= " t.sendinblue_listid,";
		$sql .= " t.sendinblue_segmentid,";
		$sql .= " t.sendinblue_sender_name,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "sendinblue as t";
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
				$this->sendinblue_webid = $obj->sendinblue_webid;
				$this->sendinblue_listid = $obj->sendinblue_listid;
				$this->sendinblue_segmentid = $obj->sendinblue_segmentid;
				$this->sendinblue_sender_name = $obj->sendinblue_sender_name;
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
	function fetch_all($month_filter=0) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_mailing,";
		$sql .= " t.sendinblue_id,";
		$sql .= " t.sendinblue_webid,";
		$sql .= " t.sendinblue_listid,";
		$sql .= " t.sendinblue_segmentid,";
		$sql .= " t.sendinblue_sender_name,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "sendinblue as t";
		if (!empty($month_filter)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
			$sql .= " WHERE (t.datec BETWEEN '".$this->db->escape($this->db->idate(dol_time_plus_duree(dol_now(),$month_filter*-1,'m') ))."' AND NOW())";
			$sql .= " ORDER BY t.datec DESC";
		}

		dol_syslog(get_class($this) . "::fetch_all sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {

				$this->lines = array();

				while ( $obj = $this->db->fetch_object($resql) ) {

					$line = new DolSendinBlueLine();

					$line->id = $obj->rowid;

					$line->entity = $obj->entity;
					$line->fk_mailing = $obj->fk_mailing;
					$line->sendinblue_id = $obj->sendinblue_id;
					$line->sendinblue_webid = $obj->sendinblue_webid;
					$line->sendinblue_listid = $obj->sendinblue_listid;
					$line->sendinblue_segmentid = $obj->sendinblue_segmentid;
					$line->sendinblue_sender_name = $obj->sendinblue_sender_name;
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
			dol_syslog(get_class($this) . "::fetch_all " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $id of mailing
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_by_mailing($id) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_mailing,";
		$sql .= " t.sendinblue_id,";
		$sql .= " t.sendinblue_webid,";
		$sql .= " t.sendinblue_listid,";
		$sql .= " t.sendinblue_segmentid,";
		$sql .= " t.sendinblue_sender_name,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "sendinblue as t";
		$sql .= " WHERE t.fk_mailing = " . $id;

		dol_syslog(get_class($this) . "::fetch_by_mailing sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->entity = $obj->entity;
				$this->fk_mailing = $obj->fk_mailing;
				$this->sendinblue_id = $obj->sendinblue_id;
				$this->sendinblue_webid = $obj->sendinblue_webid;
				$this->sendinblue_listid = $obj->sendinblue_listid;
				$this->sendinblue_segmentid = $obj->sendinblue_segmentid;
				$this->sendinblue_sender_name = $obj->sendinblue_sender_name;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_by_mailing " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $id of mailing
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_by_sendinblueid($id) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.entity,";
		$sql .= " t.fk_mailing,";
		$sql .= " t.sendinblue_id,";
		$sql .= " t.sendinblue_webid,";
		$sql .= " t.sendinblue_listid,";
		$sql .= " t.sendinblue_segmentid,";
		$sql .= " t.sendinblue_sender_name,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";

		$sql .= " FROM " . MAIN_DB_PREFIX . "sendinblue as t";
		$sql .= " WHERE t.sendinblue_id = '" . $this->db->escape($id) . "'";

		dol_syslog(get_class($this) . "::fetch_by_sendinblueid sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->entity = $obj->entity;
				$this->fk_mailing = $obj->fk_mailing;
				$this->sendinblue_id = $obj->sendinblue_id;
				$this->sendinblue_webid = $obj->sendinblue_webid;
				$this->sendinblue_listid = $obj->sendinblue_listid;
				$this->sendinblue_segmentid = $obj->sendinblue_segmentid;
				$this->sendinblue_sender_name = $obj->sendinblue_sender_name;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_by_sendinblueid " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param User $user that modifies
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function update($user = 0, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->entity))
			$this->entity = trim($this->entity);
		if (isset($this->fk_mailing))
			$this->fk_mailing = trim($this->fk_mailing);
		if (isset($this->sendinblue_id))
			$this->sendinblue_id = trim($this->sendinblue_id);
		if (isset($this->sendinblue_webid))
			$this->sendinblue_webid = trim($this->sendinblue_webid);
		if (isset($this->sendinblue_listid))
			$this->sendinblue_listid = trim($this->sendinblue_listid);
		if (isset($this->sendinblue_segmentid))
			$this->sendinblue_segmentid = trim($this->sendinblue_segmentid);
		if (isset($this->sendinblue_sender_name))
			$this->sendinblue_sender_name = trim($this->sendinblue_sender_name);

			// Check parameters
			// Put here code to add a control on parameters values

		// Update request
		$sql = "UPDATE " . MAIN_DB_PREFIX . "sendinblue SET";

		$sql .= " entity=" . $conf->entity . ",";
		$sql .= " fk_mailing=" . (isset($this->fk_mailing) ? $this->fk_mailing : "null") . ",";
		$sql .= " sendinblue_id=" . (isset($this->sendinblue_id) ? "'" . $this->sendinblue_id . "'" : "null") . ",";
		$sql .= " sendinblue_webid=" . (isset($this->sendinblue_webid) ? "'" . $this->sendinblue_webid . "'" : "null") . ",";
		$sql .= " sendinblue_listid=" . (isset($this->sendinblue_listid) ? "'" . $this->db->escape($this->sendinblue_listid) . "'" : "null") . ",";
		$sql .= " sendinblue_segmentid=" . (isset($this->sendinblue_segmentid) ? "'" . $this->db->escape($this->sendinblue_segmentid) . "'" : "null") . ",";
		$sql .= " sendinblue_sender_name=" . (isset($this->sendinblue_sender_name) ? "'" . $this->db->escape($this->sendinblue_sender_name) . "'" : "null") . ",";

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
	 * @param User $user that deletes
	 * @param int $notrigger triggers after, 1=disable triggers
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
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "sendinblue";
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

	/**
	 * Get Current instance of Mailjet Connector
	 *
	 * return SendinBlue Object of rise error
	 */
	/*private function getInstanceSendinBlue() {
		global $conf, $langs;

		if (! is_object($this->sendinblue)) {
			if (empty($conf->global->MAILCHIMP_MAIL_SMTPS_ID)) {
				$langs->load("sendinblue@sendinblue");
				$this->error = $langs->trans("SendinBlueAPIKeyNotSet");
				dol_syslog(get_class($this) . "::getInstanceSendinBlue " . $this->error, LOG_ERR);
				return - 1;
			}
			if (empty($conf->global->MAILCHIMP_MAIL_SMTPS_PW)) {
				$langs->load("sendinblue@sendinblue");
				$this->error = $langs->trans("SendinBlueSecretKeyNotSet");
				dol_syslog(get_class($this) . "::getInstanceSendinBlue " . $this->error, LOG_ERR);
				return - 1;
			}

			if (! empty($conf->global->MAILCHIMP_MAIL_EMAIL_TLS)) {
				$param = array(
						'ssl_verifypeer' => true,
						'ssl_verifyhost' => 2
				);
				// TODO: Need to specify ssl_cainfo or PATH check SendinBlue.php
			} else {
				$param = array(
						'ssl_verifypeer' => false
				);
			}
			$sendinblue = new SendinBlue($conf->global->MAILCHIMP_MAIL_SMTPS_PW, $param);
			
			$this->sendinblue = $sendinblue;
		}

		return 1;
	}*/

	private function getInstanceSendinBlue() {
			global $conf, $langs;
	
			if (! is_object($this->sendinblue)) {
				if (empty($conf->global->MAILCHIMP_SENDINBLUE_API_KEY)) {
					$langs->load("sendinblue@sendinblue");
					$this->error = $langs->trans("SendinBlueAPIKeyNotSet");
					dol_syslog(get_class($this) . "::getInstanceSendinBlue " . $this->error, LOG_ERR);
					return - 1;
				}
				
				
				$sendinblue = new SendinBlue('https://api.sendinblue.com/v2.0', $conf->global->MAILCHIMP_SENDINBLUE_API_KEY);
				
				$this->sendinblue = $sendinblue;
			}
	
			return 1;
		}

	/**
	 * Retreive SendinBlue Contact List
	 *
	 * @param array $filters a hash of filters to apply to this query - all are optional:
	 *        string list_id optional - return a single list using a known list_id. Accepts multiples separated by commas when not using exact matching
	 *        string list_name optional - only lists that match this name
	 *        string from_name optional - only lists that have a default from name matching this
	 *        string from_email optional - only lists that have a default from email matching this
	 *        string from_subject optional - only lists that have a default from email matching this
	 *        string created_before optional - only show lists that were created before this date/time (in GMT) - format is YYYY-MM-DD HH:mm:ss (24hr)
	 *        string created_after optional - only show lists that were created since this date/time (in GMT) - format is YYYY-MM-DD HH:mm:ss (24hr)
	 *        boolean exact optional - flag for whether to filter on exact values when filtering, or search within content for filter values - defaults to true
	 * @param int $start optional - control paging of lists, start results at this list #, defaults to 1st page of data (page 0)
	 * @param int $limit optional - control paging of lists, number of lists to return with each call, defaults to 25 (max=100)
	 * @param string $sort_field optional - "created" (the created date, default) or "web" (the display order in the web app). Invalid values will fall back on "created" - case insensitive.
	 * @param string $sort_dir optional - "DESC" for descending (default), "ASC" for Ascending. Invalid values will fall back on "created" - case insensitive. Note: to get the exact display order as the web app you'd use "web" and "ASC"
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function getListDestinaries($filters = array(), $start = 0, $limit = 100, $sort_field = 'created', $sort_dir = 'DESC') {
		$error = 0;

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::getListDestinaries " . $this->error, LOG_ERR);
			return - 1;
		}

		// Call
		try {

			if (! empty($filters['list_id'])) {
				$response = $this->sendinblue->get('lists/' . $filters['list_id']);
			} else {
				$response = $this->sendinblue->get('lists');
			}
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			$error ++;
		}

		if (! empty($error)) {
			dol_syslog(get_class($this) . "::getListDestinaries " . $this->error, LOG_ERR);
			return - 1;
		} else {
			$nb_lists = $response['total_items'];
			if ($nb_lists > 100) {
				$response = $this->sendinblue->get('lists');
			}
			if (! empty($filters['list_id'])) {
				$this->listdest_lines = array(
						$response
				);
			} else {
				$this->listdest_lines = $response['lists'];
			}

			return 1;
		}
	}

	/**
	 * Retreive SendinBlue segment List
	 *
	 * @param string $id the list id to connect to. Get by calling lists()
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function getListSegmentDestinaries($id) {
		$error = 0;

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::getListSegmentDestinaries " . $this->error, LOG_ERR);
			return - 1;
		}

		// Call
		try {
			$response = $this->sendinblue->get('lists/' . $id . '/segments');
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			$error ++;
		}

		if (! empty($error)) {
			dol_syslog(get_class($this) . "::getListSegmentDestinaries " . $this->error, LOG_ERR);
			return - 1;
		} else {
			$this->listsegment_lines = $response['segments'];
			return 1;
		}
	}

	/**
	 * Retreive SendinBlue list for email
	 *
	 * @param string $email email to know list
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function getListForEmail($email) {
		$error = 0;

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::getListForEmail " . $this->error, LOG_ERR);
			return - 1;
		}

		// Call
		try {
			$response = $this->sendinblue->get('lists', array(
					'email' => $email
			));
		} catch ( Exception $e ) {
			if (get_class($e) != 'SendinBlue_List_NotSubscribed') {
				$this->error = $e->getMessage();
				$error ++;
			}
		}

		if (! empty($error)) {
			dol_syslog(get_class($this) . "::getListForEmail error " . $this->error, LOG_ERR);
			return - 1;
		} else {
			$this->listlist_lines = $response;
			return $response;
		}
	}

	/**
	 * Retraive email from sendinblue segment
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function getEmailSegment() {
		global $conf;

		$error = 0;

		$this->email_lines = array();
		$emailsegment = 0;

		$dc = "us1";
		if (strstr($conf->global->MAILCHIMP_MAIL_SMTPS_PW, "-")) {
			list ( $key, $dc ) = explode("-", $conf->global->MAILCHIMP_MAIL_SMTPS_PW, 2);
			if (! $dc)
				$dc = "us1";
		}

		$url_string = "&segment[match]=all&segment[conditions][0][field]=static_segment&segment[conditions][0][op]=eq&segment[conditions][0][value]=" . $this->sendinblue_segmentid;

		$chunk_size = 4096; // in bytes
		$url = 'http://' . $dc . '.api.sendinblue.com/export/1.0/list?apikey=' . $conf->global->MAILCHIMP_MAIL_SMTPS_PW . '&id=' . $this->sendinblue_listid . $url_string;
		// print $url.'<BR>';
		/**
		 * a more robust client can be built using fsockopen *
		 */
		$handle = @fopen($url, 'r');
		if (! $handle) {
			$this->error = "failed to access url\n";
			dol_syslog(get_class($this) . "::getEmailSegment " . $this->error, LOG_ERR);
			return - 1;
		} else {
			$i = 0;
			$email_addr = array();
			while ( ! feof($handle) ) {
				$buffer = fgets($handle, $chunk_size);
				if (trim($buffer) != '') {
					$obj = json_decode($buffer);
					if ($i != 0) {
						if (! in_array($obj[0], $this->email_lines)) {
							$this->email_lines[] = $obj[0];
							$emailsegment ++;
						}
					}
					$i ++;
				}
			}
			fclose($handle);
		}

		return $emailsegment;
	}

	/**
	 * Retraive email from sendinblue List
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function getEmailList() {
		global $conf;

		$error = 0;

		$this->email_lines = array();
		$emailsegment = 0;

		$dc = "us1";
		if (strstr($conf->global->MAILCHIMP_MAIL_SMTPS_PW, "-")) {
			list ( $key, $dc ) = explode("-", $conf->global->MAILCHIMP_MAIL_SMTPS_PW, 2);
			if (! $dc)
				$dc = "us1";
		}

		$chunk_size = 4096; // in bytes
		$url = 'http://' . $dc . '.api.sendinblue.com/export/1.0/list?apikey=' . $conf->global->MAILCHIMP_MAIL_SMTPS_PW . '&status=subscribed&id=' . $this->sendinblue_listid;
		// print $url.'<BR>';
		/**
		 * a more robust client can be built using fsockopen *
		 */
		$handle = @fopen($url, 'r');
		if (! $handle) {
			$this->error = "failed to access url\n";
			dol_syslog(get_class($this) . "::getEmailList " . $this->error, LOG_ERR);
			return - 1;
		} else {
			$i = 0;
			$email_addr = array();
			while ( ! feof($handle) ) {
				$buffer = fgets($handle, $chunk_size);
				if (trim($buffer) != '') {
					$obj = json_decode($buffer);
					if ($i != 0) {
						if (! in_array($obj[0], $this->email_lines)) {
							$this->email_lines[] = $obj[0];
							$emailsegment ++;
						}
					}
					$i ++;
				}
			}
			fclose($handle);
		}

		return $emailsegment;
	}

	/**
	 * Retraive activty for a campaign
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function getCampaignActivity() {
		global $conf;

		$error = 0;

		$this->email_lines = array();
		$emailsegment = 0;

		$dc = "us1";
		if (strstr($conf->global->MAILCHIMP_MAIL_SMTPS_PW, "-")) {
			list ( $key, $dc ) = explode("-", $conf->global->MAILCHIMP_MAIL_SMTPS_PW, 2);
			if (! $dc)
				$dc = "us1";
		}

		// $chunk_size = 4096; //in bytes
		$url = 'http://' . $dc . '.api.sendinblue.com/export/1.0/campaignSubscriberActivity?apikey=' . $conf->global->MAILCHIMP_MAIL_SMTPS_PW . '&id=' . $this->sendinblue_id . '&include_empty=true';

		dol_syslog(get_class($this) . "::".__METHOD__." url=".$url, LOG_DEBUG);
		/**
		 * a more robust client can be built using fsockopen *
		 */
		$handle = @fopen($url, 'r');
		if (! $handle) {
			$this->error = "getCampaignActivity failed to access url\n";
			dol_syslog(get_class($this) . "::getCampaignActivity " . $this->error, LOG_ERR);
			return - 1;
		} else {
			$this->email_activity = array();
			while ( ! feof($handle) ) {
				$buffer = fgets($handle);
				if (trim($buffer) != '') {
					$array_obj = json_decode($buffer, true);
					foreach ( $array_obj as $key => $activity ) {
						$this->email_activity[] = array(
								'email' => $key,
								'activity' => $activity
						);
					}
				}
			}
			fclose($handle);
		}
		return 1;
	}

	/**
	 * Create SendinBlue segment on List
	 *
	 * @param string $listid the list id to connect to. Get by calling lists()
	 * @param string $segmentname New segment name
	 *
	 * @return int <0 if KO, id of new segment if OK
	 */
	function createSegment($listid, $segmentname) {
		$error = 0;

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::createSegment " . $this->error, LOG_ERR);
			return - 1;
		}

		// Call
		try {
			$response = $this->sendinblue->post('lists/' . $listid . '/segments', array(
					'name' => $segmentname,
					'static_segment' => array()
			));
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			$error ++;
		}

		if (! empty($error)) {
			dol_syslog(get_class($this) . "::createSegment " . $this->error, LOG_ERR);
			return - 1;
		} else {
			return $response['id'];
		}
	}

	/**
	 * update SendinBlue segment on List
	 *
	 * @param string $listid the list id to connect to. Get by calling lists()
	 * @param string $segmentid New segment name
	 * @param array $emailtoadd email to add
	 * @param int $resetseg reset segment , 0 to only add
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function updateSegment($listid, $segmentid, $emailtoadd, $resetseg = 0) {
		$error = 0;

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::updateSegment " . $this->error, LOG_ERR);
			return - 1;
		}

		if (! empty($resetseg)) {
			try {
				$myMembers = $this->sendinblue->get('/lists/' . $listid . '/segments/' . $segmentid . '/members');
				$emails_to_remove = array();
				foreach ( $myMembers['members'] as $b ) {
					array_push($emails_to_remove, $b['email_address']);
				}

				$response = $this->sendinblue->post('lists/' . $listid . '/segments/' . $segmentid, array(
						'members_to_remove' => $emails_to_remove
				));
			} catch ( Exception $e ) {
				$this->error = $e->getMessage();
				dol_syslog(get_class($this) . "::updateSegment  listStaticSegmentReset " . $this->error, LOG_ERR);
				return - 1;
			}
		}

		$batch = array();
		$total_added = 0;
		$nb_emailadded = 1;
		foreach ( $emailtoadd as $email ) {
			$tmp_array = explode('&', $email);
			if (! empty($tmp_array[0]) && isValidEmail($tmp_array[0]) && filter_var($tmp_array[0], FILTER_VALIDATE_EMAIL) && ! in_array($tmp_array[0], $batch)) {
				$idx_tbl = intval($nb_emailadded / 9000);
				$batch[$idx_tbl][] = array(
						'email' => $tmp_array[0]
				);
				$nb_emailadded ++;
			}
		}
		dol_syslog(get_class($this) . '::updateSegment count($batch)=' . count($batch), LOG_DEBUG);
		foreach ( $batch as $groupbatch ) {
			dol_syslog(get_class($this) . '::updateSegment count($groupbatch)=' . count($groupbatch), LOG_DEBUG);
			try {

				$gbatch = array();
				foreach ( $groupbatch as $gb ) {
					array_push($gbatch, $gb['email']);
				}

				$response = $this->sendinblue->post('lists/' . $listid . '/segments/' . $segmentid, array(
						'members_to_add' => $gbatch
				));

				dol_syslog(get_class($this) . '::updateSegment $response[total_added]=' . $response['success_count'], LOG_DEBUG);

				$total_added += $response['total_added'];

				dol_syslog(get_class($this) . '::updateSegment $total_added=' . $total_added, LOG_DEBUG);
			} catch ( Exception $e ) {
				$this->errors[] = $e->getMessage();
				dol_syslog(get_class($this) . "::updateSegment  staticSegmentMembersAdd " . $this->error, LOG_ERR);
				$error ++;
			}
		}

		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::updateSegment Error" . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			dol_syslog(get_class($this) . "::updateSegment Error=" . $this->error, LOG_ERR);
			return - 1;
		} else {
			return $total_added;
		}
	}

	/**
	 * update SendinBlue segment on List
	 *
	 * @param string $listid the list id to connect to. Get by calling lists()
	 * @param string $segmentname New segment name
	 * @param array $array_email email to add
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function deleteEmailFromSegment($listid, $segmentid, $array_email) {
		$error = 0;

		if (empty($listid)) {
			$this->error = 'listid is mandatory';
			dol_syslog(get_class($this) . "::deleteEmailFromSegment " . $this->error, LOG_ERR);
			return - 1;
		}
		if (count($array_email) == 0) {
			$this->error = '$array_email is empty';
			dol_syslog(get_class($this) . "::deleteEmailFromSegment " . $this->error, LOG_ERR);
			return - 1;
		}

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::deleteEmailFromSegment " . $this->error, LOG_ERR);
			return - 1;
		}

		foreach ( $array_email as $mail ) {
			$batch_email_to_unsubscribe[] = array(
					'email' => $mail
			);
		}
		try {
			$response = $this->sendinblue->post('lists/' . $listid . '/segments/' . $segmentid, array(
					'members_to_remove' => $batch_email_to_unsubscribe
			));
		} catch ( Exception $e ) {
			$error ++;
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::deleteEmailFromSegment " . $this->error, LOG_ERR);
		}
		if ($response['error_count'] > 0) {
			$error ++;
			foreach ( $response['errors'] as $err ) {
				$this->errors[] = $err['error'];
			}
		}
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::deleteEmailFromSegment Error" . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1;
		} else {
			return $response['total_removed'];
		}
	}

	/**
	 * List eMail same as referent product screen
	 *
	 * @param string $type of referent
	 * @param int $idproduct Id
	 * @return int <0 if KO, >0 if OK
	 */
	function getEmailListFromReferent($type, $idproduct) {
		global $conf, $langs, $socid, $user;

		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

		if ($type == 'invoice') {
			$table_source = "facture";
			$tabledet_source = "facturedet";
			$fkelement = "fk_facture";
		} else if ($type == 'propal') {
			$table_source = "propal";
			$tabledet_source = "propaldet";
			$fkelement = "fk_propal";
		} else if ($type == 'supplyorder') {
			$table_source = "commande_fournisseur";
			$tabledet_source = "commande_fournisseurdet";
			$fkelement = "fk_commande";
		} else if ($type == 'order') {
			$table_source = "commande";
			$tabledet_source = "commandedet";
			$fkelement = "fk_commande";
		} else if ($type == 'contract') {
			$table_source = "contrat";
			$tabledet_source = "contratdet";
			$fkelement = "fk_contrat";
		} else if ($type == 'supplyinvoice') {
			$table_source = "facture_fourn";
			$tabledet_source = "facture_fourn_det";
			$fkelement = "fk_facture_fourn";
		}

		$sql = "SELECT DISTINCT s.nom, ";
		$sql .= " s.rowid as socid,";
		$sql .= " s.email as custemail";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
		$sql .= ", " . MAIN_DB_PREFIX . $table_source . " as f";
		$sql .= ", " . MAIN_DB_PREFIX . $tabledet_source . " as d";
		if (! $user->rights->societe->client->voir && ! $socid)
			$sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
		$sql .= " WHERE f.fk_soc = s.rowid";
		$sql .= " AND f.entity = " . $conf->entity;
		$sql .= " AND d." . $fkelement . " = f.rowid";
		$sql .= " AND d.fk_product =" . $idproduct;

		dol_syslog(get_class($this) . "::getEmailListFromReferent sql=" . $sql);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				while ( $obj = $this->db->fetch_object($resql) ) {

					// Add customer mail if exists and is valid email
					if (! empty($obj->custemail) && isValidEMail($obj->custemail)) {
						$custemail = new DolSendinBlueeMailLine($this->db);
						$custemail->id = $obj->socid;
						$custemail->email = $obj->custemail;
						$custemail->thirdparty = $obj->nom;
						$custemail->type = 'thirdparty';
						$this->email_lines[] = $custemail;
					}

					// Find contect of customer email
					$sqlmailcontact = "SELECT rowid as contid";
					$sqlmailcontact .= " FROM " . MAIN_DB_PREFIX . "socpeople";
					$sqlmailcontact .= " WHERE fk_soc = " . $obj->socid;

					dol_syslog(get_class($this) . "::getEmailListFromReferent sqlmailcontact=" . $sqlmailcontact);
					$resqlcont = $this->db->query($sqlmailcontact);
					if ($resqlcont) {
						if ($this->db->num_rows($resqlcont)) {

							while ( $objcont = $this->db->fetch_object($resqlcont) ) {

								$contactstatic = new Contact($this->db);
								$contactstatic->fetch($objcont->contid);
								// Add customer mail if exists and is valid email
								if (! empty($contactstatic->email) && isValidEMail($contactstatic->email)) {
									$custemail = new DolSendinBlueeMailLine($this->db);
									$custemail->id = $contactstatic->id;
									$custemail->email = $contactstatic->email;
									$custemail->thirdparty = $contactstatic->socname;
									$custemail->contactfullname = $contactstatic->getFullName($langs);
									$custemail->type = 'contact';
									$this->email_lines[] = $custemail;
								}
							}
						}
					}

					$this->db->free($resqlcont);
				}
			}

			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::getEmailListFromReferent " . $this->error, LOG_ERR);
			return - 1;
		}
	}

	/**
	 * Check if sender mail is already a validated sender
	 *
	 * @param string $mail_sender use to send mails
	 * @return int <0 if KO, >0 if OK
	 */
	function checkMailSender($mail_sender = '') {
		if (empty($mail_sender) && ! isValidEmail($mail_sender)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Populate an array with campaign
	 *
	 * @return int <0 if KO, 1 if OK
	 */
	function getListCampaign() {
		
		$result = $this->getInstanceSendinBlue();
		$resultSendinBlue = $this->getInstanceSendinBlue();
		if ($result < 0 && $resultSendinBlue <0) {
			dol_syslog(get_class($this) . "::getListDestinaries " . $this->error, LOG_ERR);
			return - 1;
		}

		// Call
		try {
			if(!($result<0)){
				$response = $this->sendinblue->get('campaigns', array());
			}
			if(!($resultSendinBlue<0)){
				$responseSendinBlue = $this->sendinblue->get_campaigns_v2(array("type"=>"classic", "page"=>1,"page_limit"=>10));
			}		
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::getListCampaign " . $this->error, LOG_ERR);
			return - 1;
		}
		if(!($result<0)){
			$this->listcampaign_lines = $response['campaigns'];
		}
		if(!($resultSendinBlue<0)){
			
			$this->listcampaign_lines = array_merge($this->listcampaign_lines , $responseSendinBlue['data']['campaign_records']);
		}
		return 1;
	}

	/**
	 * Populate an array with campaign
	 *
	 * @return int <0 if KO, 1 if OK
	 */
	function getBatchInforamtion($batchnum='') {
		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::getListDestinaries " . $this->error, LOG_ERR);
			return - 1;
		}

		// Call
		try {
			$response = $this->sendinblue->get('batches/'.$batchnum);
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::getListCampaign " . $this->error, LOG_ERR);
			return - 1;
		}

		var_dump($response);

		return 1;
	}

	/**
	 * Add email to list
	 *
	 * @param int $listid to add
	 * @param array $array_email add
	 * @return int <0 if KO, >0 if OK
	 */
	function addEmailToList($listid = 0, $array_email = array()) {
		global $conf;

		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

		$error = 0;

		if (empty($listid)) {
			$this->error = 'listid is mandatory';
			dol_syslog(get_class($this) . "::addEmailToList " . $this->error, LOG_ERR);
			return - 1;
		}
		if (count($array_email) == 0) {
			$this->error = '$array_email is empty';
			dol_syslog(get_class($this) . "::addEmailToList " . $this->error, LOG_ERR);
			return - 1;
		}

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::getListDestinaries " . $this->error, LOG_ERR);
			return - 1;
		}

		$email_added = array();
		$email_to_add = array();

		dol_syslog(get_class($this) . '::addEmailToList count($$array_email)=' . count($array_email), LOG_DEBUG);

		foreach ( $array_email as $email ) {

			// email is formated like email&type&id where type=contact for contact or thirdparty and id is the id of contact or thridparty
			$tmp_array = explode('&', $email);
			$merge_vars = new stdClass();
			if (! empty($tmp_array[0]) && isValidEmail($tmp_array[0]) && ! in_array($tmp_array[0], $email_added)) {

				if ($tmp_array[1] == 'contact') {
					$contactstatic = new Contact($this->db);
					$result = $contactstatic->fetch($tmp_array[2]);

					if ($result < 0) {
						$this->error = $contactstatic->error;
						dol_syslog(get_class($this) . "::getListDestinaries " . $this->error, LOG_ERR);
						return - 1;
					}
					if (! empty($contactstatic->id)) {
						$merge_vars->FNAME = $contactstatic->firstname;
						$merge_vars->LNAME = $contactstatic->lastname;
						$merge_vars->EMAIL = $tmp_array[0];
					}
				}
				if ($tmp_array[1] == 'thirdparty') {
					$socstatic = new Societe($this->db);
					$result = $socstatic->fetch($tmp_array[2]);
					if ($result < 0) {
						$this->error = $socstatic->error;
						dol_syslog(get_class($this) . "::getListDestinaries " . $this->error, LOG_ERR);
						return - 1;
					}
					if (! empty($socstatic->id)) {
						$merge_vars->FNAME = $socstatic->name;
						$merge_vars->EMAIL = $tmp_array[0];
					}
				}

				dol_syslog(get_class($this) . "::addEmailToList listid=" . $listid . " merge_vars=" . var_export($merge_vars, true) . ' $tmp_array[0]=' . $tmp_array[0], LOG_DEBUG);

				// Add only on time the email
				$email_added[] = $tmp_array[0];

				$idx_tbl = intval(count($email_added) / 5000);
				$email_to_add[$idx_tbl][] = array (
						'email_address' => $tmp_array[0],
						'status'=>'subscribed',
						'email_type' => 'html',
						'merge_vars' => $merge_vars
				);
			}
		}

		dol_syslog(get_class($this).'::addEmailToList var_export($email_to_add)='.var_export($email_to_add,true), LOG_DEBUG);
		dol_syslog(get_class($this) . '::addEmailToList count($email_to_add)=' . count($email_to_add), LOG_DEBUG);
		$batch_email_to_add_error=array();

		$add_count = 0;

		foreach ( $email_to_add as $key_batch=>$batch_email_to_add ) {

			dol_syslog(get_class($this) . '::addEmailToList $key_batch=' . $key_batch, LOG_DEBUG);
			dol_syslog(get_class($this) . '::addEmailToList count($batch_email_to_add)=' . count($batch_email_to_add), LOG_DEBUG);

			dol_syslog(get_class($this) . '::addEmailToList start batchSubscribe ' . dol_print_date(dol_now(), 'standard'), LOG_DEBUG);

			$attributes=array();

			foreach ( $batch_email_to_add as $key_mail=>$emails ) {

				$email_object = new stdClass();
				$email_object->email_address = $emails['email_address'];
				$email_object->status_if_new = $emails['status'];
				$email_object->email_type = $emails['email_type'];
				$email_object->merge_fields = $emails['merge_vars'];


				$attributes_object = new stdClass();
				$attributes_object->method='PUT';
				$attributes_object->path='lists/' . $listid . '/members/'.$this->sendinblue->subscriberHash($email_object->email_address);
				$attributes_object->body=json_encode($email_object);


				$attributes['operations'][] = $attributes_object;
			}
			// Call
			try {
				$response = $this->sendinblue->post('batches/', $attributes);
			} catch ( Exception $e ) {
				$this->errors[] = $e->getMessage();
				$batch_email_to_add_error=$batch_email_to_add;
				$error ++;
			}

			dol_syslog(get_class($this) . '::addEmailToList end batchSubscribe ' . dol_print_date(dol_now(), 'standard'), LOG_DEBUG);

		}
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::addEmailToList Error" . $errmsg, LOG_ERR);
				dol_syslog(get_class($this) . "::addEmailToList batch_email_to_add=" . var_export($batch_email_to_add_error,true), LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1;
		} else {
			return count($email_added);
		}
	}

	/**
	 * remove email from list
	 *
	 * @param int $listid to remove
	 * @param array $array_email add
	 * @return int <0 if KO, >0 if OK
	 */
	function deleteEmailFromList($listid = 0, $array_email = array()) {
		global $conf;

		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

		$error = 0;

		if (empty($listid)) {
			$this->error = 'listid is mandatory';
			dol_syslog(get_class($this) . "::deleteEmailFromList " . $this->error, LOG_ERR);
			return - 1;
		}
		if (count($array_email) == 0) {
			$this->error = '$array_email is empty';
			dol_syslog(get_class($this) . "::deleteEmailFromList " . $this->error, LOG_ERR);
			return - 1;
		}

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::deleteEmailFromList " . $this->error, LOG_ERR);
			return - 1;
		}
		$success_count = 0;

		foreach ( $array_email as $mail ) {
			try {

				$response = $this->sendinblue->delete('lists/' . $listid . '/members/' . $this->sendinblue->subscriberHash($mail));

				$success_count ++;
			} catch ( Exception $e ) {
				$error ++;
				$this->error = $e->getMessage();
				dol_syslog(get_class($this) . "::getListCampaign " . $this->error, LOG_ERR);
			}
		}
		if ($response['error_count'] > 0) {
			$error ++;
			foreach ( $response['errors'] as $err ) {
				$this->errors[] = $err['error'];
			}
		}
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::deleteEmailFromList Error" . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1;
		} else {
			return $success_count;
		}
	}

	/**
	 * Unsubscribe the given email address from the list
	 *
	 * @param string $id
	 * @param associative_array $email - email string an email address
	 *        - euid string the unique id for an email address (not list related) - the email "id" returned from listMemberInfo, Webhooks, Campaigns, etc.
	 *        - leid string the list email id (previously called web_id) for a list-member-info type call. this doesn't change when the email address changes
	 * @param boolean $delete_member
	 * @param boolean $send_goodbye
	 * @param boolean $send_notify
	 * @return associative_array with a single entry:
	 *         - complete bool whether the call worked. reallistically this will always be true as errors will be thrown otherwise.
	 */
	function unsubscribeEmail($listid, $email, $delete_member = false, $send_goodbye = true, $send_notify = true) {
		global $conf;

		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

		$error = 0;

		if (empty($listid)) {
			$this->error = 'listid is mandatory';
			dol_syslog(get_class($this) . "::unsubscribeEmail " . $this->error, LOG_ERR);
			return - 1;
		}
		if (empty($email)) {
			$this->error = '$email is empty';
			dol_syslog(get_class($this) . "::unsubscribeEmail " . $this->error, LOG_ERR);
			return - 1;
		}

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::unsubscribeEmail " . $this->error, LOG_ERR);
			return - 1;
		}

		$succes_count = 0;
		try {
			$response = $this->sendinblue->put('lists/' . $listid . '/members/' . $this->sendinblue->subscriberHash($email), array(
					'status' => 'unsubscribed'
			));

			$success_count ++;
		} catch ( Exception $e ) {
			$error ++;
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::unsubscribeEmail" . $this->error, LOG_ERR);
		}
		if ($response['error_count'] > 0) {
			$error ++;
			foreach ( $response['errors'] as $err ) {
				$this->errors[] = $err['error'];
			}
		}
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::unsubscribeEmail Error" . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1;
		} else {
			return $succes_count;
		}
	}

	/**
	 * *
	 * TODO A tester
	 * Subscribe the provided email to a list.
	 * By default this sends a confirmation email - you will not see new members until the link contained in it is clicked!
	 *
	 * @param string $id
	 * @param associative_array $email - email string an email address - for new subscribers obviously this should be used
	 *        - euid string the unique id for an email address (not list related) - the email "id" returned from listMemberInfo, Webhooks, Campaigns, etc.
	 *        - leid string the list email id (previously called web_id) for a list-member-info type call. this doesn't change when the email address changes
	 * @param associative_array $merge_vars - new-email string set this to change the email address. This is only respected on calls using update_existing or when passed to listUpdateMember().
	 *        - groupings array of Interest Grouping structs. Each should contain:
	 *        - id int Grouping "id" from lists/interest-groupings (either this or name must be present) - this id takes precedence and can't change (unlike the name)
	 *        - name string Grouping "name" from lists/interest-groupings (either this or id must be present)
	 *        - groups array an array of valid group names for this grouping.
	 *        - optin_ip string Set the Opt-in IP field. <em>Abusing this may cause your account to be suspended.</em> We do validate this and it must not be a private IP address.
	 *        - optin_time string Set the Opt-in Time field. <em>Abusing this may cause your account to be suspended.</em> We do validate this and it must be a valid date. Use - 24 hour format in <strong>GMT</strong>, eg "2013-12-30 20:30:00" to be safe. Generally, though, anything strtotime() understands we'll understand - <a href="http://us2.php.net/strtotime" target="_blank">http://us2.php.net/strtotime</a>
	 *        - mc_location associative_array Set the member's geographic location either by optin_ip or geo data.
	 *        - latitude string use the specified latitude (longitude must exist for this to work)
	 *        - longitude string use the specified longitude (latitude must exist for this to work)
	 *        - anything string if this (or any other key exists here) we'll try to use the optin ip. NOTE - this will slow down each subscribe call a bit, especially for lat/lng pairs in sparsely populated areas. Currently our automated background processes can and will overwrite this based on opens and clicks.
	 *        - mc_language string Set the member's language preference. Supported codes are fully case-sensitive and can be found <a href="http://kb.sendinblue.com/article/can-i-see-what-languages-my-subscribers-use#code" target="_new">here</a>.
	 *        - mc_notes array of structs for managing notes - it may contain:
	 *        - note string the note to set. this is required unless you're deleting a note
	 *        - id int the note id to operate on. not including this (or using an invalid id) causes a new note to be added
	 *        - action string if the "id" key exists and is valid, an "update" key may be set to "append" (default), "prepend", "replace", or "delete" to handle how we should update existing notes. "delete", obviously, will only work with a valid "id" - passing that along with "note" and an invalid "id" is wrong and will be ignored.
	 * @return associative_array the ids for this subscriber
	 *         - email string the email address added
	 *         - euid string the email unique id
	 *         - leid string the list member's truly unique id
	 */
	function subscribeEmail($listid, $email, $merge_vars = null, $email_type = 'html', $double_optin = true, $update_existing = false, $replace_interests = true, $send_welcome = false) {
		global $conf;

		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

		$error = 0;

		if (empty($listid)) {
			$this->error = 'listid is mandatory';
			dol_syslog(get_class($this) . "::subscribeEmail " . $this->error, LOG_ERR);
			return - 1;
		}
		if (empty($email)) {
			$this->error = '$email is empty';
			dol_syslog(get_class($this) . "::subscribeEmail " . $this->error, LOG_ERR);
			return - 1;
		}

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::subscribeEmail " . $this->error, LOG_ERR);
			return - 1;
		}

		$success_count = 0;
		try {
			$response = $this->sendinblue->put('lists/' . $listid . '/members/' . $this->sendinblue->subscriberHash($email), array(
					'status' => 'subscribed',
					'email_adress' => $email,
					'status_if_new' => 'subscribed'
			));
			$success_count ++;
		} catch ( Exception $e ) {
			$error ++;
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::subscribeEmail" . $this->error, LOG_ERR);
		}

		if ($response['error_count'] > 0) {
			$error ++;
			foreach ( $response['errors'] as $err ) {
				$this->errors[] = $err['error'];
			}
		}
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::subscribeEmail Error" . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1;
		} else {
			return $success_count;
		}
	}

	/**
	 * get SendinBlue campaign status
	 *
	 * @param string $status Status to convert
	 * @param int $mode 1 with picto, 0 only text
	 * @return String status
	 */
	static function getLibStatus($status, $mode = 1) {
		global $langs;

		$langs->load("sendinblue@sendinblue");

		if ($mode == 0) {
			return $langs->trans('SendinBlue' . $status);
		}
		if ($mode == 1) {

			if ($status == 'save' || $status== 'Draft') {
				return img_picto($langs->trans( $status), 'stcomm0') . ' ' . $langs->trans( $status);
			}
			if ($status == 'paused') {
				return img_picto($langs->trans('SendinBlue' . $status), 'stcomm1_grayed') . ' ' . $langs->trans('SendinBlue' . $status);
			}
			if ($status == 'schedule') {
				return img_picto($langs->trans('SendinBlue' . $status), 'stcomm0_grayed') . ' ' . $langs->trans('SendinBlue' . $status);
			}
			if ($status == 'sent' ||$status == 'Sent' ) {
				return img_picto($langs->trans( $status), 'stcomm3') . ' ' . $langs->trans($status);
			}
			if ($status == 'sending') {
				return img_picto($langs->trans('SendinBlue' . $status), 'stcomm2') . ' ' . $langs->trans('SendinBlue' . $status);
			}
		}
	}

	/**
	 * get SendinBlue campaign status
	 *
	 * @param int $mode 1 with picto, 0 only text
	 * @return string status
	 */
	function getSendinBlueCampaignStatus($mode = 1) {
		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::getSendinBlueCampaignStatus " . $this->error, LOG_ERR);
			return - 1;
		}

		$opts['campaign_id'] = $this->sendinblue_id;

		// Call
		try {
			$response = $this->sendinblue->get('campaigns/' . $this->sendinblue_id);
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::getListCampaign " . $this->error, LOG_ERR);
			return - 1;
		}

		if ($mode == 1) {
			return DolSendinBlue::getLibStatus($response['status']);
		} elseif ($mode == 0) {
			return $response['status'];
		}
	}

	/**
	 * Send SendinBlue campaign
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function sendSendinBlueCampaign() {
		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::sendSendinBlueCampaign " . $this->error, LOG_ERR);
			return - 1;
		}

		try {
			$response = $this->sendinblue->post('campaigns/' . $this->sendinblue_id . '/actions/send');
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::sendSendinBlueCampaign " . $this->error, LOG_ERR);
			return - 1;
		}

		return 1;
	}

	/**
	 * Get dolibarr destinaries email
	 *
	 * @param string $returntype populate email_lines with only email, 'toadd' for 'email&type&id'
	 * @return int <0 if KO, >0 if OK
	 */
	function getEmailMailingDolibarr($returntype = 'simple') {
		global $conf;
		$this->email_lines = array();

		$sql = "SELECT mc.email,mc.source_type,mc.source_id";
		$sql .= " FROM " . MAIN_DB_PREFIX . "mailing_cibles as mc";
		$sql .= " WHERE mc.fk_mailing=" . $this->fk_mailing;

		dol_syslog(get_class($this) . "::getEmailMailingDolibarr sql=" . $sql);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				while ( $obj = $this->db->fetch_object($result) ) {
					if ($returntype == 'simple') {
						$this->email_lines[] = $obj->email;
					} elseif ($returntype == 'toadd') {
						$this->email_lines[] = $obj->email . '&' . $obj->source_type . '&' . $obj->source_id;
					}
				}
			}
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::getEmailMailingDolibarr	 " . $this->error, LOG_ERR);
			return - 1;
		}

		return 1;
	}

	/**
	 * Import into dolibarr email
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function importSegmentDestToDolibarr($segment_id) {
		global $conf;

		$error = 0;
		$insertcible = 0;

		$this->db->begin();

		$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'mailing_cibles WHERE fk_mailing=' . $this->fk_mailing;
		dol_syslog(get_class($this) . "::importSegmentDestToDolibarr sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if (! $result) {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::importSegmentDestToDolibarr " . $this->error, LOG_ERR);
			return - 1;
		}

		$this->sendinblue_segmentid = $segment_id;
		$result = $this->getEmailSegment();

		if ($result > 0) {
			// Try to find for each email if it is already into dolibarr as thirdparty or contact
			foreach ( $this->email_lines as $email ) {
				$sql = 'SELECT rowid,nom from ' . MAIN_DB_PREFIX . 'societe WHERE email=\'' . $email . '\'';
				dol_syslog(get_class($this) . "::importSegmentDestToDolibarr sql=" . $sql, LOG_DEBUG);
				$result = $this->db->query($sql);
				if ($result) {
					if ($this->db->num_rows($result)) {
						$obj = $this->db->fetch_object($result);

						$url = '<a href="' . DOL_URL_ROOT . '/societe/soc.php?socid=' . $obj->rowid . '"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/object_company.png" border="0" alt="" title=""></a>';

						$sqlinsert = 'INSERT INTO ' . MAIN_DB_PREFIX . 'mailing_cibles (fk_mailing,fk_contact,lastname,email,statut,source_url,source_id,source_type)';
						$sqlinsert .= 'VALUES (' . $this->fk_mailing . ',0,\'' . $this->db->escape($obj->nom) . '\',\'' . $email . '\',0,\'' . $url . '\',' . $obj->rowid . ',\'thirdparty\')';
					}
					$this->db->free($result);
				}
				$sql = 'SELECT rowid,lastname,firstname from ' . MAIN_DB_PREFIX . 'socpeople WHERE email=\'' . $email . '\'';
				dol_syslog(get_class($this) . "::importSegmentDestToDolibarr sql=" . $sql, LOG_DEBUG);
				$result = $this->db->query($sql);
				if ($result) {
					if ($this->db->num_rows($result)) {
						$obj = $this->db->fetch_object($result);

						$url = '<a href="' . DOL_URL_ROOT . '/contact/card.php?id=' . $obj->rowid . '"><img src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/object_contact.png" border="0" alt="" title=""></a>';

						$sqlinsert = 'INSERT INTO ' . MAIN_DB_PREFIX . 'mailing_cibles (fk_mailing,fk_contact,lastname,firstname,email,statut,source_url,source_id,source_type)';
						$sqlinsert .= 'VALUES (' . $this->fk_mailing . ',' . $obj->rowid . ',\'' . $this->db->escape($obj->lastname) . '\',\'' . $this->db->escape($obj->firstname) . '\',\'' . $email . '\',0,\'' . $url . '\',' . $obj->rowid . ',\'contact\')';
					}
					$this->db->free($result);
				}

				// If not found, no matter into email wihtout thirdparty/contact link
				if (empty($sqlinsert)) {
					$sqlinsert = 'INSERT INTO ' . MAIN_DB_PREFIX . 'mailing_cibles (fk_mailing,fk_contact,lastname,firstname,email,statut,source_url,source_id,source_type)';
					$sqlinsert .= 'VALUES (' . $this->fk_mailing . ',0,\'\',\'\',\'' . $email . '\',0,\'\',NULL,\'file\')';
				}

				if (! empty($sqlinsert)) {
					dol_syslog(get_class($this) . "::importSegmentDestToDolibarr sqlinsert=" . $sqlinsert, LOG_DEBUG);
					$result = $this->db->query($sqlinsert);
					$insertcible ++;
					if (! $result) {
						$this->errors[] = "Error " . $this->db->lasterror();
						$error ++;
					}
				}

				if (! empty($insertcible)) {
					$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'mailing SET nbemail=' . $insertcible . ' WHERE rowid=' . $this->fk_mailing;
					dol_syslog(get_class($this) . "::importSegmentDestToDolibarr sql=" . $sql, LOG_DEBUG);
					$result = $this->db->query($sql);
					if (! $result) {
						$this->errors[] = "Error " . $this->db->lasterror();
						$error ++;
					}
				}

				$sqlinsert = '';
			}

			// Commit or rollback
			if ($error) {
				foreach ( $this->errors as $errmsg ) {
					dol_syslog(get_class($this) . "::importSegmentDestToDolibarr " . $errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
				}
				$this->db->rollback();
				return - 1 * $error;
			} else {
				$this->db->commit();
				return 1;
			}
		} else {
			return - 1;
		}
	}

	/**
	 * Export to list and segments sendinblue only segment from dolibarr email
	 *
	 * @param int $segmentid segment id
	 * @param string $newsegmentname segment name
	 * @param int $resetseg segment
	 * @return int <0 if KO, >0 if OK
	 */
	function exportSegmentDesttoSendinBlue($segmentid, $newsegmentname, $resetseg = 0) {
		global $conf;

		$result = $this->getEmailMailingDolibarr('toadd');
		if ($result < 0) {
			return - 1;
		}
		if (count($this->email_lines)) {

			$result_add_to_list = $this->addEmailToList($this->sendinblue_listid, $this->email_lines);

			$this->sendinblue_segmentid = $segmentid;
			if (! empty($newsegmentname)) {
				$result = $this->createSegment($this->sendinblue_listid, $newsegmentname);
				if ($result < 0) {
					return - 1;
				}
				$this->sendinblue_segmentid = $result;
			}

			$result = $this->updateSegment($this->sendinblue_listid, $this->sendinblue_segmentid, $this->email_lines, $resetseg);
			if ($result < 0) {
				return - 1;
			}
		}

		if ($result_add_to_list < 0) {
			return - 2;
		} else {
			return 1;
		}
	}

	/**
	 * Export to sendinblue only segment from dolibarr email
	 *
	 * @param int $segmentid segment id
	 * @param string $newsegmentname segment name
	 * @param int $resetseg segment
	 * @return int <0 if KO, >0 if OK
	 */
	function exportSegmentOnlyDesttoSendinBlue($segmentid, $newsegmentname, $resetseg = 0) {
		global $conf;

		$result = $this->getEmailMailingDolibarr('toadd');
		if ($result < 0) {
			return - 1;
		}
		if (count($this->email_lines)) {

			$this->sendinblue_segmentid = $segmentid;

			$result = $this->updateSegment($this->sendinblue_listid, $this->sendinblue_segmentid, $this->email_lines, $resetseg);
			if ($result < 0) {
				return - 1;
			}
		}

		if ($result < 0) {
			return - 2;
		} else {
			return 1;
		}
	}

	/**
	 * Create the capaign on SendinBlue
	 *
	 * @param user $user
	 * @return int <0 if KO, >0 if OK
	 */
	function createSendinBlueCampaign($user) {
		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::createSendinBlueCampaign " . $this->error, LOG_ERR);
			return - 1;
		}

		$type = 'regular';
		$recipients = new stdClass();
		$settings = new stdClass();
		$tracking = new stdClass();

		$recipients->segment_opts = new stdClass();
		$recipients->list_id = $this->sendinblue_listid;
		$recipients->segment_opts->match = 'all';

		$conditions = new stdClass();
		$conditions->field = 'static_segment';
		$conditions->op = 'static_is';
		$conditions->value = $this->sendinblue_segmentid;

		$recipients->segment_opts->conditions = array();
		$recipients->segment_opts->conditions = array(
				$conditions
		);

		$settings->subject_line = $this->currentmailing->sujet;

		$settings->reply_to = $this->currentmailing->email_from;
		$settings->from_name = $this->sendinblue_sender_name;
		$settings->authenticate = true;
		$settings->title = $this->currentmailing->titre;
		$tracking->opens = true;
		$tracking->html_clicks = true;

		$content = array(
				'html' => $this->currentmailing->body,
				'plain_text' => $this->currentmailing->body
		);

		if (empty($this->sendinblue_id)) {
			try {

				$response = $this->sendinblue->post('campaigns', array(
						'type' => $type,
						'recipients' => $recipients,
						'settings' => $settings,
						'tracking' => $tracking
				));
			} catch ( Exception $e ) {
				$this->error = $e->getMessage();
				dol_syslog(get_class($this) . "::createSendinBlueCampaign " . $this->error, LOG_ERR);
				return - 1;
			}

			$this->sendinblue_id = $response['id'];
			$r = $this->sendinblue->put('campaigns/' . $this->sendinblue_id . '/content', $content);

			$opts['campaign_id'] = $this->sendinblue_id;
			try {
				$response = $this->sendinblue->get('campaigns/' . $this->sendinblue_id);
			} catch ( Exception $e ) {
				$this->error = $e->getMessage();
				dol_syslog(get_class($this) . "::createSendinBlueCampaign " . $this->error, LOG_ERR);
				return - 1;
			}

			$array_rep = $response['data'];
			$newcampaign = $array_rep[0];

			$this->sendinblue_webid = $newcampaign['web_id'];

			$result = $this->update($user);
			if ($result < 0) {
				return - 1;
			}
		}

		return 1;
	}

	/**
	 * Update destinaies status
	 *
	 * @param user $user
	 * @param int $month_filter month filter
	 * @return int <0 if KO, >0 if OK
	 */
	public function updateSendinBlueAllCampaignStatus($user,$month_filter=0) {
		$sendinbluestatic = new DolSendinBlue($this->db);

		$result=$sendinbluestatic->fetch_all($month_filter);
		if ($result < 0) {
			dol_syslog(get_class($sendinbluestatic) . "::updateSendinBlueAllCampaignStatus " . $sendinbluestatic->error, LOG_ERR);
			return - 1;
		}

		$error = 0;

		if (is_array($sendinbluestatic->lines) && count($sendinbluestatic->lines) > 0) {
			foreach ( $sendinbluestatic->lines as $line ) {
				if ($line->sendinblue_id != null) {

					$this->sendinblue_id = $line->sendinblue_id;
					$this->fk_mailing = $line->fk_mailing;
					$this->updateSendinBlueCampaignStatus($user);
					if ($result < 0) {
						$this->errors[] = $this->error;
						$error ++;
					}
				}
			}
		}

		if (empty($error)) {
			return 1;
		} else {
			if (is_array($this->errors) && count($this->errors) > 0) {
				foreach ( $this->errors as $errmsg ) {
					dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
				}
			}
			return - 1;
		}
	}

	/**
	 * Update destinaies status
	 *
	 * @param user $user
	 * @return int <0 if KO, >0 if OK
	 */
	public function updateSendinBlueCampaignStatus($user) {
		global $conf;

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::updateSendinBlueCampaignStatus " . $this->error, LOG_ERR);
			return - 1;
		}

		$error = 0;

		// get HTML content
		$body_html = '';
		try {

			$response = $this->sendinblue->get('campaigns/' . $this->sendinblue_id . '/content');

			$body_html = $response['html'];
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::updateSendinBlueCampaignStatus " . $this->error, LOG_ERR);
			$error ++;
		}

		// Set Dolibarr campaign with this information from sendinblue
		require_once DOL_DOCUMENT_ROOT . '/comm/mailing/class/mailing.class.php';
		$mailing = new Mailing($this->db);
		$result = $mailing->fetch($this->fk_mailing);
		if ($result < 0) {
			$this->errors[] = "Error class Mailing Dolibarr " . $result . ' ' . $mailing->error;
			$error ++;
		}

		if (! empty($body_html)) {
			$mailing->body = $response['html'];
			$result = $mailing->update($user);
			if ($result < 0) {
				$this->errors[] = "Error class Mailing Dolibarr " . $result . ' ' . $mailing->error;
				$error ++;
			}
		}

		// Call
		try {
			$responsecampaigndt = $this->sendinblue->get('campaigns/' . $this->sendinblue_id);
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::getListCampaign " . $this->error, LOG_ERR);
			return - 1;
		}

		$date_send_text = $responsecampaigndt['send_time'];

		$dt_send_unix = strtotime($date_send_text);

		dol_syslog(get_class($this) . "::getCampaignActivity start " . dol_print_date(dol_now(), 'standard'), LOG_DEBUG);

		$result = $this->getCampaignActivity();

		if ($result < 0) {
			$error ++;
		}

		dol_syslog(get_class($this) . "::getCampaignActivity end " . dol_print_date(dol_now(), 'standard'), LOG_DEBUG);
		if ($this->email_activity[0]['email'] == 'error') {
			$error ++;
		}
		if (empty($error)) {
			if (is_array($this->email_activity) && count($this->email_activity) > 0) {

				foreach ( $this->email_activity as $email_activity ) {
					// Sent
					$result = $this->updateTargetMailingStatus($user, 1, $email_activity['email'], 0, $dt_send_unix);
					if ($result < 0) {
						$error ++;
					}

					// Each activities
					$done_open = false;
					$done_click = false;

					if (count($email_activity['activity']) > 0) {
						foreach ( $email_activity['activity'] as $activities ) {
							// dol_syslog(get_class($this)."::getCampaignActivity activities=".var_export($activities,true), LOG_DEBUG);

							if ($activities['action'] == 'open' && ! $done_open) {
								$result = $this->updateTargetMailingStatus($user, 2, $email_activity['email'], 0, $dt_send_unix);
								if ($result < 0) {
									$error ++;
								}
								$done_open = true;
							}

							if ($activities['action'] == 'click' && ! $done_click) {
								$result = $this->updateTargetMailingStatus($user, 4, $email_activity['email'], 0, $dt_send_unix);
								if ($result < 0) {
									$error ++;
								}
								$done_click = true;
							}
						}
					}
				}
			}
		}

		dol_syslog(get_class($this) . "::updateSendinBlueCampaignStatus" . dol_print_date(dol_now(), 'standard'), LOG_DEBUG);
		try {
			$response = $this->sendinblue->get('reports/' . $this->sendinblue_id . '/sent-to');
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::updateSendinBlueCampaignStatus " . $this->error, LOG_ERR);
			$error ++;
		}
		if (empty($error)) {
			foreach ( $response['sent_to'] as $activity ) {
				if ($activity['status']=='sent') {
					$status=1;
				}
				if ($activity['status']=='hard') {
					$status=5;
				}
				if ($activity['status']=='soft') {
					$status=6;
				}
				$result = $this->updateTargetMailingStatus($user, $status, $activity['email_address'], 0, $dt_send_unix);
				if ($result < 0) {
					$error ++;
				}
			}
		}


		dol_syslog(get_class($this) . "::updateSendinBlueCampaignStatus start unsubscribe " . dol_print_date(dol_now(), 'standard'), LOG_DEBUG);
		// Unsubscribe
		try {
			$response = $this->sendinblue->get('reports/' . $this->sendinblue_id . '/unsubscribed');
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::updateSendinBlueCampaignStatus " . $this->error, LOG_ERR);
			$error ++;
		}
		if (empty($error)) {
			if (! empty($response['unsubscribes'])) {
				foreach ( $response['unsubscribes'] as $activity ) {
					$result = $this->updateTargetMailingStatus($user, 3, $activity['email_address'], 0, $dt_send_unix);
					if ($result < 0) {
						$error ++;
					}
				}
			}
		}


		if (! empty($conf->global->MAILCHIMP_SAVE_ACTIVITY_LOCALY)) {
			// Save email activites into Dolibarr
			// Find each email for this mailing
			$result = $this->getEmailMailingDolibarr('simple');
			if ($result < 0) {
				$error ++;
			}
			if (empty($error) && is_array($this->email_lines) && count($this->email_lines) > 0) {

				// For each mail find the total activites
				foreach ( $this->email_lines as $email ) {
					$result = $this->getEmailcontactActivites($email);

					if ($result < 0) {
						$error ++;
					} else {
						// if activites is found then send it to save it
						if (is_array($this->contactemail_activity) && count($this->contactemail_activity) > 0) {
							require_once 'sendinblueactivites.class.php';
							$contact_activites = new SendinBlueActivites($this->db);
							$contact_activites->fk_mailing = $this->fk_mailing;
							$contact_activites->sendinblue_id = $this->sendinblue_id;
							$result = $contact_activites->saveEmailContactActivites($user, $this->contactemail_activity, $email);
							if ($result < 0) {
								$this->errors[] = $contact_activites->error;
								$error ++;
							}
						}
					}
					// }
				}
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::updateSendinBlueCampaignStatus " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1;
		} else {
			return 1;
		}
	}
	/**
	 * Show if the email you chose is unsubscribed to the list you chose too
	 *
	 * @param $idList = id of the list
	 * @param $email = email of the contact
	 * @return false if KO, true if OK
	 *
	 */
	public function isUnsubscribed($idList, $email) {
		global $conf, $langs;
		$response = $this->sendinblue->get('lists/' . $idList . '/members', array(
				'status' => 'unsubscribed'
		));

		foreach ( $response['members'] as $list_unsub ) {
			if ($email == $list_unsub['email_address']) {

				return true;
			}
		}

		return false;
	}

	/**
	 * Update destinaies status
	 *
	 * @param user $user
	 * @return int <0 if KO, >0 if OK
	 */
	public function updateTargetMailingStatus($user, $status, $email = '', $notrigger = 0, $send_dt = '') {
		global $conf, $langs;

		$error = 0;

		// $this->db->begin();

		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'mailing_cibles ';
		$sql .= " SET statut = " . $status;
		if (! empty($send_dt)) {
			$sql .= " ,date_envoi='" . $this->db->idate($send_dt) . "' ";
		}
		$sql .= " WHERE fk_mailing = " . $this->fk_mailing;
		if (! empty($email)) {
			$sql .= " AND email = '" . $this->db->escape($email) . "'";
		}

		dol_syslog(get_class($this) . "::updateTargetMailingStatus sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if (! $result) {
			$this->errors[] = "Error " . $this->db->lasterror();
			$error ++;
		}

		/*if (! $error)
		 {
		 $object = new DolSendinBlueTargetLine($this->db);
		 $object->id = $this->id;
		 $object->entity = $this->entity;
		 $object->fk_mailing = $this->fk_mailing;
		 $object->sendinblue_id = $this->sendinblue_id;
		 $object->sendinblue_webid = $this->sendinblue_webid;
		 $object->sendinblue_listid = $this->sendinblue_listid;
		 $object->sendinblue_segmentid = $this->sendinblue_segmentid;
		 $object->sendinblue_sender_name = $this->sendinblue_sender_name;
		 $object->fk_user_author = $this->fk_user_author;
		 $object->datec=  $this->datec;
		 $object->fk_user_mod = $this->fk_user_mod;
		 $object->tms = $this->tms;
		 $object->status = $status;
		 $object->email = $email;

		 // Appel des triggers
		 include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
		 $interface=new Interfaces($this->db);
		 $result=$interface->run_triggers('MAILING_TARGET_STATUS_UPDATE',$object,$user,$langs,$conf);
		 if ($result < 0) { $error++; $this->errors=$interface->errors; }
		 // Fin appel triggers
		 }*/

		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::updateTargetMailingStatus " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			// $this->db->rollback();
			return - 1 * $error;
		} else {
			// $this->db->commit();
			return 1;
		}
	}

	/**
	 * Renvoi le libelle d'un statut donne
	 *
	 * @param int $statut statut
	 * @param int $mode long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @return string Label
	 */
	static public function libStatutDest($statut, $mode = 0) {
		global $langs;
		$langs->load('sendinblue@sendinblue');

		if ($mode == 0) {
			if ($statut == - 1)
				return $langs->trans("MailingStatusError");
			if ($statut == 0)
				return $langs->trans("MailingStatusNotSent");
			if ($statut == 1)
				return $langs->trans("MailingStatusSent");
			if ($statut == 2)
				return $langs->trans("SendinBlueStatusOpen");
			if ($statut == 3)
				return $langs->trans("SendinBlueUnsucscribe");
			if ($statut == 4)
				return $langs->trans("SendinBlueClick");
			if ($statut == 5)
				return $langs->trans("SendinBlueHardBounce");
			if ($statut == 6)
				return $langs->trans("SendinBlueSoftBounce");
		}
		if ($mode == 1) {
			if ($statut == - 1)
				return $langs->trans("MailingStatusError");
			if ($statut == 0)
				return $langs->trans("MailingStatusNotSent");
			if ($statut == 1)
				return $langs->trans("MailingStatusSent");
			if ($statut == 2)
				return $langs->trans("SendinBlueOpen");
			if ($statut == 3)
				return $langs->trans("SendinBlueUnsucscribe");
			if ($statut == 4)
				return $langs->trans("SendinBlueClick");
			if ($statut == 5)
				return $langs->trans("SendinBlueHardBounce");
			if ($statut == 6)
				return $langs->trans("SendinBlueSoftBounce");
		}
		if ($mode == 2) {
			if ($statut == - 1)
				return $langs->trans("MailingStatusError") . ' ' . img_error();
			if ($statut == 0)
				return $langs->trans("MailingStatusNotSent");
			if ($statut == 1)
				return $langs->trans("MailingStatusSent") . ' ' . img_picto($langs->trans("MailingStatusSent"), 'statut4');
			if ($statut == 2)
				return $langs->trans("SendinBlueOpen") . ' ' . img_picto($langs->trans("MailingStatusRead"), 'statut6');
			if ($statut == 3)
				return $langs->trans("SendinBlueUnsucscribe") . ' ' . img_picto($langs->trans("SendinBlueUnsucscribe"), 'statut8');
			if ($statut == 4)
				return $langs->trans("SendinBlueClick") . ' ' . img_picto($langs->trans("SendinBlueClick"), 'statut6');
			if ($statut == 5)
				return $langs->trans("SendinBlueHardBounce") . ' ' . img_error();
			if ($statut == 6)
				return $langs->trans("SendinBlueSoftBounce") . ' ' . img_error();
		}
		if ($mode == 3) {
			if ($statut == - 1)
				return $langs->trans("MailingStatusError") . ' ' . img_error();
			if ($statut == 0)
				return $langs->trans("MailingStatusNotSent");
			if ($statut == 1)
				return $langs->trans("MailingStatusSent") . ' ' . img_picto($langs->trans("MailingStatusSent"), 'statut4');
			if ($statut == 2)
				return $langs->trans("SendinBlueOpen") . ' ' . img_picto($langs->trans("MailingStatusRead"), 'statut6');
			if ($statut == 3)
				return $langs->trans("SendinBlueUnsucscribe") . ' ' . img_picto($langs->trans("SendinBlueUnsucscribe"), 'statut8');
			if ($statut == 4)
				return $langs->trans("SendinBlueClick") . ' ' . img_picto($langs->trans("SendinBlueClick"), 'statut6');
			if ($statut == 5)
				return $langs->trans("SendinBlueHardBounce") . ' ' . img_error();
			if ($statut == 6)
				return $langs->trans("SendinBlueSoftBounce") . ' ' . img_error();
		}
		if ($mode == 4) {
			if ($statut == - 1)
				return $langs->trans("MailingStatusError") . ' ' . img_error();
			if ($statut == 0)
				return $langs->trans("MailingStatusNotSent");
			if ($statut == 1)
				return $langs->trans("MailingStatusSent") . ' ' . img_picto($langs->trans("MailingStatusSent"), 'statut4');
			if ($statut == 2)
				return $langs->trans("SendinBlueOpen") . ' ' . img_picto($langs->trans("MailingStatusRead"), 'statut6');
			if ($statut == 3)
				return $langs->trans("SendinBlueUnsucscribe") . ' ' . img_picto($langs->trans("SendinBlueUnsucscribe"), 'statut8');
			if ($statut == 4)
				return $langs->trans("SendinBlueClick") . ' ' . img_picto($langs->trans("SendinBlueClick"), 'statut6');
			if ($statut == 5)
				return $langs->trans("SendinBlueHardBounce") . ' ' . img_error();
			if ($statut == 6)
				return $langs->trans("SendinBlueSoftBounce") . ' ' . img_error();
		}
		if ($mode == 5) {
			if ($statut == - 1)
				return $langs->trans("MailingStatusError") . ' ' . img_error();
			if ($statut == 0)
				return $langs->trans("MailingStatusNotSent");
			if ($statut == 1)
				return $langs->trans("MailingStatusSent") . ' ' . img_picto($langs->trans("MailingStatusSent"), 'statut4');
			if ($statut == 2)
				return $langs->trans("SendinBlueOpen") . ' ' . img_picto($langs->trans("MailingStatusRead"), 'statut6');
			if ($statut == 3)
				return $langs->trans("SendinBlueUnsucscribe") . ' ' . img_picto($langs->trans("SendinBlueUnsucscribe"), 'statut8');
			if ($statut == 4)
				return $langs->trans("SendinBlueClick") . ' ' . img_picto($langs->trans("SendinBlueClick"), 'statut6');
			if ($statut == 5)
				return $langs->trans("SendinBlueHardBounce") . ' ' . img_error();
			if ($statut == 6)
				return $langs->trans("SendinBlueSoftBounce") . ' ' . img_error();
		}
	}

	/**
	 * Get email activites
	 *
	 * @param string $email Email adress
	 * @return int Status
	 */
	public function getEmailcontactActivites($email = '') {
		global $conf;
		if (! empty($email)) {

			$result = $this->getInstanceSendinBlue();
			if ($result < 0) {
				dol_syslog(get_class($this) . "::".__METHOD__." " . $this->error, LOG_ERR);
				return - 1;
			}

			$error = 0;

			$this->contactemail_activity = array();

			// Call
			$response = array();

			// Test if there's too much list, browse only list which contains email
			$res = $this->sendinblue->get('lists');
			if (ctype_digit($conf->global->MAILCHIMP_NB_MAX_LIST)) {
				if ($conf->global->MAILCHIMP_NB_MAX_LIST > 0 && $conf->global->MAILCHIMP_NB_MAX_LIST < count($res['lists'])) {
					$res = $this->sendinblue->get('lists', array(
							'email' => $email
					));
				}
			}

			foreach ( $res['lists'] as $lists ) {

				try {
					$res_array=$this->sendinblue->get('campaigns', array(
							'list_id' => $lists['id']
					));
					if ($res_array['status'] == 500) {
						$this->error = $e->getMessage();
						dol_syslog(get_class($this) . "::".__METHOD__." ". ' API return error 500', LOG_ERR);
						return - 1;
					}

					array_push($response, $res_array);
				} catch ( Exception $e ) {
					$this->error = $e->getMessage();
					dol_syslog(get_class($this) . "::".__METHOD__." ". $this->error, LOG_ERR);
					return - 1;
				}
			}

			foreach ( $response as $campaign ) {
				if (! empty($campaign['campaigns'])) {
					foreach ( $campaign['campaigns'] as $c ) {

						if (is_array($c) && count($c) > 0) {

							$staticsendinblue = new DolSendinBlue($this->db);

							$line = new DolSendinBlueActivitesLine();
							$line->campaign = $c['settings']['title'];
							$line->campaignid = $c['id'];
							$result = $staticsendinblue->fetch_by_sendinblueid($c['id']);

							if ($result < 0) {
								$this->error = $staticsendinblue->error;
								return - 1;
							}
							$line->fk_mailing = $staticsendinblue->fk_mailing;
							$succes_count = 0;

							if (! empty($line->campaignid) && ! empty($line->fk_mailing)) {
								// Call
								try {
									$responseact = $this->sendinblue->get('reports/' . $line->campaignid . '/email-activity/' . $this->sendinblue->subscriberHash($email));

									if ($responseact['status'] != 404) {
										$succes_count ++;
									}
								} catch ( Exception $e ) {
									$this->error = $e->getMessage();
									dol_syslog(get_class($this) . "::getEmailcontactActivites " . $this->error, LOG_ERR);
									return - 1;
								}

								if ($succes_count > 0) {

									if ($responseact['data'][0]['member']['status'] == 'unsubscribed') {
										$line->activites = array(
												0 => array(
														'action' => $responseact['data'][0]['member']['status'],
														'timestamp' => $responseact['data'][0]['member']['timestamp']
												)
										);
									} elseif ($responseact['data'][0]['member']['status'] == 'cleaned') {
										// If cleand found the status into mailing_cibles
										$sql = 'SELECT statut FROM ' . MAIN_DB_PREFIX . 'mailing_cibles ';
										$sql .= " WHERE fk_mailing = " . $line->fk_mailing;
										$sql .= " AND email = '" . $this->db->escape($email) . "'";

										dol_syslog(get_class($this) . "::getEmailcontactActivites sql=" . $sql, LOG_DEBUG);
										$resql = $this->db->query($sql);
										if ($resql) {
											if ($this->db->num_rows($resql)) {
												$objstatus = $this->db->fetch_object($resql);
												$objstatuslibel = DolSendinBlue::libStatutDest($objstatus->statut);
												$line->activites = array(
														0 => array(
																'action' => $objstatuslibel,
																'timestamp' => $responseact['data'][0]['member']['timestamp']
														)
												);
											}
										} else {
											$this->error = "Error " . $this->db->lasterror();
											dol_syslog(get_class($this) . "::getEmailcontactActivites " . $this->error, LOG_ERR);
											return - 1;
										}
									} elseif (count($responseact['activity']) == 0) {
										// If no activity found the status into mailing_cibles
										$sql = 'SELECT statut FROM ' . MAIN_DB_PREFIX . 'mailing_cibles ';
										$sql .= " WHERE fk_mailing = " . $line->fk_mailing;
										$sql .= " AND email = '" . $this->db->escape($email) . "'";

										dol_syslog(get_class($this) . "::getEmailcontactActivites sql=" . $sql, LOG_DEBUG);
										$resql = $this->db->query($sql);
										if ($resql) {
											if ($this->db->num_rows($resql)) {
												$objstatus = $this->db->fetch_object($resql);
												$objstatuslibel = DolSendinBlue::libStatutDest($objstatus->statut);
												$line->activites = array(
														0 => array(
																'action' => $objstatuslibel,
																'timestamp' => $c['send_time']
														)

												);
											}
										} else {
											$this->error = "Error " . $this->db->lasterror();
											dol_syslog(get_class($this) . "::getEmailcontactActivites " . $this->error, LOG_ERR);
											return - 1;
										}
									} else {
										$line->activites = $responseact['activity'];
									}
								}

								$this->contactemail_activity[] = $line;
							}
							$this->contactemail_activity = array_reverse($this->contactemail_activity);
						}
					}
				}
			}
		}
	}

	/**
	 * Return URL Link
	 *
	 * @return string with URL
	 */
	function getNomUrl() {
		require_once DOL_DOCUMENT_ROOT . '/comm/mailing/class/mailing.class.php';
		$object = new Mailing($this->db);
		$result = $object->fetch($this->fk_mailing);

		$result = '<a href="' . dol_buildpath('/sendinblue/sendinblue/sendinblue.php', 1) . '?id=' . $object->id . '">';
		$result .= $object->titre;
		$result .= '</a>';

		return $result;
	}

	/**
	 * Return activity mail from database
	 *
	 * @param string $email Email address
	 * @return int Status
	 */
	public function getEmailcontactActivitesFromDB($email = '') {
		$error = 0;

		require_once 'sendinblueactivites.class.php';
		$contact_activites = new SendinBlueActivites($this->db);
		$contact_activites->email = $email;
		$result = $contact_activites->fetchEmailContactActivites($email);
		if ($result < 0) {
			$this->errors[] = $contact_activites->error;
			$error ++;
		}

		if (empty($error)) {
			$this->contactemail_activity = $contact_activites->lines;
			return 1;
		} else {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return - 1;
		}
	}
}
class DolSendinBlueeMailLine
{
	var $id;
	var $email;
	var $thirdparty;
	var $contactfullname;
	var $type;

	/**
	 * Constructor
	 */
	function __construct() {
		return 0;
	}
}
class DolSendinBlueTargetLine extends DolSendinBlue
{
	var $email;
	var $status;

	/**
	 * Constructor
	 */
	function __construct($db) {
		parent::__construct($db);
		return 0;
	}
}
class DolSendinBlueActivitesLine
{
	public $campaign;
	public $campaignid;
	public $fk_mailing;
	public $activites = array();

	/**
	 * Constructor
	 */
	function __construct() {
		return 0;
	}
}
class DolSendinBlueLine
{
	public $id;
	public $entity;
	public $fk_mailing;
	public $sendinblue_id;
	public $sendinblue_webid;
	public $sendinblue_listid;
	public $sendinblue_segmentid;
	public $sendinblue_sender_name;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';

	/**
	 * Constructor
	 */
	function __construct() {
		return 0;
	}
}
