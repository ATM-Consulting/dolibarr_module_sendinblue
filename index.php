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

// Change this following line to use the correct relative path (../, ../../, etc)
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include '../main.inc.php';            // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../main.inc.php")) $res = @include '../../main.inc.php';        // to work if your module directory is into a subdir of root htdocs directory
if (!$res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT . '/comm/mailing/class/mailing.class.php';
dol_include_once('/sendinblue/class/dolsendinblue.class.php');

// Load translation files required by the page
$langs->load("sendinblue@sendinblue");
$langs->load('products');

$action=GETPOST('action','alpha');
$confirm = GETPOST('confirm', 'none');

$limit = (GETPOST('limit', 'int') && GETPOST('limit', 'int') <= Sendinblue::$SENDINBLUE_API_LINES_LIMIT) ? GETPOST('limit', 'int') : Sendinblue::$SENDINBLUE_API_LINES_LIMIT; // SendInBlue does not send any result if limit > 50
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1
$pageprev = $page - 1;
$pagenext = $page + 1;
$page = ($page >= 0 ? ($page <= 499 ? $page : 499) : 0);
$page_limit = ($page_limit > 0 ? ($page_limit < 1000 ? $page_limit : 1000) : 1);

$param='';
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
$param2='';
if ($page > 0) $param2 .= '&page=' . urlencode($page);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('sendinblueindex'));


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if ($action == 'updateallcampagin_confirm' && $confirm = 'yes' && $user->rights->mailing->creer) {
		$sendinblue = new DolSendinblue($db);
		$result = $sendinblue->updateSendinBlueAllCampaignStatus($user);
		if ($result < 0) {
			setEventMessage($sendinblue->error, 'errors');
		}
	} elseif ($action == 'createemailing_confirm' && $confirm = 'yes' && !empty($user->rights->mailing->creer)) {
		$sendinblue_id = GETPOST('sendinblueid', 'int');
		$list_choice = GETPOST('listchoice', 'int');
		$list_id = GETPOST('listid', 'int');
		$list_name = GETPOST('listname', 'san_alpha');

		$sendinblue = new DolSendinBlue($db);
		$result = $sendinblue->createDolibarrEmailingFromSendinBlueCampaign($user, $sendinblue_id,
			$list_choice == 1 ? $list_id : 0, $list_choice == 2 ? $list_name : '');
		if ($result < 0) {
			setEventMessages($sendinblue->error, $sendinblue->errors, 'errors');
		} else {
			$parameters = ltrim($param . $param2, '&');
			header("Location: " . $_SERVER['PHP_SELF'] . (!empty($parameters) ? '?' . $parameters : ''));
		}
	}
}

/*
 * View
 */

$form = new Form($db);

llxHeader('',$langs->trans("Module104036Name"));

$formconfirm = '';

if ($action == 'updateallcampagin') {
	$text = $langs->trans("SendinBlueConfirmUpdateAllCampaignText", dol_buildpath('/sendinblue/script/update_all_campagin_target.php') . ' ' . $user->login . ' ' . $langs->defaultlang);
	$parameters = ltrim($param . $param2, '&');
	if (method_exists($form, '')) {
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . (!empty($parameters) ? '?' . $parameters : ''), $langs->trans("SendinBlueConfirmUpdateAllCampaign"),
			$text, "updateallcampagin_confirm", '', '', 1, 250);
	} else {
		ob_start();
		$ret = $form->form_confirm($_SERVER['PHP_SELF'] . (!empty($parameters) ? '?' . $parameters : ''), $langs->trans("SendinBlueConfirmUpdateAllCampaign"),
			$text, "updateallcampagin_confirm", '', '', 1, 250);
		if ($ret == 'html') print '<br>';
		$formconfirm = ob_get_contents();
		ob_clean();
	}
} elseif ($action == 'createemailing') {
	$sendinblue_id = GETPOST('sendinblueid', 'int');

	$sendinblue = new DolSendinBlue($db);
	$list_array = $sendinblue->getCampaignListArray($sendinblue_id);
	if (!is_array($list_array)) {
		setEventMessages($sendinblue->error, $sendinblue->errors, 'errors');
	} else {
		$campaignname = GETPOST('campaignname', 'alpha');
		$list_choice = GETPOST('listchoice', 'int');
		$list_id = GETPOST('listid', 'int');
		$list_name = GETPOST('listname', 'san_alpha');
		if ($list_choice === '') $list_choice = empty($list_array) ? 2 : 1;
		if ($list_name === '') $list_name = trim($campaignname);

		$formquestion = array(
			array("type" => "onecolumn", "value" => $langs->trans('SendinBlueAssociateCampaignList') . ' :'),
			array("type" => "other", "name" => "listid",
				"label" => '<input type="radio" name="listchoice" id="listchoice" value="1"' . ($list_choice == 1 ? ' checked="checked"' : '') . (empty($list_array) ? ' disabled="disabled"' : '') . '> ' .
					'<label class="radioselect">' . $langs->trans('SendinBlueChoiceExistingCampaignList') . '</label>',
				"value" => $form->selectarray("listid", $list_array, $list_id, 0, 0, 0, '', 0, 0, empty($list_array), '', 'radioselect')),
			array("type" => "text", "name" => "listname", "morecss" => "centpercent radioselect",
				"label" => '<input type="radio" name="listchoice" id="listchoice" value="2"' . ($list_choice == 2 ? ' checked="checked"' : '') . '> ' .
					'<label class="radioselect">' . $langs->trans('SendinBlueCreateNewCampaignList') . '</label>',
				"value" => $list_name),
			array("name" => "listchoice"),
		);
		if (method_exists($form, '')) {
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?sendinblueid=' . $sendinblue_id . $param . $param2, $langs->trans("SendinBlueCreateEmailingFromCampaign"),
				$langs->trans("SendinBlueConfirmCreateEmailingFromCampaign", $campaignname), "createemailing_confirm", $formquestion, '', 1, 230, 800);
		} else {
			ob_start();
			$ret = $form->form_confirm($_SERVER['PHP_SELF'] . '?sendinblueid=' . $sendinblue_id . $param . $param2, $langs->trans("SendinBlueCreateEmailingFromCampaign"),
				$langs->trans("SendinBlueConfirmCreateEmailingFromCampaign", $campaignname), "createemailing_confirm", $formquestion, '', 1, 230, 800);
			if ($ret == 'html') print '<br>';
			$formconfirm = ob_get_contents();
			ob_clean();
		}
		$formconfirm .= <<<SCRIPT
    <script type="text/javascript" language="javascript">
        jQuery(document).ready(function(){
            jQuery('.radioselect').on('click', function(){
                jQuery(this).closest('tr').find('#listchoice').prop('checked', true);
            })
        });
    </script>
SCRIPT;
	}
}

