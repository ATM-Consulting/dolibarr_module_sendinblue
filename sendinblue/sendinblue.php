<?php
/* <SendinBlue connector>
 * Copyright (C) 2013 Florian Henry florian.henry@open-concept.pro
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

/*error_reporting(E_ALL);
 ini_set('display_errors', true);
ini_set('html_errors', false);*/

/**
 *	\file		/sendinblue/sendinblue/sendinblue.php
 *	\ingroup	sendinblue
 */


require '../config.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/emailing.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once '../class/dolsendinblue.class.php';
require_once '../class/html.formsendinblue.class.php';

// Load translation files required by the page
$langs->load("sendinblue@sendinblue");
$langs->load("mails");

// Get parameters
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm');
$createList = GETPOST('createList');
$nameList = GETPOST('nameList');

$error=0;

// Access control
if (! $user->rights->mailing->creer || (empty($conf->global->EXTERNAL_USERS_ARE_AUTHORIZED) && $user->societe_id > 0 )) {
	accessforbidden();
}

$object=new Mailing($db);
$result=$object->fetch($id);
if ($result<0) {
	setEventMessage($object->error,'errors');
}

$sendinblue= new DolSendinBlue($db);
$result=$sendinblue->fetch_by_mailing($id);
if ($result<0) {
	setEventMessage($sendinblue->error,'errors');
}

$extrafields = new ExtraFields($db);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('sendinbluecard'));



$error_sendinblue_control=0;



//$sendinblue->APIListExport();

/*
 * ACTIONS
*
* Put here all code to do according to value of "action" parameter
*/

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if(!empty($createList) && !empty($nameList)){
	$sendinblue->createList($nameList);
}
// Action update description of emailing
if ($action == 'settitre' || $action == 'setemail_from') {

	if ($action == 'settitre')					$object->titre          = trim(GETPOST('titre','alpha'));
	else if ($action == 'setemail_from')		$object->email_from     = trim(GETPOST('email_from','alpha'));

	else if ($action == 'settitre' && empty($object->titre))		$mesg.=($mesg?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->transnoentities("MailTitle"));
	else if ($action == 'setfrom' && empty($object->email_from))	$mesg.=($mesg?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->transnoentities("MailFrom"));

	if (empty($mesg)) {
		if ($object->update($user) >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			setEventMessage($object->error,'errors');
		}
	} else {
		setEventMessage($mesg,'errors');
	}

	$action="";
}

if ($action=='associateconfirm') {

	$import=GETPOST('import','alpha');
	$export=GETPOST('export','alpha');
	$updateonly=GETPOST('updateonly','alpha');
	$updatesegment=GETPOST('updatesegment','alpha');
	$segmentid=GETPOST('segmentlist','alpha');
	$listid=GETPOST('selectlist','alpha');
	$newsegmentname=GETPOST('segmentname','alpha');
	$resetseg=GETPOST('resetseg','int');

	$sendinblue->sendinblue_listid=$listid;

	if (empty($sendinblue->id)) {
		$sendinblue->fk_mailing=$id;
		$result=$sendinblue->create($user);
		if ($result<0) {
			setEventMessage($sendinblue->error,'errors');
			$error++;
		}
	}

	if (empty($error)) {
		if (!empty($import)) {
			if (!empty($listid)) {
				$result=$sendinblue->importSegmentDestToDolibarr($listid);
				if ($result<0) {
					$error++;
					setEventMessage($sendinblue->error,'errors');
				}
			}
		}

		if (!empty($export)) {
			if (empty($listid)) {
				setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("SendinBlueList")), 'errors');
			} else {
				$result=$sendinblue->exportDesttoSendinBlue($listid);
				if ($result<0) {
					$error++;
					setEventMessage($sendinblue->error,'errors');
				}
			}
		}
		
		if (!$error)
		{
			$result=$sendinblue->update($user);
			if ($result<0) {
				setEventMessage($sendinblue->error,'errors');
			}
		}
	}

	$result=$object->fetch($id);
	if ($result<0) {
		setEventMessage($object->error,'errors');
	}
}

