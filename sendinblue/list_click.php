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
 * \file /mailchimp/mailchimp/list_click.php
 * \ingroup mailchimp
 * \brief Click List from DB
 */
$res = false;
if (file_exists("../main.inc.php")) {
	$res = @include ("../main.inc.php");
}
if (! $res && file_exists("../../main.inc.php")) {
	$res = @include ("../../main.inc.php");
}
if (! $res && file_exists("../../../main.inc.php")) {
	$res = @include ("../../../main.inc.php");
}
if (! $res) {
	die("Main include failed");
}
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

require_once '../class/dolmailchimp.class.php';
require_once '../class/mailchimpactivites.class.php';
require_once '../class/html.formmailchimp.class.php';

$langs->load("companies");
$langs->load("users");
$langs->load("other");
$langs->load("commercial");
$langs->load("mailchimp@mailchimp");

$search_month = GETPOST('search_month', 'aplha');
$search_year = GETPOST('search_year', 'int');
$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');


if (empty($search_year)) {
	$search_year=dol_print_date(dol_now(),'%Y');
}

if (empty($search_month)) {
	if (intval(dol_print_date(dol_now(),'%m'))!=1) {
		$search_month=intval(dol_print_date(dol_now(),'%m'))-1;
	} else {
		$search_month=1;
	}
}

if (empty($sortfield)) {
	$sortfield='ml.date_creat';
	$sortorder='DESC';
}

$search_title=GETPOST('search_title','alpha');
$search_mail=GETPOST('search_mail','alpha');
$search_link=GETPOST('search_link','alpha');
$search_socname=GETPOST('search_socname','alpha');

if ($page == - 1) {
	$page = 0;
}

$offset = $conf->liste_limit * $page;

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) {
	$search_month = '';
	$search_year = '';
	$search_title='';
	$search_mail='';
	$search_link='';
	$search_socname='';
}

$filter = array ();
$options = '';
if (! empty($search_year)) {
	$filter['date_format(ml.date_valid,\'%Y\')'] = $search_year;
	$options .= '&amp;search_year=' . $search_year;
}
if (! empty($search_month)) {
	$filter['date_format(ml.date_valid,\'%m\')'] = $search_month;
	$options .= '&amp;search_month=' . $search_month;
}
if (! empty($search_title)) {
	$filter['ml.titre'] = $search_title;
	$options .= '&amp;search_title=' . $search_title;
}
if (! empty($search_mail)) {
	$filter['t.email'] = $search_mail;
	$options .= '&amp;search_mail=' . $search_mail;
}
if (! empty($search_link)) {
	$filter['link'] = $search_link;
	$options .= '&amp;search_link=' . $search_link;
}
if (! empty($search_socname)) {
	$filter['soc.nom'] = $search_socname;
	$options .= '&amp;search_socname=' . $search_socname;
}

$mailchimp = new DolMailchimp($db);
$mailchimpactivities = new MailchimpActivites($db);
$formother = new FormOther($db);
$contact = new Contact($db);

// Security check
if (! $user->rights->mailchimp->read && ! empty($conf->global->MAILCHIMP_SAVE_ACTIVITY_LOCALY)) {
	accessforbidden();
}

/*
 *	View
 */
$title = $langs->trans("MailChimpClickReport");
llxHeader('', $title);

$form = new Form($db);

/*
 * MailChimp Campagin Actvites
 */

if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $mailchimpactivities->getEmailcontactActivitesClick($sortorder, $sortfield, 0, 0, $filter);
}

$result = $mailchimpactivities->getEmailcontactActivitesClick($sortorder, $sortfield, $limit, $offset, $filter);
if ($result < 0) {
	setEventMessage($mailchimpactivities->error, 'errors');
}

print_barre_liste($title, $page, $_SERVEUR['PHP_SELF'], $option, $sortfield, $sortorder, '', $result, $nbtotalofrecords);

print '<form method="post" action="' . $_SERVER ['PHP_SELF'] . '" name="search_form">' . "\n";

print '<div class="liste_titre">';
print $langs->trans('DateValid');
print $langs->trans('Month') . ':<input class="flat" type="text" size="4" name="search_month" value="' . $search_month . '">';
print $langs->trans('Year') . ':' . $formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 20, 5);


print '<input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
print '&nbsp; ';
print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';

print '</div>';

print '<table class="border" width="100%">';
print '<tr>';
print_liste_field_titre($langs->trans("MailChimpCampaign"), $_SERVEUR['PHP_SELF'], "ml.titre", "", $options, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Date"), $_SERVEUR['PHP_SELF'], "ml.date_creat", "", $options, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Email"), $_SERVEUR['PHP_SELF'], "t.email", "", $options, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Company"), $_SERVEUR['PHP_SELF'], "soc.nom", "", $options, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Link"), $_SERVEUR['PHP_SELF'], "", "", $options, '', $sortfield, $sortorder);
print_liste_field_titre($langs->trans("Contact"), $_SERVEUR['PHP_SELF'], "", "", $options, '', $sortfield, $sortorder);
print '</tr>';
print '<tr class="liste_titre">';
print '<td><input type="text" class="flat" name="search_title" value="' . $search_title . '" size="10"></td>';
print '<td></td>';
print '<td><input type="text" class="flat" name="search_mail" value="' . $search_mail . '" size="10	"></td>';
print '<td><input type="text" class="flat" name="search_socname" value="' . $search_socname . '" size="10	"></td>';
print '<td><input type="text" class="flat" name="search_link" value="' . $search_link . '" size="20"></td>';
print '<td></td>';
print '</tr>';
$mailchimpstatic = new DolMailchimp($db);
$contact_array=array();
if (is_array($mailchimpactivities->contactemail_clickactivity) && count($mailchimpactivities->contactemail_clickactivity) > 0) {
	foreach ( $mailchimpactivities->contactemail_clickactivity as $activites ) {
		
		if (!array_key_exists($activites->contactid, $contact_array) && !empty($activites->contactid)) {
			$result=$contact->fetch($activites->contactid);
			if ($result<0) {
				setEventMessage($contact->error,'errors');
			} else {
				$contact_array[$activites->contactid]=$contact->getNomUrl();
			}
		} 
		
		if (is_array($activites->activites) && count($activites->activites) > 0) {
			foreach ( $activites->activites as $act ) {
				$var = ! $var;
				print "<tr $bc[$var]>";
				print '<td>';
				$mailchimpstatic->fk_mailing = $activites->fk_mailing;
				print $mailchimpstatic->getNomUrl();
				print '</td>';
				print '<td>';
				print dol_print_date($activites->datec);
				print '</td>';
				print '<td>';
				print $activites->email;	
				print '</td>';
				print '<td>';
				print $activites->socname;
				print '</td>';
				print '<td>';
				print $act['timestamp'] . ':' . $act['url'];
				print '</td>';
				print '<td>';
				print $contact_array[$activites->contactid];
				print '</td>';
				
				print '</tr>';
			}
		}
	}
}

print "</table>";
print '</form>';

dol_fiche_end();

llxFooter();
$db->close();