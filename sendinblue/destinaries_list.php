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

/**
 *	\file		/sendinblue/sendinblue/destinaries_list.php
 *	\ingroup	sendinblue
 */

$res = 0;
if (! $res && file_exists("../main.inc.php")) {
	$res = @include("../main.inc.php");
}
if (! $res && file_exists("../../main.inc.php")) {
	$res = @include("../../main.inc.php");
}
if (! $res && file_exists("../../../main.inc.php")) {
	$res = @include("../../../main.inc.php");
}
if (! $res) {
	die("Main include failed");
}
require_once '../class/dolsendinblue.class.php';
require_once '../class/html.formsendinblue.class.php';

global $bc, $conf, $db, $langs;

// Load translation files required by the page
$langs->load("sendinblue@sendinblue");

// Get parameters
$productid = GETPOST('productid', 'int');
$action = GETPOST('action', 'alpha');
$type = GETPOST('type', 'alpha');
$nameList = GETPOST('nameList', 'none');

//Set page var
$refemail=false;
$error=0;

$sendinblue= new DolSendinBlue($db);
if ($result<0) {
	setEventMessage($sendinblue->error,'errors');
}


// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('sendinbluedestlist'));


/*
 * ACTIONS
*
* Put here all code to do according to value of "action" parameter
*/

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

if ($action=='associateconfirm') {
	if(!empty($nameList)){
		$listid = $sendinblue->createList($nameList);
	} else {
		$listid=GETPOST('selectlist','alpha');
	}
	$emailtoadd=GETPOST('emailtoadd', 'custom', 0, FILTER_SANITIZE_EMAIL);
	if (empty($listid)) {
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("SendinBlueUpdateExistingList")),'errors');
		$error++;
	}




	if (!is_array($emailtoadd)) {
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("EMail")),'errors');
		$error++;
	} else if (count($emailtoadd)==0) {
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("EMail")),'errors');
		$error++;
	}
	if (!$error) {
		$result=$sendinblue->addEmailToList($listid,$emailtoadd);
		if ($result<0) {
			setEventMessage($sendinblue->error,'errors');
		}
	}else {
		$action='associate';
	}

}

$result=$sendinblue->getListDestinaries();
/*
 * VIEW
*
* Put here all code to build page
*/

llxHeader('',$langs->trans("SendinBlueDestList"));

$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$product,$action);

$formsendinblue = new FormSendinBlue($db);

