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
dol_include_once('/sendinblue/class/Sendinblue.class.php');

/**
 * Put here description of your class
 */
class DolSendinBlue extends CommonObject
{
	public $db; // !< To store db handler
	public $error; // !< To return error code (or message)
	public $errors = array(); // !< To return several error codes (or messages)
	public $element = 'sendinblue'; // !< Id that identify managed objects
	public $table_element = 'sendinblue'; // !< Name of table without prefix where object is stored
	/** @var SendinBlue $sendinblue */
    public $sendinblue; // API Object
	public $email_lines = array();
	public $listdest_lines = array();
	public $listsegment_lines = array();
	public $listcampaign_lines = array();
	public $total_campaign_records;
	public $listlist_lines = array();
	public $email_activity = array();
	public $contactemail_activity = array();
	public $id;
	public $entity;
	public $fk_mailing;

	public $target_added;
	public $target_updated;
	public $target_deleted;

	public $sendinblue_id;
	public $sendinblue_webid;
	public $sendinblue_listid;
	public $sendinblue_segmentid;
	public $sendinblue_sender_name;
	public $fk_user_author;
	public $datec = '';
	public $fk_user_mod;
	public $tms = '';
	public $currentmailing;
	public $lines = array();

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
		$sql .= " " . (! isset($this->fk_mailing) ? 'NULL' : $this->fk_mailing ) . ",";
		$sql .= " " . (! isset($this->sendinblue_id) ? 'NULL' : "'" . $this->sendinblue_id . "'") . ",";
		$sql .= " " . (! isset($this->sendinblue_webid) ? 'NULL' : "'" . $this->sendinblue_webid . "'") . ",";
		$sql .= " " . (! isset($this->sendinblue_listid) ? 'NULL' : "'" . $this->db->escape($this->sendinblue_listid) . "'") . ",";
		$sql .= " " . (! isset($this->sendinblue_segmentid) ? 'NULL' : "'" . $this->db->escape($this->sendinblue_segmentid) . "'") . ",";
		$sql .= " " . (! isset($this->sendinblue_sender_name) ? 'NULL' : "'" . $this->db->escape($this->sendinblue_sender_name) . "'") . ",";

		$sql .= " '" . $user->id . "',";
		$sql .= " '" . $this->db->idate(dol_now()) . "',";
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
			if ($this->db->num_rows($resql) > 0) {
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


				$this->db->free($resql);
				return 1;
			}

			return 0;
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



	private function getInstanceSendinBlue() {
			global $conf, $langs;

			if (! is_object($this->sendinblue)) {
				if (empty($conf->global->SENDINBLUE_API_KEY)) {
					$langs->load("sendinblue@sendinblue");
					$this->error = $langs->trans("SendinBlueAPIKeyNotSet");
					dol_syslog(get_class($this) . "::getInstanceSendinBlue " . $this->error, LOG_ERR);
					return - 1;
				}

				$timeout = !empty($conf->global->SENDINBLUE_API_TIMEOUT) ? $conf->global->SENDINBLUE_API_TIMEOUT * 1000 : '';
				$sendinblue = new SendinBlue('https://api.sendinblue.com/v3', $conf->global->SENDINBLUE_API_KEY, $timeout);
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
	 *        string sender optional - only lists that have a default from name matching this
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
			if(!empty($filters)){
				$response = $this->sendinblue->get_list($filters);
			}
			else {
				$response = $this->sendinblue->get_lists(array());
			}
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			$error ++;
		}

		if (! empty($error)) {
			dol_syslog(get_class($this) . "::getListDestinaries " . $this->error, LOG_ERR);
			return - 1;
		} else {
			if (!empty($filters['id'])) {
				$this->listdest_lines = array(
					'data' => array($response)
				);
			} else {
				if(!empty($response['count'])) {
					$nb_lists = $response['count'];
					if($nb_lists > 100) {
						$response = $this->sendinblue->get('contacts/lists');
					}
				}

				$this->listdest_lines = array();
				if (!empty($response['lists'])) {
					$this->listdest_lines = array(
						'data' => $response['lists']
					);
				}
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
			$response = $this->sendinblue->get_list(array("id"=>$id));
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


	function getSMTPDetails() {
		$error = 0;

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::getSMTPDetails " . $this->error, LOG_ERR);
			return - 1;
		}

		// Call
		try {
			$response = $this->sendinblue->get_smtp_details();
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			$error ++;
		}

		if (! empty($error)) {
			dol_syslog(get_class($this) . "::getSMTPDetails " . $this->error, LOG_ERR);
			return - 1;
		} else {
			return $response['data']['relay_data'];
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
			$response = $this->sendinblue->get_user($email);
			if(!empty($response['code'])){
				$this->error = 'SendinBlue error: ' . $response['message'];
				return -1;
			}

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
			$this->listlist_lines = $response['listIds'];
			return $response;
		}
	}




	function delete_user($email){
		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::getListForEmail " . $this->error, LOG_ERR);
			return - 1;
		}

		// Call
		try {
			$response = $this->sendinblue->delete_user($email);
		} catch ( Exception $e ) {
			if (get_class($e) != 'SendinBlue_List_NotSubscribed') {
				$this->error = $e->getMessage();
				$error ++;
			}
		}
	}



	/**
	 * Retraive email from sendinblue List
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function getEmailList() {
		global $conf;
		$this->getInstanceSendinBlue();
		$error = 0;

		$this->email_lines = array();
		$list = $this->sendinblue->get_list(array('id'=>$this->sendinblue_listid));
		if(!empty($list['totalSubscribers'])){
			$subscribers = ceil($list['totalSubscribers']/500);
		}
		for($i=1;$i<=$subscribers;$i++){
			$response = $this->sendinblue->display_list_users($this->sendinblue_listid, array('page'=>$i,'page_limit'=>500));
			$this->email_lines =array_merge($this->email_lines,$response['contacts']);
		}

		if(!empty($response)){
			$emailsegment = 1;
		} else {
			$emailsegment = -1;
		}

		return $emailsegment;
	}

	/**
	 * Retraive activty for a campaign
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function getCampaignActivity($response=null) {
		global $conf;

        if ($response === null)
		    $response = $this->sendinblue->get_campaign($this->sendinblue_id);


        foreach ($response['recipients']['lists'] as $listid){
			$r = $this->sendinblue->display_list_users($listid,
				array(
					"page" => 1,
					"page_limit" => 500
				)
			);

			// TODO revoir la logique de ce truc
			foreach($r['contacts'] as $e){
				$listuser = $this->sendinblue->get_user($e['email']);

				$status = "";

				// Bon j'ai repris "l'ancienne" Methode et j'ai factorisé mais franchement c'est bizarre comment c'est géré
				$TCampStatus = array(
					'hard_bounces' => 'hard_bounce', // TODO n'existe plus en V3 ? Comment les detecter maintenant ?
					'soft_bounces' => 'soft_bounces', // TODO n'existe plus en V3 ? Comment les detecter maintenant ?

					'unsubscriptions' => 'unsubscribe',
					'spam' => 'spam',
					'opened' => 'opened',
					'click' => 'click',
				);

				foreach ($TCampStatus as $campStatus => $dolStatus)
				{
					if(empty($status)) {
						if (isset($listuser['statistics'][$campStatus])) {
							$TData = $listuser['statistics'][$campStatus];
							if($campStatus == 'unsubscriptions'){
								$TData = $listuser['statistics']['unsubscriptions']['userUnsubscription'];
							}

							if(is_array($TData)){
								foreach ($TData as $opened) {
									if ($opened['campaignId'] == $this->sendinblue_id) {
										$status = $dolStatus;
										break;
									}
								}
							}
						}
					}else{ break; }
				}

				$this->email_activity[] = array('email'=>$e['email'], 'activity'=>$status);
			}
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
	function updateList($listid,  $emailtoadd) {
		$error = 0;

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::updateSegment " . $this->error, LOG_ERR);
			return - 1;
		}

		/*if (! empty($resetseg)) {
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
		}*/

		$batch = array();
		$total_added = 0;
		$nb_emailadded = 1;
		foreach ( $emailtoadd as $email ) {
			//var_dump($email);exit;
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
	 * @param 	int		$page			Page to get
	 * @param 	int		$page_limit		Nb records to get
	 * @return	int						<0 if KO, 1 if OK
	 */
	function getListCampaign($page = 1, $page_limit = 10)
	{
		$this->total_campaign_records = 0;
		$this->listcampaign_lines = array();

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::getListCampaign " . $this->error, LOG_ERR);
			return -1;
		}

		// Clean parameters
		$page = ($page > 0 ? ($page <= 500 ? $page : 500) : 0);
		$page_limit = ($page_limit > 0 ? ($page_limit <= 1000 ? $page_limit : 1000) : 1) + 1;
		$offset = $page * ($page_limit - 1);

		// Call
		try {
			$options = array("type" => "classic", "offset" => $offset, "limit" => $page_limit);
			$responseSendinBlue = $this->sendinblue->get_campaigns($options);
			if (!empty($responseSendinBlue['code'])) {
				$this->error = $responseSendinBlue['message'];
				$this->errors[] = $this->error;
				return -1;
			}
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::getListCampaign " . $this->error, LOG_ERR);
			return -1;
		}

		$this->total_campaign_records = $responseSendinBlue['count'];
		$this->listcampaign_lines = $responseSendinBlue['campaigns'];

		return 1;
	}

	/**
	 *  Set object value with send in blue campaign get
	 *
	 * @param 	int			$campaign_id	Campaign ID to get
	 * @return	int|array					<0 if KO, Data campaign if OK
	 */
	function getCampaignData($campaign_id)
	{
		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(__METHOD__ . " " . $this->error, LOG_ERR);
			return -1;
		}

		// Call
		try {
			$response = $this->sendinblue->get_campaign($campaign_id);
			if(!empty($response['code'])){
				$this->error = $response['message'];
				return -1;
			}
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			dol_syslog(__METHOD__ . " " . $this->error, LOG_ERR);
			return -1;
		}

		return $response;
	}

