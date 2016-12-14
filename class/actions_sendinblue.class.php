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
 * \file    class/actions_sendinblue.class.php
 * \ingroup sendinblue
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class Actionssendinblue
 */
class Actionssendinblue
{
	var $db;

	var $error;
	var $errors = array();

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	function __construct($db) {
		$this->db = $db;
		$this->error = 0;
		$this->errors = array();

	}

	/**
	 * formObjectOptions Method Hook Call
	 *
	 * @param array $parameters Array of parameters
	 * @param Object &$object Object to use hooks on
	 * @param string &$action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager Hook manager class instance
	 * @return void
	 */
	function formObjectOptions($parameters, &$object, &$action, $hookmanager) {
		global $langs, $conf, $user;

		dol_syslog(get_class($this) . ':: formObjectOptions', LOG_DEBUG);

		/*dol_syslog(get_class($this).':: $hookmanager->contextarray='.var_export($hookmanager->contextarray,true),LOG_DEBUG);
		 dol_syslog(get_class($this).':: $action='.$action,LOG_DEBUG);
		 dol_syslog(get_class($this).':: $object->table_element='.$object->table_element,LOG_DEBUG);
		 dol_syslog(get_class($this).':: $object->id='.$object->id,LOG_DEBUG);
		 dol_syslog(get_class($this).':: $conf->global->MAILCHIMP_ACTIVE='.$conf->global->MAILCHIMP_ACTIVE,LOG_DEBUG);*/

		// Add javascript Jquery to add button Create Destinatires list
		if (empty($action) && ($object->table_element == 'product')
				&& (! empty($object->id)) && (! empty($conf->global->MAILCHIMP_ACTIVE)
						|| ! empty($conf->global->MAILCHIMP_ACTIVE_MAILING_ONLY))) {

			$langs->load('sendinblue@sendinblue');



			$type_list = '';

			// Add button for product/propal list
			if (in_array('productstatspropal', $hookmanager->contextarray)) {
				$type_list = 'propal';
			}
			if (in_array('productstatssupplyorder', $hookmanager->contextarray)) {
				$type_list = 'supplyorder';
			}
			if (in_array('productstatsorder', $hookmanager->contextarray)) {
				$type_list = 'order';
			}
			if (in_array('productstatscontract', $hookmanager->contextarray)) {
				$type_list = 'contract';
			}
			if (in_array('productstatssupplyinvoice', $hookmanager->contextarray)) {
				$type_list = 'supplyinvoice';
			}
			if (in_array('productstatsinvoice', $hookmanager->contextarray)) {
				$type_list = 'invoice';
			}

			$object_param_string = 'productid=' . $object->id;

			$btnhtml = '<div class=\"inline-block divButAction\"><a class=\"butAction\" href=\"' . dol_buildpath('/sendinblue/sendinblue/destinaries_list.php', 1) . '?action=associate&' . $object_param_string . '&type=' . $type_list . '\">' . $langs->trans('SendinBlueCreateList') . '</a></div>';

			print '<script type="text/javascript">
					jQuery(document).ready(function () {
						jQuery(function() {
							jQuery(".fiche").append("' . $btnhtml . '");
						});
					});
					</script>';
		}

		if ($parameters['currentcontext'] == 'categorycard' ||
				in_array('categorycard', explode(':', $parameters['context']))
				&& (stripos($_SERVER['PHP_SELF'], 'viewcat.php') !== false
						|| stripos($_SERVER['PHP_SELF'], 'edit.php') !== false)) {
			$mode_view = 0;
			if (stripos($_SERVER['PHP_SELF'], 'viewcat.php') !== false) {
				// Pour le moment j'ai pas mieux pour différencier la vue de l'édition
				$mode_view = 1;
			}

			dol_include_once('/sendinblue/lib/sendinblue.lib.php');
			dol_include_once('/sendinblue/class/dolsendinblue.class.php');

			$sendinblue = new DolSendinBlue($this->db);
			$TInfo = getSendinBlueInfoFromCategorie($object->id);

			$TListId = $TSegmentId = array();
			if (! empty($TInfo)) {
				foreach ( $TInfo as &$line ) {
					$TListId[] = $line->sendinblue_listid;
					$TSegmentId[] = $line->sendinblue_segmentid;
				}
			}

			// Get sendinblue list
			$result = $sendinblue->getListDestinaries();

			$TSegment = array();
			foreach ( $TListId as $listId ) {
				// Get sendinblue segment for each list
				$r = $sendinblue->getListSegmentDestinaries($listId);
				if ($r > 0) {
					$TSegment[$listId] = $sendinblue->listsegment_lines;
				}
			}

			if ($result >= 0) {
				$options = array(
						'' => ''
				);
				if (is_array($sendinblue->listdest_lines)) {
					foreach ( $sendinblue->listdest_lines as $line ) {
						$options[$line['id']] = $line['name'];
					}
				}

				dol_include_once('/core/class/html.form.class.php');
				$form = new Form($this->db);

				$html = '<tr>
							<td>' . $langs->trans('sendinblue_select_list') . '</td>';

				if ($mode_view) {
					$html .= '<td>';
					foreach ( $options as $id => $name ) {
						if (in_array($id, $TListId))
							$html .= '<span style="background:#aaa; color:#fff; border-radius:3px; margin-right:5px; padding:1px 5px;">' . $name . '</span>';
					}
					$html .= '</td>';
				} else {
					// TODO faire évoluer le select en multiselect
					$html .= '<td>' . $form->selectarray('sendinblue_listid', $options, ! empty($TListId) ? $TListId[0] : '') . '</td>';
				}

				$html .= '</tr>';

				$html .= '<tr>
							<td>' . $langs->trans('sendinblue_select_segment') . '</td>';

				if ($mode_view) {
					$html .= '<td>';
					foreach ( $TSegment as $listId => &$Tab ) {
						foreach ( $Tab as &$s ) {
							if (in_array($s['id'], $TSegmentId))
								$html .= '<span style="background:#aaa; color:#fff; border-radius:3px; margin-right:5px; padding:1px 5px;">' . $options[$listId] . ' >> ' . $s['name'] . '</span>';
						}
					}
					$html .= '</td>';
				} else {
					// TODO faire évoluer le select en multiselect
					$html .= '<td><select name="sendinblue_segmentid" id="sendinblue_segmentid"></select></td>';

				}

				echo $html;
			}

			// Si mode vue, alors j'affiche aussi un nouveau boutton pour faire de la syncho sendinblue
			if ($mode_view) {
				if (! empty($user->rights->sendinblue->sync)) {
					?>

					<script type="text/javascript">
						$(function() {
						var sendinblue_bt_sync = $("<a onclick='if (!window.confirm(\"<?php echo $langs->transnoentitiesnoconv('sendinblue_confirm_sync'); ?>\")) return false;' title='<?php echo $langs->transnoentitiesnoconv('sendinblue_warning_info_about_sync'); ?>' class='butAction' href='<?php echo dol_buildpath('/sendinblue/scripts/interface.php', 1).'?id='.$object->id.'&type='.$object->type.'&action=sendinblueSync'; ?>'><?php echo $langs->transnoentities('sendinblue_sync'); ?></a>");

							$(".tabsAction").prepend(sendinblue_bt_sync);
						});
					</script>

<?php
				}
			} else {
?>

				<script type="text/javascript">
					$(function() {
						$("#sendinblue_listid").change(function(event) {
							var sendinblue_listId = $(this).val(); // TODO quand le select "sendinblue_listid" sera de type multiple il faudra affecter la valeur directement à TListId
							var sendinblue_TListId = [sendinblue_listId];
							var sendinblue_TDefaultSegment = <?php echo json_encode($TSegmentId); ?>;
							var sendinblue_first_loop_done = false;

							$("#sendinblue_segmentid").empty();
							$.ajax({
								url: "<?php echo dol_buildpath('/sendinblue/scripts/interface.php', 1); ?>"
								,type: "POST"
								,dataType: "json"
								,data: {
									id: <?php echo (int) $object->id; ?>
									,type: <?php echo (int) $object->type; ?>
									,action: "sendinblueGetSegmentFromListId"
									,TListId: sendinblue_TListId
								}
							}).done(function(TSegment) {
								var target = $("#sendinblue_segmentid");
								target.append($("<option value=''></option>"));

								for (var i in TSegment) {
									var option = $("<option value='"+TSegment[i].id+"'>"+TSegment[i].name+"</option>");
									if (!sendinblue_first_loop_done && sendinblue_TDefaultSegment.indexOf(TSegment[i].id.toString()) != -1) option.attr('selected', true);

									target.append(option);
								}

								sendinblue_first_loop_done = true;
							});
						}).trigger('change');
					});
				</script>

<?php
			}

			// var_dump($parameters);
			// exit;
		}
		if (in_array('contactcard', $hookmanager->contextarray)) {

			if ($action == 'edit' && GETPOST('action') == 'update') {
				echo '<div id="dialog" title="' . $langs->trans('UpdateSendinBlueObject') . '">';
				echo $langs->trans('ConfirmUpdateSendinBlueObject');
				echo "</div>";
?>
						<script type="text/javascript">
						  $(function() {
						    $( "#dialog" ).dialog({
						      modal: true,
						      width: 400,
						      buttons: {
						        "Oui": function() {
						          $('input[name=action]').val('confirm_update');
						          $('form[name=formsoc]').submit();
						          $( this ).dialog( "close" );
						        },
						        "Non": function() {
						          $( this ).dialog( "close" );
						        }
						      }
						    });
						  });
						</script>
<?php
				// $form=new Form($db);
				// $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $contact->id, $langs->trans('UpdateSendinBlueObject'), $langs->trans('ConfirmUpdateSendinBlueObject'), 'confirm_update', '', 0, 1);
				// print $formconfirm;
			}
		}
	}

