<?php

/**
 * SendinBlue REST client
 */

class Sendinblue
{
	static $SENDINBLUE_API_LINES_LIMIT = 50; 
    public $api_key;
    public $base_url;
    public $timeout;
    public $curl_opts = array();
    public function __construct($base_url,$api_key,$timeout='')
    {
        if(!function_exists('curl_init'))
        {
            throw new RuntimeException('Sendinblue requires cURL module');
        }
        $this->base_url = $base_url;
        $this->api_key = $api_key;
        $this->timeout = $timeout;
    }
    /**
     * Do CURL request with authorization
     */
    private function do_request($resource,$method,$input)
    {
        $called_url = $this->base_url."/".$resource;
        $ch = curl_init($called_url);
        $auth_header = 'api-key:'.$this->api_key;
        $content_header = "Content-Type:application/json";
        $timeout = ($this->timeout!='')?($this->timeout):30000; //default timeout: 30 secs
//        if ($timeout!='' && ($timeout <= 0 || $timeout > 60000)) {
//            throw new Exception('value not allowed for timeout');
//        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows only over-ride
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($auth_header,$content_header));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
        $data = curl_exec($ch);
        if(curl_errno($ch))
        {
            throw new RuntimeException('cURL error: ' . curl_error($ch));
        }
        if($method == 'GET' && (!is_string($data) || !strlen($data))) {
            throw new RuntimeException('Request Failed');
        }
        curl_close($ch);
        return json_decode($data,true);
    }
    public function get($resource,$input = '')
    {
        return $this->do_request($resource,"GET",$input);
    }
    public function put($resource,$input = '')
    {
        return $this->do_request($resource,"PUT",$input);
    }
    public function post($resource,$input = '')
    {
        return $this->do_request($resource,"POST",$input);
    }
    public function delete($resource,$input = '')
    {
        return $this->do_request($resource,"DELETE",$input);
    }

	public function subscriberHash($email)
	{
		return md5(strtolower($email));
	}

    /* TODO
        Get SMTP details.
        No input required
    */
    public function get_smtp_details()
    {
        return $this->get("smtp/email","");
    }

    /*
        Get a particular campaign detail.
    */
    public function get_campaign($campaignid)
    {
        return $this->get("emailCampaigns/".$campaignid);
    }

    /*
        Get all campaigns detail.
        @param {Array} data contains php array with key value pair.
        @options data {String} type: Type of campaign. Possible values – classic, trigger, sms, template ( case sensitive ) [Optional]
        @options data {String} status: Status of campaign. Possible values – draft, sent, archive, queued, suspended, in_process, temp_active, temp_inactive ( case sensitive ) [Optional]
        @options data {Integer} offset: Index of the first document in the page. Maximum number of records per request is 500, if there are more than 500 campaigns then you can use this parameter to get next 500 results [Optional]
        @options data {Integer} limit: Number of documents per page. This should be a valid number between 1-500 [Optional]
    */
    public function get_campaigns($data)
    {
        return $this->get("emailCampaigns" . (!empty($data) ? '?' . http_build_query($data): ''));
    }

    /*
        Create and Schedule your campaigns. It returns the ID of the created campaign.
        @param {Array} data contains php array with key value pair.
        @options data {String} category: Tag name of the campaign [Optional]
        @options data {Array} sender :Sender details including id or email and name (optional). Only one of either Sender's email or Sender's ID shall be passed in one request at a time. For example:
			{"name":"xyz", "email":"example@abc.com"}
			{"name":"xyz", "id":123}
        @options data {String} from_name: Sender name from which the campaign emails are sent [Mandatory: for Dedicated IP clients, please make sure that the sender details are defined here, and in case of no sender, you can add them also via API & for Shared IP clients, if sender exists]
        @options data {String} name: Name of the campaign [Mandatory]
        @options data {String} bat: Email address for test mail [Optional]
        @options data {String} htmlContent: Body of the content. The HTML content field must have more than 10 characters [Mandatory: if html_url is empty]
        @options data {String} html_url: Url which content is the body of content [Mandatory: if htmlContent is empty]
        @options data {Array} listid: These are the lists to which the campaign has been sent [Mandatory: if scheduled_date is not empty]
        @options data {String} scheduled_date: The day on which the campaign is supposed to run[Optional]
        @options data {String} subject: Subject of the campaign [Mandatory]
        @options data {String} from_email: Sender email from which the campaign emails are sent [Mandatory: for Dedicated IP clients, please make sure that the sender details are defined here, and in case of no sender, you can add them also via API & for Shared IP clients, if sender exists]
        @options data {String} replyTo: The reply to email in the campaign emails [Optional]
        @options data {String} toField: This is to personalize the «To» Field. If you want to include the first name and last name of your recipient, add [PRENOM] [NOM] To use the contact attributes here, these should already exist in SendinBlue account [Optional]
        @options data {Array} exclude_list: These are the lists which must be excluded from the campaign [Optional]
        @options data {String} attachment_url: Provide the absolute url of the attachment [Optional]
        @options data {Integer} inline_image: Status of inline image. Possible values = 0 (default) & 1. inline_image = 0 means image can’t be embedded, & inline_image = 1 means image can be embedded, in the email [Optional]
        @options data {Integer} mirror_active: Status of mirror links in campaign. Possible values = 0 & 1 (default). mirror_active = 0 means mirror links are deactivated, & mirror_active = 1 means mirror links are activated, in the campaign [Optional]
        @options data {Integer} send_now: Flag to send campaign now. Possible values = 0 (default) & 1. send_now = 0 means campaign can’t be send now, & send_now = 1 means campaign ready to send now [Optional]

    */
    public function create_campaign($data)
    {
		if(empty($data['sender'])){
			$data['sender'] = array();
			if(isset($data['from_name'])){  $data['sender']['name'] = $data['from_name']; }
			if(isset($data['from_email'])){  $data['sender']['email'] = $data['from_email']; }
		}

        return $this->post("emailCampaigns",json_encode($data));
    }

	/**
	 * Update an email campaign, based on campaignId
	 * @param {Array} data contains php array with key value pair.
	 * @return null | array
    */
	public function updateCampaign($campaignId, $data)
	{
		return $this->put("emailCampaigns/".$campaignId, json_encode($data));
	}

	/**
	 * Send an email campaign immediately, based on campaignId
	 * @return null | array
	 */
	public function sendCampaign($campaignId)
	{
		return $this->post("emailCampaigns/".$campaignId."/sendNow");
	}

    /*
        Get all lists detail.
        @param {Array} data contains php array with key value pair.
        @options data {Integer} folderId: This is the existing folder id & can be used to get all lists belonging to it [Optional]
        @options data {Integer} page: Maximum number of records per request is 50, if there are more than 50 processes then you can use this parameter to get next 50 results [Mandatory]
        @options data {Integer} page_limit: This should be a valid number between 1-50 [Mandatory]
    */
    public function get_lists($data, $limit = -1, $offset=0, $sort='desc')
    {
		if($limit>=50){
			// au dessus de 50 send in bleu reponds vide
			$limit = -1;
		}

		if($limit < 0){
			// Mode no limit

			$output = array(
				'lists' => array()
			);

			while ($data = $this->get("contacts/lists?limit=50&offset=".$offset.'&sort='.$sort,json_encode($data))){

				if(empty($data['lists'])){
					break;
				}

				$output = array(
					'lists' => array_merge($output['lists'], $data['lists'])
				);

				$offset+=50;


				if($offset>500){
					break;
				}
			}

			return $output;
		}
		else{
			return $this->get("contacts/lists?limit=".$limit.'&offset='.$offset.'&sort='.$sort,json_encode($data));
		}
    }

    /*
        Get a particular list detail.
        @param {Array} data contains php array with key value pair.
        @options data {Integer} id: Id of list to get details [Mandatory]
    */
    public function get_list($data)
    {
        return $this->get("contacts/lists/".$data['id'],"");
    }

    /*
        Create a new list.
        @param {Array} data contains php array with key value pair.
        @options data {String} list_name: Desired name of the list to be created [Mandatory]
        @options data {Integer} folderId: Folder ID [Mandatory]
    */
    public function create_list($data)
    {
        return $this->post("contacts/lists",json_encode($data));
    }

    /*
        Display details of all users for the given lists.
        @param {Array} data contains php array with key value pair.
        @options data {Array} listIds: These are the list ids to get their data. The ids found will display records [Mandatory]
        @options data {String} timestamp: This is date-time filter to fetch modified user records >= this time. Valid format Y-m-d H:i:s. Example: "2015-05-22 14:30:00" [Optional]
        @options data {Integer} offset: Maximum number of records per request is 50, if in your list there are more than 50 users then you can use this parameter to get next 50 results [Optional]
        @options data {Integer} limit: This should be a valid number between 1-50 [Optional]
    */
    public function display_list_users($listid, $data)
    {
        return $this->get("contacts/lists/".intval($listid)."/contacts" . (!empty($data) ? '?' . http_build_query($data): ''));
    }

    /*
        Delete already existing users in the SendinBlue contacts from the list.
        @param {Array} data contains php array with key value pair.
        @options data {Integer} id: Id of list to unlink users from it [Mandatory]
        @options data {Array} users: Email address of the already existing user(s) in the SendinBlue contacts to be modified. Example: "test@example.net". You can use commas to separate multiple users [Mandatory]
    */
    public function delete_users_list($data)
    {
        $id = $data['id'];
        unset($data['id']);
        return $this->post("contacts/lists/".intval($id).'/contacts/remove',json_encode($data));
    }


    /*
        Create a new user if an email provided as input, doesn’t exists in the contact list of your SendinBlue account, otherwise it will update the existing user.
        @param {Array} data contains php array with key value pair.
        @options data {String} email: Email address of the user to be created in SendinBlue contacts. Already existing email address of user in the SendinBlue contacts to be modified [Mandatory]
        @options data {Array} attributes: The name of attribute present in your SendinBlue account. It should be sent as an associative array. Example: array("NAME"=>"name"). You can use commas to separate multiple attributes [Optional]
        @options data {Integer} blacklisted: This is used to blacklist/ Unblacklist a user. Possible values – 0 & 1. blacklisted = 1 means user has been blacklisted [Optional]
        @options data {bool} updateEnabled: Facilitate to update the existing contact in the same request (updateEnabled = true)
        @options data {Array} listIds: The list id(s) to be linked from user [Optional]
        @options data {Array} smsBlacklisted: This is used to blacklist/ Unblacklist a user’s SMS number. Possible values – 0 & 1. blacklisted_sms = 1 means user’s SMS number has been blacklisted [Optional]
    */