if ($action=='setsendinblue_sender_name') {
	$sendinblue->sendinblue_sender_name  = GETPOST('sendinblue_sender_name','alpha');
	if (empty($sendinblue->id)) {
		$sendinblue->fk_mailing=$id;
		$result=$sendinblue->create($user);
	}else {
		$result=$sendinblue->update($user);
	}
	if ($result<0) {
		setEventMessage($sendinblue->error,'errors');
	}else {
		$result=$sendinblue->fetch_by_mailing($id);
		if ($result<0) {
			setEventMessage($sendinblue->error,'errors');
		}
	}
}


if ($action == 'createsendinbluecampaign') {

	$sendinblue->currentmailing=$object;

	$result=$sendinblue->createSendinBlueCampaign($user);
	if ($result<0) {
		
		setEventMessage($sendinblue->error,'errors');
	}
}

if ($action=='sendsendinbluecampaign') {
	//Send campaign
	$result=$sendinblue->sendSendinBlueCampaign();
	if ($result<0) {
		setEventMessage($sendinblue->error,'errors');
	} else {
		//Update mailing general status
		$object->statut=3;
		$sql="UPDATE ".MAIN_DB_PREFIX."mailing SET statut=".$object->statut." WHERE rowid=".$object->id;
		dol_syslog("sendinblue/sendinblue/sendinblue.php: update global status sql=".$sql, LOG_DEBUG);
		$resql2=$db->query($sql);
		if (! $resql2)	{
			setEventMessage($db->lasterror(),'errors');
		}
	}
}

if ($action=='updatesendinbluecampaignstatus_confirm' && $confirm='yes') {
	$result=$sendinblue->updateSendinBlueCampaignStatus($user);
	if ($result<0) {
		setEventMessage($sendinblue->error,'errors');
	} else {
		//Header("Location: " . $_SERVER ['PHP_SELF'] . "?id=" . $id);
	}
}




//Attached file are not allowed for SendinBlue Mailing
$error_file_attach=false;
$upload_dir = $conf->mailing->dir_output . "/" . get_exdir($object->id,2,0,1,$object,'mailing');
$listofpaths=dol_dir_list($upload_dir,'all',0,'','','name',SORT_ASC,0);
if (count($listofpaths))
{
	$error_file_attach =true;
	$error_sendinblue_control++;
}
//Unsubscribe link mandatory
$error_no_unscubscribe_link=false;

//if (preg_match('/\*\|UNSUB\|\*/',$object->body)==0) {
//	$error_no_unscubscribe_link=true;
//	$error_sendinblue_control++;
//}


//Listid must be define
$error_list_define=false;
if (empty($sendinblue->sendinblue_listid)) {
	$error_list_define=true;
	$error_sendinblue_control++;
}


/*
 * Control for SendinBlue regarding Dolibarr standard mailing
*/
//Check sender name
$error_sendername=false;
if (empty($sendinblue->sendinblue_sender_name)) {
	$error_sendername=true;
	$error_sendinblue_control++;
}

