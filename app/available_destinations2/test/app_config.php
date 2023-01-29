<?php

	//application details
		$apps[$x]['name'] = 'Bridges';
		$apps[$x]['uuid'] = 'a6a7c4c5-340a-43ce-bcbc-2ed9bab8659d';
		$apps[$x]['category'] = '';
		$apps[$x]['subcategory'] = '';
		$apps[$x]['version'] = '';
		$apps[$x]['license'] = 'Mozilla Public License 1.1';
		$apps[$x]['url'] = 'http://www.fusionpbx.com';
		$apps[$x]['description']['en-us'] = '';
		$apps[$x]['description']['en-gb'] = '';

	//permission details
		$y = 0;
		$apps[$x]['permissions'][$y]['name'] = 'available_destinations_view';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'available_destinations_add';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'available_destinations_edit';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'available_destinations_delete';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		//$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'available_destinations_all';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$y++;
		$apps[$x]['permissions'][$y]['name'] = 'available_destinations_destinations';
		$apps[$x]['permissions'][$y]['groups'][] = 'superadmin';
		$apps[$x]['permissions'][$y]['groups'][] = 'admin';
		$y++;

	//destination details
		$y = 0;
		$apps[$x]['destinations'][$y]['type'] = "sql";
		$apps[$x]['destinations'][$y]['label'] = "available_destinations";
		$apps[$x]['destinations'][$y]['name'] = "available_destinations";
		//$apps[$x]['destinations'][$y]['sql'] = "select destination_trunk_name, destination_number, destination_description from v_available_destinations ";
		$apps[$x]['destinations'][$y]['where'] = "where domain_uuid = '\${domain_uuid}' and available_destinations_enabled = 'true'";
		$apps[$x]['destinations'][$y]['order_by'] = "destination_trunk_name asc";
		$apps[$x]['destinations'][$y]['field']['available_destinations_uuid'] = "available_destinations_uuid";
		$apps[$x]['destinations'][$y]['field']['name'] = "destination_trunk_name";
		$apps[$x]['destinations'][$y]['field']['description'] = "destination_description";
		$apps[$x]['destinations'][$y]['field']['destination'] = "destination_number";
		$apps[$x]['destinations'][$y]['select_value']['user_contact'] = "\${destination}";
		$apps[$x]['destinations'][$y]['select_value']['dialplan'] = "bridge:\${destination}";
		$apps[$x]['destinations'][$y]['select_value']['ivr'] = "menu-exec-app:bridge \${destination}";
		$apps[$x]['destinations'][$y]['select_label'] = "\${name} \${description} ";
		$y++;

	//Bridges
		$y = 0;
		$apps[$x]['db'][$y]['table']['name'] = 'v_available_destinations';
		$apps[$x]['db'][$y]['table']['parent'] = '';
		$z = 0;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'available_destinations_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'primary';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'domain_uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
		$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
		$apps[$x]['db'][$y]['fields'][$z]['key']['type'] = 'foreign';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['table'] = 'v_domains';
		$apps[$x]['db'][$y]['fields'][$z]['key']['reference']['field'] = 'domain_uuid';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'destination_trunk_name';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the name.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'destination_number';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the destination.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'destination_enabled';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Select to enable or disable.';
		$z++;
		$apps[$x]['db'][$y]['fields'][$z]['name'] = 'destination_description';
		$apps[$x]['db'][$y]['fields'][$z]['type'] = 'text';
		$apps[$x]['db'][$y]['fields'][$z]['search'] = 'true';
		$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'Enter the description.';
		$z++;

?>
