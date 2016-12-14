<?php
/* <MailChimp connector>
 * Copyright (C) 2013 Florian Henry florian.henry@open-concept.pro
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
 *       \file       mailchimp/mailchimp/ajax/mailchimp.php
 *       \brief      File to load mailchimp combobox
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

// Dolibarr environment
$res = @include("../../../main.inc.php"); // From htdocs directory
if (! $res) {
	$res = @include("../../../../main.inc.php"); // From "custom" directory
}

$id			= GETPOST('id','alpha');
$action		= GETPOST('action','alpha');
$htmlname	= GETPOST('htmlname','alpha');

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

// Load original field value
if (! empty($id) && $action=='getSegment' && ! empty($htmlname))
{
	dol_include_once('/mailchimp/class/html.formmailchimp.class.php');
	$form = new FormMailChimp($db);
	
	$return=array();
	
	$return['value']	= $form->select_mailchimpsegement($id,'segmentlist',1,'',1);
	$return['num']		= $form->num;
	$return['error']	= $form->error;
	
	echo json_encode($return);
}