$warning_destnotsync=false;
//Check dolibarr dest versus list segment define
if(!empty($conf->global->SENDINBLUE_API_KEY)){
	if (!empty($sendinblue->id)) {

	if ($object->statut==0 || $object->statut==1) {
		$email_seg_array=array();

		//retrive email for segment and Or List
		if (!empty($sendinblue->sendinblue_segmentid)) {
			$result=$sendinblue->getEmailList();
			if ($result<0) {
				setEventMessage($sendinblue->error,'errors');
			} else {
				foreach($sendinblue->email_lines as $l){
					$email_seg_array[]=$l['email'];
				}
			}
		}
		else {
			if (!empty($sendinblue->sendinblue_listid)) {
				$result=$sendinblue->getEmailList();
				
				if ($result<0) {
					
					setEventMessage($sendinblue->error,'errors');
				} else {
					foreach($sendinblue->email_lines as $l){
					$email_seg_array[]=$l['email'];
				}
				}
			}
		}


		//Retreive mail from mailling destinaries
		$sendinblue->fk_mailing=$id;
		$result=$sendinblue->getEmailMailingDolibarr();
		if ($result<0) {
			setEventMessage($sendinblue->error,'errors');
		} else {
			$email_dol_array=$sendinblue->email_lines;
		}

		$email_in_dol_not_in_sendinblue=array();
		//First compare count easy and quick
		if (count($email_dol_array)!=count($email_seg_array)) {
			$warning_destnotsync=true;
			foreach ($email_dol_array as $emailadress) {
				if (array_search($emailadress, $email_seg_array)===false) {
					$email_in_dol_not_in_sendinblue[]=$emailadress;
				}
			}

		}else {
			foreach($email_seg_array as $emailadress){
				$email_sb_array[]=$emailadress; 
			}

			//if count is same compare email by email
			foreach($email_dol_array as $emailadress) {
				if (!in_array($emailadress,$email_sb_array)) {
					
					$warning_destnotsync=true;
					break;
				}
			}
			if(!empty($email_sb_array)){
				foreach($email_sb_array as $emailadress) {
					if (!in_array($emailadress,$email_dol_array)) {
						$warning_destnotsync=true;
						break;
					}
				}
			}
		}
	}
} else {
	$warning_destnotsync=true;
}
	
}



/*
 * VIEW
*
* Put here all code to build page
*/

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label('mailing');

llxHeader('',$langs->trans("Mailing"));

if ($action=='updatesendinbluecampaignstatus') {
	$form = new Form($db);
	$urlconfirm='id='.$id;
	$text=$langs->trans("SendinBlueConfirmUpdateCampaignText",dol_buildpath('/sendinblue/scripts/update_all_campagin_target.php').' '.$user->login.' '.$langs->defaultlang.' '.$id);
	$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?'.$urlconfirm,$langs->trans("SendinBlueConfirmUpdateCampaign"),$text,"updatesendinbluecampaignstatus_confirm",'','',1,250);
	if ($ret == 'html') print '<br>';
}



$head = emailing_prepare_head($object);

dol_fiche_head($head, 'tabSendinBlueSending', $langs->trans("SendinBlue"), 0, 'email');