if (!empty($conf->global->SENDINBLUE_API_KEY)) {

	//View associate
	if ($action=='associate') {

		print_fiche_titre($langs->trans('SendinBlueDestListAction'));

		print '<form name="formsoc" method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
		print '<input type="hidden" value="associateconfirm" name="action">';
		print '<input type="hidden" value="'.$_SESSION['newtoken'].'" name="token">';
		print '<input type="hidden" value="'.$productid.'" name="productid">';
		print '<input type="hidden" value="'.$type.'" name="type">';

		print '<table class="border" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('EMail').'</td>';
		print '<td>'.$langs->trans('ThirdParty').'</td>';
		print '<td>'.$langs->trans('ContactsForCompany').'</td>';
		print '<td>'.$langs->trans('SendinBlueSelected').'</td>';
		print '</tr>';

		$result=$sendinblue->getEmailListFromReferent($type,$productid);
		if ($result<0) {
			setEventMessage($sendinblue->error,'errors');
		}

		if (is_array($sendinblue->email_lines) && count($sendinblue->email_lines)>0) {

			$refemail=true;

			foreach ($sendinblue->email_lines as $line) {

				$var=!$var;

				print "<tr " . $bc[$var] . ">";
				print '<td>'.$line->email.'</td>';
				if ($line->type=='thirdparty') {
					print '<td><a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$line->id.'">'.$line->thirdparty.'</a></td>';
				}else {
					print '<td>'.$line->thirdparty.'</td>';
				}
				if ($line->type=='contact') {
					print '<td><a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$line->id.'">'.$line->contactfullname.'</a></td>';
				}else {
					print '<td>'.$line->contactfullname.'</td>';
				}
				print '<td><input type="checkbox"  checked="checked" name="emailtoadd[]" value="'.$line->email.'&'.$line->type.'&'.$line->id.'"></td>';
				print '</tr>';
			}
		}else {
			print "<tr " . $bc[$var] . ">";
			print '<td colspan="4">'.$langs->trans('SendinBlueNoEmailFound').'</td>';
			print '</tr>';
		}

		print '</table>';


		if ($refemail) {

			print '<center><br>';
			print $langs->trans('SendinBlueUpdateExistingList');
			$events=array();
			if ($conf->use_javascript_ajax) {
				$events[]=array('method' => 'getSegment', 'url' => dol_buildpath('/sendinblue/sendinblue/ajax/sendinblue.php',1), 'htmlname' => 'segmentlist','params' => array('blocksegement' => 'style'));
			}
			print $formsendinblue->select_sendinbluelist('selectlist',1,'',0,$events);
			print '<br>'.$langs->trans('SendinBlueOr');

			print ' '.$langs->trans('SendinBlueCreateList').' : ';
		//print '&nbsp;<a href="https://my.sendinblue.com/lists" target="_blanck" >'.$langs->trans('SendinBlueNewListName').'</a>';

			print '&nbsp; <input type="text" name="nameList"></input>';
			print '</td></tr>';
			print '<div id="blocksegement" >';
		//	print $langs->trans('SendinBlueUpdateExistingSegments');
			//print $formsendinblue->select_sendinbluesegement(0,'segmentlist');
			//print '<br>'.$langs->trans('SendinBlueOr');

			//print '&nbsp;'.$langs->trans('SendinBlueNewSegmentName').': <input type="text" class="flat" size="8" maxsize="50" name="segmentname">';

			print '<br><input type="submit" class="button" value="'.$langs->trans('Save').'"/>';
			print '</div>';
			print '</center>';

		}
	}

	print '<form>';

	print '<BR>';
	print_fiche_titre($langs->trans('SendinBlueDestList'));

	print '<table class="border" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('SendinBlueListName').'</td>';
	print '<td>'.$langs->trans('SendinBlueSubcribers').'</td>';
	print '<td>'.$langs->trans('DateCreation').'</td>';
	print '</tr>';

	$sendinbluesegment= new DolSendinBlue($db);
	if (is_array($sendinblue->listdest_lines) && count($sendinblue->listdest_lines)>0) {
		foreach($sendinblue->listdest_lines['data'] as $dest_line) {

			$var=!$var;
			print "<tr " . $bc[$var] . ">";
			print '<td><a target="_blanck" href="https://my.sendinblue.com/users/list/id/'.$dest_line['id'].'">'.$dest_line['name'].'</a></td>';
			if(empty($dest_line['total_subscribers'])) $dest_line['total_subscribers']=0;
			print '<td>'.$dest_line['total_subscribers'].'</td>';
			print '<td>'.$dest_line['entered'].'</td>';
			print '</tr>';

			/*$result=$sendinbluesegment->getListSegmentDestinaries($dest_line['id']);
			if ($result<0) {
				setEventMessage($sendinbluesegment->error,'errors');
			}

			if (is_array($sendinbluesegment->listsegment_lines) && count($sendinbluesegment->listsegment_lines)>0) {
				foreach($sendinbluesegment->listsegment_lines as $seg_line) {

					print "<tr " . $bc[$var] . ">";
					print '<td>&nbsp;&nbsp;&nbsp;&nbsp;'.$langs->trans('SendinBlueSegment').' : '.$seg_line['name'].'</td>';
					print '<td>'.$seg_line['member_count'].'</td>';
					print '<td>'.$seg_line['created_at'].'</td>';
					print '</tr>';
				}

			}*/

		}
	}
	else {
		print "<tr " . $bc[$var] . ">";
		print '<td colspan="3">'.$langs->trans('SendinBlueListNoEmailFound').'</td>';
		print '</tr>';
	}

	print '</table>';


}

llxFooter();
$db->close();