	/**
	 * Return action of hook
	 *
	 * @param array $parameters Linked object
	 * @param object $object Object
	 * @param string $action Action type
	 * @param object $hookmanager Hook manager class instance
	 * @return int Status
	 */
	function doActions($parameters = false, &$object, &$action = '', $hookmanager) {
		dol_syslog(get_class($this) . ':: doActions', LOG_DEBUG);

		global $db, $langs, $conf;

		if ((! empty($conf->global->MAILCHIMP_ACTIVE) || ! empty($conf->global->MAILCHIMP_ACTIVE_MAILING_ONLY)) && in_array('mailingcard', $hookmanager->contextarray)) {

			$langs->load("sendinblue@sendinblue");
			$langs->load("user");

			// Change substitution array
			$object->substitutionarray = array(
					'*|FNAME|*' => 'Firstname',
					'*|LNAME|*' => 'Lastname',
					'*|EMAIL|*' => 'EMail',
					'*|UNSUB|*' => 'SendinBlueUnsubscribe'
			);

			$object->substitutionarrayfortest = array(
					'*|FNAME|*' => 'Firstname',
					'*|LNAME|*' => 'Lastname',
					'*|EMAIL|*' => 'EMail',
					'*|UNSUB|*' => 'SendinBlueUnsubscribe'
			);

			if ($action == '' || $action == 'valid') {
				$error_sendinblue_control = 0;
				// Unsubscribe link mandatory
				// if (preg_match('/\*\|UNSUB\|\*/',$object->body)==0) {
				// setEventMessage('SendinBlue:'.$langs->trans("MailJetUnsubLinkMandatory"),'warnings');
				// $error_mailjet_control++;
				// }

				require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

				// Attached file are not allowed for MailJet Mailing
				$error_file_attach = false;
				$upload_dir = $conf->mailing->dir_output . "/" . get_exdir($object->id, 2, 0, 1, $object, 'mailing');
				$listofpaths = dol_dir_list($upload_dir, 'files', 0, '', '', 'name', SORT_ASC, 0);
				if (count($listofpaths)) {
					setEventMessage('SendinBlue:' . $langs->trans("SendinBlueNoFileAttached"), 'warnings');
					$error_sendinblue_control ++;
				}

				if ($action == 'valid' && ! empty($error_sendinblue_control)) {
					setEventMessage('SendinBlue:' . $langs->trans("SendinBlueCannotSendControlNotOK"), 'warnings');
				}
			}

			if ($action == 'sendall') {
				setEventMessage('SendinBlue:' . $langs->trans("SendinBlueToSendByMailJetGoToSendinBlue"), 'warnings');
			}
		}
		// require_once DOL_DOCUMENT_ROOT .'/core/db/Database.interface.php';

		if (in_array('contactcard', $hookmanager->contextarray)) {
			if ($action == 'confirm_update') {
				$action = 'update';
			} else {
				dol_include_once('/sendinblue/class/dolsendinblue.class.php');
				$sendinblue = new DolSendinBlue($this->db);
				$contact = new Contact($db);
				$contact->fetch(GETPOST('id'));
				$result=$sendinblue->getListForEmail($contact->email);
				if ($result != - 1
						&& array_key_exists('total_items', $result)
						&& $result['total_items']>0
						&& $action == 'update'
						&& $contact->email != GETPOST('email')) {
					$action = 'edit';
				}
			}
		}

		return 0;
	}
	}
