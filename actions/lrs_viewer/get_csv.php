<?php
	
	$container_guid = get_input('container_guid');

	$group = get_entity($container_guid);
	$groupname = $group->name;

	$dbprefix = elgg_get_config('dbprefix');

	$query1 = "SELECT * FROM {$dbprefix}events_log WHERE 1 AND container_guid=$container_guid";
	$query2 = "SELECT * FROM {$dbprefix}lrs_import WHERE 1 AND group_id=$container_guid";

	$aux1 = get_data($query1);
	$aux2 = get_data($query2);

	$result1 = $result2 = array();
	$i = 0;
	foreach ($aux1 as $value) {
		$resource = get_entity($value->resource_guid);
		if (empty($resource->title)) {
			$title = elgg_echo('events_collector:resource:not_available');
		}else
			$title = $resource->title;
		$actor = get_entity($value->actor_guid);
		if ($actor instanceof ElggUser){
			$name = $actor->getDisplayName();
		}else
			$name = elgg_echo('lrs_viewer:user_deleted');

		$format = 'Y-m-d h:i:s';
		$fecha = date($format,$value->time_created);

		$result1[$i] = array(
			'actor' => $name,
			'resource_type' => elgg_echo('lrs_viewer:'.strtolower($value->resource_type)),
			'action_type' => elgg_echo('lrs_viewer:'.strtolower($value->action_type)),
			'time' => $fecha,
			'resource' => $title,
			);
		$i++;
	}

	$i = 0;
	foreach ($aux2 as $value) {
		$actor = get_entity($value->actor_guid);
		if ($actor instanceof ElggUser){
			$name = $actor->getDisplayName();
		}else
			$name = elgg_echo('lrs_viewer:user_deleted');

		$result2[$i] = array(
			'actor' => $name,
			'resource_type' => elgg_echo('lrs_viewer:'.strtolower($value->resource_type)),
			'action_type' => elgg_echo('lrs_viewer:'.strtolower($value->action_type)),
			'time' => $value->time_created,
			'resource' => $value->object_name,
			);
		$i++;
	}

	$result_all = array_merge($result1,$result2);

	usort($result_all, function($a, $b) {
    return strtotime($a['time']) - strtotime($b['time']);
});

	$salida_csv = elgg_echo("lrs_viewer:date") . "," . elgg_echo("lrs_viewer:actor") . ", " . elgg_echo("lrs_viewer:resource_type") . "," . elgg_echo("lrs_viewer:action") . "," . elgg_echo("lrs_viewer:resource_name") . ",\n";
	foreach ($result_all as $value) {
		$salida_csv .= $value['time']. "," . $value['actor'] . "," . $value['resource_type'] . "," .$value['action_type'] . "," . $value['resource'] . ",\n";
	}

	header("Content-type: application/vnd.ms-excel");
    header("Content-disposition: csv" . date("Y-m-d") . ".csv");
    header("Content-disposition: filename=" . $groupname . ".csv");
    print $salida_csv;
    exit;

?>