if ( !empty($conf->global->SENDINBLUE_API_KEY)) {

	$form = new Form($db);
	$formsendinblue = new FormSendinBlue($db);

	/*print '<script type="text/javascript" language="javascript">
			$(document).ready(function() {

				// Click Function
				$(":button[name=updatesendinbluecampaignstatus]").click(function() {
						$( "#dialog" ).dialog();
						$( "#progressbar" ).progressbar({
							value: 37
						});
				});
			});
		</script>';

	print '<div id="dialog" title="Basic dialog" style="display:none">';
	print '<p>Operation in progress</p>';
	print '<div id="progressbar"></div>';
	print '</div>';*/

	print '<table class="border" width="100%">';

	if ((float) DOL_VERSION <= 3.6)	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/liste.php">'.$langs->trans("BackToList").'</a>';
	else $linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/list.php">'.$langs->trans("BackToList").'</a>';

	print '<tr><td width="15%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object,'id', $linkback);
	print '</td></tr>';

	// Description
	print '<tr><td>'.$form->editfieldkey("MailTitle",'titre',$object->titre,$object,$user->rights->mailing->creer && $object->statut < 3,'string').'</td><td colspan="3">';
	print $form->editfieldval("MailTitle",'titre',$object->titre,$object,$user->rights->mailing->creer && $object->statut < 3,'string');
	print '</td></tr>';

	// From
	print '<tr><td>'.$form->editfieldkey("MailFrom",'email_from',$object->email_from,$object,$user->rights->mailing->creer && $object->statut < 3,'string').'</td><td colspan="3">';
	print $form->editfieldval("MailFrom",'email_from',$object->email_from,$object,$user->rights->mailing->creer && $object->statut < 3,'string');
	print '</td></tr>';

	// Status
	print '<tr><td width="15%">'.$langs->trans("Status").'</td><td colspan="3">'.$object->getLibStatut(4).'</td></tr>';

	// Nb of distinct emails
	print '<tr><td width="15%">';
	print $langs->trans("TotalNbOfDistinctRecipients");
	print '</td><td colspan="3">';
	$nbemail = ($object->nbemail?$object->nbemail:img_warning('').' <font class="warning">'.$langs->trans("SendinBlueSelectSegmentOrList").'</font>');
	if ($object->statut != 3 && !empty($conf->global->MAILING_LIMIT_SENDBYWEB) && is_numeric($nbemail) && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail)
	{
		if ($conf->global->MAILING_LIMIT_SENDBYWEB > 0)	{
			$text=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
			print $form->textwithpicto($nbemail,$text,1,'warning');
		} else {
			$text=$langs->trans('NotEnoughPermissions');
			print $form->textwithpicto($nbemail,$text,1,'warning');
		}
	} else {
		print $nbemail;
	}
	print '</td></tr>';

	//Glue to avoid problem with edit in place option
	if (! empty($conf->global->MAIN_USE_JQUERY_JEDITABLE)) {
		$objecttoedit=$sendinblue;
		if (empty($sendinblue->id)) {
			$sendinblue->fk_mailing=$object->id;
			$result=$sendinblue->create($user);
		}
	}else {
		$objecttoedit=$object;
	}

	// SendinBlue Sender Name
	print '<tr><td width="15%">';
	print $form->editfieldkey("SendinBlueSenderName",'sendinblue_sender_name',$sendinblue->sendinblue_sender_name,$objecttoedit,$user->rights->mailing->creer && $object->statut < 3 && empty($sendinblue->sendinblue_id),'string');
	print '</td><td colspan="3">';
	print $form->editfieldval("SendinBlueSenderName",'sendinblue_sender_name',$sendinblue->sendinblue_sender_name,$objecttoedit,$user->rights->mailing->creer && $object->statut < 3 && empty($sendinblue->sendinblue_id),'string');
	print '</td></tr>';


	if (!empty($sendinblue->sendinblue_id)) {

		//Status campaign sendinblue
		print '<tr><td width="15%">';
		print $langs->trans("SendinBlueStatus");
		print '</td><td colspan="3">';
		if (!empty($sendinblue->sendinblue_id)) {
			print $sendinblue->getSendinBlueCampaignStatus();
		}
		print '</td></tr>';

		// SendinBlue Campaign
		print '<tr><td width="15%">';
		print $langs->trans("SendinBlueCampaign");
		print '</td><td colspan="3">';
		print '<a target="_blanck" href="https://my.sendinblue.com/camp/listing#draft_c">'.$langs->trans('SendinBlueCampaign').'</a>';
		print '</td></tr>';

		//List campaign sendinblue
		print '<tr><td width="15%">';
		print $langs->trans("SendinBlueDestList");
		print '</td><td colspan="3">';
		if (!empty($sendinblue->sendinblue_listid)) {
			$result=$sendinblue->getListDestinaries(array('id'=>$sendinblue->sendinblue_listid));
			if ($result<0) {
				setEventMessage($sendinblue->error,'errors');
			}
			if (is_array($sendinblue->listdest_lines) && count($sendinblue->listdest_lines)>0) {
				
					
				print $sendinblue->listdest_lines['data']['name'];
				
			}
		}
		print '</td></tr>';
	/*	print '<tr><td width="15%">';
		print $langs->trans("SendinBlueSegment");
		print '</td><td colspan="3">';
		if (!empty($sendinblue->sendinblue_segmentid) && !empty($sendinblue->sendinblue_listid)) {
			$result=$sendinblue->getListSegmentDestinaries($sendinblue->sendinblue_listid);
			if ($result<0) {
				setEventMessage($sendinblue->error,'errors');
			}
			if (is_array($sendinblue->listsegment_lines) && count($sendinblue->listsegment_lines)>0) {
				foreach($sendinblue->listsegment_lines as $line) {
					if ($sendinblue->sendinblue_segmentid== $line['id']) {
						print $line['name'];
					}
				}
			}
		}
		print '</td></tr>';*/
	}


	// Other attributes
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);
	if (empty($reshook) && ! empty($extrafields->attribute_label)) {
		foreach($extrafields->attribute_label as $key=>$label) {
			$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
			print '<tr><td';
			if (! empty($extrafields->attribute_required[$key])) print ' class="fieldrequired"';
			print '>'.$label.'</td><td colspan="3">';
			print $extrafields->showInputField($key,$value);
			print "</td></tr>\n";
		}
	}

	print '</table>';

	if (empty($sendinblue->sendinblue_id) || $object->statut==0) {

		print '<form name="formmailing" method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'">';
		print '<input type="hidden" value="associateconfirm" name="action">';
		print '<input type="hidden" value="'.$_SESSION['newtoken'].'" name="token">';

		print '<table class="border" width="100%">';
		print '<tr class="pair"><td class="fieldrequired">';
		print $langs->trans('SendinBlueUpdateExistingList');
		print '</td><td>';
		$events=array();
		//if ($conf->use_javascript_ajax) {
			//$events[]=array('method' => 'getSegment', 'url' => dol_buildpath('/sendinblue/sendinblue/ajax/sendinblue.php',1), 'htmlname' => 'segmentlist','params' => array('blocksegement' => 'style'));
		//}
		print $formsendinblue->select_sendinbluelist('selectlist',1,$sendinblue->sendinblue_listid,0,$events);
		print '&nbsp;'.$langs->trans('SendinBlueOr');
		print '&nbsp;<input type="submit" class="button" name="createList" value="'.$langs->trans('SendinBlueCreateList').'"/>';
		//print '&nbsp;<a href="https://my.sendinblue.com/lists" target="_blanck" >'.$langs->trans('SendinBlueNewListName').'</a>';
		
		print '&nbsp; <input type="text" name="nameList"></input>';
		
		print '&nbsp;&nbsp;<input type="submit" class="button" name="save" value="'.$langs->trans('Save').'" />';
		
		print '</td></tr>';

		/*print '<tr class="impair" id="blocksegement"><td class="fieldrequired">';
		print $langs->trans('SendinBlueUpdateExistingSegments');
		print '</td><td>';
		//print $formsendinblue->select_sendinbluesegement($sendinblue->sendinblue_listid,'segmentlist',1,$sendinblue->sendinblue_segmentid);
		//print '<input type="checkbox" name="resetseg" value="1"/>'.$langs->trans('SendinBlueResetSegment');
		//print '&nbsp;'.$langs->trans('SendinBlueOr');
		//print '&nbsp;'.$langs->trans('SendinBlueNewSegmentName').': <input type="text" class="flat" size="8" maxsize="50" name="segmentname">';
		print '</td></tr>';*/
		print '<tr class="pair"><td colspan="2" style="text-align:center">';
		print '<input type="submit" class="button" name="import" value="'.$langs->trans('SendinBlueImportForm').'"/>';
		print '<input type="button" class="button" onclick="sendInBlueCallImport()" value="test ajax import" />';
		print '<input type="submit" class="button" name="export" value="'.$langs->trans('SendinBlueExportTo').'"/>';
		print '<input type="button" class="button" onclick="sendInBlueCallExport()" value="test ajax export" />';
	//	print '<input type="submit" class="button" name="updateonly" value="'.$langs->trans('SendinBlueUpdateOnly').'"/>';
		//print '<input type="submit" class="button" name="updatesegment" value="'.$langs->trans('SendinBlueUpdateSegmentOnly').'"/>';
		print '</td></tr></table>';

		print '<form>';
	}

	print "</div>";

	if ($error_file_attach) {
		dol_htmloutput_mesg($langs->trans("SendinBlueNoFileAttached"),'','error',1);
	}
	if ($error_no_unscubscribe_link) {
		dol_htmloutput_mesg($langs->trans("SendinBlueUnsubLinkMandatory"),'','error',1);
	}
	if ($error_sendername) {
		dol_htmloutput_mesg($langs->trans("SendinBlueSenderNameMandatory"),'','error',1);
	}
	if(!strpos($object->email_from,'@')){
		dol_htmloutput_mesg($langs->trans("SendinBlueSenderMustBeAnEmail"),'','error',1);
	}
	if ($warning_destnotsync) {
		dol_htmloutput_mesg($langs->trans("SendinBlueEmailNotSync"),'','warning',1);
		if (count($email_in_dol_not_in_sendinblue)>0) {
			dol_htmloutput_mesg($langs->trans("SendinBlueEmailNotSyncInDolNotSendinBlue").'<br>'.implode('<br>',$email_in_dol_not_in_sendinblue),'','warning',1);
		}
	}
	if ($object->statut == 0) {
		if ((float) DOL_VERSION < 3.7) dol_htmloutput_mesg($langs->trans("SendinBlueNotValidated").' : <a href="'.dol_buildpath('/comm/mailing/fiche.php',1).'?id='.$object->id.'">'.$langs->trans('Mailing').'</a>','','warning',1);
		else  dol_htmloutput_mesg($langs->trans("SendinBlueNotValidated").' : <a href="'.dol_buildpath('/comm/mailing/card.php',1).'?id='.$object->id.'">'.$langs->trans('Mailing').'</a>','','warning',1);
	}

	print "\n\n<div class=\"tabsAction\">\n";
	if (($object->statut == 0) && $user->rights->mailing->creer) {
		if ((float) DOL_VERSION < 3.7) print '<a class="butAction" href="'.dol_buildpath('/comm/mailing/fiche.php',1).'?action=edit&amp;id='.$object->id.'">'.$langs->trans("EditMailing").'</a>';
		else print '<a class="butAction" href="'.dol_buildpath('/comm/mailing/card.php',1).'?action=edit&amp;id='.$object->id.'">'.$langs->trans("EditMailing").'</a>';
	}

	if (($object->statut == 1 || $object->statut == 2) && $object->nbemail > 0 && $user->rights->mailing->valider && !$error_sendinblue_control) {
		if ((! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! $user->rights->mailing->mailing_advance->send)) {
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("SendinBlueCreateCampaign").'</a>';
		} else {
			if (empty($sendinblue->sendinblue_id)) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=createsendinbluecampaign&amp;id='.$object->id.'">'.$langs->trans("SendinBlueCreateCampaign").'</a>';
			}
		}
	}else {
		print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("SendinBlueCannotSendControlNotOK")).'">'.$langs->trans("SendinBlueCreateCampaign").'</a>';
	}
	if (!empty($sendinblue->sendinblue_id) && !$error_sendinblue_control) {
		if (($object->statut == 1 || $object->statut == 2) && $object->nbemail > 0 && $user->rights->mailing->valider) {
			if ((! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! $user->rights->mailing->mailing_advance->send)) {
				print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("SendMailing").'</a>';
			} else {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=sendsendinbluecampaign&amp;id='.$object->id.'">'.$langs->trans("SendinBlueSendMailing").'</a>';
			}
		}
	}

	if (!empty($sendinblue->sendinblue_id) && !$error_sendinblue_control) {
		if (($object->statut == 3 ) && $object->nbemail > 0 && $user->rights->mailing->valider) {
			if ((! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! $user->rights->mailing->mailing_advance->send)) {
				print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("SendMailing").'</a>';
			} else {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=updatesendinbluecampaignstatus&amp;id='.$object->id.'">'.$langs->trans("SendinBlueUpdateStatus").'</a>';
			}
		}
		//TODO: manage with jquery to avoid timeout browser
		//print '<input type="button" name="updatesendinbluecampaignstatus" id="updatesendinbluecampaignstatus" value="' . $langs->trans ( 'SendinBlueUpdateStatus' ) . '" class="butAction"/>';
	}

	print '<br><br></div>';


	// Print mail content
	print_fiche_titre($langs->trans("EMail"),'','');
	print '<table class="border" width="100%">';

	// Subject
	print '<tr><td width="15%">'.$langs->trans("MailTopic").'</td><td colspan="3">'.$object->sujet.'</td></tr>';

	// Message
	print '<tr><td valign="top">'.$langs->trans("MailMessage").'</td>';
	print '<td colspan="3" bgcolor="'.($object->bgcolor?(preg_match('/^#/',$object->bgcolor)?'':'#').$object->bgcolor:'white').'">';
	print dol_htmlentitiesbr($object->body);
	print '</td>';
	print '</tr>';

	print '</table>';
	print "<br>";
}else {
	dol_htmloutput_mesg($langs->trans("InvalidAPIKey"),'','error',1);
}
if($object->statut == 3){
		$PDOdb = new TPDOdb;
		$listeview = new TListviewTBS('graphCampaignActions');
		$TSum[] = array($langs->transnoentities('unique views'),$sendinblue->sendinblue_webid['data'][0]['unique_views']);
		$TSum[] = array($langs->transnoentities('viewed'),$sendinblue->sendinblue_webid['data'][0]['viewed']);	
		$TSum[] = array($langs->transnoentities('clicked'),$sendinblue->sendinblue_webid['data'][0]['clicked']);	
		$TSum[] = array($langs->transnoentities('Hard Bounce'),$sendinblue->sendinblue_webid['data'][0]['hard_bounce']);	
		$TSum[] = array($langs->transnoentities('Soft Bounce'),$sendinblue->sendinblue_webid['data'][0]['soft_bounce']);	
		$TSum[] = array($langs->transnoentities('unsub'),$sendinblue->sendinblue_webid['data'][0]['unsub']);	
		$TSum[] = array($langs->transnoentities('mirror click'),$sendinblue->sendinblue_webid['data'][0]['mirror_click']);	
		$TSum[] = array($langs->transnoentities('complaints'),$sendinblue->sendinblue_webid['data'][0]['complaints']);	
		
		if(!empty($sendinblue->sendinblue_webid['data'][0]['delivered'])){
			print $listeview->renderArray($PDOdb, $TSum
			,array(
			'type' => 'chart'
			,'chartType' => 'PieChart'
			,'liste'=>array(
			'titre'=>$langs->transnoentitiesnoconv('titleGraphCampaignActions')
			)
			)
			);
		}
		
}

