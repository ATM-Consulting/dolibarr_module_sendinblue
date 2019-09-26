<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
* Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
* Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
* Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
* Copyright (C) 2014      Florian Henry		  	<florian.henry@open-concept.pro>
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
 *       \file       /sendinblue/sendinblue/contact_activites.php
*       \ingroup    sendinblue
*       \brief      Card of a contact sendinblue activites
*/

require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

require_once '../class/dolsendinblue.class.php';
require_once '../class/html.formsendinblue.class.php';

$langs->load("companies");
$langs->load("users");
$langs->load("other");
$langs->load("commercial");
$langs->load("sendinblue@sendinblue");

$mesg=''; $error=0; $errors=array();

$action		= (GETPOST('action','alpha') ? GETPOST('action','alpha') : 'view');
$confirm	= GETPOST('confirm','alpha');
$backtopage = GETPOST('backtopage','alpha');
$id			= GETPOST('id','int');
$socid		= GETPOST('socid','int');
$listid		= GETPOST('listid','alpha');
if ($user->societe_id) $socid=$user->societe_id;

$object = new Contact($db);
$extrafields = new ExtraFields($db);
$sendinblue= new DolSendinBlue($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

//Load thridparty
$objsoc = new Societe($db);
if ($socid > 0)
{
	$objsoc->fetch($socid);
}


// Si edition contact deja existant
$res=$object->fetch($id, $user);
if ($res < 0) { dol_print_error($db,$object->error); exit; }
$res=$object->fetch_optionals($object->id,$extralabels);



// Security check
$result = restrictedArea($user, 'contact', $id, 'socpeople&societe', '', '', 'rowid', $objcanvas); // If we create a contact with no company (shared contacts), no check on write permission

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('sendinbluecontactcard'));

/*
 *	Actions
*/

$parameters=array('id'=>$id, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
$error=$hookmanager->error; $errors=array_merge($errors, (array) $hookmanager->errors);

if (empty($reshook))
{

}

if ($action=='unsubscribe') {
	$result = $sendinblue->deleteEmailFromList($listid,array($object->email));
	if ($result<0) {
		setEventMessage($sendinblue->error,'errors');
	}
}

if ($action=='subscribe') {
	$result = $sendinblue->addEmailToList($listid,array($object->email.'&contact&'.$object->id));
	if ($result<0) {
		setEventMessage($sendinblue->error,'errors');
	}
}

/*
 *	View
*/


$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$langs->trans("SendinBlueActivites"),$help_url);

$form = new Form($db);
$formcompany = new FormCompany($db);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';



// Show tabs
$head = contact_prepare_head($object);

$title = $langs->trans("Contacts");
dol_fiche_head($head, 'tabSendinBlueActivites', $title, 0, 'contact');


print '<table class="border" width="100%">';

$linkback = '<a href="'.DOL_URL_ROOT.'/contact/list.php">'.$langs->trans("BackToList").'</a>';

// Ref
print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
print $form->showrefnav($object, 'id', $linkback);
print '</td></tr>';

// Name
print '<tr><td width="20%">'.$langs->trans("Lastname").' / '.$langs->trans("Label").'</td><td width="30%">'.$object->lastname.'</td>';
print '<td width="20%">'.$langs->trans("Firstname").'</td><td width="30%">'.$object->firstname.'</td></tr>';

// Company
if (empty($conf->global->SOCIETE_DISABLE_CONTACTS))
{
	print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
	if ($object->socid > 0)
	{
		$objsoc->fetch($object->socid);
		print $objsoc->getNomUrl(1);
	}
	else
	{
		print $langs->trans("ContactNotLinkedToCompany");
	}
	print '</td></tr>';
}

// Civility
print '<tr><td width="15%">'.$langs->trans("UserTitle").'</td><td colspan="3">';
print $object->getCivilityLabel();
print '</td></tr>';

// Role
print '<tr><td>'.$langs->trans("PostOrFunction").'</td><td colspan="3">'.$object->poste.'</td>';

// Address
print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3">';
dol_print_address($object->address,'gmap','contact',$object->id);
print '</td></tr>';

// Zip/Town
print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3">';
print $object->zip;
if ($object->zip) print '&nbsp;';
print $object->town.'</td></tr>';

// Country
print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
$img=picto_from_langcode($object->country_code);
if ($img) print $img.' ';
print $object->country;
print '</td></tr>';

// State
if (empty($conf->global->SOCIETE_DISABLE_STATE))
{
	print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">'.$object->state.'</td>';
}