// Call Hook formConfirm
$parameters = array();
$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;

// Print form confirm
print $formconfirm;


$sendinblue= new DolSendinBlue($db);
$result=$sendinblue->getListCampaign($page, $limit);
if ($result<0) {
	setEventMessage($sendinblue->error,'errors');
}

dol_htmloutput_mesg($langs->transnoentities('SendinBlueExplainIndex',$langs->transnoentities('Reference'),$langs->transnoentities('ProductServiceCard')),'','warning',1);

print '<form method="POST" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="page" value="'.$page.'">';

print_barre_liste($langs->trans('SendinBlueCampaign'), $page, $_SERVER["PHP_SELF"], $param, '', '', '',
	count($sendinblue->listcampaign_lines), $sendinblue->total_campaign_records, '', 0, '', '', $limit);

print '</form>';

print '<table class="border" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans('SendinBlueCampaign').'</td>';
print '<td>'.$langs->trans('DolibarrCampaignLink').'</td>';
print '<td>'.$langs->trans('Status').'</td>';
print '</tr>';

if (is_array($sendinblue->listcampaign_lines) && count($sendinblue->listcampaign_lines)>0) {

	$idx = 0;
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
		}else if(!empty($line['name'])){
			$link = "https://my.sendinblue.com/camp/report/id/".$line['id'];
			$title = $line['name'];
		}

		print "<tr " . $bc[$var] . ">";
		print '<td><a target="_blanck" href='.$link.'>'.$title.'</a></td>';
		print '<td>';
		if (!empty($sendinblue_dolibarr->fk_mailing)) {
			$mailing = new Mailing($db);
			$mailing->fetch($sendinblue_dolibarr->fk_mailing);
			print '<a target="_blanck" href="' . dol_buildpath('/comm/mailing/card.php', 1) . '?id=' . $mailing->id . $param . $param2 . '">' . (empty($mailing->titre) ? $langs->trans('RecordDeleted') : $mailing->titre) . '</a>';
		} else {
			print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=createemailing&sendinblueid=' . $line['id'] .
				'&campaignname=' . urlencode(!empty($line['settings']['title']) ? $line['settings']['title'] : $line['campaign_name']) . $param . $param2 . '">' .
				$langs->trans("SendinBlueCreateEmailingFromCampaign") . '</a>';
		}
		print '</td>';
		print '<td>'.DolSendinBlue::getLibStatus($line['status']).'</td>';
		print '</tr>';
		if (++$idx >= $limit) break;
	}
}else {
	print "<tr " . $bc[$var] . ">";
	print '<td colspan="3">'.$langs->trans('NoRecords').'</td>';
	print '</tr>';
}
print '<table>';

print "\n\n<div class=\"tabsAction\">\n";
if ($user->rights->mailing->creer && is_array($sendinblue->listcampaign_lines) && count($sendinblue->listcampaign_lines)>0) {
	print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=updateallcampagin' . $param . $param2 . '">' . $langs->trans("SendinBlueUpdateAllCampaign") . '</a>';
}
print '<br><br></div>';

llxFooter();
$db->close();