//unset($_SESSION['SENDINBLUE_PID_ACTIVE']);
//$_SESSION['SENDINBLUE_PID_ACTIVE'][$object->id][$sendinblue->sendinblue_listid][] = 158;
//$_SESSION['SENDINBLUE_PID_ACTIVE'][$object->id][$sendinblue->sendinblue_listid][] = 159;

//var_dump($_SESSION['SENDINBLUE_PID_ACTIVE']);
//exit;
?>
<script type="text/javascript">
	sendInBlueTimer = null;
	TSendInBluePid = [];
	<?php 
	if (!empty($_SESSION['SENDINBLUE_PID_ACTIVE'][$object->id])) { 
		foreach ($_SESSION['SENDINBLUE_PID_ACTIVE'][$object->id] as $lid => $TPid) {
			foreach ($TPid as $pid) { 
			?>
				TSendInBluePid.push(<?php echo $pid; ?>);
			<?php
			} 
		}
	}
	?>
	
	sendInBlueCallExport = function() {
		sendInBlueCallAjax('export', '');
	};
	
	sendInBlueCallImport = function() {
		sendInBlueCallAjax('import', '');
	};
	
	sendInBlueCallAjax = function(set, get) {
		var listid = $('#selectlist').val();
		var fk_mailing = <?php echo $object->id; ?>;
		$.ajax({
			url: '<?php echo dol_buildpath('/sendinblue/script/interface.php', 1); ?>'
			,type: 'GET'
			,dataType: 'json'
			,data: {
				json: 1
				,get: get
				,set: set
				,listid: listid
				,fk_mailing: fk_mailing
			}
		}).done(function(pid) {
			if (pid > 0) {
				TSendInBluePid.push(pid);
				if (sendInBlueTimer === null) triggerIntervalChecker();
			}
		});
	};
	
	triggerIntervalChecker = function() {
		sendInBlueTimer = setInterval(function() {
			// TODO check TSendInBluePid, when empty go reload page
		}, 5000);
	};
</script>
<?php
// End of page
dol_fiche_end();
llxFooter();
$db->close();
