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
	Portions created by the Initial Developer are Copyright (C) 2018 - 2019
	the Initial Developer. All Rights Reserved.
*/

//includes files
	require_once dirname(__DIR__, 2) . "/resources/require.php";
	require_once "resources/check_auth.php";

//check permissions
	if (!permission_exists('available_destination_add') && !permission_exists('available_destination_edit')) {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//action add or update
	if (!empty($_REQUEST["id"]) && is_uuid($_REQUEST["id"])) {
		$action = "update";
		$available_destination_uuid = $_REQUEST["id"];
		$id = $_REQUEST["id"];
	}
	else {
		$action = "add";
	}

//get http post variables and set them to php variables
	if (is_array($_POST)) {
		if ($action == "update" && is_uuid($_POST["available_destination_uuid"])) {
				$available_destination_uuid = $_POST["available_destination_uuid"];
		}
		$destination_trunk_id = $_POST["destination_trunk_id"];
		$destination_number = $_POST["destination_number"];
		$destination_enabled = $_POST["destination_enabled"];
		$destination_description = $_POST["destination_description"];
		$destination_used = $_POST["destination_used"];
		$db_destination_number = $_POST["db_destination_number"];
	}

//process the user data and save it to the database
	if (count($_POST) > 0 && strlen($_POST["persistformvar"]) == 0) {

		//delete the available destination
			if (permission_exists('available_destination_delete')) {
				if ($_POST['action'] == 'delete' && is_uuid($available_destination_uuid)) {
					//prepare
						$array[0]['checked'] = 'true';
						$array[0]['uuid'] = $available_destination_uuid;
					//delete
						$obj = new available_destinations;
						$obj->delete($array);
					//redirect
						header('Location: available_destinations.php');
						exit;
				}
			}

		//get the uuid from the POST
			if ($action == "update" && is_uuid($_POST["available_destination_uuid"])) {
				$available_destination_uuid = $_POST["available_destination_uuid"];
			}

		//get the destination row values
			if ($action == 'update' && is_uuid($available_destination_uuid)) {
				$sql = "select * from v_available_destinations ";
				$sql .= "where available_destination_uuid = :available_destination_uuid ";
				$parameters['available_destination_uuid'] = $available_destination_uuid;
				$database = new database;
				$row = $database->select($sql, $parameters, 'row');
				unset($sql, $parameters);
			}

		//validate the token
			$token = new token;
			if (!$token->validate($_SERVER['PHP_SELF'])) {
				message::add($text['message-invalid_token'],'negative');
				header('Location: available_destinations.php');
				exit;
			}

		//check for all required data
			$msg = '';
			if (empty($destination_trunk_id)) { $msg .= $text['message-required']." ".$text['label-destination_trunk_id']."<br>\n"; }
			if (empty($destination_number)) { $msg .= $text['message-required']." ".$text['label-destination_number']."<br>\n"; }
			if (empty($destination_enabled)) { $msg .= $text['message-required']." ".$text['label-available_destinations_enabled']."<br>\n"; }
		//check for transfareds
			if ($destination_number != $db_destination_number && $_SESSION['destinations']['unique']['boolean'] == 'true') {
				$sql = "select count(*) from v_available_destinations ";
				$sql .= "where (destination_number = :destination_number)";
				$parameters['destination_number'] = $destination_number;
				$database = new database;
				$num_rows = $database->select($sql, $parameters, 'column');
				if ($num_rows > 0) {
					$msg .= $text['message-transfared']."<br>\n";
				}
				unset($sql, $parameters, $num_rows);
			}
			if (!empty($msg) && empty($_POST["persistformvar"])) {
				require_once "resources/header.php";
				require_once "resources/persist_form_var.php";
				echo "<div align='center'>\n";
				echo "<table><tr><td>\n";
				echo $msg."<br />";
				echo "</td></tr></table>\n";
				persistformvar($_POST);
				echo "</div>\n";
				require_once "resources/footer.php";
				return;
			}
			
		

		//add the available_destination_uuid
			if (empty($available_destination_uuid)) {
				$available_destination_uuid = uuid();
			}

		//prepare the array
			$array['available_destinations'][0]['available_destination_uuid'] = $available_destination_uuid;
			//$array['available_destinations'][0]['domain_uuid'] = null;
			$array['available_destinations'][0]['destination_trunk_id'] = $destination_trunk_id;
			$array['available_destinations'][0]['destination_number'] = $destination_number;
			$array['available_destinations'][0]['destination_used'] = $destination_used;
			$array['available_destinations'][0]['destination_enabled'] = $destination_enabled;
			$array['available_destinations'][0]['destination_description'] = $destination_description;
			

		//save to the data
			$database = new database;
			$database->app_name = 'available destinations';
			$database->app_uuid = '8e0f8acd-f6f8-4456-8558-80bcd68521ce';
			$database->save($array);
			$message = $database->message;

		//clear the destinations session array
			if (isset($_SESSION['destinations']['array'])) {
				unset($_SESSION['destinations']['array']);
			}

		//redirect the user
			if (isset($action)) {
				if ($action == "add") {
					$_SESSION["message"] = $text['message-add'];
				}
				if ($action == "update") {
					$_SESSION["message"] = $text['message-update'];
				}
				header('Location: available_destinations.php');
				return;
			}
	}

//pre-populate the form
	if (!empty($_GET) && is_array($_GET) && (empty($_POST["persistformvar"]) || $_POST["persistformvar"] != "true")) {
		$available_destination_uuid = $_GET["id"];
		$sql = "select * from v_available_destinations ";
		$sql .= "where available_destination_uuid = :available_destination_uuid ";
		$parameters['available_destination_uuid'] = $available_destination_uuid;
		$database = new database;
		$row = $database->select($sql, $parameters ?? null, 'row');
		if (!empty($row)) {
			$destination_trunk_id = $row["destination_trunk_id"];
			$destination_number = $row["destination_number"];
			$destination_enabled = $row["destination_enabled"];
			$destination_description = $row["destination_description"];
			$destination_used = $row["destination_used"];
		}
		unset($sql, $parameters, $row);
	}

//create token
	$object = new token;
	$token = $object->create($_SERVER['PHP_SELF']);

//show the header
	$document['title'] = $text['title-available_destinations'];
	require_once "resources/header.php";

//show the content
	echo "<form name='frm' id='frm' method='post'>\n";

	echo "<div class='action_bar' id='action_bar'>\n";
	echo "	<div class='heading'><b>".$text['title-available_destinations']."</b></div>\n";
	echo "	<div class='actions'>\n";
	echo button::create(['type'=>'button','label'=>$text['button-back'],'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'available_destinations.php']);
	if ($action == 'update' && permission_exists('available_destination_delete')) {
		echo button::create(['type'=>'button','label'=>$text['button-delete'],'icon'=>$_SESSION['theme']['button_icon_delete'],'name'=>'btn_delete','style'=>'margin-right: 15px;','onclick'=>"modal_open('modal-delete','btn_delete');"]);
	}
	echo button::create(['type'=>'submit','label'=>$text['button-save'],'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','name'=>'action','value'=>'save']);
	echo "	</div>\n";
	echo "	<div style='clear: both;'></div>\n";
	echo "</div>\n";

	if ($action == 'update' && permission_exists('available_destination_delete')) {
		echo modal::create(['id'=>'modal-delete','type'=>'delete','actions'=>button::create(['type'=>'submit','label'=>$text['button-continue'],'icon'=>'check','id'=>'btn_delete','style'=>'float: right; margin-left: 15px;','collapse'=>'never','name'=>'action','value'=>'delete','onclick'=>"modal_close();"])]);
	}

	echo "<div class='card'>\n";
	echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";

	echo "<tr>\n";
	echo "<td width='30%' class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_trunk_id']."\n";
	echo "</td>\n";
	echo "<td width='70%' class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_trunk_id' maxlength='255' value='".escape($destination_trunk_id)."'>\n";
	echo "<br />\n";
	echo $text['description-destination_trunk_id']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_number']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	echo "	<input class='formfld' type='text' name='destination_number' maxlength='255' value='".escape($destination_number)."'>\n";
	echo "<br />\n";
	echo $text['description-destination_number']."\n";
	echo "</td>\n";
	echo "</tr>\n";
	if ($action == "update"){
		echo "<tr>\n";
		echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
		echo "	".$text['label-destinations_used']."\n";
		echo "</td>\n";
		echo "<td class='vtable' style='position: relative;' align='left'>\n";
		echo "	<select class='formfld' name='destination_used'>\n";
		if ($destination_used == "not use") {
			echo "		<option value='not use' selected='selected' hidden>".$text['label-not-used']."</option>\n";
		}
		else {
			echo "		<option value='not use' hidden>".$text['label-not-used']."</option>\n";
		}
		if ($destination_used == "used") {
			echo "		<option value='used' selected='selected' hidden>".$text['label-used']."</option>\n";
		}
		else {
			echo "		<option value='used' hidden>".$text['label-used']."</option>\n";
		}
		if ($destination_used == "transfared") {
                echo "          <option value='transfared' selected='selected'>".$text['label-transfared']."</option>\n";
        }
        else {
                echo "          <option value='transfared'>".$text['label-transfared']."</option>\n";
        }
		echo "	</select>\n";
		echo "<br />\n";
		echo $text['description-available_destinations_used']."\n";
		echo "</td>\n";
		echo "</tr>\n";
	}
	else
	{
		echo "<input type='hidden' name='destination_used' value='not use'>\n";
	}
	echo "<tr>\n";
	echo "<td class='vncellreq' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-available_destinations_enabled']."\n";
	echo "</td>\n";
	echo "<td class='vtable' style='position: relative;' align='left'>\n";
	if (substr($_SESSION['theme']['input_toggle_style']['text'], 0, 6) == 'switch') {
		echo "	<label class='switch'>\n";
		echo "		<input type='checkbox' id='destination_enabled' name='destination_enabled' value='true' ".(!empty($destination_enabled) && $destination_enabled == 'true' ? "checked='checked'" : null).">\n";
		echo "		<span class='slider'></span>\n";
		echo "	</label>\n";
	}
	else {
		echo "	<select class='formfld' id='destination_enabled' name='destination_enabled'>\n";
		echo "		<option value='true' ".($destination_enabled == 'true' ? "selected='selected'" : null).">".$text['option-true']."</option>\n";
		echo "		<option value='false' ".($destination_enabled == 'false' ? "selected='selected'" : null).">".$text['option-false']."</option>\n";
		echo "	</select>\n";
	}
	echo "<br />\n";
	echo $text['description-available_destinations_enabled']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "<tr>\n";
	echo "<td class='vncell' valign='top' align='left' nowrap='nowrap'>\n";
	echo "	".$text['label-destination_description']."\n";
	echo "</td>\n";
	echo "<td class='vtable' align='left'>\n";
	echo "	<textarea style='height: 200px;' class='formfld' name='destination_description' height='150px' rows='10' cols='100' >".escape($destination_description ?? '')."</textarea>\n";
	echo "<br />\n";
	echo $text['description-destination_description']."\n";
	echo "</td>\n";
	echo "</tr>\n";

	echo "</table>\n";
	echo "</div>\n";
	echo "<br /><br />\n";

	if ($action == "update") {
		echo "<input type='hidden' name='db_destination_number' value='".escape($destination_number)."'>\n";
		echo "<input type='hidden' name='available_destination_uuid' value='".escape($available_destination_uuid)."'>\n";
	}
	echo "<input type='hidden' name='".$token['name']."' value='".$token['hash']."'>\n";

	echo "</form>";

//include the footer
	require_once "resources/footer.php";

?>