// Phone
print '<tr><td>'.$langs->trans("PhonePro").'</td><td>'.dol_print_phone($object->phone_pro,$object->country_code,$object->id,$object->socid,'AC_TEL').'</td>';
print '<td>'.$langs->trans("PhonePerso").'</td><td>'.dol_print_phone($object->phone_perso,$object->country_code,$object->id,$object->socid,'AC_TEL').'</td></tr>';

print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td>'.dol_print_phone($object->phone_mobile,$object->country_code,$object->id,$object->socid,'AC_TEL').'</td>';
print '<td>'.$langs->trans("Fax").'</td><td>'.dol_print_phone($object->fax,$object->country_code,$object->id,$object->socid,'AC_FAX').'</td></tr>';

// Email
print '<tr><td>'.$langs->trans("EMail").'</td><td>'.dol_print_email($object->email,$object->id,$object->socid,'AC_EMAIL').'</td>';
if (! empty($conf->mailing->enabled))
{
	$langs->load("mails");
	print '<td nowrap>'.$langs->trans("NbOfEMailingsSend").'</td>';
	print '<td><a href="'.DOL_URL_ROOT.'/comm/mailing/list.php?filteremail='.urlencode($object->email).'">'.$object->getNbOfEMailings().'</a></td>';
}
else
{
	print '<td colspan="2">&nbsp;</td>';
}
print '</tr>';

// Instant message and no email
print '<tr><td>'.$langs->trans("IM").'</td><td>'.$object->jabberid.'</td>';
if (!empty($conf->mailing->enabled))
{
	print '<td>'.$langs->trans("No_Email").'</td><td>'.yn($object->no_email).'</td>';
}
else
{
	print '<td colspan="2">&nbsp;</td>';
}
print '</tr>';

print '<tr><td>'.$langs->trans("ContactVisibility").'</td><td colspan="3">';
print $object->LibPubPriv($object->priv);
print '</td></tr>';
// Note Public
print '<tr><td valign="top">'.$langs->trans("NotePublic").'</td><td colspan="3">';
print nl2br($object->note_public);
print '</td></tr>';

// Note Private
print '<tr><td valign="top">'.$langs->trans("NotePrivate").'</td><td colspan="3">';
print nl2br($object->note_private);
print '</td></tr>';

// Statut
print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
print '<td>';
print $object->getLibStatut(5);
print '</td>';
print '</tr>'."\n";
// Other attributes
$parameters=array('socid'=>$socid, 'colspan' => ' colspan="3"');
$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
if (empty($reshook) && ! empty($extrafields->attribute_label))
{
	print $object->showOptionals($extrafields);
}

print "</table>";
print '</div>';

/*
 * SendinBlue list subscription
 */

