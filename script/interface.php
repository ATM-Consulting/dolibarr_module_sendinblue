<?php

$sapi_type = php_sapi_name();
// from ajax call (apache2handler) from php command line (cli)
if (substr($sapi_type, 0, 3) == 'cli')
{
	@set_time_limit(0);
	
	define('INC_FROM_CRON_SCRIPT', 1);
	chdir(dirname(__FILE__));
	
	// get params
	foreach($argv as $key => $val)
	{
		if (preg_match('/async_action=([^\s]+)$/',$val,$reg)) $async_action=$reg[1];
		if (preg_match('/listid=([^\s]+)$/',$val,$reg)) $listid=$reg[1];
		if (preg_match('/fk_mailing=([^\s]+)$/',$val,$reg)) $fk_mailing=$reg[1];
		if (preg_match('/fk_user=([^\s]+)$/',$val,$reg)) $fk_user=$reg[1];
	}
}

require '../config.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
dol_include_once('/sendinblue/class/dolsendinblue.class.php');

if (!empty($fk_user) && $user->id != $fk_user)
{
	$user->fetch($fk_user);
}


$get=GETPOST('get');
$set=GETPOST('set');

if (empty($listid)) $listid = GETPOST('listid');
if (empty($fk_mailing)) $fk_mailing = GETPOST('fk_mailing');

if (empty($listid)) return __out('listid param missing');
if (empty($fk_mailing)) return __out('fk_mailing param missing');

$sendinblue= new DolSendinBlue($db);
$sendinblue->fetch_by_mailing($fk_mailing);

$sendinblue->sendinblue_listid=$listid;
$sendinblue->fk_mailing=$fk_mailing;

// Cas possible lors d'un import
if (empty($sendinblue->id))
{
	$result=$sendinblue->create($user);
	if ($result<0) {
		return __out($sendinblue->error);
	}
}
else
{
	$result=$sendinblue->update($user);
	if ($result<0) {
		return __out($sendinblue->error);
	}
}

switch ($get) {
	case 'pidIsRunning':
		__out(file_exists('/proc/'.$pid));
		exit;
		
		break;
}

switch ($set) {
	case 'export':
		$script = dol_buildpath('/sendinblue/script/interface.php', 0);
		$params = 'async_action=export listid='.$listid.' fk_mailing='.$fk_mailing.' fk_user='.$user->id;
		
		$pid = exec('php '.$script.' '.$params.' > /dev/null 2>&1 & echo $!;');
		$_SESSION['SENDINBLUE_PID_ACTIVE'][$fk_mailing][$listid][] = $pid;
		
		__out($pid);
		exit;

		break;
	case 'import':
		$script = dol_buildpath('/sendinblue/script/interface.php', 0);
		$params = 'async_action=import listid='.$listid.' fk_mailing='.$fk_mailing.' fk_user='.$user->id;
		
		$pid = exec('php '.$script.' '.$params.' > /dev/null 2>&1 & echo $!;');
		$_SESSION['SENDINBLUE_PID_ACTIVE'][$fk_mailing][$listid][] = $pid;
		
		__out($pid);
		exit;
		
		break;
}

switch ($async_action) {
	case 'export':
		$result=$sendinblue->exportDesttoSendinBlue($listid);
		exit;
		break;
	case 'import':
		$result=$sendinblue->importSegmentDestToDolibarr($listid);
		exit;
		break;
}