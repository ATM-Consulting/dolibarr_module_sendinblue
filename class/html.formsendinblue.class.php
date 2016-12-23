<?php
/* <SendinBlue connector>
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
 *	\file       /sendinblue/class/html.formsendinblue.class.php
 *  \ingroup    sendinblue
 *	\brief      HTML coponent for SendinBlue
 */
require_once 'dolsendinblue.class.php';

/**
 *	Class to offer components to list and upload files
 */
class FormSendinBlue
{
	var $db;
	var $error;
	
	var $num;



	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		return 1;
	}

	/**
	 *	Select list with current SendinBlue List
	 *
	 *  @param		string		$htmlname     	HTML name input
	 *  @param		int			$showempty      display empty options
	 *  @param		string		$selected      	Id of preselected option
	 *  @param		int			$option_only 	output only options
	 *  @param		array		$event			Event options. (disabled if $option_only=true)
	 *  @param array $filters a hash of filters to apply to this query - all are optional:
			 string list_id optional - return a single list using a known list_id. Accepts multiples separated by commas when not using exact matching
			 string list_name optional - only lists that match this name
			 string from_name optional - only lists that have a default from name matching this
			 string from_email optional - only lists that have a default from email matching this
			 string from_subject optional - only lists that have a default from email matching this
			 string created_before optional - only show lists that were created before this date/time (in GMT) - format is YYYY-MM-DD HH:mm:ss (24hr)
			 string created_after optional - only show lists that were created since this date/time (in GMT) - format is YYYY-MM-DD HH:mm:ss (24hr)
			 boolean exact optional - flag for whether to filter on exact values when filtering, or search within content for filter values - defaults to true
	 * @param int $start optional - control paging of lists, start results at this list #, defaults to 1st page of data  (page 0)
	 * @param int $limit optional - control paging of lists, number of lists to return with each call, defaults to 25 (max=100)
	 * @param string $sort_field optional - "created" (the created date, default) or "web" (the display order in the web app). Invalid values will fall back on "created" - case insensitive.
	 * @param string $sort_dir optional - "DESC" for descending (default), "ASC" for Ascending.  Invalid values will fall back on "created" - case insensitive. Note: to get the exact display order as the web app you'd use "web" and "ASC"
	 *
	 *  @return string 		HTML input
	 */
	function select_sendinbluelist($htmlname='selectlist',$showempty=0,$selected='',$option_only=0, $event=array(), $filters = array(),$start=0, $limit=100, $sort_field='created', $sort_dir='DESC') {
			
		$error=0;
		$out='';
			
		$sendinblue= new DolSendinBlue($this->db);
		$result=$sendinblue->getListDestinaries();
		if ($result<0) {
			$this->error=$sendinblue->errors;
			dol_syslog(get_class($this)."::select_sendinbluelist Error : ".$this->error, LOG_ERR);
			return -1;
		}
		
		if (empty($option_only)) {
			if (count($event)>0) {
				$out .= "\n".'<script type="text/javascript">
					$(document).ready(function () {
						$("select#'.$htmlname.'").change(function() {
						
				    			var obj = '.json_encode($event).';
				    			$.each(obj, function(key,values) {
				    				if (values.method.length) {
				    					getMethod(values);
				    				}
								});
						
						});
							
						function getMethod(obj) {
							var id = $("select#'.$htmlname.'").val();
							var method = obj.method;
							var url = obj.url;
							var htmlname = obj.htmlname;
				    		$.getJSON(url,
									{
										action: method,
										id: id,
										htmlname: htmlname
									},
									function(response) {
										$.each(obj.params, function(key,action) {
											if (key.length) {
												var num = response.num;
												if (num > 0) {
													$("#" + key).removeAttr(action);
												} else {
													$("#" + key).attr(action, action);
												}
											}
										});
										$("select#" + htmlname).html(response.value);
									});
						}
					});';
	
				$out.= "</script>\n";
			}
			$out.= '<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
		}

		if (!empty($showempty)) {
			if (empty($selected)) {
				$out.= '<option value="" selected="selected">&nbsp;</option>';
			}else {
				$out.= '<option value="">&nbsp;</option>';
			}
		}
			
		if (is_array($sendinblue->listdest_lines) && count($sendinblue->listdest_lines)>0) {
			foreach($sendinblue->listdest_lines['data'] as $line) {
				if ($selected == $line['id'])
				{
					$out.= '<option value="'.$line['id'].'" selected="selected">';
				}
				else
				{
					$out.= '<option value="'.$line['id'].'">';
				}
				$out.=$line['name'];
				$out.= '</option>';

			}
		}
			
		if (empty($option_only)) {
			$out.= '</select>';
		}
		return $out;
	}
	
	/**
	 *	Select list with current SendinBlue List
	 *
	 * 	@param 		string 		$id 			id of list
	 *  @param		string		$htmlname     	HTML name input
	 *  @param		int			$showempty      display empty options
	 *  @param		string		$selected      	Id of preselected option
	 *  @param		int			$option_only 	output only options
	 * 
	 *  @return string 		HTML input
	 */
	function select_sendinbluesegement($id,$htmlname='segmentlist',$showempty=0,$selected='',$option_only=0) {
		
		$error=0;
		$out='';
		
		if (!empty($id)) {
			$sendinblue= new DolSendinBlue($this->db);
			$result=$sendinblue->getListSegmentDestinaries($id);
			if ($result<0) {
				$this->error=$sendinblue->errors;
				dol_syslog(get_class($this)."::select_sendinbluesegement Error : ".$this->error, LOG_ERR);
				return -1;
			}
		}
		$this->num=1;
		
		if (empty($option_only)) {
			$out= '<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
		}

		if (!empty($showempty)) {
			if (empty($selected)) {
				$out.= '<option value="" selected="selected">&nbsp;</option>';
			}else {
				$out.= '<option value="">&nbsp;</option>';
			}
		}
			
		if (is_array($sendinblue->listsegment_lines) && count($sendinblue->listsegment_lines)>0) {
			foreach($sendinblue->listsegment_lines as $line) {

				if ($selected == $line['id'])
				{
					$out.= '<option value="'.$line['id'].'" selected="selected">';
				}
				else
				{
					$out.= '<option value="'.$line['id'].'">';
				}
				$out.=$line['name'];
				$out.= '</option>';
			}
		}
			
		if (empty($option_only)) {
			$out.= '</select>';
		}
		return $out;
	}
	
	/**
	 * HTLM select dest status
	 * @param string $selectedid
	 * @param string $htmlname
	 * @param int $show_empty
	 * @return string
	 */
	public function selectDestinariesStatus($selectedid='',$htmlname='dest_status', $show_empty=0) {
	
		global $langs;
		$langs->load("mails");
	
		require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
		$mailing = new Mailing($this->db);
	
	
		$array = $mailing->statut_dest;
		//Cannot use form->selectarray because empty value is defaulted to -1 in this method and we use here status -1...
	
		$out = '<select name="'.$htmlname.'" class="flat">';
	
		if ($show_empty) {
			$out .= '<option value=""></option>';
		}
		$array_status=array(1=>$langs->trans("MailingStatusSent"),
			2=>$langs->trans("SendinBlueOpen"),
			3=>$langs->trans("SendinBlueUnsucscribe"),
			4=>$langs->trans("SendinBlueClick"),
			5=>$langs->trans("SendinBlueHardBounce"),
			6=>$langs->trans("SendinBlueSoftBounce"));

		foreach($array_status as $id=>$status) {
			if ($selectedid==$id)  {
				$selected=" selected=selected ";
			}else {
				$selected="";
			}
			$out .= '<option '.$selected.' value="'.$id.'">'.$langs->trans($status).'</option>';
		}
	
		$out .= '</select>';
		return $out;
	}
}