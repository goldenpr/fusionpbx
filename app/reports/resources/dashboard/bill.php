<?php

//set the include path
	$conf = glob("{/usr/local/etc,/etc}/fusionpbx/config.conf", GLOB_BRACE);
	set_include_path(parse_ini_file($conf[0])['document.root']);

//includes files
	require_once "resources/require.php";

//check permisions
	require_once "resources/check_auth.php";
	if (permission_exists('reports_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get($_SESSION['domain']['language']['code'], 'core/user_settings');

//bills calls
	echo "<div class='hud_box'>\n";

//if also viewing system status, show more recent calls (more room avaialble)
	$bills_limit = (is_array($selected_blocks) && in_array('counts', $selected_blocks)) ? 10 : 5;
	
	$sql =	"select \n";
	$sql .=	"	domain_name, \n";
	$sql .=	"	to_char( min ( start_stamp ), 'Month' ) as month, \n";
	$sql .=	"	to_char( min ( start_stamp ), 'dd/Mon' ) as sdate, \n";
	$sql .=	"	to_char( max ( end_stamp ), 'dd/Mon' ) as edate, \n";
	$sql .=	"	round(sum(billmsec/60000),1) as sec \n";
	$sql .=	"from \n";
	$sql .=	"	v_xml_cdr \n";
	$sql .=	"where \n";
	$sql .=	"	domain_uuid = :domain_uuid \n";
	$sql .=	"	and (direction = 'outbound') \n";
	$sql .= "group by domain_name, date_trunc( 'month', start_stamp ) \n";
	$sql .= "order by domain_name asc \n";
	
	$parameters['domain_uuid'] = $_SESSION['domain_uuid'];

	$database = new database;
	$result = $database->select($sql, $parameters, 'all');

	$num_rows = is_array($result) ? sizeof($result) : 0;

	$c = 0;
	$row_style["0"] = "row_style0";
	$row_style["1"] = "row_style1";
	$local['direction'] = "outbound";
	
	//get the domain active and inactive counts
	$sql = "select ";
	$sql .= "(select count(distinct date_trunc( 'month', start_stamp )) from v_xml_cdr where domain_uuid = :domain_uuid1 and date_trunc( 'month', CURRENT_DATE ) = date_trunc( 'month', end_stamp ) and (direction = 'outbound') ) as unpaid, ";
	$sql .= "(select count(distinct date_trunc( 'month', start_stamp )) from v_xml_cdr where domain_uuid = :domain_uuid1 and date_trunc( 'month', CURRENT_DATE ) > date_trunc( 'month', end_stamp ) and (direction = 'outbound')) as paid; ";
	$parameters1['domain_uuid1'] = $_SESSION['domain_uuid'];
	$database = new database;
	$row = $database->select($sql, $parameters1, 'row');
	$unpaid_bill = $row['unpaid'];
	$paid_bill = $row['paid'];
	$domain_total = $unpaid_bill + $paid_bill;
	unset($sql, $row);

//add doughnut chart
	?>
	<div style='display: flex; flex-wrap: wrap; justify-content: center; padding-bottom: 20px;'>
		<div style='width: 250px; height: 175px;'><canvas id='bills_chart'></canvas></div>
	</div>
	<script>
			var bills_chart_context = document.getElementById('bills_chart').getContext('2d');

			const bills_chart_data = {
				labels: ['Unpaid: <?php echo $unpaid_bill; ?>','Paid: <?php echo $paid_bill; ?>'],
				datasets: [{
					data: ['<?php echo $unpaid_bill; ?>','<?php echo $unpaid_bill; ?>', 0.00001],
					backgroundColor: [
						'<?php echo $_SESSION['dashboard']['bills_chart_main_background_color']['text']; ?>', 
						'<?php echo $_SESSION['dashboard']['bills_chart_sub_background_color']['text']; ?>'
					],
					borderColor: '<?php echo $_SESSION['dashboard']['bills_chart_border_color']['text']; ?>',
					borderWidth: '<?php echo $_SESSION['dashboard']['bills_chart_border_Width']['text']; ?>',
					cutout: chart_cutout
				}]
			};

			const bills_chart_config = {
				type: 'doughnut',
				data: bills_chart_data,
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						chart_counter: {
							chart_text: '<?php echo $num_rows; ?>'
						},
						legend: {
						position: 'right',
							reverse: true,
							labels: {
								usePointStyle: true,
								pointStyle: 'rect'
							}
						},
						title: {
							display: true,
							text: '<?php echo $text['label-bills']; ?>'
						}
					}
				},
				plugins: [chart_counter],
			};

			const bills_chart = new Chart(
				bills_chart_context,
				bills_chart_config
			);
		</script>
	<?php

	echo "<div class='hud_details hud_box' id='bills_details'>";
	echo "<table class='tr_hover' width='100%' cellpadding='0' cellspacing='0' border='0'>\n";
	echo "<tr>\n";
	if ($num_rows > 0) {
		echo "<th class='hud_heading'>&nbsp;</th>\n";
	}
	echo "<th class='hud_heading' width='100%' style='text-align: center; padding-left: 0; padding-right: 0;'>Client name</th>\n";
	echo "<th class='hud_heading' width='50%' style='text-align: center; padding-left: 30px; padding-right: 30px;'>".$text['label-month']."</th>\n";
	echo "<th class='hud_heading' width='50%' style='text-align: center; padding-left: 10px; padding-right: 10px;'>".$text['label-sdate']."</th>\n";
	echo "<th class='hud_heading' width='50%' style='text-align: center; padding-left: 10px; padding-right: 10px;'>".$text['label-edate']."</th>\n";
	echo "<th class='hud_heading' style='text-align: center;'>".$text['label-minutes']."</th>\n";
	echo "</tr>\n";

	if ($num_rows > 0) {
		$theme_image_path = $_SERVER["DOCUMENT_ROOT"]."/themes/".$_SESSION['domain']['template']['name']."/images/";
		$theme_cdr_images_exist = (
			file_exists($theme_image_path."icon_cdr_inbound_voicemail.png") &&
			file_exists($theme_image_path."icon_cdr_inbound_cancelled.png") &&
			file_exists($theme_image_path."icon_cdr_local_voicemail.png") &&
			file_exists($theme_image_path."icon_cdr_local_cancelled.png")
			) ? true : false;

		foreach($result as $index => $row) {
			if ($index + 1 > $bills_limit) { break; } //only show limit
			$tmp_year = date("Y", strtotime($row['start_stamp']));
			$tmp_month = date("M", strtotime($row['start_stamp']));
			$tmp_day = date("d", strtotime($row['start_stamp']));
			$tmp_minutes= ($row['billmsec']/6000) ;
			$tmp_start_epoch = ($_SESSION['domain']['time_format']['text'] == '12h') ? date("n/j g:ia", $row['start_epoch']) : date("n/j H:i", $row['start_epoch']);
			//set click-to-call variables
			if (permission_exists('click_to_call_call')) {
				$tr_link = "onclick=\"send_cmd('".PROJECT_PATH."/app/click_to_call/click_to_call.php".
					"?src_cid_name=".urlencode($row['caller_id_name']).
					"&src_cid_number=".urlencode($row['caller_id_number']).
					"&dest_cid_name=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_name']).
					"&dest_cid_number=".urlencode($_SESSION['user']['extension'][0]['outbound_caller_id_number']).
					"&src=".urlencode($_SESSION['user']['extension'][0]['user']).
					"&dest=".urlencode($row['caller_id_number']).
					"&rec=".(isset($_SESSION['click_to_call']['record']['boolean'])?$_SESSION['click_to_call']['record']['boolean']:"false").
					"&ringback=".(isset($_SESSION['click_to_call']['ringback']['text'])?$_SESSION['click_to_call']['ringback']['text']:"us-ring").
					"&auto_answer=".(isset($_SESSION['click_to_call']['auto_answer']['boolean'])?$_SESSION['click_to_call']['auto_answer']['boolean']:"true").
					"');\" ".
					"style='cursor: pointer;'";
			}
			echo "<tr ".$tr_link." text-align='center'>\n";
			echo "<td valign='middle' class='".$row_style[$c]."' style='cursor: help; padding: 0 0 0 6px;'>\n";
			if ($theme_cdr_images_exist) {
				$call_result = ($row['answer_stamp'] != '') ? 'voicemail' : 'cancelled';
				if (isset($local['direction'])) {
					echo "	<img src='".PROJECT_PATH."/themes/".$_SESSION['domain']['template']['name']."/images/icon_cdr_".$local['direction']."_".$call_result.".png' width='16' style='border: none;' title='".$text['label-'.$local['direction']].": ".$text['label-'.$call_result]."'>\n";
				}
			}
			echo "</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap' style='text-align: center;'><a href='javascript:void(0);' ".(($row['domain_name'] != '') ? "title=\"".$row['domain_name']."\"" : null).">".((is_numeric($row['domain_name'])) ? format_phone($row['domain_name']) : $row['domain_name'])."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap' style='text-align: center;'><a href='javascript:void(0);' ".(($row['month'] != '') ? "title=\"".$row['month']."\"" : null).">".((is_numeric($row['month'])) ? format_phone($row['month']) : $row['month'])."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap' style='text-align: center;'><a href='javascript:void(0);' ".(($tmp_day_month  != '') ? "title=\"".$row['sdate']."\"" : null).">".((is_numeric($row['sdate'])) ? format_phone($row['sdate']) : $row['sdate'])."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap' style='text-align: center;'><a href='javascript:void(0);' ".(($row['s'] != '') ? "title=\"".$row['edate']."\"" : null).">".((is_numeric($row['edate'])) ? format_phone($row['edate']) : $row['edate'])."</td>\n";
			echo "<td valign='top' class='".$row_style[$c]." hud_text' nowrap='nowrap' style='text-align: center;'><a href='javascript:void(0);' ".(($row['sec'] != '') ? "title=\"".$row['sec']."\"" : null).">".((is_numeric($row['sec'])) ? format_phone($row['sec']) : $row['sec'])."</td>\n";
			echo "</tr>\n";
			$c = ($c) ? 0 : 1;
		}
	}
	unset($sql, $parameters, $result, $num_rows, $index, $row);

	echo "</table>\n";
	echo "<span style='display: block; margin: 6px 0 7px 0;'><a href='".PROJECT_PATH."/app/reports/bills_report.php?direction=outbound'>".$text['label-view_all']."</a></span>\n";
	echo "</div>";
	$n++;

	echo "<span class='hud_expander' onclick=\"$('#bills_details').slideToggle('fast');\"><span class='fas fa-ellipsis-h'></span></span>";
	echo "</div>\n";

?>
