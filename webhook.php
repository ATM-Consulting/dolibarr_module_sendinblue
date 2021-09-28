<?php

define('NOCSRFCHECK', 1);
define('INC_FROM_CRON_SCRIPT',1);
define('NOTOKENRENEWAL','1'); // Disables token renewal
define('NOREQUIREMENU','1');
define('NOREQUIREHTML','1');
define('NOREQUIREAJAX','1');
define('NOLOGIN','1');
require __DIR__ . '/config.php';

$tokenKey = GETPOST('token', 'alphanohtml');

if(empty($conf->global->CRON_KEY)
	|| $conf->global->CRON_KEY != $tokenKey
	|| empty($conf->global->SENINBLUE_USER_ID)){
	http_response_code(401);
	print 'Unauthorized';
	exit;
}

require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/mailing/class/mailing.class.php';

require_once __DIR__ . '/class/dolsendinblue.class.php';
require_once __DIR__ . '/class/sendinblueactivites.class.php';
require_once __DIR__ . '/class/html.formsendinblue.class.php';

$langs->load("companies");
$langs->load("users");
$langs->load("other");
$langs->load("commercial");
$langs->load("sendinblue@sendinblue");


// SEE DOC
// https://developers.sendinblue.com/docs/how-to-use-webhooks
$data = json_decode(file_get_contents('php://input'));



/**
 * MARKETING WEBHOOK
 */
if(!empty($data->event) && in_array($data->event, array(
	"list_addition",
	"contact_updated",
	"contact_deleted",
	"unsubscribed",
	"delivered",
	"soft_bounced",
	"hard_bounced",
	"click",
	"opened",
	"spam",
)))
{

	/**
	 * Marked as Spam
	 */
	/*
	  {
		"id": xxxxxx,
		"camp_id": xx,
		"email": "example@domain.com",
		"campaign name": "My First Campaign ",
		"date_sent": "2020-10-09 00:00:00",
		"date_event": "2020-10-09 00:00:00",
		"event": "spam",
		"reason": "undesired",
		"tag": "",
		"ts_sent": 1604933619,
		"ts_event": 1604933737,
		"ts": 1604937337
		}
	*/


	/**
	 * Marked as Opened
	 */
	/*
	  {
		"id": xxxxxx,
		"camp_id": xx,
		"email": "example@domain.com",
		"campaign name": "My First Campaign",
		"date_sent": "2020-10-09 00:00:00",
		"date_event": "2020-10-09 00:00:00",
		"event": "opened",
		"tag": "",
		"ts_sent": 1604933619,
		"ts_event": 1604933737,
		"ts": 1604937337
		}
	*/

	/**
	 * Marked as Clicked
	 */
	/*
	  {
			"id": xxxxxx,
		  "camp_id": xx,
		  "email": "example@domain.com",
		  "campaign name": "My First Campaign",
		  "date_sent": "2020-10-09 00:00:00",
		  "date_event": "2020-10-09 00:00:00",
		  "event": "click",
		  "tag": "",
		  "ts_sent": 1604933619,
		  "ts_event": 1604933737,
		  "ts": 1604937337,
		  "URL": "https://myCampaignUrl.net"
		}
	*/


	/**
	 * Marked as Hard Bounced
	 */
	/*
	  {
	  "id": xxxxxx,
	  "camp_id": xx,
	  "email": "example@domain.com",
	  "campaign name": "My First Campaign",
	  "date_sent": "2020-10-09 00:00:00",
	  "date_event": "2020-10-09 00:00:00",
	  "reason": "deferred",
	  "event": "hard_bounced",
	  "tag": "",
	  "sending_ip": "xxx.xxx.xxx.xxx",
	  "ts_sent": 1604933619,
	  "ts_event": 1604933737,
	  "ts": 1604937337,
		}
	*/


	/**
	 * Marked as Soft Bounced
	 */
	/*
	  {
		"id": xxxxxx,
	  "camp_id": xx,
	  "email": "example@domain.com",
	  "campaign name": "My First Campaign",
	  "date_sent": "2020-10-09 00:00:00",
	  "date_event": "2020-10-09 00:00:00",
	  "reason": "deferred",
	  "event": "soft_bounced",
	  "tag": "",
	  "sending_ip": "xxx.xxx.xxx.xxx"
	  "ts_sent": 1604933619,
	  "ts_event": 1604933737,
	  "ts": 1604937337,
		}
	*/



	/**
	 * Marked as Delivered
	 */
	/*
	  {
	  "id": xxxxxx,
	  "camp_id": xx,
	  "email": "example@domain.com",
	  "campaign name": "My First Campaign",
	  "date_sent": "2020-10-09 00:00:00",
	  "date_event": "2020-10-09 00:00:00",
	  "event": "delivered",
	  "tag": "",
	  "sending_ip": "xxx.xxx.xxx.xxx"
	  "ts_sent": 1604933619,
	  "ts_event": 1604933737,
	  "ts": 1604937337,
		}
	*/


	/**
	 * Marked as Unsubscribed
	 */
	/*
	  {
		"id": xxxxxx,
		"camp_id": xx,
		"email": "example@domain.com",
		"campaign name": "My First Campaign",
		"date_sent": "2020-10-09 00:00:00",
		"date_event": "2020-10-09 00:00:00",
		"event": "unsubscribed",
		"tag": "",
		"sending_ip": "xxx.xxx.xxx.xxx",
		"list_id": [
			3,
			42,
		],
		"ts_sent": 1604933619,
		"ts_event": 1604933737,
		"ts": 1604937337,
		}
	*/



	/**
	 * Marked as Unsubscribed
	 */
	/*
	  {
	"id": xxxxxx,
	  "email": "example@domain.com",
	  "event": "contact_deleted",
	  "key": "xxxxxxxxxxxxxxxxxx",
	  "list_id": [
		35
	  ],
	  "date": "2020-10-09 00:00:00",
	  "ts": 1604937111
		}
	*/


	/**
	 * Contact updated
	 */
	/*
	  {
	"id": xxxxxx,
	  "email": "example@domain.com",
	  "event": "contact_updated",
	  "key": "xxxxxxxxxxxxxxxxxx",
	  "content": [
		"name": "John",
		"lastname" : "Doe",
		"work_phone": "+506 2220 2307"
	  ],
	  "date": "2020-10-09 00:00:00",
	  "ts": 1604937111
		}
	*/


	/**
	 * Contact added to list
	 */
	/*
	  {
		"id": xxxxxx,
	  "email": "example@domain.com",
	  "event": "list_addition",
	  "key": "xxxxxxxxxxxxxxxxxx",
	  "list_id": [
			 34,
		 12
	  ],
	  "date": "2020-10-09 00:00:00",
	  "ts": 1604937111
		}
	*/

}