//    public function create_update_user($data, $tryUpdate = false)
//    {
//		$result = $this->post("contacts",json_encode($data));
//
//		if($tryUpdate && !empty($result['code']) && $result['code'] == 'duplicate_parameter'){
//			$updateData = array();
//			// Parce qu'il faut adapter à la V3 avec les anciens appels
//			if(!empty($data['listIds'])) {
//				$listIds = $data['listIds'];
//				foreach ($listIds as $listid) {
//					$updateData['emails'] = array($data['email']);
//					$result = $this->addExistingContactsToLists($listid, $updateData);
//					if ($result < 0) {
//						return -1;
//					}
//				}
//			}
//		}
//
//		return 1;
//    }

	/**
	 * TODO : Typiquement le genre de methode à mettre en sortie de do_request avec une vraie gestion des erreurs mais imposerait de revoir entièrement le code module .
	 * @param $response
	 * @param bool $errorSetEventMessage to display erro rmessage
	 * @param string $error_prefix
	 * @return bool
	 */
	public function analyseResponseResult($response, $errorSetEventMessage = false, $error_prefix = "")
	{
		if (!empty($response['code'])) {
			$error = $response['code'];
			if (!empty($response['message'])) {
				$error .= ' - ' . $response['message'];
			}
			if ($errorSetEventMessage) {
				setEventMessage($error, 'errors');
			}
			$this->errors[] = !empty($error_prefix)?($error_prefix.' : '.$error):$error;
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @param $data [emails, ids] limité a 150 items par appels, utiliser l'import s'il faut en faire plus
	 * @return int 				<0 if KO, >0 if OK
	 */
	public function addExistingContactsToLists($db, $fk_mailing, $listid, $data, $contactData)
	{
		// Bon ya pas de gestion d'erreur correct ici pourtant l'api de sendinblue renvoi 2 tableaux success et failure contenant les adresses emails
		$listid = intval($listid);
		$maxSend = 150;

		$error = 0;
		$this->error = '';
		$this->errors = array();

		if (is_array($data['emails'])) {
			$data_to_send = $data['emails'];
			$data_type = 'emails';
		} elseif (is_array($data['ids'])) {
			$data_to_send = $data['ids'];
			$data_type = 'ids';
		} else {
			$this->errors[] = 'Wrong data send (only emails or ids) to list (ID: ' . $listid . ', data: ' . json_encode($data) . ')';
			$error++;
		}

		if (!$error) {
			$success_email = array();
			$failure_email = array();

			$added = 0;
			$nb = count($data_to_send);
			while ($added < $nb) {
				$items = array_slice($data_to_send, $added, min($maxSend, $nb - $added));
				$added += count($items);
				$response = $this->post("contacts/lists/" . $listid . "/contacts/add", json_encode(array($data_type => $items)));
				if (!$this->analyseResponseResult($response) && $response['message'] != "Contact already in list and/or does not exist") {
					$failure_email = array_merge($failure_email, $items);
					$failure_email = array_unique($failure_email);
					$this->errors[] = ' send contacts to list (ID: ' . $listid . ') error - try 1 - data: ' . json_encode(array($data_type => $items));
					$error++;
				} else {
					if (!empty($response['contacts']['success'])) {
						$success_email = array_merge($success_email, $response['contacts']['success']);
						$this->errors[] = 'success_email : ' . var_export($response['contacts']['success'], true);
					}

					if ((!empty($response['contacts']['failure']) && is_array($response['contacts']['failure'])) || $response['message'] == "Contact already in list and/or does not exist") { // code: invalid_parameter (same code for different message ...)

						$contactToCreate = !empty($response['contacts']['failure']) ? $response['contacts']['failure'] : $items;
						foreach ($contactToCreate as $email) {
							if (isset($contactData[$email])) {
								// Creation du contact par API
								$response2 = $this->post("contacts", json_encode($contactData[$email]));
								if (!empty($response2['code']) && $response2['code'] == 'duplicate_parameter') {
									// Le contact existe déja il faut donc mettre à jour l'info sur Dolibarr
									// cela peut venir d'une erreur lors de la première synchro
									self::removeItemFromArray($failure_email, $email);
									$success_email[] = $email;
									$success_email = array_unique($success_email);
								}
								elseif (
									!$this->analyseResponseResult($response2, false, "contacts/lists/" . $listid . "/contacts/add")
									&& (
										empty($response['code']) || $response2['code'] != 'duplicate_parameter' // Message: Contact already exist
									)
								) {
									$this->errors[] = ' create contact error - data: ' . json_encode($contactData[$email]);
									$error++;
								}
								elseif(!empty($response2['id'])){
									// le contact est bien créé, il ne faut pas forcer la création
									self::removeItemFromArray($contactToCreate, $email);
								}
							}
						}
						if (!$error && !empty($contactToCreate)) {
							// Resend
							$response3 = $this->post("contacts/lists/" . $listid . "/contacts/add", json_encode(array($data_type => $contactToCreate)));
							if (!$this->analyseResponseResult($response3, false, " restart contacts/lists/" . $listid . "/contacts/add")) {
								$failure_email = array_merge($failure_email, $contactToCreate);
								$this->errors[] = ' send contacts to list (ID: ' . $listid . ') error - try 2 - data: ' . json_encode(array($data_type => $contactToCreate));
								$error++;
							} else {
								if (!empty($response3['contacts']['success'])) {
									$success_email = array_merge($success_email, $response3['contacts']['success']);
								}
								if (!empty($response3['contacts']['failure']) && is_array($response3['contacts']['failure'])) {
									$failure_email = array_merge($failure_email, $response3['contacts']['failure']);
									$this->errors[] = ' send contacts to list (ID: ' . $listid . ') failed for ' . $data_type . ': ' . implode(', ', $contactToCreate);
								}
							}
						} elseif(is_array($response['failure']) && is_array($response2['failure'])){
							$failure_email = array_merge($failure_email, $response['failure']);
						}
					}
				}
			}

			if ($fk_mailing > 0) {
				if (!empty($failure_email)) {
					$failure_email = array_flip(array_flip($failure_email));
					$escaped_email = array();
					foreach ($failure_email as $email) {
						$escaped_email[] = $db->escape($email);
					}

					$sql = "UPDATE " . MAIN_DB_PREFIX . "mailing_cibles SET sendinblue_status = 0" .
						" WHERE fk_mailing = " . $fk_mailing . " AND email IN ('" . implode("','", $escaped_email) . "')";
					$resql = $db->query($sql);
					if (!$resql) {
						$this->error = "Error update failure email " . $db->lasterror();
						dol_syslog(__METHOD__ . " - Update target status 0 - Error: " . $this->error, LOG_ERR);
					}
				}

				if (!empty($success_email)) {
					$success_email = array_flip(array_flip($success_email));
					$escaped_email = array();
					foreach ($success_email as $email) {
						$escaped_email[] = $db->escape($email);
					}

					$sql = "UPDATE " . MAIN_DB_PREFIX . "mailing_cibles SET sendinblue_status = 1" .
						" WHERE fk_mailing = " . $fk_mailing . " AND email IN ('" . implode("','", $escaped_email) . "')";
					$resql = $db->query($sql);
					if (!$resql) {
						$this->error = "Error update success email " . $db->lasterror();
						dol_syslog(__METHOD__ . " - Update target status 1 - Error: " . $this->error, LOG_ERR);
					}
				}

			}
		}

		if ($error) {
			dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 * @param $data [emails, ids] limité a 150 items par appels, utiliser l'import s'il faut en faire plus
	 * @return int 				<0 if KO, >0 if OK
	 */
	public function delExistingContactsToLists($listid, $data)
	{
		// Bon ya pas de gestion d'erreur correct ici pourtant l'api de sendinblue renvoi 2 tableaux success et failure contenant les adresses emails
		$listid = intval($listid);
		$maxSend = 150;

		$error = 0;
		$this->error = '';
		$this->errors = array();

		if (is_array($data['emails'])) {
			$data_to_send = $data['emails'];
			$data_type = 'emails';
		} elseif (is_array($data['ids'])) {
			$data_to_send = $data['ids'];
			$data_type = 'ids';
		} else {
			$this->errors[] = 'Wrong data send (only emails or ids) to list (ID: ' . $listid . ', data: ' . json_encode($data) . ')';
			$error++;
		}

		if (!$error) {
			$removed = 0;
			$nb = count($data_to_send);
			while ($removed < $nb) {
				$items = array_slice($data_to_send, $removed, min($maxSend, $nb - $removed));
				$removed += count($items);
				$response = $this->post("contacts/lists/" . $listid . "/contacts/remove", json_encode(array($data_type => $items)));
				if (!$this->analyseResponseResult($response)) {
					$this->errors[] = ' delete contacts to list (ID: ' . $listid . ') error - data: ' . json_encode(array($data_type => $items));
					$error++;
				}
			}
		}

		if ($error) {
			dol_syslog(__METHOD__ . " - Error: " . $this->errorsToString(), LOG_ERR);
			return -1;
		} else {
			return 1;
		}
	}

    /*
        Get Access a specific user Information.
        @param {Array} data contains php array with key value pair.
        @options data {String} email: Email address of the already existing user in the SendinBlue contacts [Mandatory]
    */
    public function get_user($email)
    {
        return $this->get("contacts/".$email,"");
    }

    /**
        Unlink existing user from all lists.
        @param $email: Email address of the already existing user in the SendinBlue contacts to be unlinked from all lists [Mandatory]
    */
    public function delete_user($email)
    {
        return $this->delete("contacts/".$email,"");
    }

	/**
	 * Method to output saved errors
	 *
	 * @return	string		String with errors
	 */
	public function errorsToString()
	{
		return $this->error.(is_array($this->errors)?(($this->error!=''?', ':'').join(', ', $this->errors)):'');
	}

	/**
	 * @param array $items
	 * @param string|int $valueToDel
	 */
	static public function removeItemFromArray(&$items, $valueToDel){
		foreach (array_keys($items, $valueToDel, true) as $key) {
			unset($items[$key]);
		}
	}
}
