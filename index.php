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
 *	\file		/sendinblue/index.php
 *	\ingroup	sendinblue
 */
/*
error_reporting(E_ALL);
ini_set('display_errors', true);
ini_set('html_errors', false);
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

require_once 'class/dolsendinblue.class.php';

$action=GETPOST('action','alpha');
$confirm = GETPOST('confirm');

if ($action=='updateallcampagin_confirm' && $confirm='yes' && $user->rights->mailing->creer) {
	$sendinblue= new DolSendinblue($db);
	$result=$sendinblue->updateSendinBlueAllCampaignStatus($user);
	if ($result<0) {
		setEventMessage($sendinblue->error,'errors');
	}
}


// Load translation files required by the page
$langs->load("sendinblue@sendinblue");

llxHeader('',$langs->trans("Module104036Name"));

if ($action=='updateallcampagin') {
	$form = new Form($db);
	$text=$langs->trans("SendinBlueConfirmUpdateAllCampaignText",dol_buildpath('/sendinblue/script/update_all_campagin_target.php').' '.$user->login.' '.$langs->defaultlang);
	$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?'.$urlconfirm,$langs->trans("SendinBlueConfirmUpdateAllCampaign"),$text,"updateallcampagin_confirm",'','',1,250);
	if ($ret == 'html') print '<br>';
}


$sendinblue= new DolSendinBlue($db);
$result=$sendinblue->getListCampaign();

if ($result<0) {
	setEventMessage($sendinblue->error,'errors');
}
$langs->load('products');

dol_htmloutput_mesg($langs->trans('SendinBlueExplainIndex',$langs->transnoentities('Reference'),$langs->transnoentities('ProductServiceCard')),'','warning',1);

print_fiche_titre($langs->trans('SendinBlueCampaign'));

print '<table class="border" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('SendinBlueCampaign').'</td>';
print '<td>'.$langs->trans('DolibarrCampaignLink').'</td>';
print '<td>'.$langs->trans('Status').'</td>';
print '</tr>';

if (is_array($sendinblue->listcampaign_lines) && count($sendinblue->listcampaign_lines)>0) {

	foreach($sendinblue->listcampaign_lines as $line) {
		$var=!$var;
		$sendinblue_dolibarr= new DolSendinBlue($db);
		if (!empty($line['id'])) {
			
			$result=$sendinblue_dolibarr->fetch_by_sendinblueid($line['id']);
			if ($result<0) {
				setEventMessage($sendinblue_dolibarr->error,'errors');
			}
		}
		if(!empty($line['settings']['title'])){
			$link = "http://admin.sendinblue.com/campaigns/show?id=".$line['id'];
			$title = $line['settings']['title'];
		}else if(!empty($line['campaign_name'])){
			$link = "https://my.sendinblue.com/camp/step4/type/".$line['type']."/id/".$line['id'];
			$title = $line['campaign_name'];
		}
		
		print "<tr " . $bc[$var] . ">";
		print '<td><a target="_blanck" href='.$link.'>'.$title.'</a></td>';
		print '<td>';
		if (!empty($sendinblue_dolibarr->fk_mailing)) {
			print '<a target="_blanck" href="'.dol_buildpath('/comm/mailing/card.php',1).'?id='.$sendinblue_dolibarr->fk_mailing.'">'.$line['campaign_name'].'</a>';
		} else {
			print '-';
		}
		print '</td>';
		print '<td>'.DolSendinBlue::getLibStatus($line['status']).'</td>';
		print '</tr>';
	}
}else {
	print "<tr " . $bc[$var] . ">";
	print '<td>'.$langs->trans('NoRecords').'</td>';
	print '</tr>';
}
print '<table>';

print "\n\n<div class=\"tabsAction\">\n";
if ($user->rights->mailing->creer && count($sendinblue->listcampaign_lines)>0) {
	print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=updateallcampagin">'.$langs->trans("SendinBlueUpdateAllCampaign").'</a>';
}
print '<br><br></div>';

llxFooter();
$db->close();