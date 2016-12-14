#!/usr/bin/php
<?php
/* Copyright (C) 2012   Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013   Florian Henry <forian.henry@open-concept.pro
 * Copyright (C) 2013   Laurent Destailleur <eldy@users.sourceforge.net>
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
 *  \file       scripts/cron/cron_run_jobs.php
 *  \ingroup    cron
 *  \brief      Execute pendings jobs
 */
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOLOGIN'))        define('NOLOGIN','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');


$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

if (is_file($path."../../master.inc.php")) require_once ($path."../../master.inc.php");
elseif (is_file($path."../../../master.inc.php")) require_once ($path."../../../master.inc.php");
else {
	echo "Error: master.inc.php not found.\n".$path;
	exit(-1);
}
require_once (DOL_DOCUMENT_ROOT."/cron/class/cronjob.class.php");
require_once (DOL_DOCUMENT_ROOT.'/user/class/user.class.php');
dol_include_once('/mailchimp/class/dolmailchimp.class.php');
dol_include_once('/mailchimp/lib/mailchimp.lib.php');

// Global variables
$version=DOL_VERSION;
$error=0;


/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".getmypid()." *****\n";


// Display banner and help
echo '--- start ' . dol_print_date(dol_now(),'dayhourtext')."\n";

// Do my code
$sql = 'SELECT entity, mailchimp_listid, mailchimp_segmentid, fk_category FROM '.MAIN_DB_PREFIX.'mailchimp_category_contact ORDER BY entity, fk_category';
echo '== Requete SQL : ' . $sql."\n\n";

$resql = $db->query($sql);
if ($resql)
{
	$TGroupUpdate = array();
	
	while ($row = $db->fetch_object($resql))
	{
		$TGroupUpdate[$row->fk_category][$row->mailchimp_listid][$row->mailchimp_segmentid][] =  $row->entity;
	}
	
	foreach ($TGroupUpdate as $fk_category => $Tab)
	{
		if (!empty($Tab))
		{
			foreach ($Tab as $mailchimp_listid => $Tab2)
			{
				if (!empty($Tab2))
				{
					foreach ($Tab2 as $mailchimp_segmentid => $TEntity)
					{
						$TEmailDiff = _doUpdateMailchimpList($TEntity, $fk_category, $mailchimp_listid, $mailchimp_segmentid, true);
					}	
				}
				
			}	
		}
	}
}
else
{
	echo 'Error [SQL] => '.$sql."\n";
}
// End my code

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

$db->close();

return $exit_status;