//find is email is in the list
if($conf->global->SENDINBLUE_API_KEY){

$result = $sendinblue->getListForEmail($object->email);
if ($result<0) {
	setEventMessage($sendinblue->error,'errors');
}
$list_subcribed_id=array();
if (is_array($sendinblue->listlist_lines) && count($sendinblue->listlist_lines)>0) {
	foreach ($sendinblue->listlist_lines as $listsubcribed) {
		$list_subcribed_id[]=$listsubcribed;
	}
}


$result=$sendinblue->getListDestinaries();
if ($result<0) {
	setEventMessage($sendinblue->error,'errors');
}

if (is_array($sendinblue->listdest_lines) && count($sendinblue->listdest_lines)>0) {
	print load_fiche_titre($langs->trans("SendinBlueDestList"),'','');
	print '<table class="border" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('SendinBlueListName').'</td>';
	print '<td>'.$langs->trans('SendinBlueContactIsList').'</td>';
	print '<td>'.$langs->trans('SendinBlueSubscribersState').'</td>';
	print '</tr>';

	foreach($sendinblue->listdest_lines['data'] as $line) {
		//Si le contact n'a pas d'email

		if($object->email == null){
 			$var=!$var;
 			print "<tr " . $bc[$var] . ">";
 			print '<td width="20%"><a target="_blanck" href="https://my.sendinblue.com/users/list/id/'.$line['id'].'">'.$line['name'].'</a></td>';
 			print '<td>';

 			print '<link rel="stylesheet" href="../script/style.css" />';
 			print "<div style='position:relative;' >";
 			print "<div class='sendinblue_grise'></div>";
 			print img_picto($langs->trans("Enabled"),'switch_off');
 			print "</div>";


 			print "</td>\n";
			print '<td>';


	 		print $langs->trans("NoEmail");

			print "</td>\n";

 			print '</tr>';
		}


 		//Récupération du statut
 		else if($object->email != null){
 			$var=!$var;
			//var_dump($line);exit;
 			$emails = array(array('email' => $object->email));
			if(!in_array($line['id'],$list_subcribed_id)){
 				//$result = $sendinblue->sendinblue->get('lists/'.$line['id'].'/members/'.$sendinblue->sendinblue->subscriberHash($object->email));
				$statut = 'cleaned';
			}else {
				$statut = 'subscribed';
			}

 			print "<tr " . $bc[$var] . ">";
 			print '<td width="20%"><a target="_blanck" href="https://my.sendinblue.com/users/list/id/'.$line['id'].'">'.$line['name'].'</a></td>';
 			print '<td>';
 			if ($statut == 'subscribed') {
 				print '<a href="'.$_SERVER['PHP_SELF'].'?action=unsubscribe&id='.$object->id.'&listid='.$line['id'].'">';
 				print img_picto($langs->trans("Disabled"),'switch_on');
 				print '</a>';
 			}  else if($sendinblue->isUnsubscribed($line['id'], $object->email) || $object->statut == 0){
 				print '<link rel="stylesheet" href="../script/style.css" />';
 				print "<div style='position:relative;' >";
 				print "<div class='sendinblue_grise'></div>";
 				print img_picto($langs->trans("Enabled"),'switch_off');
				$statut='unsubscribed';
 				print "</div>";
 			} else {
 				print '<a href="'.$_SERVER['PHP_SELF'].'?action=subscribe&id='.$object->id.'&listid='.$line['id'].'">';
 				print img_picto($langs->trans("Enabled"),'switch_off');
 				print '</a>';
 			}
 			print "</td>\n";
 			print '<td>';

 			if($statut != null){


	 			print $langs->trans("SendinBlueStatus".$statut);

			}
 			print "</td>\n";
 			print '</tr>';

 		}
 		print "</td>\n";

		print '</tr>';
	}
	print '</table>';
}

/*
 * SendinBlue Campagin Actvites
 */

$result=$sendinblue->getEmailcontactActivites($object->email);

if ($result<0) {
	setEventMessage($sendinblue->error,'errors');
}
$sendinbluestatic= new DolSendinBlue($db);

print load_fiche_titre($langs->trans("SendinBlueActivites"),'','');

print '<table class="border" width="100%">';
if(is_array($sendinblue->contactemail_activity) && count($sendinblue->contactemail_activity)>0) {
	foreach($sendinblue->contactemail_activity as $activites) {
		if(!empty($activites->activites)){
			print '<tr class="pair">';
			print '<td>';
			print $langs->trans('SendinBlueCampaign');
			print '</td>';
			print '<td>';
			$sendinbluestatic->fk_mailing=$activites->fk_mailing;
			print $sendinbluestatic->getNomUrl();
			print '</td>';
			print '</tr>';
			print '<tr class="impair">';
			print '<td>';
			print $langs->trans('Activité');
			print '</td>';
			print '<td>';

				print '<table class="noborder">';
					print '<tr class="pair">';
					print '<td>'.$langs->trans($activites->activites).'</td>';
					print '<td>'.$activites->timestamp.'</td>';
					print '</tr>';
				print '</table>';

			print '</td>';
			print '</tr>';

			print '<tr><td colspan="2"></td></tr>';
			}

	}
}


print "</table>";
		$PDOdb = new TPDOdb;
		$listeview = new TListviewTBS('graphContactActions');
		if(!empty($sendinblue->contactemail_activity)){
			foreach($sendinblue->contactemail_activity as $act){
				$bool=false;
				if(!empty($TSum)){
					foreach($TSum as &$t){
						if($t[0] == $act->activites){
							$t[1]= $t[1]+1;

							$bool=true;
						}
					}

				}
				if(!$bool){
					$TSum[] = array($langs->transnoentities($act->activites),1);
				}
			}

			print $listeview->renderArray($PDOdb, $TSum
			,array(
			'type' => 'chart'
			,'chartType' => 'PieChart'
			,'liste'=>array(
			'titre'=>$langs->transnoentitiesnoconv('titleGraphContactActions')
			)
			)
			);

		}
}else{
	setEventMessage($langs->trans('InvalidAPIKey'),'errors');
}

dol_fiche_end();

llxFooter();
$db->close();