	/**
	 *  Get an array with list associated to a campaign
	 *
	 * @param 	int			$campaign_id	Campaign ID
	 * @return	int|array					<0 if KO, array if OK
	 */
	function getCampaignListArray($campaign_id)
	{
		// Get campaign sendinblue data
		$campaign_data = $this->getCampaignData($campaign_id);
		if (!is_array($campaign_data)) {
			return -1;
		}

		$list_array = array();
		if (!empty($campaign_data['recipients']['lists'])) {
			foreach ($campaign_data['recipients']['lists'] as $list_id) {
				// Call
				try {
					$response = $this->sendinblue->get_list(array("id" => $list_id));
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					return -1;
				}

				$list_array[$list_id] = $response['name'];
			}
		}

		return $list_array;
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


		return 1;
	}

	/**
	 * Add email to list
	 *
	 * @param int $listid to add
	 * @param array $array_email add
	 * @param bool $copy 	Copy exact of dolibarr send to sendinblue
	 * @return int <0 if KO, >0 if OK
	 */
	function addEmailToList($listid = 0, $array_email = array(), $copy = false) {
		global $conf, $db;

		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

        $extrafields_societe = new ExtraFields($db);
        $extrafields_societe->fetch_name_optionals_label('societe');
        $extrafields_contact = new ExtraFields($db);
        $extrafields_contact->fetch_name_optionals_label('socpeople');

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

        $TExtSociete = '';
		$TExtContact = '';
		if(!empty($conf->global->SENDINBLUE_EXTRAFIELDS_SOCIETE_ALLOWED)) $TExtSociete = explode(',', $conf->global->SENDINBLUE_EXTRAFIELDS_SOCIETE_ALLOWED);
		if(!empty($conf->global->SENDINBLUE_EXTRAFIELDS_CONTACT_ALLOWED)) $TExtContact = explode(',', $conf->global->SENDINBLUE_EXTRAFIELDS_CONTACT_ALLOWED);
		foreach ( $array_email as $email ) {

			// email is formated like email&type&id where type=contact for contact or thirdparty and id is the id of contact or thridparty
			$tmp_array = explode('&', $email);
			$merge_vars = new stdClass();
            $merge_extrafields = new stdClass();
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

						if (!empty($TExtContact))
                        {
                            foreach ($extrafields_contact->attribute_label as $code => $label)
                            {
                                if (in_array($code, $TExtContact)) $merge_extrafields->{$code} = $contactstatic->array_options['options_'.$code];
                            }
                        }
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

						if (!empty($TExtSociete))
                        {
                            foreach ($extrafields_societe->attribute_label as $code => $label)
                            {
                                if (in_array($code, $TExtSociete)) $merge_extrafields->{$code} = $socstatic->array_options['options_'.$code];
                            }
                        }
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
						'merge_vars' => $merge_vars,
                        'merge_extrafields' => $merge_extrafields
				);
			}
		}

		dol_syslog(get_class($this).'::addEmailToList var_export($email_to_add)='.var_export($email_to_add,true), LOG_DEBUG);
		dol_syslog(get_class($this) . '::addEmailToList count($email_to_add)=' . count($email_to_add), LOG_DEBUG);
		$batch_email_to_add_error=array();

		foreach ( $email_to_add as $key_batch=>$batch_email_to_add ) {

			dol_syslog(get_class($this) . '::addEmailToList $key_batch=' . $key_batch, LOG_DEBUG);
			dol_syslog(get_class($this) . '::addEmailToList count($batch_email_to_add)=' . count($batch_email_to_add), LOG_DEBUG);

			dol_syslog(get_class($this) . '::addEmailToList start batchSubscribe ' . dol_print_date(dol_now(), 'standard'), LOG_DEBUG);

			$contactToAddInList = array();
			$contactData = array();

			foreach($batch_email_to_add as $email){
				$contactToAddInList[] = $email['email_address'];

				// Call
				$data = array(
					"email" => $email['email_address'],
					"attributes" => array(
						"FIRST_NAME" => $email['merge_vars']->FNAME,
						"LAST_NAME" => $email['merge_vars']->LNAME,
					),
					"listIds" => array(intval($listid))
				);

				if (!empty($email['merge_extrafields']))
				{
					foreach ($email['merge_extrafields'] as $code => $val)
					{
						// strtoupper car sendinblue force les majuscules et remplace les espaces par des _
						$data['attributes'][strtoupper($code)] = $val;
					}
				}

				// If update fail we will create contact with this info
				$contactData[strtolower($email['email_address'])] = $data;
			}

			// Retrieve email in sendinblue
			if ($this->fk_mailing > 0 && !$copy) {
				$result = $this->getEmailMailingDolibarr('simple', true);
			} else {
				$result = $this->getEmailMailingSendinblue($listid);
			}
			if ($result < 0) {
				$error++;
			} else {
				$contactToDelInList = array_values(array_diff(array_map('strtolower', $this->email_lines), array_map('strtolower', $contactToAddInList)));
				$contactToAddInList = array_values(array_diff(array_map('strtolower', $contactToAddInList), array_map('strtolower', $this->email_lines)));

				try {
					$result = $this->sendinblue->addExistingContactsToLists($this->db, $this->fk_mailing, $listid, array('emails' => $contactToAddInList), $contactData);
					if ($result < 0) {
						$batch_email_to_add_error=$contactToAddInList;
						$this->errors[] = '----- START ERRORS FROM addExistingContactsToLists -----';
						$this->errors = array_merge($this->errors, $this->sendinblue->errors);
						$this->errors[] = '----- END ERRORS FROM addExistingContactsToLists -----';
						$error ++;
					}
				} catch ( Exception $e ) {
					$this->errors[] = $e->getMessage();
					$batch_email_to_add_error=$contactToAddInList;
					$error ++;
				}

				if ($copy) {
					try {
						$result = $this->sendinblue->delExistingContactsToLists($listid, array('emails' => $contactToDelInList));
						if ($result < 0) {
							$this->errors = array_merge($this->errors, $this->sendinblue->errors);
							$batch_email_to_add_error=$contactToAddInList;
							$error ++;
						}
					} catch ( Exception $e ) {
						$this->errors[] = $e->getMessage();
						$batch_email_to_add_error=$contactToAddInList;
						$error ++;
					}
				}
			}

			dol_syslog(get_class($this) . '::addEmailToList end batchSubscribe ' . dol_print_date(dol_now(), 'standard'), LOG_DEBUG);
		}
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::addEmailToList Error: " . $errmsg, LOG_ERR);
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

		self::toLowerCase($array_email);
		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::deleteEmailFromList " . $this->error, LOG_ERR);
			return - 1;
		}
		$success_count = 0;


		try {
			$response = $this->sendinblue->delete_users_list(array('id'=>$listid,'emails'=>$array_email));
			$success_count ++;
		} catch ( Exception $e ) {
			$error ++;
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::getListCampaign " . $this->error, LOG_ERR);
		}

		if (isset($response['code']) > 0) {
			$error ++;
			$this->errors[] = $response['message'];
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
	 * Change mail adresses to lower case
	 * @param array $TMail
	 */
	static function toLowerCase(&$TMail) {
		if(!empty($TMail)) {
			foreach($TMail as &$email) $email=strtolower($email);
		}
	}

	/**
	 * Unsubscribe the given email address from the list
	 *
	 * @param string $id
	 * @param array $email - email string an email address
	 *        - euid string the unique id for an email address (not list related) - the email "id" returned from listMemberInfo, Webhooks, Campaigns, etc.
	 *        - leid string the list email id (previously called web_id) for a list-member-info type call. this doesn't change when the email address changes
	 * @param boolean $delete_member
	 * @param boolean $send_goodbye
	 * @param boolean $send_notify
	 * @return array with a single entry:
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
	 * @param array $email - email string an email address - for new subscribers obviously this should be used
	 *        - euid string the unique id for an email address (not list related) - the email "id" returned from listMemberInfo, Webhooks, Campaigns, etc.
	 *        - leid string the list email id (previously called web_id) for a list-member-info type call. this doesn't change when the email address changes
	 * @param array $merge_vars - new-email string set this to change the email address. This is only respected on calls using update_existing or when passed to listUpdateMember().
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
	 * @return array the ids for this subscriber
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

	function createList($namelist)
	{
		global $conf;

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::getListDestinaries " . $this->error, LOG_ERR);
			return -1;
		}

		$response = $this->sendinblue->create_list(array("name" => (!empty($conf->global->SENDINBLUE_PREFIXNEWLISTONSENDINBLUE) ? $conf->global->SENDINBLUE_PREFIXNEWLISTONSENDINBLUE : '') . $namelist, "folderId" => 1));
		if (!empty($response['code'])) {
			$this->error = $response['message'];
			$this->errors[] = $this->error;
			return -1;
		}

		return $response['id'];
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
			if ($status == 'Sent and Archived'  ) {
				return img_picto($langs->trans( $status), 'stcomm3') . ' ' . $langs->trans($status);
			}
			if ($status == 'sending') {
				return img_picto($langs->trans('SendinBlue' . $status), 'stcomm2') . ' ' . $langs->trans('SendinBlue' . $status);
			}
			if ($status == 'Scheduled') {
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
			$response = $this->sendinblue->get_campaign($this->sendinblue_id);
				$this->sendinblue_webid = $response;
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
	function sendSendinBlueCampaign()
	{
		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::sendSendinBlueCampaign " . $this->error, LOG_ERR);
			return -1;
		}

		try {
			if ($this->sendinblue_listid > 0) {
				// Get campaign sendinblue data
				$campaign_data = $this->getCampaignData($this->sendinblue_id);
				if (!is_array($campaign_data)) {
					return -1;
				}

				// Add the recipient list in campaign if not present
				$current_recipients_list = is_array($campaign_data['recipients']['lists']) ? $campaign_data['recipients']['lists'] : array();
				if (!in_array($this->sendinblue_listid, $current_recipients_list)) {
					$data = array(
						'recipients' => array(
							'listIds' => array_flip(array_flip(array_merge($current_recipients_list, array($this->sendinblue_listid))))
						),
					);

					$response = $this->sendinblue->updateCampaign($this->sendinblue_id, $data);
					if (!empty($response['code'])) {
						$this->error = $response['message'];
						return -1;
					}
				}
			}

			$response = $this->sendinblue->sendCampaign($this->sendinblue_id);
			if(!empty($response['code'])){
				$this->error = $response['message'];
				return -1;
			}
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::sendSendinBlueCampaign " . $this->error, LOG_ERR);
			return -1;
		}

		return 1;
	}

	/**
	 * Get dolibarr destinaries email
	 *
	 * @param	string 	$returntype 	populate email_lines with only email, 'toadd' for 'email&type&id'
	 * @param	bool	$linked			Get only liked to sendinblue (null: all)
	 * @return	int						<0 if KO, >0 if OK
	 */
	function getEmailMailingDolibarr($returntype = 'simple', $linked = null)
	{
		$this->email_lines = array();

		$sql = "SELECT mc.email,mc.source_type,mc.source_id";
		$sql .= " FROM " . MAIN_DB_PREFIX . "mailing_cibles as mc";
		$sql .= " WHERE mc.fk_mailing=" . $this->fk_mailing;
		if (isset($linked)) $sql .= " AND mc.sendinblue_status = " . ($linked ? 1 : 0);

		dol_syslog(get_class($this) . "::getEmailMailingDolibarr sql=" . $sql);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				while ( $obj = $this->db->fetch_object($result) ) {
					if ($returntype == 'simple') {
						$this->email_lines[] = strtolower($obj->email);
					} elseif ($returntype == 'toadd') {
						$this->email_lines[] = strtolower($obj->email) . '&' . $obj->source_type . '&' . $obj->source_id;
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
	 * Get sendinblue subscribers email
	 *
	 * @param	int		$segment_id			List sendinblue ID
	 * @param	bool	$no_blacklisted		Don't get blacklisted subscribers
	 * @return	int							<0 if KO, >0 if OK
	 */
	function getEmailMailingSendinblue($segment_id, $no_blacklisted = false)
	{
		$this->email_lines = array();
		$block_size = 200; // max 500

		// Get subscribers
		try {
			$result = $this->getInstanceSendinBlue();
			if ($result < 0) {
				return -1;
			}

			$offset = 0;
			do {
				// Get contacts of the list
				$filter = array('offset' => $offset, 'limit' => $block_size);
				$response = $this->sendinblue->display_list_users($segment_id, $filter);
				if (!$this->sendinblue->analyseResponseResult($response)) {
					$this->error = $this->sendinblue->error;
					$this->errors = $this->sendinblue->errors;
					dol_syslog(__METHOD__ . " - Get SendinBlue subscribers - Error: " . $this->errorsToString(), LOG_ERR);
					return -1;
				}

				// Add email into the list
				if (!empty($response['contacts'])) {
					foreach ($response['contacts'] as $d) {
						// dont get blacklisted email
						if ($d['emailBlacklisted'] && $no_blacklisted) continue;

						$email = strtolower($d['email']);
						$this->email_lines[$email] = $email;
					}
				}

				$offset += $block_size;
			} while($offset <= $response['count']);
		} catch (Exception $e) {
			$this->error = "Error " . $e->getMessage();
			dol_syslog(__METHOD__ . " - Get SendinBlue subscribers - Error: " . $this->error, LOG_ERR);
			return -1;
		}

		return 1;
	}

	/**
	 * Get sendinblue subscribers email
	 *
	 * @return	int						<0 if KO, >0 if OK
	 */
	function getEmailMailingNotSynchronised()
	{
		$this->email_lines = array();

		$result = $this->getEmailMailingDolibarr('simple', false);
		if ($result < 0) {
			return -1;
		} else {
			$this->email_lines = array_map('strtolower', $this->email_lines);
		}

		return 1;
	}

	/**
	 * Import into dolibarr email
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	function importSegmentDestToDolibarr($segment_id, $copy = false)
	{
		global $conf, $user;

		// Get existing targets
		$sql = "SELECT rowid, fk_mailing, fk_contact, lastname, firstname, email, statut, source_url, source_id, source_type FROM " . MAIN_DB_PREFIX . "mailing_cibles WHERE fk_mailing = " . $this->fk_mailing;
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(__METHOD__ . " - Get existing targets - Error: " . $this->error, LOG_ERR);
			return -1;
		}
		$existing_target_list = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$existing_target_list[strtolower($obj->email)] = array(
				'rowid' => $obj->rowid,
				'fk_mailing' => $obj->fk_mailing,
				'fk_contact' => $obj->fk_contact,
				'lastname' => $obj->lastname,
				'firstname' => $obj->firstname,
				'email' => $obj->email,
				'statut' => $obj->statut,
				'source_url' => $obj->source_url,
				'source_id' => $obj->source_id,
				'source_type' => $obj->source_type,
			);
		}

		// Get SendinBlue subscribers
		$result = $this->getEmailMailingSendinblue($segment_id);
		if ($result < 0) {
			return -1;
		}

		$escaped_email = array();
		foreach ($this->email_lines as $email) {
			$escaped_email[] = $this->db->escape($email);
		}

		$subscribers_list = array();
		// Get existing objects by email
		if (!empty($escaped_email)) {
			// Get thirdparties by email
			$sql = "SELECT rowid, nom, email FROM " . MAIN_DB_PREFIX . "societe WHERE email IN ('" . implode("','", $escaped_email) . "') AND entity IN (" . getEntity('societe') . ")";
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(__METHOD__ . " - Get existing targets - Error: " . $this->error, LOG_ERR);
				return -1;
			}
			if ($this->db->num_rows($resql) > 0) {
				$url_base = dol_buildpath('/societe/card.php', 1);
				$icon = img_object('', 'company');
				while ($obj = $this->db->fetch_object($resql)) {
					$email = strtolower($obj->email);
					$subscribers_list[$email] = array(
						'fk_mailing' => $this->fk_mailing,
						'fk_contact' => 0,
						'lastname' => $obj->nom,
						'firstname' => '',
						'email' => $email,
						'statut' => 0,
						'source_url' => '<a href="' . $url_base . '?socid=' . $obj->rowid . '">' . $icon . '</a>',
						'source_id' => $obj->rowid,
						'source_type' => 'thirdparty',
					);
				}
			}

			// Get contacts by email
			$sql = "SELECT rowid, lastname, firstname, email FROM " . MAIN_DB_PREFIX . "socpeople WHERE email IN ('" . implode("','", $escaped_email) . "') AND entity IN (" . getEntity('socpeople') . ")";
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(__METHOD__ . " - Get existing targets - Error: " . $this->error, LOG_ERR);
				return -1;
			}
			if ($this->db->num_rows($resql) > 0) {
				$url_base = dol_buildpath('/contact/card.php', 1);
				$icon = img_object('', 'contact');
				while ($obj = $this->db->fetch_object($resql)) {
					$email = strtolower($obj->email);
					$subscribers_list[$email] = array(
						'fk_mailing' => $this->fk_mailing,
						'fk_contact' => $obj->rowid,
						'lastname' => $obj->lastname,
						'firstname' => $obj->firstname,
						'email' => $email,
						'statut' => 0,
						'source_url' => '<a href="' . $url_base . '?id=' . $obj->rowid . '">' . $icon . '</a>',
						'source_id' => $obj->rowid,
						'source_type' => 'contact',
					);
				}
			}
		}

		// Get unknown subscribers
		foreach ($this->email_lines as $email) {
			$email = strtolower($email);
			if (!isset($subscribers_list[$email])) {
				$subscribers_list[$email] = array(
					'fk_mailing' => $this->fk_mailing,
					'fk_contact' => 0,
					'lastname' => '',
					'firstname' => '',
					'email' => $email,
					'statut' => 0,
					'source_url' => '',
					'source_id' => 0,
					'source_type' => 'file',
				);
			}
		}

		$error = 0;
		$this->db->begin();

		$this->target_added = array();
		$this->target_updated = array();
		$this->target_deleted = array();

		// Add/Update targets
		foreach ($subscribers_list as $email => $target) {
			$email = strtolower($email);
			if (isset($existing_target_list[$email])) {
				// Update target
				$this->target_updated[] = array_merge(array('rowid' => $existing_target_list[$email]['rowid']), $target);
				$sql = "UPDATE " . MAIN_DB_PREFIX . "mailing_cibles SET sendinblue_status = 1" .
					", fk_mailing = " . $this->db->escape($target['fk_mailing']) .
					", fk_contact = " . ($target['fk_contact'] > 0 ? $this->db->escape($target['fk_contact']) : 0) .
					", lastname = '" . (!empty($target['lastname']) ? $this->db->escape($target['fk_contact']) : '') . "'" .
					", firstname = '" . (!empty($target['firstname']) ? $this->db->escape($target['firstname']) : '') . "'" .
					", email = '" . $this->db->escape($target['email']) . "'" .
					", statut = " . (!empty($target['statut']) ? $this->db->escape($target['statut']) : 0) .
					", source_url = '" . (!empty($target['source_url']) ? $this->db->escape($target['source_url']) : '') . "'" .
					", source_id = " . ($target['source_id'] > 0 ? $this->db->escape($target['source_id']) : "NULL") .
					", source_type = '" . (!empty($target['source_type']) ? $this->db->escape($target['source_type']) : "file") . "'" .
					" WHERE rowid = " . $existing_target_list[$email]['rowid'];
			} else {
				// Add target
				$this->target_added[] = $target;
				$sql = "INSERT INTO " . MAIN_DB_PREFIX . "mailing_cibles (fk_mailing, fk_contact, lastname, firstname, email, statut, source_url, source_id, source_type, sendinblue_status)" .
					" VALUES (" . $this->db->escape($target['fk_mailing']) .
					", " . ($target['fk_contact'] > 0 ? $this->db->escape($target['fk_contact']) : 0) .
					", '" . (!empty($target['lastname']) ? $this->db->escape($target['fk_contact']) : '') . "'" .
					", '" . (!empty($target['firstname']) ? $this->db->escape($target['firstname']) : '') . "'" .
					", '" . $this->db->escape($target['email']) . "'" .
					", " . (!empty($target['statut']) ? $this->db->escape($target['statut']) : 0) .
					", '" . (!empty($target['source_url']) ? $this->db->escape($target['source_url']) : '') . "'" .
					", " . ($target['source_id'] > 0 ? $this->db->escape($target['source_id']) : "NULL") .
					", '" . (!empty($target['source_type']) ? $this->db->escape($target['source_type']) : "file") . "'" .
					", 1" .
					")";
			}
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(__METHOD__ . " - Add/update target - Error: " . $this->error, LOG_ERR);
				$error++;
				break;
			}
		}

		// Delete targets
		if (!$error) {
			foreach ($existing_target_list as $email => $target) {
				$email_key = strtolower($email);
				if (!isset($subscribers_list[$email_key])) {
					if (!empty($copy)) {
						// Delete target
						$this->target_deleted[] = $target;
						$sql = "DELETE FROM " . MAIN_DB_PREFIX . "mailing_cibles WHERE rowid = " . $target['rowid'];
						$resql = $this->db->query($sql);
						if (!$resql) {
							$this->error = "Error " . $this->db->lasterror();
							dol_syslog(__METHOD__ . " - Delete target - Error: " . $this->error, LOG_ERR);
							$error++;
							break;
						}
					} else {
						// Delete linked status
						$sql = "UPDATE " . MAIN_DB_PREFIX . "mailing_cibles SET" .
							"  sendinblue_status = 0" .
							" WHERE rowid = " . $existing_target_list[$email]['rowid'];
						$resql = $this->db->query($sql);
						if (!$resql) {
							$this->error = "Error " . $this->db->lasterror();
							dol_syslog(__METHOD__ . " - Delete Sendinblue status - Error: " . $this->error, LOG_ERR);
							$error++;
							break;
						}
					}
				}
			}
		}

		// Update target count on emailing
		if (!$error) {
			$nb = 0;
			$sql = "SELECT COUNT(*) AS nb FROM " . MAIN_DB_PREFIX . "mailing_cibles WHERE fk_mailing = " . $this->fk_mailing;
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(__METHOD__ . " - Get target count - Error: " . $this->error, LOG_ERR);
				$error++;
			} elseif ($obj = $this->db->fetch_object($resql)) {
				$nb = $obj->nb;
			}

			$sql = "UPDATE " . MAIN_DB_PREFIX . "mailing SET nbemail = " . $nb . " WHERE rowid = " . $this->fk_mailing;
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = "Error " . $this->db->lasterror();
				dol_syslog(__METHOD__ . " - Update target count - Error: " . $this->error, LOG_ERR);
				$error++;
			}
		}

		if (!$error) {
			// Call trigger
			$result = $this->call_trigger('SENDINBLUE_IMPORT_SUBSCRIBERS', $user);
			if ($result < 0) $error++;
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * Export to list and segments sendinblue only segment from dolibarr email
	 *
	 * @param int $listid 	segment id
	 * @param bool $copy 	Copy exact of dolibarr send to sendinblue
	 * @return int <0 if KO, >0 if OK
	 */
	function exportDesttoSendinBlue($listid, $copy = false) {
		global $conf;

		$result = $this->getEmailMailingDolibarr('toadd');
		if ($result < 0) {
			return - 1;
		}
		if (count($this->email_lines)) {
			$result_add_to_list = $this->addEmailToList($this->sendinblue_listid, $this->email_lines, $copy);
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
	/*function exportSegmentOnlyDesttoSendinBlue($segmentid, $newsegmentname, $resetseg = 0) {
		global $conf;

		$result = $this->getEmailMailingDolibarr('toadd');
		if ($result < 0) {
			return - 1;
		}
		if (count($this->email_lines)) {

			$this->sendinblue_segmentid = $segmentid;

			$result = $this->updateList($this->sendinblue_listid,  $this->email_lines, $resetseg);
			if ($result < 0) {
				return - 1;
			}
		}

		if ($result < 0) {
			return - 2;
		} else {
			return 1;
		}
	}*/

	/**
	 * Create the emailing on Dolibarr from the capaign on SendinBlue
	 *
	 * @param	user		$user							User who make this action
	 * @param 	int			$campaign_id					SendinBlue campaign ID to get
	 * @param 	int			$list_id						SendinBlue list ID specified (if not create new from emailing)
	 * @param 	int			$list_name						SendinBlue list name specified (if create new from emailing)
	 * @param 	array		$mailing_properties				Default properties values for emailing
	 * @return	int											<0 if KO, >0 if OK
	 */
	function createDolibarrEmailingFromSendinBlueCampaign($user, $campaign_id, $list_id = 0, $list_name = '', $mailing_properties = array())
	{
		global $conf, $langs;
		$langs->loadLangs(array('mails', 'sendinblue@sendinblue'));

		$error = 0;
		$this->error = '';
		$this->errors = array();

		// Clear parameters
		$campaign_id = $campaign_id > 0 ? $campaign_id : 0;
		$list_id = $list_id > 0 ? $list_id : 0;

		// Check parameters
		if (empty($campaign_id)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("SendinBlueCampaignID"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . " - check params - " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		// Get campaign sendinblue data
		$campaign_data = $this->getCampaignData($campaign_id);
		if (!is_array($campaign_data)) {
			return -1;
		}

		$emailing_title = !empty($list_name) ? $list_name : trim($campaign_data["name"]);

		// Create list on Sendinblue if not specified
		$new_list = false;
		if (empty($list_id)) {
			$new_list = true;
			$list_id = $this->createList($emailing_title);
			if ($list_id < 0) {
				return -1;
			}

			// Update list on campaign
			$current_recipients_list = is_array($campaign_data['recipients']['lists']) ? $campaign_data['recipients']['lists'] : array();
			$data = array(
				'recipients' => array(
					'listIds' => array_flip(array_flip(array_merge($current_recipients_list, array($list_id)))),
				),
			);
			try {
				$response = $this->sendinblue->updateCampaign($campaign_id, $data);
				if(!empty($response['code'])){
					$this->error = $response['message'];
					return -1;
				}
			} catch (Exception $e) {
				$this->error = $e->getMessage();
				dol_syslog(__METHOD__ . " - link new list to campaign - " . $this->error, LOG_ERR);
				return -1;
			}
		}

		// Create emailing with campaign data
		require_once DOL_DOCUMENT_ROOT . '/comm/mailing/class/mailing.class.php';
		$mailing = new Mailing($this->db);
		$mailing->email_from = trim($campaign_data["sender"]["email"]);
		$mailing->email_replyto = trim($campaign_data["replyTo"]);
		$mailing->email_errorsto = trim(!empty($conf->global->MAILING_EMAIL_ERRORSTO) ? $conf->global->MAILING_EMAIL_ERRORSTO : $conf->global->MAIN_MAIL_ERRORS_TO);
		$mailing->titre = $emailing_title;
		$mailing->sujet = trim($campaign_data["subject"]);
		$mailing->body = trim($campaign_data["htmlContent"]);
		$mailing->bgcolor = "";
		$mailing->bgimage = "";
		if (is_array($mailing_properties) && count($mailing_properties)) {
			foreach ($mailing_properties as $k => $v) {
				$mailing->$k = $v;
			}
		}

		if (empty($mailing->titre)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("MailTitle"));
			$error++;
		}
		if (empty($mailing->sujet)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("MailTopic"));
			$error++;
		}
		if (empty($mailing->body)) {
			$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("MailMessage"));
			$error++;
		}
		if ($error) {
			dol_syslog(__METHOD__ . " - check mailing params - " . $this->errorsToString(), LOG_ERR);
			return -1;
		}

		$this->db->begin();

		$mailing_id = $mailing->create($user);
		if ($mailing_id < 0) {
			$this->error = $mailing->error;
			$this->errors = $mailing->errors;
			$error++;
		}

		// Create link emailing with sendinblue
		$sendinblue = new DolSendinblue($this->db);
		if (!$error) {
			$sendinblue->fk_mailing = $mailing_id;
			$sendinblue->sendinblue_id = $campaign_id;
			$sendinblue->sendinblue_webid = array('data' => array(0 => $campaign_data));
			$sendinblue->sendinblue_listid = $list_id > 0 ? $list_id : null;
			$sendinblue->sendinblue_segmentid = null;
			$sendinblue->sendinblue_sender_name = trim($campaign_data["sender"]["name"]);

			$id = $sendinblue->create($user);
			if ($id < 0) {
				$this->error = $sendinblue->error;
				$this->errors = $sendinblue->errors;
				$error++;
			}
		}

		// Get contact list
		if (!$error && !$new_list) {
			$result = $sendinblue->importSegmentDestToDolibarr($list_id);
			if ($result < 0) {
				$this->error = $sendinblue->error;
				$this->errors = $sendinblue->errors;
				dol_syslog(__METHOD__ . " - get contact list of SendinBlue campaign ID $campaign_id, list ID $list_id - " . $sendinblue->errorsToString(), LOG_ERR);
				$error++;
			}
		}

		$status = $campaign_data["status"];
		if (!$error && ($status == 'save' || $status== 'Draft' || $status == 'paused' ||
				$status == "suspended" || $status == "archive" || $status == "darchive" || $status == "replicate" || $status == "replicateTemplate")) {
			// Do nothing
		}
		// Set emailing status to validated
		if (!$error && ($status == 'schedule' || $status == 'Scheduled' ||
				$status == "queued")
		) {
			$result = $mailing->valid($user);
			if ($result < 0) {
				$this->error = $mailing->error;
				$this->errors = $mailing->errors;
				$error++;
			}
		}
		// Set emailing status to sent partially
		if (!$error && ($status == 'sending')) {
			$result = $mailing->setStatut(2);
			if ($result < 0) {
				$this->error = $mailing->error;
				$this->errors = $mailing->errors;
				$error++;
			}
		}
		// Set emailing status to sent
		if (!$error && ($status == 'sent' ||$status == 'Sent' || $status == 'Sent and Archived')) {
			$result = $mailing->setStatut(3);
			if ($result < 0) {
				$this->error = $mailing->error;
				$this->errors = $mailing->errors;
				$error++;
			}
		}

		// Update campaign/contacts status
		if (!$error && ($mailing->statut == 3)) {
			// todo uncomment when the code is compatible with API v3
//			$result = $sendinblue->updateSendinBlueCampaignStatus($user);
//			if ($result < 0) {
//				$this->error = $sendinblue->error;
//				$this->errors = $sendinblue->errors;
//				dol_syslog(__METHOD__ . " - Update status SendinBlue campaign ID $campaign_id - " . $sendinblue->errorsToString(), LOG_ERR);
//				$error++;
//			}
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return $id;
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

		if(empty($this->currentmailing->title)){
			$this->currentmailing->title = $this->currentmailing->titre; // for Dolibarr < V13
		}

		$data =array(
			"tag"=>'Send by dolibarr',
			"name" => $this->currentmailing->title,
			"htmlContent"=> $this->currentmailing->body,
			"recipients"=>array('listIds' => array(intval($this->sendinblue_listid))), // intval is important
			"subject"=>$this->currentmailing->sujet,
			"sender" => array(
				"name" => $this->sendinblue_sender_name,
				"email" => $this->currentmailing->email_from,
			),
			"replyTo"=>$this->currentmailing->email_from);

		if (empty($this->sendinblue_id)) {
			try {
				$response = $this->sendinblue->create_campaign($data);
				if(!empty($response['code'])){

					$this->error = $response['message'];
					return -1;
				}
				//var_dump($response);exit;
			} catch ( Exception $e ) {
				$this->error = $e->getMessage();
				dol_syslog(get_class($this) . "::createSendinBlueCampaign " . $this->error, LOG_ERR);
				return - 1;
			}


			$this->sendinblue_id = $response['id'];
			$opts['campaign_id'] = $this->sendinblue_id;
			try {
				$response = $this->sendinblue->get_campaign($this->sendinblue_id);
			} catch ( Exception $e ) {
				$this->error = $e->getMessage();
				dol_syslog(get_class($this) . "::createSendinBlueCampaign " . $this->error, LOG_ERR);
				return - 1;
			}

			$this->sendinblue_webid = $response['id'];

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
	public function updateSendinBlueCampaignStatus($user)
	{
		global $conf;

		$result = $this->getInstanceSendinBlue();
		if ($result < 0) {
			dol_syslog(get_class($this) . "::updateSendinBlueCampaignStatus " . $this->error, LOG_ERR);
			return -1;
		}

		$error = 0;

		// get HTML content
		$body_html = '';
		try {

			$response = $this->sendinblue->get_campaign($this->sendinblue_id);

			$body_html = $response['htmlContent'];
		} catch ( Exception $e ) {
			$this->error = $e->getMessage();
			dol_syslog(get_class($this) . "::updateSendinBlueCampaignStatus " . $this->error, LOG_ERR);
			$error++;
		}

		// Set Dolibarr campaign with this information from sendinblue
		require_once DOL_DOCUMENT_ROOT . '/comm/mailing/class/mailing.class.php';
		$mailing = new Mailing($this->db);

		$result = $mailing->fetch($this->fk_mailing);
		if ($result < 0) {
			$this->errors[] = "Error class Mailing Dolibarr " . $result . ' ' . $mailing->error;
			$error++;
		}

		if (!empty($body_html)) {
			$mailing->body = $response['htmlContent'];
			$result = $mailing->update($user);
			if ($result < 0) {
				$this->errors[] = "Error class Mailing Dolibarr " . $result . ' ' . $mailing->error;
				$error++;
			}
		}

		$date_send_text = $response['scheduledAt'];

		$dt_send_unix = strtotime($date_send_text);

		dol_syslog(get_class($this) . "::getCampaignActivity start " . dol_print_date(dol_now(), 'standard'), LOG_DEBUG);

		$result = $this->getCampaignActivity($response);

		if ($result < 0) {
			$error++;
		}

		dol_syslog(get_class($this) . "::getCampaignActivity end " . dol_print_date(dol_now(), 'standard'), LOG_DEBUG);
		if ($this->email_activity[0]['email'] == 'error') {
			$error++;
		}

		if (!$error) {
			if (is_array($this->email_activity) && count($this->email_activity) > 0) {
				foreach ($this->email_activity as $email_activity) {
					// Sent
					$result = $this->updateTargetMailingStatus($user, 1, $email_activity['email'], 0, $dt_send_unix);
					if ($result < 0) {
						$error++;
					}

					// Each activities
					if (!$error && isset($email_activity['activity'])) {
						// dol_syslog(get_class($this)."::getCampaignActivity activities=".var_export($activities,true), LOG_DEBUG);
						$result = 0;
						if ($email_activity['activity'] == '') {
							$result = $this->updateTargetMailingStatus($user, 1, $email_activity['email'], 0, $dt_send_unix);
						} elseif ($email_activity['activity'] == 'open') {
							$result = $this->updateTargetMailingStatus($user, 2, $email_activity['email'], 0, $dt_send_unix);
						} elseif ($email_activity['activity'] == 'unsubscribe') {
							var_dump($email_activity);
							$result = $this->updateTargetMailingStatus($user, 3, $email_activity['email'], 0, $dt_send_unix);
						} elseif ($email_activity['activity'] == 'click') {
							$result = $this->updateTargetMailingStatus($user, 4, $email_activity['email'], 0, $dt_send_unix);
						} elseif ($email_activity['activity'] == 'hard_bounce') {
							$result = $this->updateTargetMailingStatus($user, 5, $email_activity['email'], 0, $dt_send_unix);
						} elseif ($email_activity['activity'] == 'soft_bounce') {
							$result = $this->updateTargetMailingStatus($user, 6, $email_activity['email'], 0, $dt_send_unix);
						}
						if ($result < 0) {
							$error++;
						}
					}
				}
			}
		}

		// Save email activites into Dolibarr
		// Find each email for this mailing
		if (!$error) {
			$result = $this->getEmailMailingDolibarr('simple');
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error && is_array($this->email_lines) && count($this->email_lines) > 0) {
			// For each mail find the total activites
			foreach ($this->email_lines as $email) {
				$result = $this->getEmailcontactActivites($email);
				if ($result < 0) {
					$error++;
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
							$error++;
						}
					}
				}
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::updateSendinBlueCampaignStatus " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			return -1;
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
		$response = $this->sendinblue->get_user($email);

	if(!empty($response['data']['unsubscription']['user_unsubscribe'])){
		foreach($response['data']['unsubscription']['user_unsubscribe'] as $u){
			if($u['camp_id'] == $idList){
					return true;
				}
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
			$this->getInstanceSendinBlue();
			$reponse = $this->sendinblue->get_user($email);

			// TODO : change hard_bounces does not exist anymore... --> use webhooks
			if(!empty($reponse['statistics']['hardBounces'])){
				foreach($reponse['statistics']['hardBounces'] as $camp){
					$fk_mailing = $this->getMaillingFromCampainId($camp['campaignId']);
					if($fk_mailing > 0){
						$tmp = new stdClass;
						$tmp->fk_mailing =  $fk_mailing;
						$tmp->activites = 'Hard Bounce';
						$tmp->timestamp = $camp['eventTime'];
						$this->contactemail_activity[] = $tmp;
					}
				}
			}

			// TODO : change soft_bounces does not exist anymore --> use webhooks
			if(!empty($reponse['statistics']['softBounces'])){
				foreach($reponse['statistics']['softBounces'] as $camp){
					$fk_mailing = $this->getMaillingFromCampainId($camp['campaignId']);
					if($fk_mailing > 0) {
						$tmp = new stdClass;
						$tmp->fk_mailing = $fk_mailing;
						$tmp->activites = 'Soft Bounce';
						$tmp->timestamp = $camp['eventTime'];
						$this->contactemail_activity[] = $tmp;
					}
				}
			}
			// TODO change spam does not exist anymore --> use webhooks
			if(!empty($reponse['statistics']['spam'])){
				foreach($reponse['statistics']['spam'] as $camp){
					$fk_mailing = $this->getMaillingFromCampainId($camp['campaignId']);
					if($fk_mailing > 0) {
						$tmp = new stdClass;
						$tmp->fk_mailing = $fk_mailing;
						$tmp->activites = 'Spam';
						$tmp->timestamp = $camp['eventTime'];
						$this->contactemail_activity[] = $tmp;
					}
				}
			}

			if(!empty($reponse['statistics']['opened'])){
				foreach($reponse['statistics']['opened'] as $camp){
					$fk_mailing = $this->getMaillingFromCampainId($camp['campaignId']);
					if($fk_mailing > 0) {
						$tmp = new stdClass;
						$tmp->fk_mailing = $fk_mailing;
						$tmp->activites = 'opened';
						$tmp->timestamp = $camp['eventTime'];
						$this->contactemail_activity[] = $tmp;
					}
				}
			}

			if(!empty($reponse['statistics']['clicks'])){
				foreach($reponse['statistics']['clicks'] as $camp){
					$fk_mailing = $this->getMaillingFromCampainId($camp['campaignId']);
					if($fk_mailing > 0) {
						$tmp = new stdClass;
						$tmp->fk_mailing = $fk_mailing;
						$tmp->activites = 'clicks';
						$tmp->timestamp = $camp['eventTime'];
						$this->contactemail_activity[] = $tmp;
					}
				}
			}

			if(!empty($reponse['statistics']['unsubscriptions']['userUnsubscription'])){
				foreach($reponse['statistics']['unsubscriptions']['userUnsubscription'] as $camp){
					$fk_mailing = $this->getMaillingFromCampainId($camp['campaignId']);
					if($fk_mailing > 0) {
						$tmp = new stdClass;
						$tmp->fk_mailing = $fk_mailing;
						$tmp->activites = 'unsubscribe';
						$tmp->timestamp = $camp['eventTime'];
						$this->contactemail_activity[] = $tmp;
					}
				}
			}
		}
	}

	/**
	 * @param      $campaignId
	 * @param bool $useCache
	 * @return    int <0 KO >0 OK
	 */
	public function getMaillingFromCampainId ($campaignId, $useCache = true) {
		global $cacheGetMaillingFromCampainId;

		if(empty($cacheGetMaillingFromCampainId)){
			$cacheGetMaillingFromCampainId = array();
		}

		if($useCache && !empty($cacheGetMaillingFromCampainId[$campaignId])){
			 return $cacheGetMaillingFromCampainId[$campaignId];
		}

		$sql = "SELECT fk_mailing FROM ".MAIN_DB_PREFIX."sendinblue WHERE sendinblue_id ='".intval($campaignId)."' LIMIT 1;";
		$res = $this->db->query($sql);
		if ($res)
		{
			$obj =$this->db->fetch_object($res);
			if($obj){
				$cacheGetMaillingFromCampainId[intval($campaignId)] = $obj->fk_mailing;
				return $obj->fk_mailing;
			}

			return 0;
		}
		else{
			return -1;
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
