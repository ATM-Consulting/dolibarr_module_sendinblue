<?php

if (is_file('../../../../main.inc.php')) require '../../../../main.inc.php';
elseif (is_file('../../../main.inc.php')) require '../../../main.inc.php';
elseif (is_file('../../main.inc.php')) require '../../main.inc.php';
else {
	exit('main.inc.php not found');
}

dol_include_once('/mailchimp/class/dolmailchimp.class.php');
dol_include_once('/mailchimp/lib/mailchimp.lib.php');

$id=GETPOST('id');
$type=GETPOST('type');

$action=GETPOST('action');

if ($action == 'mailchimpSync')
{
	$langs->load('mailchimp@mailchimp');
	
	//check permission
	if (!empty($user->rights->mailchimp->sync))
	{
		$TInfo = getMailChimpInfoFromCategorie($id);
		if (!empty($TInfo))
		{
			$nb_mail_add = 0;
			foreach ($TInfo as &$line)
			{
				$TEmailDiff = _doUpdateMailchimpList(array($conf->entity), $id, $line->mailchimp_listid, $line->mailchimp_segmentid);
				if (!empty($TEmailDiff))
				{
					$nb_mail_add += count($TEmailDiff['add']);
				}
			}
			
			if (function_exists('setEventMessages')) setEventMessages('', array($langs->trans('mailchimp_nb_email_added', $nb_mail_add) ));
			else {
				setEventMessage($langs->trans('mailchimp_nb_email_added', $nb_mail_add)); // @info l'ajout renvoi uniquement le nombre de nouveaux emails ajoutés
			}
		}
		
	}
}
elseif ($action == 'mailchimpGetSegmentFromListId')
{
	$TSegment = array();
	$TListId = GETPOST('TListId');
	if (!empty($TListId))
	{
		$mailchimp = new DolMailchimp($db);
		foreach ($TListId as $listId)
		{
			if (!empty($listId))
			{
				$mailchimp->getListSegmentDestinaries($listId);
				$TSegment = $mailchimp->listsegment_lines;
			}
		}
	}
	
	echo json_encode($TSegment);
	exit;
}


header('Location: '.dol_buildpath('/categories/viewcat.php', 1).'?id='.$id.'&type='.$type);
exit;