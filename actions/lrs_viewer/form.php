<?php


$container_guid = get_input('container_guid');

// elgg_log("Actor: ".$actor,"ERROR");
// elgg_log("Accion: ".$action,"ERROR");
// elgg_log("Subtype: ".$subtype,"ERROR");
// elgg_log("Ini_date: ".$ini_date,"ERROR");
// elgg_log("End_date: ".$end_date,"ERROR");
// elgg_log("Choose_info: ".$choose_info,"ERROR");
// elgg_log("Choose_graf: ".$choose_graf,"ERROR");
$action_url = '';
$action = get_input('action');
$first = True;
foreach ($action as $key => $value) {
	if(gettype($value) == 'array'){
		foreach ($value as $key2 => $value2) {
			if ($first){
				$action_url .= $value2;
				$first = False;
			}
			else
				$action_url .= ',' . $value2;
		}
	}
}


$subtype_url = '';
$subtype = get_input('subtype');
$first = True;
foreach ($subtype as $key => $value) {
	if(gettype($value) == 'array'){
		foreach ($value as $key2 => $value2) {
			if ($first){
				$subtype_url .= $value2;
				$first = False;
			}
			else
				$subtype_url .= ',' . $value2;
		}
	}

}


$url = '/lrs_viewer/lrsbrowser?container_guid=' . $container_guid;
$array_aux = array(
					"actor" => get_input('actor'),
					"action" => $action_url,
					"subtype" => $subtype_url,
					"ini_date" => get_input('ini_date'),
					"end_date" => get_input('end_date'),
					"choose_info" => get_input('choose_info'),
					"choose_graf" => get_input('choose_graf'),
					"offset" => get_input('offset'),
					"limit" => get_input('limit'),
					);

foreach ($array_aux as $key => $value) {
	if(!empty($value) && $value!='none')
		$url .= '&' . $key . '=' . $value;
}


forward($url);
