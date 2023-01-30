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
*/

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";
	require_once "resources/check_auth.php";
	require_once "resources/paging.php";

//check permissions
	if (permission_exists('reports_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//set 24hr or 12hr clock
	define('TIME_24HR', 1);

//get post or get variables from http
	if (count($_REQUEST) > 0) {
		$cdr_id = $_REQUEST["cdr_id"];
		$missed = $_REQUEST["missed"];
		$direction = $_REQUEST["direction"];
		$caller_id_name = $_REQUEST["caller_id_name"];
		$caller_id_number = $_REQUEST["caller_id_number"];
		$caller_destination = $_REQUEST["caller_destination"];
		//$domain_uuida = $_REQUEST["domain_uuida"];
		$destination_number = $_REQUEST["destination_number"];
		$context = $_REQUEST["context"];
		$start_stamp_begin = $_REQUEST["start_stamp_begin"];
		$start_stamp_end = $_REQUEST["start_stamp_end"];
		$answer_stamp_begin = $_REQUEST["answer_stamp_begin"];
		$answer_stamp_end = $_REQUEST["answer_stamp_end"];
		$end_stamp_begin = $_REQUEST["end_stamp_begin"];
		$end_stamp_end = $_REQUEST["end_stamp_end"];
		$start_epoch = $_REQUEST["start_epoch"];
		$stop_epoch = $_REQUEST["stop_epoch"];
		$duration_min = $_REQUEST["duration_min"];
		$duration_max = $_REQUEST["duration_max"];
		$billmsec = $_REQUEST["billmsec"];
		$hangup_cause = $_REQUEST["hangup_cause"];
		$call_result = $_REQUEST["call_result"];
		$xml_cdr_uuid = $_REQUEST["xml_cdr_uuid"];
		$bleg_uuid = $_REQUEST["bleg_uuid"];
		$accountcode = $_REQUEST["accountcode"];
		$read_codec = $_REQUEST["read_codec"];
		$write_codec = $_REQUEST["write_codec"];
		$remote_media_ip = $_REQUEST["remote_media_ip"];
		$network_addr = $_REQUEST["network_addr"];
		$bridge_uuid = $_REQUEST["network_addr"];
		$tta_min = $_REQUEST['tta_min'];
		$tta_max = $_REQUEST['tta_max'];
		$recording = $_REQUEST['recording'];
		foreach ($_REQUEST["domain_uuida"] as $domain) {
				$domains[] = $domain;
				}
														
		$order_by = $_REQUEST["order_by"];
		$order = $_REQUEST["order"];
		if (is_array($_SESSION['cdr']['field'])) {
			foreach ($_SESSION['cdr']['field'] as $field) {
				$array = explode(",", $field);
				$field_name = end($array);
				if (isset($_REQUEST[$field_name])) {
					$$field_name = $_REQUEST[$field_name];
				}
			}
		}
		
		if (strlen($_REQUEST["mos_comparison"]) > 0) {
			switch($_REQUEST["mos_comparison"]) {
				case 'less': $mos_comparison = "<"; break;
				case 'greater': $mos_comparison = ">"; break;
				case 'lessorequal': $mos_comparison = "<="; break;
				case 'greaterorequal': $mos_comparison = ">="; break;
				case 'equal': $mos_comparison = "<"; break;
				case 'notequal': $mos_comparison = "<>"; break;
			}
		}
		else {
			$mos_comparison = '';
		}
		//$mos_comparison = $_REQUEST["mos_comparison"];
		$mos_score = $_REQUEST["mos_score"];
		$leg = $_REQUEST["leg"];
	}
	
	

//get variables used to control the order
	$order_by = $_REQUEST["order_by"];
	$order = $_REQUEST["order"];
//validate the order
	switch ($order) {
		case 'asc':
			break;
		case 'desc':
			break;
		default:
			$order = '';
	}

//set the param variable which is used with paging
	$param = "&cdr_id=".urlencode($cdr_id);
	$param .= "&missed=".urlencode($missed);
	$param .= "&direction=".urlencode($direction);
	$param .= "&caller_id_name=".urlencode($caller_id_name);
	$param .= "&caller_id_number=".urlencode($caller_id_number);
	$param .= "&caller_destination=".urlencode($caller_destination);
	$param .= "&domain_uuida=".urlencode($domain_uuida);
	$param .= "&destination_number=".urlencode($destination_number);
	$param .= "&context=".urlencode($context);
	$param .= "&start_stamp_begin=".urlencode($start_stamp_begin);
	$param .= "&start_stamp_end=".urlencode($start_stamp_end);
	$param .= "&answer_stamp_begin=".urlencode($answer_stamp_begin);
	$param .= "&answer_stamp_end=".urlencode($answer_stamp_end);
	$param .= "&end_stamp_begin=".urlencode($end_stamp_begin);
	$param .= "&end_stamp_end=".urlencode($end_stamp_end);
	$param .= "&start_epoch=".urlencode($start_epoch);
	$param .= "&stop_epoch=".urlencode($stop_epoch);
	$param .= "&duration_min=".urlencode($duration_min);
	$param .= "&duration_max=".urlencode($duration_max);
	$param .= "&billmsec=".urlencode($billmsec);
	$param .= "&hangup_cause=".urlencode($hangup_cause);
	$param .= "&call_result=".urlencode($call_result);
	$param .= "&xml_cdr_uuid=".urlencode($xml_cdr_uuid);
	$param .= "&bleg_uuid=".urlencode($bleg_uuid);
	$param .= "&accountcode=".urlencode($accountcode);
	$param .= "&read_codec=".urlencode($read_codec);
	$param .= "&write_codec=".urlencode($write_codec);
	$param .= "&remote_media_ip=".urlencode($remote_media_ip);
	$param .= "&network_addr=".urlencode($network_addr);
	$param .= "&bridge_uuid=".urlencode($bridge_uuid);
	$param .= "&mos_comparison=".urlencode($mos_comparison);
	$param .= "&mos_score=".urlencode($mos_score);
	$param .= "&recording=".urlencode($recording);
	if (is_array($_SESSION['cdr']['field'])) {
		foreach ($_SESSION['cdr']['field'] as $field) {
			$array = explode(",", $field);
			$field_name = end($array);
			if (isset($$field_name)) {
				$param .= "&".$field_name."=".urlencode($$field_name);
			}
		}
	}
	if ($_GET['show'] == 'all' && permission_exists('reports_all')) {
		$param .= "&show=all";
	}
	if (isset($order_by)) {
		$param .= "&order_by=".urlencode($order_by)."&order=".urlencode($order);
	}

//create the sql query to get the xml cdr records
	if (strlen($order_by) == 0) { $order_by  = "sdate"; }
	if (strlen($order) == 0) { $order  = "desc"; }

//set a default number of rows to show
	$num_rows = '0';

//limit the number of results
	if ($_SESSION['cdr']['limit']['numeric'] > 0) {
		$num_rows = $_SESSION['cdr']['limit']['numeric'];
	}

//set the default paging
	$rows_per_page = $_SESSION['domain']['paging']['numeric'];

//prepare to page the results
	//$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50; //set on the page that includes this page
	if (is_numeric($_GET['page'])) { $page = $_GET['page']; }
	if (!isset($_GET['page'])) { $page = 0; $_GET['page'] = 0; }
	$offset = $rows_per_page * $page;

//get the results from the db
	$sql = "select \n";
	$sql .= "a.domain_name, \n";
	$sql .= "a.domain_uuid as domain, \n";
	$sql .= "b.domain_description, \n";
	$sql .= "	count(*) AS tcalls, \n"; 
	$sql .=	"	to_char(min(a.start_stamp), 'Month'::text) as month, \n";
	$sql .=	"	to_char(min(a.start_stamp), 'dd/Mon'::text) as sdate, \n";
	$sql .=	"	to_char(max(a.end_stamp), 'dd/Mon'::text) as edate, \n";
	$sql .=	"	sum(case when length(caller_destination) <= 10 then a.billmsec end) as sec, \n";
	$sql .=	"	sum(case when length(caller_destination) > 10 then a.billmsec end) as international \n";
	if (is_array($_SESSION['cdr']['field'])) {
		foreach ($_SESSION['cdr']['field'] as $field) {
			$array = explode(",", $field);
			$field_name = end($array);
			$sql .= $field_name.", \n";
		}
	}
	if (is_array($_SESSION['cdr']['export'])) {
		foreach ($_SESSION['cdr']['export'] as $field) {
			$sql .= $field.", \n";
		}
	}
	$sql .= "from v_xml_cdr a \n";
	$sql .= "join v_domains b on a.domain_uuid = b.domain_uuid \n";
	if ($_REQUEST['show'] == "all" && permission_exists('reports_all')) {
		if (is_array($domains)) {
		$end = end(array_keys($domains));
		$sql .= "and a.domain_uuid IN ( \n";
			foreach ($domains as $key => $value) {
				$sql .= " '".$value."' \n";
				if ($key == $end) {
					$sql .= ") \n";
				  }
				else{
					$sql .= ", \n";
				}
			}
		}
		else {
			$sql .= "and a.domain_uuid NOT IN ('4534fa8d-2b78-4bfb-b5b9-e36168430a89','f1299fc3-c4b0-4638-a9a8-4df131084338','39a8546f-0803-4dda-91b1-39ef3e10b4d0','1a1226ab-3276-402a-a0d7-26934e153141','acd7b1e9-b30c-4eb3-b533-4843cfefb3b1') \n";
//			$sql .= "and NOT EXISTS (SELECT FROM v_domains WHERE a.domain_uuid IN ('4534fa8d-2b78-4bfb-b5b9-e36168430a89','f1299fc3-c4b0-4638-a9a8-4df131084338','39a8546f-0803-4dda-91b1-39ef3e10b4d0','1a1226ab-3276-402a-a0d7-26934e153141','acd7b1e9-b30c-4eb3-b533-4843cfefb3b1')) \n";
		}
	}
	else {
		$sql .= "where a.domain_uuid = :domain_uuid \n";
		$parameters['domain_uuid'] = $domain_uuid;
	}
	if (strlen($start_stamp_begin) > 0 && strlen($start_stamp_end) > 0) {
		$sql .= "and a.start_stamp between :start_stamp_begin::timestamptz and :start_stamp_end::timestamptz ";
		$parameters['start_stamp_begin'] = $start_stamp_begin.' 00:00:00.000 '.$time_zone;
		$parameters['start_stamp_end'] = $start_stamp_end.' 00:00:00.000 '.$time_zone;
	}
	else {
		if (strlen($start_stamp_begin) > 0) {
			$sql .= "and a.start_stamp >= :start_stamp_begin ";
			$parameters['start_stamp_begin'] = $start_stamp_begin.' 00:00:00.000 '.$time_zone;
		}
		if (strlen($start_stamp_end) > 0) {
			$sql .= "and a.start_stamp <= :start_stamp_end ";
			$parameters['start_stamp_end'] = $start_stamp_end.' 00:00:00.000 '.$time_zone;
		}
	}
	if (is_array($_SESSION['cdr']['field'])) {
		foreach ($_SESSION['cdr']['field'] as $field) {
			$array = explode(",", $field);
			$field_name = end($array);
			if (isset($$field_name)) {
				$$field_name = $_REQUEST[$field_name];
				if (strlen($$field_name) > 0) {
					if (strstr($$field_name, '%')) {
						$sql .= "and $field_name like :".$field_name." \n";
						$parameters[$field_name] = $$field_name;
					}
					else {
						$sql .= "and $field_name = :".$field_name." \n";
						$parameters[$field_name] = $$field_name;
					}
				}
			}
		}
	}

	$sql .= "and direction='outbound' and (answer_stamp is not null and bridge_uuid is not null) \n";

	$sql .= "group by a.domain_name, b.domain_description, a.domain_uuid, (date_trunc('month'::text, a.start_stamp)) \n";
	//end where
	if (strlen($order_by) > 0) {
		$sql .= order_by($order_by, $order);
	}
	
	if ($_REQUEST['export_format'] !== "csv" && $_REQUEST['export_format'] !== "pdf") {
		if ($rows_per_page == 0) {
			$sql .= " limit :limit offset 0 \n";
			$parameters['limit'] = $_SESSION['cdr']['limit']['numeric'];
		}
		else {
			$sql .= " limit :limit offset :offset \n";
			$parameters['limit'] = $rows_per_page;
			$parameters['offset'] = $offset;
		}
	}
	$sql = str_replace("  ", " ", $sql);
	$database = new database;
	if ($archive_request && $_SESSION['cdr']['archive_database']['boolean'] == 'true') {
		$database->driver = $_SESSION['cdr']['archive_database_driver']['text'];
		$database->host = $_SESSION['cdr']['archive_database_host']['text'];
		$database->type = $_SESSION['cdr']['archive_database_type']['text'];
		$database->port = $_SESSION['cdr']['archive_database_port']['text'];
		$database->db_name = $_SESSION['cdr']['archive_database_name']['text'];
		$database->username = $_SESSION['cdr']['archive_database_username']['text'];
		$database->password = $_SESSION['cdr']['archive_database_password']['text'];
	}
	$result = $database->select($sql, $parameters, 'all');
	$result_count = is_array($result) ? sizeof($result) : 0;
	unset($database, $sql, $parameters);

//return the paging
	if ($_REQUEST['export_format'] !== "csv" && $_REQUEST['export_format'] !== "pdf") {
		list($paging_controls_mini, $rows_per_page) = paging($num_rows, $param, $rows_per_page, true, $result_count); //top
		list($paging_controls, $rows_per_page) = paging($num_rows, $param, $rows_per_page, false, $result_count); //bottom
	}

?>
