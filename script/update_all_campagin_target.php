#!/usr/bin/env php
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
 *	\file		scripts/myscript.php
 *	\ingroup	mymodule
 *	\brief		This file is an example command line script
 *				Put some comments here
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ";
    echo $script_file;
    echo " from command line, you must use PHP for CLI mode.\n";
    exit;
}

if (! isset($argv[1]) || ! $argv[1]) {
	print "Usage: ".$script_file." userlogin [mailing_id] \n";
	exit;
}

// Global variables
$version = '1.0.0';
$error = 0;

/*
 * -------------------- YOUR CODE STARTS HERE --------------------
 */
/* Set this define to 0 if you want to allow execution of your script
 * even if dolibarr setup is "locked to admin user only". */
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 0);

/* Include Dolibarr environment
 * Customize to your needs
 */
require_once $path . '../../../master.inc.php';
/* After this $db, $conf, $langs, $mysoc, $user and other Dolibarr utility variables should be defined.
 * Warning: this still requires a valid htdocs/conf.php file
 */

// No timeout for this script
@set_time_limit(0);

// Set the default language
//$langs->setDefaultLang('en_US');

// Load translations for the default language
$langs->load("main");
$langs->load("sendinblue@sendinblue");
$langs->load("mails");


// Display banner and help
echo "***** " . $script_file . " (" . $version . ") *****\n";
if (! isset($argv[1]) || ! isset($argv[2])) {
	// Check parameters
	echo "Usage: " . $script_file . " userlogin lang_code [mailing_id] \n";
	exit;
}


/* User and permissions loading
 * Loads user for login 'admin'.
 * Comment out to run as anonymous user. */
$userlogin = $argv[1];

$result = $user->fetch('', $userlogin);
if (! $result > 0) {
    dol_print_error('', $user->error);
    exit;
}
$user->getrights();

$langcode=$argv[2];
$langs = new Translate("", $conf);
$langs->setDefaultLang($langcode);

// Display banner and help
echo '--- start ' . dol_print_date(dol_now(),'dayhourtext')."\n";
echo 'userlogin=' . $userlogin . "\n";

if (isset($argv[3])) {
	echo 'mailing_id=' . $argv[3] . "\n";
	$mailingid=$argv[3];
}

// Examples for manipulating a class
dol_include_once('/sendinblue/class/dolsendinblue.class.php');
$sendinblue = new DolSendinBlue($db);

if (isset($argv[3])) {
	echo '--- update only one campagin' . "\n";
	$result=$sendinblue->fetch_by_mailing($mailingid);
	if ($result<0) {
		echo '--- end error message=' . $sendinblue->error . "\n";
		$error++;
	} else {
		$result=$sendinblue->updateSendinBlueCampaignStatus($user);
		if ($result<0) {
			echo '--- updateSendinBlueCampaignStatus message=' . $sendinblue->error . "\n";
			$error++;
		}
	}
} else {
	echo '--- update all campagin' . "\n";
	
	$result=$sendinblue->updateSendinBlueAllCampaignStatus($user);
	if ($result<0) {
		echo '--- updateSendinBlueAllCampaignStatus error message=' . $sendinblue->error . "\n";
		$error++;
	}
}

/*
 * --------------------- YOUR CODE ENDS HERE ----------------------
 */

print '--- end  ' . dol_print_date(dol_now(),'dayhourtext') . "\n";
// Error management
if (! $error) {
   // $db->commit();
    echo '--- end ok' . "\n";
    $exit_status = 0; // UNIX no errors exit status
} else {
    echo '--- end error code=' . $error . "\n";
    //$db->rollback();
    $exit_status = 1; // UNIX general error exit status
}

// Close database handler
$db->close();

// Return exit status code
return $exit_status;
