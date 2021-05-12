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
 * \file admin/sendinblue.php
 * \ingroup sendinblue
 * \brief This file is an example module setup page
 * Put some comments here
 */
// Dolibarr environment
$res = @include ("../../main.inc.php"); // From htdocs directory
if (! $res) {
	$res = @include ("../../../main.inc.php"); // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/sendinblue.lib.php';
dol_include_once('/sendinblue/class/dolsendinblue.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$extrafields_societe = new ExtraFields($db);
$extralabels_societe = $extrafields_societe->fetch_name_optionals_label('societe');
$extrafields_contact = new ExtraFields($db);
$extralabels_contact = $extrafields_contact->fetch_name_optionals_label('socpeople');

// Translations
$langs->load("sendinblue@sendinblue");

// Access control
if (! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$refreshButtonPressed = isset($_SERVER['HTTP_CACHE_CONTROL']) && ($_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0' || $_SERVER['HTTP_CACHE_CONTROL'] === 'no-cache');


if(!empty($conf->global->SEND_BY_SENDINBLUE)){
	// retrait permanent de cette conf, elle créée plus de problème qu'elle n'en résous
	$TConfToDelete = array('SEND_BY_SENDINBLUE','SENDINBLUE_MAIL_SMTP_SERVER', 'SENDINBLUE_SMTP_PORT', 'SENDINBLUE_MAIL_SMTPS_ID', 'SENDINBLUE_MAIL_SMTPS_PW', 'SENDINBLUE_MAIL_EMAIL_FROM');
	foreach ($TConfToDelete as $conToDel){
		dolibarr_del_const($db, $conToDel, $conf->entity);
	}
}

/*
 * Actions
 */
if ($action == 'setvar') {

	$res = dolibarr_set_const($db, 'SENDINBLUE_API_KEY', GETPOST('SENDINBLUE_API_KEY', 'none'), 'chaine', 0, '', $conf->entity);
	if (! $res > 0) {
		$error ++;
	}

    $SENDINBLUE_EXTRAFIELDS_SOCIETE_ALLOWED = GETPOST('SENDINBLUE_EXTRAFIELDS_SOCIETE_ALLOWED', 'array');
    $res = dolibarr_set_const($db, 'SENDINBLUE_EXTRAFIELDS_SOCIETE_ALLOWED', implode(',', $SENDINBLUE_EXTRAFIELDS_SOCIETE_ALLOWED), 'chaine', 0, '', $conf->entity);
    if (! $res > 0)
        $error ++;

    $SENDINBLUE_EXTRAFIELDS_CONTACT_ALLOWED = GETPOST('SENDINBLUE_EXTRAFIELDS_CONTACT_ALLOWED', 'array');
    $res = dolibarr_set_const($db, 'SENDINBLUE_EXTRAFIELDS_CONTACT_ALLOWED', implode(',', $SENDINBLUE_EXTRAFIELDS_CONTACT_ALLOWED), 'chaine', 0, '', $conf->entity);
    if (! $res > 0)
        $error ++;


}

/*
 * View
 */
$page_name = "sendinblueSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
// $head = sendinblueAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104036Name"),
    0,
    "sendinblue@sendinblue"
);

// Setup page goes here
$form = new Form($db);
$var = false;
print '<BR>';
echo $langs->trans("SendinBlueExplain");
print '<BR>';
print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data" >';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvar">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td width="40%">' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Valeur") . '</td>';
print '<td></td>';
print "</tr>\n";

// SENDINBLUE_API_KEY
print '<tr class="impair"><td>' . $langs->trans("SENDINBLUE_API_KEY") . '</td>';
print '<td align="left">';
print '<input type="password" name="SENDINBLUE_API_KEY" value="' . $conf->global->SENDINBLUE_API_KEY . '" size="20" ></td>';
print '<td align="left">';
print $form->textwithpicto('', $langs->trans("SENDINBLUE_API_KEYHelp"), 1, 'help');
print '</td>';
print '</tr>';

print '<tr class="impair"><td>' . $langs->trans("SENDINBLUE_EXTRAFIELDS_SOCIETE_ALLOWED") . '</td>';
print '<td align="left">';
print Form::multiselectarray('SENDINBLUE_EXTRAFIELDS_SOCIETE_ALLOWED', $extralabels_societe, explode(',', $conf->global->SENDINBLUE_EXTRAFIELDS_SOCIETE_ALLOWED));
print '<td align="left">';
print $form->textwithpicto('', $langs->trans("SENDINBLUE_EXTRAFIELDS_SOCIETE_ALLOWEDHelp"), 1, 'help');
print '</td>';
print '</tr>';

print '<tr class="impair"><td>' . $langs->trans("SENDINBLUE_EXTRAFIELDS_CONTACT_ALLOWED") . '</td>';
print '<td align="left">';
print Form::multiselectarray('SENDINBLUE_EXTRAFIELDS_CONTACT_ALLOWED', $extralabels_contact, explode(',', $conf->global->SENDINBLUE_EXTRAFIELDS_CONTACT_ALLOWED));
print '<td align="left">';
print $form->textwithpicto('', $langs->trans("SENDINBLUE_EXTRAFIELDS_CONTACT_ALLOWEDHelp"), 1, 'help');
print '</td>';
print '</tr>';

print '<tr class="liste_titre"><td colspan="3" align="center"><input type="submit" class="button" value="' . $langs->trans("Save") . '"></td></tr>';

print '</table>';
print '</form>';

print '<br/>';

llxFooter();

$db->close();
