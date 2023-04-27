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

//check permissions
	if (permission_exists('reports_export_pdf')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();

//additional includes
	$rows_per_page = ($_SESSION['domain']['paging']['numeric'] != '') ? $_SESSION['domain']['paging']['numeric'] : 50;
	$archive_request = $_POST['archive_request'] == 'true' ? true : false;
	require_once "bills_report_inc.php";

//get the format
	$export_format = $_REQUEST['export_format'];

//export as a PDF
	if (permission_exists('reports_export_pdf') && $export_format == 'pdf') {

		//load pdf libraries
		require_once "resources/tcpdf/tcpdf.php";
		require_once "resources/fpdi/fpdi.php";

		//determine page size
		switch ($_SESSION['fax']['page_size']['text']) {
			case 'a4':
				$page_width = 11.7; //in
				$page_height = 8.5; //in
				break;
			case 'legal':
				$page_width = 14; //in
				$page_height = 8.5; //in
				break;
			case 'letter':
			default	:
				$page_width = 14.8; //in
				$page_height = 8.5; //in
		}

		// initialize pdf
		$pdf = new FPDI('L', 'in');
		$pdf->SetAutoPageBreak(false);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetMargins(0.7, 0.7, 0.7, true);

		//set default font
		//$pdf->SetFont('helvetica', '', 12);
		$pdf->SetFont('arial', '', 11);
		//add new page
		$pdf->AddPage('L', array($page_width, $page_height));

		//set the number of columns
		$columns = 10;

		//write the table column headers
		$data_start = '<style>
							table#some_table_id tr:nth-child(even) { background-color:#dddddd; }
						</style>';
		$data_start .= '<img src="img/logo.png" style="width:150px;height:70px;">';
		$data_start .= '<h1 align="center" >Bill report</h1>';
		$data_start .= '<br>';
		$data_start .= '<br>';
		$data_start .= '<table id="some_table_id" cellpadding="5px" cellspacing="0" border="0" width="100%" >';
		$data_end = '</table>';
		$data_head = '<tr>';
		$data_head .= '<td width="15%"><b>'.$text['label-domain'].'</b></td>';
		$data_head .= '<td width="40%"><b>'.$text['label-name'].'</b></td>';
		$data_head .= '<td width="7%"><b>'.$text['label-month'].'</b></td>';
		$data_head .= '<td width="7%"><b>'.$text['label-sdate'].'</b></td>';
		$data_head .= '<td width="7%"><b>'.$text['label-edate'].'</b></td>';
		$data_head .= '<td width="7%" align="center"><b>'.$text['label-calls'].'</b></td>';
		$data_head .= '<td width="7%" align="center"><b>'.$text['label-in-minutes'].'</b></td>';
		$data_head .= '<td width="10%" align="right"><b>'.$text['label-minutes'].'</b></td>';
		if (is_array($_SESSION['cdr']['field'])) {
			foreach ($_SESSION['cdr']['field'] as $field) {
				$array = explode(",", $field);
				$field_name = end($array);
				$field_label = ucwords(str_replace("_", " ", $field_name));
				$field_label = str_replace("Sip", "SIP", $field_label);
				if ($field_name != "destination_number") {
					$data_head .= '<td width="100%" align="left"><b>'.$field_label.'</b></td>';
				}
				$columns = $columns + 1;
			}
		}
		$data_head .= '</tr>';
		$data_head .= '<tr><td colspan="'.$columns.'"><hr></td></tr>';

		//initialize total variables
		$total['duration'] = 0;
		$total['billmsec'] = 0;
		$total['pdd_ms'] = 0;
		$total['rtp_audio_in_mos'] = 0;
		$total['sec'] = 0;
		$bcolor['bcolor-change']= "background-color:#E8E8E8;";

		//write the row cells
		$z = 0; // total counter
		$p = 0; // per page counter
		if (sizeof($result) > 0 ) {
			foreach ($result as $cdr_num => $fields) {
				$input = $fields['sec'];
		                $uSec = $input % 1000;
		                $input = floor($input / 1000);
		                $seconds = $input % 60;
		                $input = floor($input / 60);
		                $minutes = $input % 60;
	              		$input = floor($input / 60);
		                $hour = $input ;

				$ininput = $fields['international'];
                                $uSec = $ininput % 1000;
                                $ininput = floor($ininput / 1000);
                                $inseconds = $ininput % 60;
                                $ininput = floor($ininput / 60);
                                $inminutes = $ininput % 60;
                                $ininput = floor($ininput / 60);
                                $inhour = $ininput ;

				$data_body[$p] .= '<tr style="'.$bcolor['bcolor-change'].'">';
				$data_body[$p] .= '<td>'.$fields['domain_name'].'</td>';
				$data_body[$p] .= '<td>'.$fields['domain_description'].'</td>';
				$data_body[$p] .= '<td>'.$fields['month'].'</td>';
				$data_body[$p] .= '<td>'.format_phone($fields['sdate']).'</td>';
				$data_body[$p] .= '<td>'.$fields['edate'].'</td>';
				$total['tcalls'] += ($fields['tcalls'] > 0) ? $fields['tcalls'] : 0;
				$total['international'] += ($fields['international'] > 0) ? $fields['international'] : 0;
				$total['sec'] += ($fields['sec'] > 0) ? $fields['sec'] : 0;
				$data_body[$p] .= '<td align="center">'.(($fields['tcalls'] >= 0) ? $fields['tcalls'] : null).'</td>';
				$data_body[$p] .= '<td align="center">'.(($fields['international'] != null && $fields['international'] != 0) ? round($fields['international']/60000,0).' m' : null).'</td>';
//				$data_body[$p] .= '<td align="center">'.(($fields['international'] != null && $fields['international'] != 0) ? sprintf('%02d:%02d:%02d', $inhour, $inminutes, $inseconds) : null).'</td>';
				$data_body[$p] .= '<td align="right">'.(($fields['sec'] >= 0) ? round($fields['sec'] / 60000 , 0). ' m'  : null).'</td>';
//				$data_body[$p] .= '<td align="right">'.(($fields['sec'] >= 0) ? sprintf('%02d:%02d:%02d', $hour, $minutes, $seconds) : null).'</td>';
				$data_body[$p] .= '</tr>';

				$z++;
				$p++;

				if ($p == 14) {
					//output data
					$data_body_chunk = $data_start.$data_head;
					foreach ($data_body as $data_body_row) {
						$data_body_chunk .= $data_body_row;
					}
					$data_body_chunk .= $data_end;
					$pdf->writeHTML($data_body_chunk, true, false, false, false, '');
					unset($data_body_chunk);
					unset($data_body);
					$p = 0;

					//add new page
					$pdf->AddPage('L', array($page_width, $page_height));
				}

				if ($z % 2 == 0) {
					$bcolor['bcolor-change']= "background-color:#E8E8E8;";
				}
				else{
					$bcolor['bcolor-change']= "";
				}
			}

		}

		//write divider
		$data_footer = '<tr><td colspan="'.$columns.'"></td></tr>';

		//write totals
		$input = $total['sec'];
		$uSec = $input % 1000;
		$input = floor($input / 1000);
		$seconds = $input % 60;
		$input = floor($input / 60);
		$minutes = $input % 60;
		$input = floor($input / 60);
		$hour = $input ;

		$ininput = $total['international'];
                $iuSec = $ininput % 1000;
                $ininput = floor($ininput / 1000);
                $inseconds = $ininput % 60;
                $ininput = floor($ininput / 60);
                $inminutes = $ininput % 60;
                $ininput = floor($ininput / 60);
                $inhour = $ininput ;

		$ainput = $total['sec']/$z;
                $auSec = $ainput % 1000;
                $ainput = floor($ainput / 1000);
                $aseconds = $ainput % 60;
                $ainput = floor($ainput / 60);
                $aminutes = $ainput % 60;
                $ainput = floor($ainput / 60);
                $ahour = $ainput ;

		$aininput = $total['international']/$z;
                $ainuSec = $aininput % 1000;
                $aininput = floor($aininput / 1000);
                $ainseconds = $aininput % 60;
                $aininput = floor($aininput / 60);
                $ainminutes = $aininput % 60;
              	$aininput = floor($aininput / 60);
                $ainhour = $aininput ;


		$data_footer .= '<tr>';
		$data_footer .= '<td><b>'.$text['label-total'].'</b></td>';
		$data_footer .= '<td>'.$z.'</td>';
		$data_footer .= '<td colspan="3"></td>';
		$data_footer .= '<td align="center"><b>'.number_format(round($total['tcalls'], 0), 0).'</b></td>';
		$data_footer .= '<td align="center"><b>'.number_format(round($total['international']/60000, 1), 0).' m</b></td>';
		$data_footer .= '<td align="right"><b>'.number_format(round($total['sec']/60000, 1), 0).' m</b></td>';
//		$data_footer .= '<td align="center"><b>'.sprintf('%02d:%02d:%02d', $inhour, $inminutes, $inseconds).'</b></td>';
//                $data_footer .= '<td align="right"><b>'.sprintf('%02d:%02d:%02d', $hour, $minutes, $seconds).'</b></td>';
		$data_footer .= '<td colspan="2"></td>';
		$data_footer .= '</tr>';

		//write divider
		$data_footer .= '<tr><td colspan="'.$columns.'"><hr></td></tr>';

		//write averages
		$data_footer .= '<tr>';
		$data_footer .= '<td><b>'.$text['label-average'].'</b></td>';
		$data_footer .= '<td colspan="4"></td>';
		$data_footer .= '<td align="center"><b>'.round(($total['tcalls'] / $z), 1).'</b></td>';
		$data_footer .= '<td align="center"><b>'.round((round($total['international']/60000,0) / $z), 1).' m</b></td>';
		$data_footer .= '<td align="right"><b>'.round((round($total['sec']/60000,0) / $z), 1).' m</b></td>';
//		$data_footer .= '<td align="center"><b>'.sprintf('%02d:%02d:%02d', $ainhour, $ainminutes, $ainseconds).'</b></td>';
//                $data_footer .= '<td align="right"><b>'.sprintf('%02d:%02d:%02d', $ahour, $aminutes, $aseconds).'</b></td>';
		$data_footer .= '<td></td>';
		$data_footer .= '</tr>';

		//write divider
		$data_footer .= '<tr><td colspan="'.$columns.'"><hr></td></tr>';

		//add last page
		if ($p >= 14) {
			$pdf->AddPage('L', array($page_width, $page_height));
		}
		//output remaining data
		$data_body_chunk = $data_start.$data_head;
		foreach ($data_body as $data_body_row) {
			$data_body_chunk .= $data_body_row;
		}
		$data_body_chunk .= $data_footer.$data_end;
		$pdf->writeHTML($data_body_chunk, true, false, false, false, '');
		unset($data_body_chunk);

		//define file name
		$pdf_filename = "cdr_".$_SESSION['domain_name']."_".date("Ymd_His").".pdf";

		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Description: File Transfer");
		header('Content-Disposition: attachment; filename="'.$pdf_filename.'"');
		header("Content-Type: application/pdf");
		header('Accept-Ranges: bytes');
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // date in the past

		// push pdf download
		$pdf -> Output($pdf_filename, 'D');	// Display [I]nline, Save to [F]ile, [D]ownload

	}

?>
