<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2013 Laurent Destailleur  <eldy@uers.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2014	   Florian Henry        <florian.henry@open-concept.pro>
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
 *       \file       /sendinblue/sendinblue/target.php
 *       \ingroup    mailchiùmp
 *       \brief      Page to define emailing targets link with sendinblue
 */
$res=false;
if (file_exists("../main.inc.php")) {
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
require_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/emailing.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

require_once '../class/dolsendinblue.class.php';
require_once '../class/html.formsendinblue.class.php';
require_once '../class/html.formmailing.class.php';

$langs->load("mails");

// Security check
if (! $user->rights->mailing->lire || (!empty($user->societe_id) && $user->societe_id > 0)) accessforbidden();


$mesg = '';

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
$page = intval($page);
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="email";

$id=GETPOST('id','int');
$rowid=GETPOST('rowid','int');
$action=GETPOST("action", 'alpha');
$search_lastname=GETPOST("search_lastname", 'none');
$search_firstname=GETPOST("search_firstname", 'none');
$search_email=GETPOST("search_email", 'none');
$search_dest_status=GETPOST('search_dest_status', 'none');
$search_contact=GETPOST('search_contact', 'none');
$search_socname=GETPOST('search_socname', 'none');

// Search modules dirs
$modulesdir = dolGetModulesDirs('/mailings');

$object = new Mailing($db);
$sendinblue= new DolSendinBlue($db);
$formsendinblue = new FormSendinBlue($db);

/*
 * Actions
 */

if ($action == 'add')
{
	$module=GETPOST("module", 'none');
	$result=-1;

	$var=true;

	foreach ($modulesdir as $dir)
	{
	    // Load modules attributes in arrays (name, numero, orders) from dir directory
	    //print $dir."\n<br>";
	    dol_syslog("Scan directory ".$dir." for modules");

	    // Chargement de la classe
	    $file = $dir."/".$module.".modules.php";
	    $classname = "mailing_".$module;

		if (file_exists($file))
		{
			require_once $file;

			// We fill $filtersarray. Using this variable is now deprecated.
			// Kept for backward compatibility.
			$filtersarray=array();
			if (isset($_POST["filter"])) $filtersarray[0]=$_POST["filter"];

			// Add targets into database
			$obj = new $classname($db);

			$result=$obj->add_to_target($id,$filtersarray);
		}
	}

	if ($result > 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
	if ($result == 0)
	{
		$mesg='<div class="warning">'.$langs->trans("WarningNoEMailsAdded").'</div>';
	}
	if ($result < 0)
	{
		$mesg='<div class="error">'.$langs->trans("Error").($obj->error?' '.$obj->error:'').'</div>';
	}
}
else if($action=='exportlist') {

    $sql = base64_decode(GETPOST('sql', 'none'));
    $resql=$db->query($sql);

    $first = true;
    $f1  =tmpfile();
    $tmpName = tempnam(sys_get_temp_dir(), 'data');
    $f1 = fopen($tmpName, 'w');

    while($obj=$db->fetch_object($resql)) {

        $obj->adresse1='';
        $obj->adresse2='';
        $obj->adresse3='';
        $obj->adresse4 = '';

        list($obj->adresse1,$obj->adresse2,$obj->adresse3,$obj->adresse4) = explode("\n", $obj->address);
        unset($obj->address);

        if($first) {
            $first = false;
            $THeader=array();
            foreach($obj as $head=>$dummy) {
                $THeader[] = $head;
            }
           fputcsv($f1, $THeader,";",'"');
        }

        fputcsv($f1,  (array) $obj,";",'"');
    }

    header('Content-Type: application/csv');
    header('Content-Disposition: attachment; filename=liste.csv');
    header('Pragma: no-cache');

    readfile($tmpName);

    exit;
}


if (GETPOST('clearlist', 'none'))
{
	// Chargement de la classe
	$classname = "MailingTargets";
	$obj = new $classname($db);
	$obj->clear_target($id);

	header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
	exit;
}

if ($action == 'delete')
{
	// Ici, rowid indique le destinataire et id le mailing
	$sql="DELETE FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE rowid=".$rowid;
	$resql=$db->query($sql);
	if ($resql)
	{
		if (!empty($id))
		{
			$classname = "MailingTargets";
			$obj = new $classname($db);
			$obj->update_nb($id);

			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
		else
		{
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
	}
	else
	{
		dol_print_error($db);
	}
}

if (!empty($_POST["button_removefilter"]))
{
	$search_lastname='';
	$search_firstname='';
	$search_email='';
}



/*
 * View
 */

llxHeader('',$langs->trans("Mailing"),'EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing');

$form = new Form($db);
$formmailing = new FormMailing($db);

if ($object->fetch($id) >= 0)
{
	$head = emailing_prepare_head($object);

	dol_fiche_head($head, 'tabSendinBlueTarget', $langs->trans("Mailing"), 0, 'email');


	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object,'id', $linkback);
	print '</td></tr>';

    if(empty($object->titre) && !empty($object->title)) $object->titre = $object->title;
	print '<tr><td width="25%">'.$langs->trans("MailTitle").'</td><td colspan="3">'.$object->titre.'</td></tr>';

	print '<tr><td width="25%">'.$langs->trans("MailFrom").'</td><td colspan="3">'.dol_print_email($object->email_from,0,0,0,0,1).'</td></tr>';

	// Errors to
	print '<tr><td width="25%">'.$langs->trans("MailErrorsTo").'</td><td colspan="3">'.dol_print_email($object->email_errorsto,0,0,0,0,1);
	print '</td></tr>';

	// Status
	print '<tr><td width="25%">'.$langs->trans("Status").'</td><td colspan="3">'.$object->getLibStatut(4).'</td></tr>';

	// Nb of distinct emails
	print '<tr><td width="25%">';
	print $langs->trans("TotalNbOfDistinctRecipients");
	print '</td><td colspan="3">';
	$nbemail = ($object->nbemail?$object->nbemail:'0');
	if (!empty($conf->global->MAILING_LIMIT_SENDBYWEB) && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail)
	{
		$text=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
		print $form->textwithpicto($nbemail,$text,1,'warning');
	}
	else
	{
		print $nbemail;
	}
	print '</td></tr>';

	print '</table>';

	print "</div>";

	dol_htmloutput_mesg($mesg);
	$var = 0;
	$var=!$var;

	$allowaddtarget=($object->statut == 0);

	// Show email selectors
	if ($allowaddtarget && $user->rights->mailing->creer)
	{
		print_fiche_titre($langs->trans("ToAddRecipientsChooseHere"),($user->admin?info_admin($langs->trans("YouCanAddYourOwnPredefindedListHere"),1):''),'');

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre">'.$langs->trans("RecipientSelectionModules").'</td>';
		print '<td class="liste_titre" align="center">'.$langs->trans("NbOfUniqueEMails").'</td>';
		print '<td class="liste_titre" align="left">'.$langs->trans("Filter").'</td>';
		print '<td class="liste_titre" align="center">&nbsp;</td>';
		print "</tr>\n";

		clearstatcache();

		$var=true;

		foreach ($modulesdir as $dir)
		{
		    $modulenames=array();

		    // Load modules attributes in arrays (name, numero, orders) from dir directory
		    //print $dir."\n<br>";
		    dol_syslog("Scan directory ".$dir." for modules");
		    $handle=@opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle))!==false)
				{
					if (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
					{
						if (preg_match("/(.*)\.modules\.php$/i",$file,$reg))
						{
							if ($reg[1] == 'example') continue;
							$modulenames[]=$reg[1];
						}
					}
				}
				closedir($handle);
			}

			// Sort $modulenames
			sort($modulenames);

			// Loop on each submodule
            foreach($modulenames as $modulename)
            {
				// Chargement de la classe
				$file = $dir.$modulename.".modules.php";
				$classname = "mailing_".$modulename;
				require_once $file;

				$obj = new $classname($db);

				$qualified=1;
				foreach ($obj->require_module as $key)
				{

					if (empty($conf->$key->enabled) || (! $user->admin && $obj->require_admin))
					{
						$qualified=0;
						//print "Les prerequis d'activation du module mailing ne sont pas respectes. Il ne sera pas actif";
						break;
					}
				}

				// Si le module mailing est qualifie
				if ($qualified)
				{
					$var = !$var;
					print '<tr '.$bc[$var].'>';

					if ($allowaddtarget)
					{
						print '<form name="'.$modulename.'" action="'.$_SERVER['PHP_SELF'].'?action=add&id='.$object->id.'&module='.$modulename.'" method="POST" enctype="multipart/form-data">';
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					}

					print '<td>';
					if (empty($obj->picto)) $obj->picto='generic';
					print img_object($langs->trans("Module").': '.get_class($obj),$obj->picto).' '.$obj->getDesc();
					print '</td>';

					/*
					 print '<td width=\"100\">';
					 print $modulename;
					 print "</td>";
					 */
					$nbofrecipient=$obj->getNbOfRecipients('');
					print '<td align="center">';
					if ($nbofrecipient >= 0)
					{
						print $nbofrecipient;
					}
					else
					{
						print $langs->trans("Error").' '.img_error($obj->error);
					}
					print '</td>';

					print '<td align="left">';
					$filter=$obj->formFilter();
					if ($filter) print $filter;
					else print $langs->trans("None");
					print '</td>';

					print '<td align="right">';
					if ($allowaddtarget)
					{
						print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
					}
					else
					{
						//print $langs->trans("MailNoChangePossible");
						print "&nbsp;";
					}
					print '</td>';

					if ($allowaddtarget) print '</form>';

					print "</tr>\n";
				}
			}
		}	// End foreach dir

		print '</table>';
		print '<br>';
	}

	// List of selected targets
	$sql  = "SELECT DISTINCT mc.rowid, mc.lastname,civ.label as civilite,mc.firstname, mc.email, mc.other, mc.statut
	,soc.nom as 'societe',socp.address
	,socp.zip,socp.town,socp.phone,socp.phone_mobile, mc.date_envoi, mc.source_url, mc.source_id, mc.source_type";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";

	//if (!empty($search_origin)) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople as socp ON (mc.source_type='contact' AND mc.source_id=socp.rowid)";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc ON (soc.rowid=socp.fk_soc)";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople_extrafields as socex ON (socp.rowid=socex.fk_object)";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_civility as civ ON (civ.code=socp.civility)";

		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as soccom ON soccom.fk_soc=socp.fk_soc";
	//}
	$sql .= " WHERE mc.fk_mailing=".$object->id;
	if ($search_lastname)    $sql.= " AND mc.lastname    LIKE '%".$db->escape($search_lastname)."%'";
	if ($search_firstname) $sql.= " AND mc.firstname LIKE '%".$db->escape($search_firstname)."%'";
	if ($search_email)  $sql.= " AND mc.email  LIKE '%".$db->escape($search_email)."%'";
	if (!empty($search_dest_status)) $sql.= " AND mc.statut=".$db->escape($search_dest_status)." ";
	if (!empty($search_contact)) {
		$sql.= " AND (socp.firstname LIKE '%".$db->escape($search_contact)."%' OR socp.lastname LIKE '%".$db->escape($search_contact)."%')";
	}
	if (!empty($search_socname)) {
		$sql.= " AND (soc.nom LIKE '%".$db->escape($search_socname)."%' OR soc.code_client LIKE '%".$db->escape($search_socname)."%')";
	}
	if (!empty($search_ucode)) {
		$sql.= " AND usrextra.u_code='".$db->escape($search_ucode)."' ";
	}
	$sql .= $db->order($sortfield,$sortorder);
	$sqlLimit =$sql. $db->plimit($conf->liste_limit+1, $offset);

	$resql=$db->query($sqlLimit);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		$param = "&amp;id=".$object->id;
		if ($search_lastname)  $param.= "&amp;search_lastname=".urlencode($search_lastname);
		if ($search_firstname) $param.= "&amp;search_firstname=".urlencode($search_firstname);
		if ($search_email)     $param.= "&amp;search_email=".urlencode($search_email);

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		$cleartext='';
		if ($allowaddtarget) {
			$cleartext='<br></div><div>'.$langs->trans("ToClearAllRecipientsClickHere").': '.'<input type="submit" name="clearlist" class="button" value="'.$langs->trans("TargetsReset").'">';
		}

		print_barre_liste($langs->trans("MailSelectedRecipients").$cleartext,$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,"",$num,$object->nbemail,'');

		print '</form>';

		print "\n<!-- Liste destinataires selectionnes -->\n";
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';


		if ($page)			$param.= "&amp;page=".$page;
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("EMail"),$_SERVER["PHP_SELF"],"mc.email",$param,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Lastname"),$_SERVER["PHP_SELF"],"mc.lastname",$param,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Firstname"),$_SERVER["PHP_SELF"],"mc.firstname",$param,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("OtherInformations"),$_SERVER["PHP_SELF"],"",$param,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Contact"),$_SERVER["PHP_SELF"],"socp.firstname",$param,"",'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Customer"),$_SERVER["PHP_SELF"],"soc.nom",$param,"",'align="center"',$sortfield,$sortorder);

		// Date sending
		if ($object->statut < 2)
		{
			print '<td class="liste_titre">&nbsp;</td>';
		}
		else
		{
			print_liste_field_titre($langs->trans("DateSending"),$_SERVER["PHP_SELF"],"mc.date_envoi",$param,'','align="center"',$sortfield,$sortorder);
		}

		// Statut
		print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"mc.statut",$param,'','align="right"',$sortfield,$sortorder);

		//Search Icon
		print '<td class="liste_titre">';
		print '&nbsp';
		print '</td>';

		print '</tr>';

		// Ligne des champs de filtres
		print '<tr class="liste_titre">';
		// EMail
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="search_email" size="14" value="'.$search_email.'">';
		print '</td>';
		// Name
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="search_lastname" size="12" value="'.$search_lastname.'">';
		print '</td>';
		// Firstname
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="search_firstname" size="10" value="'.$search_firstname.'">';
		print '</td>';
		// Other
		print '<td class="liste_titre">';
		print '&nbsp';
		print '</td>';
		// Contact
		print '<td class="liste_titre" align="center">';
		print '<input class="flat" type="text" name="search_contact" size="20" value="'.$search_contact.'">';
		print '</td>';
		// Company
		print '<td class="liste_titre" align="center">';
		print '<input class="flat" type="text" name="search_socname" size="20" value="'.$search_socname.'">';
		print '</td>';

		// Date sending
		print '<td class="liste_titre">';
		print '&nbsp';
		print '</td>';
		//Statut
		print '<td class="liste_titre" align="right">';
		print $formsendinblue->selectDestinariesStatus($search_dest_status,'search_dest_status',1);
		print '</td>';
		//Search Icon
		print '<td class="liste_titre" align="right">';
		print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		print '&nbsp; ';
		print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
		print '</td>';
		print '</tr>';

		$var = true;
		$i = 0;

		if ($num)
		{
			while ($i < min($num,$conf->liste_limit))
			{
				$obj = $db->fetch_object($resql);
				$var=!$var;

				print "<tr " . $bc[$var] . ">";
				print '<td>'.$obj->email.'</td>';
				print '<td>'.$obj->lastname.'</td>';
				print '<td>'.$obj->firstname.'</td>';
				print '<td>'.$obj->other.'</td>';

                if (empty($obj->source_id) || empty($obj->source_type))
                {
                	print '<td align="center" colspan="2">';
                    print $obj->source_url; // For backward compatibility
                    print '</td>';
                }
                else
                {
                    if ($obj->source_type == 'member')
                    {
                    	print '<td align="center" colspan="2">';
                        include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
                        $m=new Adherent($db);
                        $m->id=$obj->source_id;
                        print $m->getNomUrl(2);
                        print '</td>';
                    }
                    else if ($obj->source_type == 'user')
                    {
                    	print '<td align="center" colspan="2">';
                        include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
                        $m=new User($db);
                        $m->id=$obj->source_id;
                        print $m->getNomUrl(2);
                        print '</td>';
                    }
                    else if ($obj->source_type == 'thirdparty')
                    {
                    	print '<td align="center" colspan="2">';
                        include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
                        $m=new Societe($db);
                        $m->id=$obj->source_id;
                        print $m->getNomUrl(2);
                        print '</td>';
                    }
                    else if ($obj->source_type == 'contact')
                    {
                    	include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
						$m=new Contact($db);
						$m->fetch($obj->source_id);
						$m->fetch_thirdparty();
						print '<td align="center">';
						print '<a href="'.dol_buildpath('/sendinblue/sendinblue/contact_activites.php',1).'?id='.$m->id.'">'.$m->getFullName($langs).'</a>';
						print '</td>';
						print '<td align="center">';
						if (!empty($m->thirdparty->id))
							print $m->thirdparty->getNomUrl(1);
						print '</td>';


                    } else {
                    	print $obj->source_url;
                    }

                }

				// Statut pour l'email destinataire (Attentioon != statut du mailing)
				if ($obj->statut == 0)
				{
					print '<td align="center">&nbsp;</td>';
					print '<td align="right" class="nowrap">'.$langs->trans("MailingStatusNotSent");
					if ($user->rights->mailing->creer && $allowaddtarget) {
						$newToken = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];
						print '<a href="'.$_SERVER['PHP_SELF'].'?action=delete&rowid='.$obj->rowid.$param.'&token='.$newToken.'">'.img_delete($langs->trans("RemoveRecipient"));
					}
					print '</td>';
				}
				else
				{
					print '<td align="center">'.$obj->date_envoi.'</td>';
					print '<td align="right" class="nowrap">';
					print $sendinblue::libStatutDest($obj->statut,2);
					print '</td>';
				}


				//Sreach Icon
				print '<td></td>';
				print '</tr>';

				$i++;
			}
		}
		else
		{
			print '<tr '.$bc[false].'><td colspan="8">'.$langs->trans("NoTargetYet").'</td></tr>';
		}
		print "</table><br>";

		print '</form>';

        if($num ) {
            print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="sql" value="'.base64_encode($sql).'">';
            print '<input type="hidden" name="id" value="'.$object->id.'">';
            print '<input type="hidden" name="action" value="exportlist">';

            print ''.$langs->trans("ExportList").': '.'<input type="submit" name="exportlist" class="button" value="'.$langs->trans("Export").'" />';

            print '</form>';

        }

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	print "\n<!-- Fin liste destinataires selectionnes -->\n";
	dol_fiche_end();
}


llxFooter();
$db->close();