/**
 * Transactional WEBHOOK
 */
if(!empty($data->event) && in_array($data->event, array(
		"request",
		"click",
		"deferred",
		"delivered",
		"soft_bounce",
		"hard_bounce",
		"complaint",
		"unique_opened",
		"opened",
		"invalid_email",
		"blocked",
		"unsubscribed",
	)))
{

	/**
	 * mail Sent
	 */
	/*
	 * {
    "event":"request",
  "email": "example@domain.com",
  "id": xxxxx,
  "date": "2020-10-09 00:00:00",
  "ts":1604933619,
  "message-id": "201798300811.5787683@relay.domain.com",
  "ts_event": 1604933654,
  "subject": "My first Transactional",
    "X-Mailin-custom": "some_custom_header",
  "sending_ip": "xxx.xxx.xxx.xxx",
  "ts_epoch": 1604933654,
  "template_id": 22,
  "tags": ["transac_messages"]
	}
	*/

	/**
	 * Clicked
	 */
	/*
	  {
    "event":"click",
  "email": "example@domain.com",
  "id": xxxxx,
  "date": "2020-10-09 00:00:00",
  "ts":1604933619,
  "message-id": "201798300811.5787683@relay.domain.com",
  "ts_event": 1604933654,
  "subject": "My first Transactional",
    "X-Mailin-custom": "some_custom_header",
  "sending_ip": "xxx.xxx.xxx.xxx",
  "ts_epoch": 1604933654,
  "template_id": 22,
  "tags": ["transac_messages"],
	*/


	/**
	 * Deferred
	 */
	/*
	  {
    "event":"deferred",
  "email": "example@domain.com",
  "id": xxxxx,
  "date": "2020-10-09 00:00:00",
  "ts":1604933619,
  "message-id": "201798300811.5787683@relay.domain.com",
  "ts_event": 1604933654,
  "subject": "My first Transactional",
    "X-Mailin-custom": "some_custom_header",
  "sending_ip": "xxx.xxx.xxx.xxx",
  "ts_epoch": 1604933654,
  "template_id": 22,
  "tags": ["transac_messages"],
  "reason": "spam"
	}
	*/



	/**
	 * Delivered
	 */
	/*
	  {
    "event":"delivered",
  "email": "example@domain.com",
  "id": xxxxx,
  "date": "2020-10-09 00:00:00",
  "ts":1604933619,
  "message-id": "201798300811.5787683@relay.domain.com",
  "ts_event": 1604933654,
  "subject": "My first Transactional",
    "X-Mailin-custom": "some_custom_header",
  "sending_ip": "xxx.xxx.xxx.xxx",
  "template_id": 22,
  "tags": ["transac_messages"],
	}
	*/

	/**
	 * Soft bounced
	 */
	/*
	  {
    "event":"soft_bounce",
  "email": "example@domain.com",
  "id": xxxxx,
  "date": "2020-10-09 00:00:00",
  "ts":1604933619,
  "message-id": "201798300811.5787683@relay.domain.com",
  "ts_event": 1604933654,
  "subject": "My first Transactional",
    "X-Mailin-custom": "some_custom_header",
  "sending_ip": "xxx.xxx.xxx.xxx",
  "template_id": 22,
  "tags": ["transac_messages"],
  "reason": "server is down"
	}
	*/


	/**
	 * Hard bounced
	 */
	/*
	  {
 "event":"hard_bounce",
  "email": "example@domain.com",
  "id": xxxxx,
  "date": "2020-10-09 00:00:00",
  "ts":1604933619,
  "message-id": "201798300811.5787683@relay.domain.com",
  "ts_event": 1604933654,
  "subject": "My first Transactional",
    "X-Mailin-custom": "some_custom_header",
  "sending_ip": "xxx.xxx.xxx.xxx",
  "template_id": 22,
  "tags": ["transac_messages"],
  "reason": "server is down",
  "ts_epoch":1604933653
	}
	*/



	/**
	 * Complaint
	 */
	/*
	  {
    "event":"complaint",
  "email": "example@domain.com",
  "id": xxxxx,
  "date": "2020-10-09 00:00:00",
  "ts":1604933619,
  "message-id": "201798300811.5787683@relay.domain.com",
  "ts_event": 1604933654,
    "X-Mailin-custom": "some_custom_header",
  "tags": ["transac_messages"],
	}
	*/



	/**
	 * First opening
	 */
	/*
	  {
 "event":"unique_opened",
  "email": "example@domain.com",
  "id": xxxxx,
  "date": "2020-10-09 00:00:00",
  "ts":1604933619,
  "message-id": "201798300811.5787683@relay.domain.com",
  "ts_event": 1604933654,
  "subject": "My first Transactional",
    "X-Mailin-custom": "some_custom_header",
  "sending_ip": "xxx.xxx.xxx.xxx",
  "template_id": 22,
  "tags": ["transac_messages"],
  "ts_epoch": 1604933623
	}
	*/


	/**
	 * Opened
	 */
	/*
	  {
"event":"opened",
  "email": "example@domain.com",
  "id": xxxxx,
  "date": "2020-10-09 00:00:00",
  "ts":1604933619,
  "message-id": "201798300811.5787683@relay.domain.com",
  "ts_event": 1604933654,
  "subject": "My first Transactional",
    "X-Mailin-custom": "some_custom_header",
  "sending_ip": "xxx.xxx.xxx.xxx",
  "template_id": 22,
  "tags": ["transac_messages"],
  "ts_epoch": 1604933623
	}
	*/

	/**
	 * Invalid email
	 */
	/*
	  {
   "event":"invalid_email",
  "email": "example@domain.com",
  "id": xxxxx,
  "date": "2020-10-09 00:00:00",
  "ts":1604933619,
  "message-id": "201798300811.5787683@relay.domain.com",
  "ts_event": 1604933654,
  "subject": "My first Transactional",
    "X-Mailin-custom": "some_custom_header",
  "template_id": 22,
  "tags": ["transac_messages"],
  "ts_epoch": 1604933623
	}
	*/



	/**
	 * Blocked
	 */
	/*
	  {
    "event":"blocked",
  "email": "example@domain.com",
  "id": xxxxx,
  "date": "2020-10-09 00:00:00",
  "ts":1604933619,
  "message-id": "201798300811.5787683@relay.domain.com",
  "ts_event": 1604933654,
  "subject": "My first Transactional",
    "X-Mailin-custom": "some_custom_header",
  "template_id": 22,
  "tags": ["transac_messages"],
  "ts_epoch": 1604933623
	*/


	/**
	 * Unsubscribe
	 */
	/*
	  {
    "event":"unsubscribed",
  "email": "example@domain.com",
  "id": xxxxx,
  "date": "2020-10-09 00:00:00",
  "ts":1604933619,
  "message-id": "201798300811.5787683@relay.domain.com",
  "ts_event": 1604933654,
  "subject": "My first Transactional",
    "X-Mailin-custom": "some_custom_header",
  "template_id": 22,
  "tag":"[\"transactionalTag\"]",
  "ts_epoch": 1604933623,
  "sending_ip": "xxx.xxx.xxx.xxx"
	*/
}
