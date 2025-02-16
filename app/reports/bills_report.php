<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2023
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
	Luis Daniel Lucio Quiroz <dlucio@okay.com.mx>
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permisions
	if (permission_exists('reports_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//get posted data
	if (!$archive_request && is_array($_POST['xml_cdrs'])) {
		$action = $_POST['action'];
		$xml_cdrs = $_POST['xml_cdrs'];
	}

//process the http post data by action
	if (!$archive_request && $action != '' && is_array($xml_cdrs) && @sizeof($xml_cdrs) != 0) {
		switch ($action) {
			case 'delete':
				if (permission_exists('reports_delete')) {
					$obj = new xml_cdr;
					$obj->delete($xml_cdrs);
				}
				break;
		}

		header('Location: bills_report.php?direction=outbound');
		exit;
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//include the header
	if ($archive_request) {
		$document['title'] = $text['title-bill_report_archive'];
	}
	else {
		$document['title'] = $text['title-bill_report'];
	}
	require_once "resources/header.php";

?>

<link href="multiselect/fSelect.css" rel="stylesheet">
<script src="multiselect/jquery.min.js"></script>
<script src="multiselect/fSelect.js"></script>
<script>
(function($) {
    $(function() {
        window.fs_test = $('.multiselect').fSelect();
    });
})(jQuery);
</script>

<?php
//xml cdr include
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	require_once "bills_report_inc.php";

//javascript function: send_cmd
	echo "<script type=\"text/javascript\">\n";
	echo "	function send_cmd(url) {\n";
	echo "		if (window.XMLHttpRequest) { // code for IE7+, Firefox, Chrome, Opera, Safari\n";
	echo "			xmlhttp=new XMLHttpRequest();\n";
	echo "		}\n";
	echo "		else {// code for IE6, IE5\n";
	echo "			xmlhttp=new ActiveXObject(\"Microsoft.XMLHTTP\");\n";
	echo "		}\n";
	echo "		xmlhttp.open(\"GET\",url,true);\n";
	echo "		xmlhttp.send(null);\n";
	echo "		document.getElementById('cmd_reponse').innerHTML=xmlhttp.responseText;\n";
	echo "	}\n";
	echo "</script>\n";

//javascript to toggle export select box
	echo "<script language='javascript' type='text/javascript'>";
	echo "	var fade_speed = 400;";
	echo "	function toggle_select(select_id) {";
	echo "		$('#'+select_id).fadeToggle(fade_speed, function() {";
	echo "			document.getElementById(select_id).selectedIndex = 0;";
	echo "			document.getElementById(select_id).focus();";
	echo "		});";
	echo "	}";
	echo "</script>";
	
	
//show the content
	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'>";
	if ($archive_request) {
		echo "<b>".$text['title-bill_report_archive']."</b>";
	}
	else {
		echo "<b>".$text['title-bill_report']."</b>";
	}
	echo "</div>\n";
	echo "	<div class='actions'>\n";
	echo 		"<form id='frm_export' class='inline' method='post' action='bills_report_export.php'>\n";
	if ($archive_request) {
		echo "	<input type='hidden' name='archive_request' value='true'>\n";
	}
	echo "		<input type='hidden' name='direction' value='".escape($direction)."'>\n";
	echo "		<input type='hidden' name='extension_uuid' value='".escape($extension_uuid)."'>\n";
	echo "		<input type='hidden' name='start_stamp_begin' value='".escape($start_stamp_begin)."'>\n";
	echo "		<input type='hidden' name='start_stamp_end' value='".escape($start_stamp_end)."'>\n";
	foreach ($domains as $key => $value) {
		echo "		<input type='hidden' name='domain_uuida[]' value='".escape($value)."'>\n";
	}
	echo "		<input type='hidden' name='end_stamp_begin' value='".escape($end_stamp_begin)."'>\n";
	echo "		<input type='hidden' name='end_stamp_end' value='".escape($end_stamp_end)."'>\n";
	echo "		<input type='hidden' name='start_epoch' value='".escape($start_epoch)."'>\n";
	echo "		<input type='hidden' name='stop_epoch' value='".escape($stop_epoch)."'>\n";
	echo "		<input type='hidden' name='billmsec' value='".escape($billmsec)."'>\n";
	echo "		<input type='hidden' name='xml_cdr_uuid' value='".escape($xml_cdr_uuid)."'>\n";
	echo "		<input type='hidden' name='read_codec' value='".escape($read_codec)."'>\n";
	echo "		<input type='hidden' name='write_codec' value='".escape($write_codec)."'>\n";
	echo "		<input type='hidden' name='remote_media_ip' value='".escape($remote_media_ip)."'>\n";
	echo "		<input type='hidden' name='network_addr' value='".escape($network_addr)."'>\n";
	echo "		<input type='hidden' name='bridge_uuid' value='".escape($bridge_uuid)."'>\n";
	if (permission_exists('reports_all') && $_REQUEST['show'] == 'all') {
		echo "	<input type='hidden' name='show' value='all'>\n";
	}
	if (isset($order_by)) {
		echo "	<input type='hidden' name='order_by' value='".escape($order_by)."'>\n";
		echo "	<input type='hidden' name='order' value='".escape($order)."'>\n";
	}
	echo button::create(['type'=>'button','label'=>$text['button-refresh'],'icon'=>'sync-alt','style'=>'margin-left: 15px;','onclick'=>'location.reload(true);']);

	if (permission_exists('reports_export_pdf')) {
		echo button::create(['type'=>'button','label'=>$text['button-export'],'icon'=>$_SESSION['theme']['button_icon_export'],'onclick'=>"toggle_select('export_format'); this.blur();"]);
		echo 		"<select class='formfld' style='display: none; width: auto;' name='export_format' id='export_format' onchange=\"display_message('".$text['message-preparing_download']."'); toggle_select('export_format'); document.getElementById('frm_export').submit();\">";
		echo "			<option value='' disabled='disabled' selected='selected'>".$text['label-format']."</option>";
		if (permission_exists('reports_export_pdf')) {
			echo "			<option value='pdf'>PDF</option>";
		}
		echo "		</select>";
	}
	if (permission_exists('reports_all') && $_REQUEST['show'] !== 'all') {
		echo button::create(['type'=>'button','label'=>$text['button-show_all'],'icon'=>$_SESSION['theme']['button_icon_all'],'link'=>'?show=all']);
	}
	if ($paging_controls_mini != '') {
		echo 	"<span style='margin-left: 15px;'>".$paging_controls_mini."</span>";
	}
	echo "		</form>\n";
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";
	echo "<br /><br />\n";

//basic search of call detail records
	if (permission_exists('reports_search')) {
		echo "<form name='frm' id='frm' method='get'>\n";

		echo "<div class='form_grid'>\n";
		
		if (permission_exists('reports_search_extension')&&$_REQUEST['show'] == "all"&& permission_exists('reports_all')) {
			// 01/09/2022 $sql = "select DISTINCT(domain_uuid), domain_name from v_xml_cdr where ";
			$sql = "select DISTINCT(d.domain_uuid), d.domain_name FROM v_domains d WHERE EXISTS (SELECT 1 FROM v_xml_cdr x WHERE d.domain_uuid = x.domain_uuid AND d.domain_enabled = 't') and domain_enabled = 't' and domain_uuid NOT IN ('4534fa8d-2b78-4bfb-b5b9-e36168430a89','f1299fc3-c4b0-4638-a9a8-4df131084338','39a8546f-0803-4dda-91b1-39ef3e10b4d0','1a1226ab-3276-402a-a0d7-26934e153141','acd7b1e9-b30c-4eb3-b533-4843cfefb3b1')";
///			$sql .= "Except SELECT domain, name, category, price FROM Books1 WHERE price > 5000";
			$sql .= "order by domain_uuid asc, domain_name asc ";
			$database = new database;
			$result_e = $database->select($sql, $parameters, 'all');
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-domain-name']."\n";
			echo "		</div>\n";
			echo "		<div class='field'>\n";
			echo "			<select class='multiselect' name='domain_uuida[]' id='domain_uuida' multiple='multiple'>\n";
			echo "				<option value=''>All</option>";
			if (is_array($result_e) && @sizeof($result_e) != 0) {
				foreach ($result_e as $key => &$row) {
					$selected = ($row['domain_uuid'] == $domains[$key]) ? "selected" : null;
					echo "		<option value='".escape($row['domain_uuid'])."' ".escape($selected).">".((is_numeric($row['domain_name'])) ? escape($row['domain_name']) : escape($row['domain_name'])." ")."</option>";
				}
			}
			echo "			</select>\n";
			echo "		</div>\n";
			echo "	</div>\n";
			unset($sql, $parameters, $result_e, $row, $selected, $key);
		}
		if (permission_exists('reports_search_start_range')) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-start_range']."\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";
			echo "			<input type='date' data-target='#start_stamp_begin' style='min-width: 115px; width: 115px;' name='start_stamp_begin' id='start_stamp_begin' placeholder='".$text['label-from']."' value='".escape($start_stamp_begin)."' autocomplete='off'>\n";
			echo "			<input type='date' data-target='#start_stamp_end' style='min-width: 115px; width: 115px;' name='start_stamp_end' id='start_stamp_end' placeholder='".$text['label-to']."' value='".escape($start_stamp_end)."' autocomplete='off'>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}
		if (permission_exists('reports_search_order')) {
			echo "	<div class='form_set'>\n";
			echo "		<div class='label'>\n";
			echo "			".$text['label-order']."\n";
			echo "		</div>\n";
			echo "		<div class='field no-wrap'>\n";
			echo "			<select name='order_by' class='formfld'>\n";
			if (permission_exists('reports_search_caller_id')) {
				echo "			<option value='domain_name' ".($order_by == 'domain_name' ? "selected='selected'" : null).">".$text['label-domain']."</option>\n";
			}
			if (permission_exists('reports_search_caller_id')) {
				echo "			<option value='domain_description' ".($order_by == 'domain_description' ? "selected='selected'" : null).">".$text['label-name']."</option>\n";
			}
			if (permission_exists('reports_search_caller_destination')) {
				echo "			<option value='month' ".($order_by == 'month' ? "selected='selected'" : null).">".$text['label-month']."</option>\n";
			}
			if (permission_exists('rreports_search_destination')) {
				echo "			<option value='sdate' ".($order_by == 'sdate' ? "selected='selected'" : null).">".$text['label-sdate']."</option>\n";
			}
			if (permission_exists('reports_search_duration')) {
				echo "			<option value='edate' ".($order_by == 'edate' ? "selected='selected'" : null).">".$text['label-edate']."</option>\n";
			}
			if (permission_exists('reports_pdd')) {
				echo "			<option value='sec' ".($order_by == 'sec' ? "selected='selected'" : null).">".$text['label-minutes']."</option>\n";
			}
			echo "			</select>\n";
			echo "			<select name='order' class='formfld'>\n";
			echo "				<option value='desc' ".($order == 'desc' ? "selected='selected'" : null).">".$text['label-descending']."</option>\n";
			echo "				<option value='asc' ".($order == 'asc' ? "selected='selected'" : null).">".$text['label-ascending']."</option>\n";
			echo "			</select>\n";
			echo "		</div>\n";
			echo "	</div>\n";
		}

		echo "</div>\n";

		button::$collapse = false;
		echo "<div style='float: right; padding-top: 15px; margin-left: 20px; white-space: nowrap;'>";
		if (permission_exists('reports_all') && $_REQUEST['show'] == 'all') {
			echo "<input type='hidden' name='show' value='all'>\n";
		}

		echo button::create(['label'=>$text['button-reset'],'icon'=>$_SESSION['theme']['button_icon_reset'],'type'=>'button','link'=>($archive_request ? 'xml_cdr_archive.php' : 'bills_report.php?direction=outbound')]);
		echo button::create(['label'=>$text['button-search'],'icon'=>$_SESSION['theme']['button_icon_search'],'type'=>'submit','id'=>'btn_save','name'=>'submit']);
		
		echo "</div>\n";
		echo "</form>";
	}

//mod paging parameters for inclusion in column sort heading links
	$param = substr($param, 1); //remove leading '&'
	$param = substr($param, 0, strrpos($param, '&order_by=')); //remove trailing order by

//show the results
	echo "<form id='form_list' method='post'>\n";
	echo "<input type='hidden' id='action' name='action' value=''>\n";

	echo "<table class='list'>\n";
	echo "<tr class='list-header'>\n";
	
	$col_count = 0;
	/*if (!$archive_request && permission_exists('reports_delete')) {
		echo "	<th class='checkbox'>\n";
		echo "		<input type='checkbox' id='checkbox_all' name='checkbox_all' onclick='list_all_toggle();' ".($result ?: "style='visibility: hidden;'").">\n";
		echo "	</th>\n";
		$col_count++;
	//}*/

//column headings
	if (permission_exists('reports_direction')) {
		echo "<th class='shrink'>&nbsp;</th>\n";
		$col_count++;
	}
		echo "<th>".$text['label-domain']."</th>\n";
		$col_count++;
		
		echo "<th>".$text['label-name']."</th>\n";
		$col_count++;
		
		echo "<th>".$text['label-month']."</th>\n";
		$col_count++;
		
		echo "<th>".$text['label-sdate']."</th>\n";
		$col_count++;
		
		echo "<th>".$text['label-edate']."</th>\n";
		$col_count++;
		
		echo "<th>".$text['label-calls']."</th>\n";
		$col_count++;
		
		echo "<th>".$text['label-in-minutes']."</th>\n";
		$col_count++;
		
		echo "<th class='center shrink'>".$text['label-minutes']."</th>\n";
		$col_count++;
		
	echo "</tr>\n";

//show results
	if (is_array($result)) {

		//determine if theme images exist
			$theme_image_path = $_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/";
			$theme_cdr_images_exist = (
				file_exists($theme_image_path."icon_cdr_inbound_answered.png") &&
				file_exists($theme_image_path."icon_cdr_inbound_voicemail.png") &&
				file_exists($theme_image_path."icon_cdr_inbound_cancelled.png") &&
				file_exists($theme_image_path."icon_cdr_inbound_failed.png") &&
				file_exists($theme_image_path."icon_cdr_outbound_answered.png") &&
				file_exists($theme_image_path."icon_cdr_outbound_cancelled.png") &&
				file_exists($theme_image_path."icon_cdr_outbound_failed.png") &&
				file_exists($theme_image_path."icon_cdr_local_answered.png") &&
				file_exists($theme_image_path."icon_cdr_local_voicemail.png") &&
				file_exists($theme_image_path."icon_cdr_local_cancelled.png") &&
				file_exists($theme_image_path."icon_cdr_local_failed.png")
				) ? true : false;

		//loop through the results
			$x = 0;
			foreach ($result as $index => $row) {

				//set an empty content variable
					$content = '';

				//recording playback
					$content .= "<tr class='list-row' href='".$list_row_url."'>\n";

				//determine call result and appropriate icon
					if (permission_exists('reports_direction')) {
						$content .= "<td class='middle'>\n";
						if ($theme_cdr_images_exist) {
							if ($row['direction'] == 'inbound' || $row['direction'] == 'local') {
								if ($row['answer_stamp'] != '' && $row['bridge_uuid'] != '') { $call_result = 'answered'; }
								else if ($row['answer_stamp'] != '' && $row['bridge_uuid'] == '') { $call_result = 'voicemail'; }
								else if ($row['answer_stamp'] == '' && $row['bridge_uuid'] == '' && $row['sip_hangup_disposition'] != 'send_refuse') { $call_result = 'cancelled'; }
								else { $call_result = 'failed'; }
							}
							else if ($row['direction'] == 'outbound') {
								if ($row['answer_stamp'] != '' && $row['bridge_uuid'] != '') { $call_result = 'answered'; }
								else if ($row['answer_stamp'] == '' && $row['bridge_uuid'] != '') { $call_result = 'cancelled'; }
								else { $call_result = 'failed'; }
							}
							if (strlen($row['direction']) > 0) {
								$image_name = "icon_cdr_" . $row['direction'] . "_" . $call_result;
								$image_name .= ".png";
								$content .= "<img src='".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/".escape($image_name)."' width='16' style='border: none; cursor: help;' title='".$text['label-'.$row['direction']]."'>\n";
							}
						}
						else { $content .= "&nbsp;"; }
						$content .= "</td>\n";
					}
				//domain name
						$input = $row['sec'];
						$uSec = $input % 1000;
						$input = floor($input / 1000);
						$seconds = $input % 60;
						$input = floor($input / 60);
						$minutes = $input % 60;
						$input = floor($input / 60);
						$hour = $input ;

						$ininput = $row['international'];
		                                $iuSec = $ininput % 1000;
                                		$ininput = floor($ininput / 1000);
                		                $inseconds = $ininput % 60;
		                                $ininput = floor($ininput / 60);
                		                $inminutes = $ininput % 60;
		                                $ininput = floor($ininput / 60);
		                                $inhour = $ininput ;

						$content .= "	<td class='middle'>".$row['domain_name']."</td>\n";
						$content .= "	<td class='middle'>".$row['domain_description']."</td>\n";
						$content .= "	<td class='middle'>".$row['month']."</td>\n";
						$content .= "	<td class='middle'>".$row['sdate']."</td>\n";
						$content .= "	<td class='middle'>".$row['edate']."</td>\n";
						$content .= "	<td class='middle'>".$row['tcalls']."</td>\n";
//						$content .= "	<td class='middle'>".$row['international']."</td>\n";
//						$content .= "   <td class='middle'>".sprintf('%02d:%02d:%02d', $inhour, $inminutes, $inseconds)."</td>\n";
						//$content .= "	<td class='middle'>".sprintf('%02d:%02d:%02d', $hour, $minutes, $seconds)."</td>\n";
						$content .= "   <td class='middle'>".(($row['international'] > 0) ? round($row['international']/60000,1).' m' : null)."</td>\n";
						$content .= "   <td class='middle'>".(($row['sec'] > 0) ? round($row['sec']/60000,0).' m' : null)."</td>\n";
						//formatMilliseconds($milliseconds)
						//$content .= "	<td class='middle'>".gmdate("G:i:s", round($row['sec'] / 1000, 0))."</td>\n";
						//$content .= " <td class='middle'>".$hours . ':' . $minsec."</td>\n";
					$content .= "</tr>\n";
				//show the leg b only to those with the permission
					echo $content;
					unset($content);

				$x++;
			}
			unset($sql, $result, $row_count);

	}

	echo "</table>\n";
	echo "<br />\n";
	echo "<div align='center'>".$paging_controls."</div>\n";
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";
	echo "</form>\n";

//store last search/sort query parameters in session
	$_SESSION['xml_cdr']['last_query'] = $_SERVER["QUERY_STRING"];

//show the footer
	require_once "resources/footer.php";

?>
