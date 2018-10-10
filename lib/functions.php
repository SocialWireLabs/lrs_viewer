<?php

$query1;
$query2;
$result1;
$result2;
$data1;
$data2;
$size_int;
$size_ext;
$values;
$names;
$image;

/**
 * Obtiene el numero de interacciones en un grupo en los ultimos 10 dias.
 * 
 * En esta función extrae de las bases de datos events_log y lrs_import todos los registros
 * desde hace 10 dias y devuelve el numero de registros por dia. 
 * 
 * @param integer $container_guid Es el id único del grupo que se desea consultar  
 * @author Adolfo del Sel Llano y Victor Corchero Morais
 * @version Elgg 1.10
 * @package lrs_viewer
 * @subpackage lib
 * @return array $actividad numero de registros de cada uno de los ultimos 10 dias
 */
function obtain_10days_activity($container_guid){
	$day = 86400;
	$ten_days=$day*10;
	$time=time();
	$time_str = date("Y-m-d", $time);
	$time = strtotime($time_str . ' 00:00:00 UTC+1');
	$fecha = $time-$ten_days;

	$fecha2 = date_create();
	$fecha2->setTime(0,0);
	date_add($fecha2, date_interval_create_from_date_string('-10 day'));
	$fecha2 = date_format($fecha2, 'Ymd');

	$dbprefix = elgg_get_config('dbprefix');
	$query = "SELECT date, COUNT(date) AS count FROM( 
	SELECT date_format(FROM_UNIXTIME(time_created),'%e/%c/%Y') AS date FROM {$dbprefix}events_log WHERE 1 AND container_guid=$container_guid AND time_created>$fecha 
	UNION ALL 
	SELECT date_format(time_created,'%e/%c/%Y') AS date FROM {$dbprefix}lrs_import WHERE 1 AND group_id=$container_guid AND time_created>'$fecha2')t 
	GROUP BY date ORDER BY date ASC";
	

	$result = get_data($query);

	

	$aux = array();
	foreach ($result as $key) {
		$aux[$key->date] = $key->count;
	}


	$i=0;
	$return = array();
	$time=time();
	$time = strtotime('-9 day',$time);
	$time_str = date("j/n/Y", $time);
	for($i=0; $i<10; $i++){
		if(array_key_exists($time_str, $aux)){
			$return[$time_str] = $aux[$time_str];
		}
		else{
			$return[$time_str] = 0;
		}
		$time = strtotime('+1 day',$time);
     	$time_str = date("j/n/Y",$time);
	}


	return $return;
}




function grafica_actividad($actividad,$nombres,$group_guid,$graph_name=''){
	
	$datay1 = $actividad;
	if(count((array)$datay1) == 1){
		$partes = split('/', $nombres[0]);
		$dia = date_create($partes[2].'-'.$partes[1].'-'.$partes[0]);
		$dia->setTime(0,0);
		$format = 'd/m/Y';	
		$fecha = date(strtotime($partes[2].'-'.$partes[1].'-'.$partes[0] . " 00:00:00 UTC+1"));	
		for ($i=0; $i < 10; $i++) { 
			array_unshift($datay1, 0);			
			date_add($dia, date_interval_create_from_date_string('-1 day'));
			$name = $dia->format($format);
			array_unshift($nombres, $name);
		}
	}

	$graph = new Graph(1000,400);
	$graph->SetScale("textlin");

	$theme_class= new UniversalTheme;
	$graph->SetTheme($theme_class);

	$graph->title->Set($graph_name);
	$graph->title->SetFont(FF_DEFAULT,FS_BOLD,20);
	$graph->title->SetColor('#2E64FE');
	$graph->SetBox(false);

	$graph->yaxis->HideZeroLabel();
	$graph->yaxis->HideLine(false);
	$graph->yaxis->HideTicks(false,false);

	$graph->xaxis->SetTickLabels($nombres);
	$graph->xaxis->SetLabelAngle(60);
	$graph->ygrid->SetFill(false);

	$graph->img->SetMargin(50,30,20,100);

	$p1 = new LinePlot($datay1);
	$graph->Add($p1);

	$p1->mark->SetType(MARK_FILLEDCIRCLE,'',1.0);
	$p1->mark->SetColor('#55bbdd');
	$p1->mark->SetFillColor('#55bbdd');

	$path = elgg_get_data_path();
	$directory = $path . 'lrs_viewer';



	$old_pass = elgg_get_config('pass');


	$pass = substr(md5(uniqid(mt_rand(), true)) , 0, 8);
	if(!empty($old_pass)){
		$aux= array();
		if(gettype($old_pass)=='array'){
			foreach ($old_pass as $value) {
				array_push($aux, $value);
			}
		}
		else
			array_push($aux,$old_pass);
		array_push($aux,$old_pass);
		array_push($aux, $group_guid.'/'.$pass.'.png');
		elgg_save_config('pass', $aux);
	}else{

		elgg_save_config('pass', $group_guid.'/'.$pass.'.png');
	}

	$dir = $directory . "/" . $group_guid;
	if (!file_exists($dir)) {
		mkdir($dir, 0755, true);
	}
	
	$filename = $directory.'/'.$group_guid.'/' . $pass .'.png';
	
	$image = $graph->Stroke();

	//elgg_log(gettype($image),"ERROR");

	// $file = new ElggFile();
	// $file->setFilename("image/graficas");
	// $a = $file->getFilenameOnFilestore($file);
	// if (!file_exists($a)) {
	// 		mkdir($a, 0755, true);
	// 	}

	 //elgg_log($filename,"ERROR");
	
	 //$graph->img->Stream($filename);
	 @imagepng($image,$filename);

	// $imagen = new ElggFile();
	// $imagen->setMetadata("nombre","image");
	// $imagen->setMimeType("image/png");
	// $imagen->setFilename("name.png");
	// $imagen->save();
	// elgg_log("ESTE ES EL LINK AL ARCHIVO: " . $imagen->getFilenameOnFilestore(),"ERROR");
	// elgg_log("TIPO: " . gettype($image),"ERROR");

	//@imagepng($image,$imagen->getFilenameOnFilestore());

	return 'lrs_viewer/getImg/' . $group_guid . '/' . $pass . '.png';
}

function usuarios_mas_activos($container_guid){
	$dbprefix = elgg_get_config('dbprefix');
	$query = "SELECT actor_guid, COUNT(actor_guid) AS times from (SELECT actor_guid from {$dbprefix}events_log where 1 and container_guid={$container_guid} UNION ALL SELECT actor_guid from {$dbprefix}lrs_import where 1 and group_id={$container_guid})t GROUP BY actor_guid";
	$result = get_data($query);

	$activos = array();
	foreach ($result as $key) {
		$actor = $key->actor_guid;
		$user = get_entity($actor);
		if($user instanceof ElggUser){
			if (!events_collector_is_logged_in_group_admin($container_guid,$actor) && 
				!($user->isAdmin()) &&
				!check_entity_relationship($actor, 'group_admin', $container_guid)) {
				$activos[$actor] = $key->times;
			}
		}
	}
	arsort($activos);
	unset($activos[0]);
	return $activos;
}

// function usuarios_mas_activos($container_guid){
// 	elgg_log(microtime(true),'ERROR');
// 	$dbprefix = elgg_get_config('dbprefix');
// 	$query = "SELECT * from {$dbprefix}events_log where 1 and container_guid=$container_guid";
// 	$result1 = get_data($query);
// 	$query = "SELECT * from {$dbprefix}lrs_import where 1 and group_id=$container_guid";
// 	$result2 = get_data($query);

// 	$result = array_merge($result1,$result2);
// 	$activos = array();
// 	foreach ($result as $key) {
// 		$actor = $key->actor_guid;
// 		$user = get_entity($actor);
// 		if($user instanceof ElggUser){
// 			if (!events_collector_is_logged_in_group_admin($container_guid,$actor) && 
// 				!($user->isAdmin()) &&
// 				!check_entity_relationship($actor, 'group_admin', $container_guid)) {
// 				if(isset($activos[$actor]))
// 					$activos[$actor]++;
// 				else
// 					$activos[$actor]=1;
// 			}
// 		}
// 	}
// 	arsort($activos);
// 	unset($activos[0]);
// 	elgg_log(microtime(true),'ERROR');
// 	return $activos;
// }

function tabla_activos($activos, $container_guid, $limit){
	if(empty($limit))
		$limit = 10;
	?>
	<table class='lrs_viewer-table' align='right'>
    <tr>
    	
        <th><?php echo elgg_echo('lrs_viewer:most_active'); ?></th>
    </tr>
	<?php
		$i=0;
		foreach ($activos as $key => $value) {
			$user = get_entity($key);
			if ($user) {
		            $user_link = elgg_view('output/url', array(
		                'href' => elgg_get_site_url() . 'lrs_viewer/lrsbrowser?container_guid=' . $container_guid . '&actor=' . $key . '&choose_graf=tables&limit=10',
		                'text' => $user->name,
		                'is_trusted' => true,
		            ));
		        } else {
		            $user_guid_link = $user_link = '&nbsp;';
		        }
		        ?>
			<tr> 
				<td>
				<?php
				$j=$i+1;
			                echo "<b>$j. </b>" . elgg_view_entity_icon($user, 'tiny');
			                echo "<h3>$user_link</h3>"; 
			                ?>
			            </td>
		    </tr>      
		<?php
			$i++;
			if($i==5)
				break;
		}
	?>
	</table>

	<?php
}


function refinar($actor,$action,$subtype,$ini_date,$end_date,$choose_info,$choose_graf){
	$vars=array(
		'actor' => get_entity($actor)->name,
		'action' =>$action,
		'subtype' => $subtype,
		'ini_date' => $ini_date,
		'end_date' => $end_date,
		'choose_info' => $choose_info,
		'choose_graf' => $choose_graf,
		);
	$form = elgg_view_form('lrs_viewer/form',array(),$vars);
	echo $form;
}

function paint_actor($container_guid,$actor,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$entries){
    global $query2,$query1,$result2,$result1,$size_int,$size_ext;

    $ret = array();
    if (empty($offset)) {
		$offset=0;
	}
	if(!empty($ini_date)){
		$time_init_dbi = strtotime($ini_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_init_dbi);
		$time_init_dbe = date_format($aux, 'Ymd');
	}else{
		$time_init_dbi = 0;
		$time_init_dbe = '19700101';
	}
	if(!empty($end_date)){
		$time_end_dbi = strtotime($end_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}else{
		$time_end_dbi = time();
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}

	$actor_query = "";
	if ($actor == 'no_admins'){
		$admins = getAdmins($container_guid);
		$i = True;
		foreach ($admins as $admin) {
			elgg_log('ADMINISTRADOR'.$admin,'ERROR');
			if($i){
				$i=false;
				$actor_query .= "actor_guid!=" . $admin->getGUID() . " ";
			}else{
				$actor_query .= "and actor_guid!=" . $admin->getGUID() . " ";
			}
		}
	}else{
		$actor_query = "actor_guid=$actor";
	}

    $dbprefix = elgg_get_config('dbprefix');
    $query1 = "SELECT * from {$dbprefix}events_log where 1 and container_guid=$container_guid and $actor_query";
    $query2 = "SELECT * from {$dbprefix}lrs_import where 1 and group_id=$container_guid and $actor_query";

    elgg_log($query1,'ERROR');
    
    if ($choose_graf=='tables') {
		get_db($ini_date,$end_date,$entries,$offset);
		
		if ($choose_info=='external') {
			$vars = array(
				'events_entries' => $result2,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_ext,
							'limit' => $entries,
						);
			echo elgg_view('page/table_externa',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
		else{
			$vars = array(
				'events_entries' => $result1,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_int,
							'limit' => $entries,
							);
			echo elgg_view('page/table',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
	}elseif($choose_graf=='graphics'){

	    // get_db($ini_date,$end_date,0);
		$actor_query = "";
		if ($actor == 'no_admins'){
			$admins = getAdmins($container_guid);
			$i = True;
			foreach ($admins as $admin) {
				elgg_log('ADMINISTRADOR'.$admin,'ERROR');
				if($i){
					$i=false;
					$actor_query .= "actor_guid!=" . $admin->getGUID() . " ";
				}else{
					$actor_query .= "and actor_guid!=" . $admin->getGUID() . " ";
				}
			}
		}else{
			$actor_query = "actor_guid=$actor";
		}

	    $array_select = array("resource_type","action_type");
	    $no_data=true;
	    foreach ($array_select as $select) {

	    	$query1 = "SELECT $select, count({$select}) AS count  FROM {$dbprefix}events_log WHERE 1 and container_guid=$container_guid and $actor_query and time_created<$time_end_dbi and time_created>=$time_init_dbi GROUP BY $select";
	    	$query2 = "SELECT $select, count({$select}) AS count FROM {$dbprefix}lrs_import where 1 and group_id=$container_guid and $actor_query and time_created>=$time_init_dbe and time_created<$time_end_dbe GROUP BY $select";
	    	$query3 = "SELECT $select, COUNT({$select}) AS count FROM (
	    		SELECT $select FROM {$dbprefix}lrs_import WHERE 1 and group_id=$container_guid and $actor_query and time_created<$time_end_dbi and time_created>=$time_init_dbi 
	    		UNION ALL 
	    		SELECT $select FROM {$dbprefix}events_log WHERE 1 and container_guid=$container_guid and $actor_query and time_created>=$time_init_dbe and time_created<$time_end_dbe) t 
	    		GROUP BY $select";
	    	$names = array();
	    	$values = array();
	    	$result = array();
	   		switch($choose_info){
	   			case 'external':
	   				$result = get_data($query2);
	    			break;
	    		case 'internal':
	    			$result = get_data($query1);
	    			break;
	    		default:
	    			$result = get_data($query3);
	    			break;
	    	}



	    	$i=0;
	    	foreach($result as $res){
	   			$names[$i] = elgg_echo('lrs_viewer:' . strtolower($res->$select));
	   			$values[$i] = (int)$res->count;
  				$i++;
 			}

	            // get_arrays($choose_info,$select);
	            // make_arrays(true);
	            // $i=0;
	            // foreach ($names as $value) {
	            // 	$names[$i] = elgg_echo('lrs_viewer:'.strtolower($value));
	            // 	$i++;
	            // }

	            if (!empty($values)) {
	            	$no_data=false;
	            	if ($select == 'resource_type')
	            		$graph_name = elgg_echo('lrs_viewer:resource_type');
	            	else
	            		$graph_name = elgg_echo('lrs_viewer:action');

	                $path = grafic_circle($values,$names,$container_guid,$graph_name);
	                array_push($ret, $path);
	            }  
	    }
	}elseif($choose_graf=='ranking'){
		$vars = array('container_guid' => $container_guid,
			'time_init_dbi' => $time_init_dbi,
			'time_end_dbi' => $time_end_dbi,
			'time_init_dbe' => $time_init_dbe,
			'time_end_dbe' => $time_end_dbe,
			'choose_info' => $choose_info,
			'offset' => $offset,
			'entries' => $entries,
			'dbprefix' => $dbprefix);
		ranking($vars);
    	
	}
	return $ret;
}

function paint_action($container_guid,$action,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$entries){
	global $query2,$query1,$result2,$result1,$values,$names,$size_int,$size_ext;

    if (empty($offset)) {
		$offset=0;
	}
	if(!empty($ini_date)){
		$time_init_dbi = strtotime($ini_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_init_dbi);
		$time_init_dbe = date_format($aux, 'Ymd');
	}else{
		$time_init_dbi = 0;
		$time_init_dbe = '19700101';
	}
	if(!empty($end_date)){
		$time_end_dbi = strtotime($end_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}else{
		$time_end_dbi = time();
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}
	$acciones = "";
	$first = True;
	foreach ($action as $key => $value) {
		if($first){
			$first = false;
			$acciones .= "action_type='" . strtoupper($value) . "' ";
		}
		else
			$acciones .= " or action_type='" . strtoupper($value) . "' ";
	}
    $dbprefix = elgg_get_config('dbprefix');
    $query1 = "SELECT * from {$dbprefix}events_log where 1 and container_guid=$container_guid and ($acciones)";
    elgg_log($query1,'ERROR');
    $query2 = "SELECT * from {$dbprefix}lrs_import where 1 and group_id=$container_guid and ($acciones)";

    if ($choose_graf=='tables') {
		get_db($ini_date,$end_date,$entries,$offset);
		
		if ($choose_info=='external') {
			$vars = array(
				'events_entries' => $result2,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_ext,
							'limit' => $entries,
						);
			echo elgg_view('page/table_externa',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
		else{
			$vars = array(
				'events_entries' => $result1,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_int,
							'limit' => $entries,
							);
			echo elgg_view('page/table',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
	}elseif($choose_graf == 'graphics'){

		$acciones = "";
		$first = True;
		foreach ($action as $key => $value) {
			if($first){
				$first = false;
				$acciones .= "action_type='" . strtoupper($value) . "' ";
			}
			else
				$acciones .= " or action_type='" . strtoupper($value) . "' ";
		}
		
	    $array_select = array("resource_type","actor_guid");
	    $no_data=true;
	    $ret = array();
	    foreach ($array_select as $select) {
	        $query1 = "SELECT $select, count({$select}) AS count  FROM {$dbprefix}events_log WHERE 1 and container_guid=$container_guid and ($acciones) and time_created<$time_end_dbi and time_created>=$time_init_dbi GROUP BY $select ORDER BY count DESC";
	    	$query2 = "SELECT $select, count({$select}) AS count FROM {$dbprefix}lrs_import where 1 and group_id=$container_guid and ($acciones) and time_created>=$time_init_dbe and time_created<$time_end_dbe GROUP BY $select ORDER BY count DESC";
	    	$query3 = "SELECT $select, COUNT({$select}) AS count FROM (
	    		SELECT $select FROM {$dbprefix}lrs_import WHERE 1 and group_id=$container_guid and ($acciones) and time_created<$time_end_dbi and time_created>=$time_init_dbi 
	    		UNION ALL 
	    		SELECT $select FROM {$dbprefix}events_log WHERE 1 and container_guid=$container_guid and ($acciones) and time_created>=$time_init_dbe and time_created<$time_end_dbe) t 
	    		GROUP BY $select ORDER BY count DESC";
	    	$names = array();
	    	$values = array();
	    	$result = array();
	   		switch($choose_info){
	   			case 'external':
	   				$result = get_data($query2);
	    			break;
	    		case 'internal':
	    			$result = get_data($query1);
	    			break;
	    		default:
	    			$result = get_data($query3);
	    			break;
	    	}

	    	$i=0;
	    	foreach($result as $res){
	    		if($select == 'resource_type'){
	    			$graph_name = elgg_echo('lrs_viewer:resource_type');
	   				$names[$i] = elgg_echo('lrs_viewer:' . strtolower($res->$select));
	   				$values[$i] = (int)$res->count;
	   				$i++;
	    		}
	   			else{
	   				$graph_name = elgg_echo('lrs_viewer:users');
	   				$user = get_entity((int)$res->$select);
	   				if($user instanceof ElggUser){
	   					if($i<10){
		   					$names[$i] = $user->getDisplayName();
		   					$values[$i] = (int)$res->count;
		   					$i++;
		   				}
		   				else{
		   					$names[9] = 'Otros';
		   					$values[9] += (int)$res->count;
		   				}
	   				}
	   			}
 			}
	            if (!empty($values)) {
	            	$no_data=false;
	                $path = grafic_bars($values,$names,$container_guid,$graph_name);
	                array_push($ret, $path);
	            }
	    }
	}elseif($choose_graf == 'ranking'){
		$vars = array('container_guid' => $container_guid,
			'time_init_dbi' => $time_init_dbi,
			'time_end_dbi' => $time_end_dbi,
			'time_init_dbe' => $time_init_dbe,
			'time_end_dbe' => $time_end_dbe,
			'choose_info' => $choose_info,
			'offset' => $offset,
			'entries' => $entries,
			'dbprefix' => $dbprefix,
			'action_type' => $action);
		ranking($vars);
	}
	return $ret;
}

function paint_subtype($container_guid,$subtype,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$entries){
	global $query2,$query1,$result2,$result1,$values,$names,$size_int,$size_ext;

    if (empty($offset)) {
		$offset=0;
	}
	if(!empty($ini_date)){
		$time_init_dbi = strtotime($ini_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_init_dbi);
		$time_init_dbe = date_format($aux, 'Ymd');
	}else{
		$time_init_dbi = 0;
		$time_init_dbe = '19700101';
	}
	if(!empty($end_date)){
		$time_end_dbi = strtotime($end_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}else{
		$time_end_dbi = time();
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}
    $tipos = "";
	$first = True;
	foreach ($subtype as $key => $value) {
		if($first){
			$first = false;
			$tipos .= "resource_type='" . $value . "' ";
		}
		else
			$tipos .= " or resource_type='" . $value . "' ";

		if($value == 'page_top')
			$tipos .= " or resource_type='page' ";
	}
    $dbprefix = elgg_get_config('dbprefix');
    $query1 = "SELECT * from {$dbprefix}events_log where 1 and container_guid=$container_guid and ($tipos)";
    $query2 = "SELECT * from {$dbprefix}lrs_import where 1 and group_id=$container_guid and ($tipos)";

    if ($choose_graf=='tables') {
		get_db($ini_date,$end_date,$entries,$offset);
		
		if ($choose_info=='external') {
			$vars = array(
				'events_entries' => $result2,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_ext,
							'limit' => $entries,
						);
			echo elgg_view('page/table_externa',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
		else{
			$vars = array(
				'events_entries' => $result1,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_int,
							'limit' => $entries,
							);
			echo elgg_view('page/table',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
	}elseif($choose_graf == 'graphics'){
	    $array_select = array("action_type","actor_guid");
	    $no_data = true;
	    $ret = array();
	    $tipos = "";
		$first = True;
		foreach ($subtype as $key => $value) {
			if($first){
				$first = false;
				$tipos .= "resource_type='" . $value . "' ";
			}
			else
				$tipos .= " or resource_type='" . $value . "' ";
			if($value == 'page_top')
				$tipos .= " or resource_type='page' ";
		}
	    foreach ($array_select as $select) {
	            $query1 = "SELECT $select, count({$select}) AS count  FROM {$dbprefix}events_log WHERE 1 and container_guid=$container_guid and ($tipos) and time_created<$time_end_dbi and time_created>=$time_init_dbi GROUP BY $select ORDER BY count DESC";
	    	$query2 = "SELECT $select, count({$select}) AS count FROM {$dbprefix}lrs_import where 1 and group_id=$container_guid and ($tipos) and time_created>=$time_init_dbe and time_created<$time_end_dbe GROUP BY $select ORDER BY count DESC";
	    	$query3 = "SELECT $select, COUNT({$select}) AS count FROM (
	    		SELECT $select FROM {$dbprefix}lrs_import WHERE 1 and group_id=$container_guid and ($tipos) and time_created<$time_end_dbi and time_created>=$time_init_dbi 
	    		UNION ALL 
	    		SELECT $select FROM {$dbprefix}events_log WHERE 1 and container_guid=$container_guid and ($tipos) and time_created>=$time_init_dbe and time_created<$time_end_dbe) t 
	    		GROUP BY $select ORDER BY count DESC";
	    	$names = array();
	    	$values = array();
	    	$result = array();
	   		switch($choose_info){
	   			case 'external':
	   				$result = get_data($query2);
	    			break;
	    		case 'internal':
	    			$result = get_data($query1);
	    			break;
	    		default:
	    			$result = get_data($query3);
	    			break;
	    	}

	    	$i=0;
	    	foreach($result as $res){
	    		if($select == 'action_type'){
	    			$graph_name = elgg_echo('lrs_viewer:action');
	   				$names[$i] = elgg_echo('lrs_viewer:' . strtolower($res->$select));
	   				$values[$i] = (int)$res->count;
	   				$i++;
	    		}
	   			else{
	   				$graph_name = elgg_echo('lrs_viewer:users');
	   				$user = get_entity((int)$res->$select);
	   				if($user instanceof ElggUser){
	   					if($i<10){
		   					$names[$i] = $user->getDisplayName();
		   					$values[$i] = (int)$res->count;
		   					$i++;
		   				}
		   				else{
		   					$names[9] = 'Otros';
		   					$values[9] += (int)$res->count;
		   				}
	   				}
	   			}
 			}
	            if (!empty($values)) {
	            	$no_data = false;    
	                $path = grafic_bars($values,$names,$container_guid,$graph_name);
	                array_push($ret, $path);
	            }
	    }
	    return $ret;
	}elseif($choose_graf == 'ranking'){
		$vars = array('container_guid' => $container_guid,
			'time_init_dbi' => $time_init_dbi,
			'time_end_dbi' => $time_end_dbi,
			'time_init_dbe' => $time_init_dbe,
			'time_end_dbe' => $time_end_dbe,
			'choose_info' => $choose_info,
			'offset' => $offset,
			'entries' => $entries,
			'dbprefix' => $dbprefix,
			'resource_type' => $subtype);
		ranking($vars);
	}
}

function paint_actor_action($container_guid,$actor,$action,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$entries){
	global $query2,$query1,$result2,$result1,$data1,$data2,$size_int,$size_ext,$values,$names;
	$dbprefix = elgg_get_config('dbprefix');

	if (empty($offset)) {
		$offset=0;
	}
	if(!empty($ini_date)){
		$time_init_dbi = strtotime($ini_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_init_dbi);
		$time_init_dbe = date_format($aux, 'Ymd');
	}else{
		$time_init_dbi = 0;
		$time_init_dbe = '19700101';
	}
	if(!empty($end_date)){
		$time_end_dbi = strtotime($end_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}else{
		$time_end_dbi = time();
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}

	$acciones = "";
	$first = True;
	foreach ($action as $key => $value) {
		if($first){
			$first = false;
			$acciones .= "action_type='" . strtoupper($value) . "' ";
		}
		else
			$acciones .= " or action_type='" . strtoupper($value) . "' ";
	}

	$actor_query = "";
	if ($actor == 'no_admins'){
		$admins = getAdmins($container_guid);
		$i = True;
		foreach ($admins as $admin) {
			elgg_log('ADMINISTRADOR'.$admin,'ERROR');
			if($i){
				$i=false;
				$actor_query .= "actor_guid!=" . $admin->getGUID() . " ";
			}else{
				$actor_query .= "and actor_guid!=" . $admin->getGUID() . " ";
			}
		}
	}else{
		$actor_query = "actor_guid=$actor";
	}

	$action = strtoupper($action);
	$query1 = "SELECT * from {$dbprefix}events_log where 1 and container_guid=$container_guid and ($acciones) and $actor_query";
	$query2 = "SELECT * from {$dbprefix}lrs_import where 1 and group_id=$container_guid and ($acciones) and $actor_query";
	

	if ($choose_graf=='tables') {
		get_db($ini_date,$end_date,$entries,$offset);
		
		if ($choose_info=='external') {
			$vars = array(
				'events_entries' => $result2,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_ext,
							'limit' => $entries,
						);
			echo elgg_view('page/table_externa',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
		else{
			$vars = array(
				'events_entries' => $result1,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_int,
							'limit' => $entries,
							);
			echo elgg_view('page/table',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
	}elseif($choose_graf == 'graphics'){


		$actor_query = "";
		if ($actor == 'no_admins'){
			$admins = getAdmins($container_guid);
			$i = True;
			foreach ($admins as $admin) {
				elgg_log('ADMINISTRADOR'.$admin,'ERROR');
				if($i){
					$i=false;
					$actor_query .= "actor_guid!=" . $admin->getGUID() . " ";
				}else{
					$actor_query .= "and actor_guid!=" . $admin->getGUID() . " ";
				}
			}
		}else{
			$actor_query = "actor_guid=$actor";
		}
		
		$query1 = "SELECT resource_type, COUNT(resource_type) AS count FROM {$dbprefix}events_log WHERE 1 AND container_guid=$container_guid AND ($acciones) AND $actor_query and time_created<$time_end_dbi and time_created>=$time_init_dbi GROUP BY resource_type";
		$query2 = "SELECT resource_type, COUNT(resource_type) AS count FROM {$dbprefix}lrs_import WHERE 1 AND group_id=$container_guid AND ($acciones) AND $actor_query and time_created>=$time_init_dbe and time_created<$time_end_dbe GROUP BY resource_type";
		$query3 = "SELECT resource_type, COUNT(resource_type) AS count FROM (
	    		SELECT resource_type FROM {$dbprefix}events_log WHERE 1 AND container_guid=$container_guid AND ($acciones) AND $actor_query and time_created<$time_end_dbi and time_created>=$time_init_dbi 
	    		UNION ALL 
	    		SELECT resource_type FROM {$dbprefix}lrs_import WHERE 1 AND group_id=$container_guid AND ($acciones) AND $actor_query and time_created>=$time_init_dbe and time_created<$time_end_dbe) t 
	    		GROUP BY resource_type ORDER BY count DESC";

		$names = array();
	    $values = array();
	   	$result = array();
	  	switch($choose_info){
	  		case 'external':
	   			$result = get_data($query2);
	    		break;
	   		case 'internal':
	   			$result = get_data($query1);
	   			break;
	   		default:
    			$result = get_data($query3);
       			break;
	   	}

    	$i=0;
    	foreach($result as $res){
   			$names[$i] = elgg_echo('lrs_viewer:' . strtolower($res->resource_type));
   			$values[$i] = (int)$res->count;
   			$i++;
    		
			}

		if (!empty($values)) {
			$graph_name = elgg_echo('lrs_viewer:resource_type');
			$i=0;

		   	$ret = array();
			$path =grafic_circle($values,$names,$container_guid,$graph_name);
			array_push($ret, $path);
		}
	}elseif($choose_graf == 'ranking'){
		$vars = array('container_guid' => $container_guid,
			'time_init_dbi' => $time_init_dbi,
			'time_end_dbi' => $time_end_dbi,
			'time_init_dbe' => $time_init_dbe,
			'time_end_dbe' => $time_end_dbe,
			'choose_info' => $choose_info,
			'offset' => $offset,
			'entries' => $entries,
			'dbprefix' => $dbprefix,
			'action_type' => $action);
		ranking($vars);
	}
	return $ret;

}

function paint_actor_subtype($container_guid,$actor,$subtype,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$entries){
	global $query2,$query1,$result2,$result1,$data1,$data2,$size_int,$size_ext,$values,$names;
	$dbprefix = elgg_get_config('dbprefix');

	if (empty($offset)) {
		$offset=0;
	}
	if(!empty($ini_date)){
		$time_init_dbi = strtotime($ini_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_init_dbi);
		$time_init_dbe = date_format($aux, 'Ymd');
	}else{
		$time_init_dbi = 0;
		$time_init_dbe = '19700101';
	}
	if(!empty($end_date)){
		$time_end_dbi = strtotime($end_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}else{
		$time_end_dbi = time();
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}
	$tipos = "";
	$first = True;
	foreach ($subtype as $key => $value) {
		if($first){
			$first = false;
			$tipos .= "resource_type='" . $value . "' ";
		}
		else
			$tipos .= " or resource_type='" . $value . "' ";
		if($value == 'page_top')
			$tipos .= " or resource_type='page' ";
	}

	$actor_query = "";
	if ($actor == 'no_admins'){
		$admins = getAdmins($container_guid);
		$i = True;
		foreach ($admins as $admin) {
			elgg_log('ADMINISTRADOR'.$admin,'ERROR');
			if($i){
				$i=false;
				$actor_query .= "actor_guid!=" . $admin->getGUID() . " ";
			}else{
				$actor_query .= "and actor_guid!=" . $admin->getGUID() . " ";
			}
		}
	}else{
		$actor_query = "actor_guid=$actor";
	}

	$query1 = "SELECT * from {$dbprefix}events_log where 1 and container_guid=$container_guid and $actor_query and ($tipos)";
	$query2 = "SELECT * from {$dbprefix}lrs_import where 1 and group_id=$container_guid and $actor_query and ($tipos)";
	

	if ($choose_graf=='tables') {
		get_db($ini_date,$end_date,$entries,$offset);
		
		if ($choose_info=='external') {
			$vars = array(
				'events_entries' => $result2,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_ext,
							'limit' => $entries,
						);
			echo elgg_view('page/table_externa',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
		else{
			$vars = array(
				'events_entries' => $result1,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_int,
							'limit' => $entries,
							);
			echo elgg_view('page/table',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
	}elseif($choose_graf == 'graphics'){

		$tipos = "";
		$first = True;
		foreach ($subtype as $key => $value) {
			if($first){
				$first = false;
				$tipos .= "resource_type='" . $value . "' ";
			}
			else
				$tipos .= " or resource_type='" . $value . "' ";
			if($value == 'page_top')
				$tipos .= " or resource_type='page' ";
		}

		$actor_query = "";
		if ($actor == 'no_admins'){
			$admins = getAdmins($container_guid);
			$i = True;
			foreach ($admins as $admin) {
				elgg_log('ADMINISTRADOR'.$admin,'ERROR');
				if($i){
					$i=false;
					$actor_query .= "actor_guid!=" . $admin->getGUID() . " ";
				}else{
					$actor_query .= "and actor_guid!=" . $admin->getGUID() . " ";
				}
			}
		}else{
			$actor_query = "actor_guid=$actor";
		}		
		
		$query1 = "SELECT action_type, COUNT(action_type) AS count FROM {$dbprefix}events_log WHERE 1 AND container_guid=$container_guid AND ($tipos) AND $actor_query and time_created<$time_end_dbi and time_created>=$time_init_dbi GROUP BY action_type";
		$query2 = "SELECT action_type, COUNT(action_type) AS count FROM {$dbprefix}lrs_import WHERE 1 AND group_id=$container_guid AND ($tipos) AND $actor_query and time_created>=$time_init_dbe and time_created<$time_end_dbe GROUP BY action_type";
		$query3 = "SELECT action_type, COUNT(action_type) AS count FROM (
	    		SELECT action_type FROM {$dbprefix}events_log WHERE 1 AND container_guid=$container_guid AND ($tipos) AND $actor_query and time_created<$time_end_dbi and time_created>=$time_init_dbi 
	    		UNION ALL 
	    		SELECT action_type FROM {$dbprefix}lrs_import WHERE 1 AND group_id=$container_guid AND ($tipos) AND $actor_query and time_created>=$time_init_dbe and time_created<$time_end_dbe) t 
	    		GROUP BY action_type ORDER BY count DESC";

		$names = array();
	    $values = array();
	   	$result = array();
	  	switch($choose_info){
	  		case 'external':
	   			$result = get_data($query2);
	    		break;
	   		case 'internal':
	   			$result = get_data($query1);
	   			break;
	   		default:
    			$result = get_data($query3);
       			break;
	   	}

    	$i=0;
    	foreach($result as $res){
   			$names[$i] = elgg_echo('lrs_viewer:' . strtolower($res->action_type));
   			$values[$i] = (int)$res->count;
   			$i++;
			}

		if (!empty($values)) {
			$graph_name = elgg_echo('lrs_viewer:action');
		  	$ret = array();
			$path =grafic_circle($values,$names,$container_guid,$graph_name);
			array_push($ret, $path);
		}
	}elseif($choose_graf == 'ranking'){
		$vars = array('container_guid' => $container_guid,
			'time_init_dbi' => $time_init_dbi,
			'time_end_dbi' => $time_end_dbi,
			'time_init_dbe' => $time_init_dbe,
			'time_end_dbe' => $time_end_dbe,
			'choose_info' => $choose_info,
			'offset' => $offset,
			'entries' => $entries,
			'dbprefix' => $dbprefix,
			'resource_type' => $subtype);
		ranking($vars);
	}
	return $ret;

}

function paint_actor_action_subtype($container_guid,$actor,$action,$subtype,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$entries){
	global $query2,$query1,$result2,$result1,$data1,$data2,$size_int,$size_ext,$values,$names;
	$dbprefix = elgg_get_config('dbprefix');

	if (empty($offset)) {
		$offset=0;
	}
	if(!empty($ini_date)){
		$time_init_dbi = strtotime($ini_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_init_dbi);
		$time_init_dbe = date_format($aux, 'Ymd');
	}else{
		$time_init_dbi = 0;
		$time_init_dbe = '19700101';
	}
	if(!empty($end_date)){
		$time_end_dbi = strtotime($end_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}else{
		$time_end_dbi = time();
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}

	$acciones = "";
	$first = True;
	foreach ($action as $key => $value) {
		if($first){
			$first = false;
			$acciones .= "action_type='" . strtoupper($value) . "' ";
		}
		else
			$acciones .= " or action_type='" . strtoupper($value) . "' ";
	}

	$tipos = "";
	$first = True;
	foreach ($subtype as $key => $value) {
		if($first){
			$first = false;
			$tipos .= "resource_type='" . $value . "' ";
		}
		else
			$tipos .= " or resource_type='" . $value . "' ";
		if($value == 'page_top')
			$tipos .= " or resource_type='page' ";
	}

	$actor_query = "";
	if ($actor == 'no_admins'){
		$admins = getAdmins($container_guid);
		$i = True;
		foreach ($admins as $admin) {
			elgg_log('ADMINISTRADOR'.$admin,'ERROR');
			if($i){
				$i=false;
				$actor_query .= "actor_guid!=" . $admin->getGUID() . " ";
			}else{
				$actor_query .= "and actor_guid!=" . $admin->getGUID() . " ";
			}
		}
	}else{
		$actor_query = "actor_guid=$actor";
	}
	
	$query1 = "SELECT * from {$dbprefix}events_log where 1 and container_guid=$container_guid and ($acciones) and ($tipos) and $actor_query";
	$query2 = "SELECT * from {$dbprefix}lrs_import where 1 and group_id=$container_guid and ($acciones) and ($tipos) and $actor_query";
	

	if ($choose_graf=='tables') {
		get_db($ini_date,$end_date,$entries,$offset);
		
		if ($choose_info=='external') {
			$vars = array(
				'events_entries' => $result2,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_ext,
							'limit' => $entries,
						);
			echo elgg_view('page/table_externa',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
		else{
			$vars = array(
				'events_entries' => $result1,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_int,
							'limit' => $entries,
							);
			echo elgg_view('page/table',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
	}elseif($choose_graf == 'graphics'){

		$acciones = "";
		$first = True;
		foreach ($action as $key => $value) {
			if($first){
				$first = false;
				$acciones .= "action_type='" . strtoupper($value) . "' ";
			}
			else
				$acciones .= " or action_type='" . strtoupper($value) . "' ";
		}

		 $tipos = "";
		$first = True;
		foreach ($subtype as $key => $value) {
			if($first){
				$first = false;
				$tipos .= "resource_type='" . $value . "' ";
			}
			else
				$tipos .= " or resource_type='" . $value . "' ";
			if($value == 'page_top')
				$tipos .= " or resource_type='page' ";
		}

		$actor_query = "";
		if ($actor == 'no_admins'){
			$admins = getAdmins($container_guid);
			$i = True;
			foreach ($admins as $admin) {
				elgg_log('ADMINISTRADOR'.$admin,'ERROR');
				if($i){
					$i=false;
					$actor_query .= "actor_guid!=" . $admin->getGUID() . " ";
				}else{
					$actor_query .= "and actor_guid!=" . $admin->getGUID() . " ";
				}
			}
		}else{
			$actor_query = "actor_guid=$actor";
		}
		
		$query1 = "SELECT date_format(FROM_UNIXTIME(time_created),'%e/%c/%Y') AS date, count(date_format(FROM_UNIXTIME(time_created),'%e/%c/%Y')) AS count FROM {$dbprefix}events_log WHERE 1 AND container_guid=$container_guid AND ($acciones) AND ($tipos) AND $actor_query and time_created<$time_end_dbi and time_created>=$time_init_dbi GROUP BY date_format(FROM_UNIXTIME(time_created),'%e/%c/%Y')";
		$query2 = "SELECT date_format(FROM_UNIXTIME(time_created),'%e/%c/%Y') AS date, count(date_format(FROM_UNIXTIME(time_created),'%e/%c/%Y')) AS count FROM {$dbprefix}lrs_import WHERE 1 AND group_id=$container_guid AND ($acciones) AND ($tipos) AND $actor_query and time_created>=$time_init_dbe and time_created<$time_end_dbe GROUP BY date_format(FROM_UNIXTIME(time_created),'%e/%c/%Y')";
		$query3 = "SELECT date, COUNT(date) AS count FROM (
		SELECT date_format(FROM_UNIXTIME(time_created),'%e/%c/%Y') AS date FROM {$dbprefix}events_log WHERE 1 AND container_guid=$container_guid AND ($acciones) AND ($tipos) AND $actor_query and time_created<$time_end_dbi and time_created>=$time_init_dbi 
		UNION ALL 
		SELECT date_format(FROM_UNIXTIME(time_created),'%e/%c/%Y') AS date FROM {$dbprefix}lrs_import WHERE 1 AND group_id=$container_guid AND ($acciones) AND ($tipos) AND $actor_query and time_created>=$time_init_dbe and time_created<$time_end_dbe)t GROUP BY date";

		$names = array();
	    $values = array();
	   	$result = array();
	  	switch($choose_info){
	  		case 'external':
	   			$result = get_data($query2);
	    		break;
	   		case 'internal':
	   			$result = get_data($query1);
	   			break;
	   		default:
    			$result = get_data($query3);
       			break;
	   	}

    	$i=0;
    	foreach($result as $res){
   			$names[$i] = $res->date;
   			$values[$i] = (int)$res->count;
   			$i++;
			}

		$ret = array();
		if (!empty($values)) {
			$path = grafica_actividad($values,$names,$container_guid);
			array_push($ret, $path);
		}
	}elseif($choose_graf == 'ranking'){
		$vars = array('container_guid' => $container_guid,
			'time_init_dbi' => $time_init_dbi,
			'time_end_dbi' => $time_end_dbi,
			'time_init_dbe' => $time_init_dbe,
			'time_end_dbe' => $time_end_dbe,
			'choose_info' => $choose_info,
			'offset' => $offset,
			'entries' => $entries,
			'dbprefix' => $dbprefix,
			'action_type' => $action,
			'resource_type' => $subtype);
		ranking($vars);
	}
	return $ret;
}

function paint_action_subtype($container_guid,$action,$subtype,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$entries){
	global $query2,$query1,$result2,$result1,$data1,$data2,$size_int,$size_ext,$values,$names;
	$dbprefix = elgg_get_config('dbprefix');

	if (empty($offset)) {
		$offset=0;
	}
	if(!empty($ini_date)){
		$time_init_dbi = strtotime($ini_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_init_dbi);
		$time_init_dbe = date_format($aux, 'Ymd');
	}else{
		$time_init_dbi = 0;
		$time_init_dbe = '19700101';
	}
	if(!empty($end_date)){
		$time_end_dbi = strtotime($end_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}else{
		$time_end_dbi = time();
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}
	$acciones = "";
	$first = True;
	foreach ($action as $key => $value) {
		if($first){
			$first = false;
			$acciones .= "action_type='" . strtoupper($value) . "' ";
		}
		else
			$acciones .= " or action_type='" . strtoupper($value) . "' ";
	}

	 $tipos = "";
	$first = True;
	foreach ($subtype as $key => $value) {
		if($first){
			$first = false;
			$tipos .= "resource_type='" . $value . "' ";
		}
		else
			$tipos .= " or resource_type='" . $value . "' ";
		if($value == 'page_top')
			$tipos .= " or resource_type='page' ";
	}
	$query1 = "SELECT * from {$dbprefix}events_log where 1 and container_guid=$container_guid and ($acciones) and ($tipos)";
	$query2 = "SELECT * from {$dbprefix}lrs_import where 1 and group_id=$container_guid and ($acciones) and ($tipos)";
	

	if ($choose_graf=='tables') {
		get_db($ini_date,$end_date,$entries,$offset);
		
		if ($choose_info=='external') {
			$vars = array(
				'events_entries' => $result2,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_ext,
							'limit' => $entries,
						);
			echo elgg_view('page/table_externa',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
		else{
			$vars = array(
				'events_entries' => $result1,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_int,
							'limit' => $entries,
							);
			echo elgg_view('page/table',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
	}elseif($choose_graf == 'graphics'){
		$acciones = "";
		$first = True;
		foreach ($action as $key => $value) {
			if($first){
				$first = false;
				$acciones .= "action_type='" . strtoupper($value) . "' ";
			}
			else
				$acciones .= " or action_type='" . strtoupper($value) . "' ";
		}

		 $tipos = "";
		$first = True;
		foreach ($subtype as $key => $value) {
			if($first){
				$first = false;
				$tipos .= "resource_type='" . $value . "' ";
			}
			else
				$tipos .= " or resource_type='" . $value . "' ";
			if($value == 'page_top')
				$tipos .= " or resource_type='page' ";
		}
		
		$query1 = "SELECT actor_guid, COUNT(actor_guid) AS count FROM {$dbprefix}events_log WHERE 1 AND container_guid=$container_guid AND ($acciones) AND ($tipos) and time_created<$time_end_dbi and time_created>=$time_init_dbi GROUP BY actor_guid ORDER BY count DESC";
		$query2 = "SELECT actor_guid, COUNT(actor_guid) AS count FROM {$dbprefix}lrs_import WHERE 1 AND group_id=$container_guid AND ($acciones) AND ($tipos) and time_created>=$time_init_dbe and time_created<$time_end_dbe GROUP BY actor_guid ORDER BY count DESC";
		$query3 = "SELECT actor_guid, COUNT(actor_guid) AS count FROM (
	    		SELECT actor_guid FROM {$dbprefix}events_log WHERE 1 AND container_guid=$container_guid AND ($acciones) AND ($tipos) and time_created<$time_end_dbi and time_created>=$time_init_dbi 
	    		UNION ALL 
	    		SELECT actor_guid FROM {$dbprefix}lrs_import WHERE 1 AND group_id=$container_guid AND ($acciones) AND ($tipos) and time_created>=$time_init_dbe and time_created<$time_end_dbe) t 
	    		GROUP BY actor_guid ORDER BY count DESC";

		$names = array();
	    $values = array();
	   	$result = array();
	  	switch($choose_info){
	  		case 'external':
	   			$result = get_data($query2);
	    		break;
	   		case 'internal':
	   			$result = get_data($query1);
	   			break;
	   		default:
    			$result = get_data($query3);
       			break;
	   	}

    	$i=0;
    	foreach($result as $res){
    		$user = get_entity((int)$res->actor_guid);
    		if($user instanceof ElggUser){
    			if($i<10){
		   			$names[$i] = $user->getDisplayName();
		   			$values[$i] = (int)$res->count;
		   			$i++;
	   			}
	   			else{
	   				$names[9] = 'Otros';
		   			$values[9] += (int)$res->count;
	   			}
	   		}
    		
		}
		if (!empty($values)) {
			$graph_name = elgg_echo('lrs_viewer:users');
			$path =grafic_bars($values,$names,$container_guid,$graph_name);
			$ret = array();
			array_push($ret, $path);
		}
	}elseif($choose_graf == 'ranking'){
		$vars = array('container_guid' => $container_guid,
			'time_init_dbi' => $time_init_dbi,
			'time_end_dbi' => $time_end_dbi,
			'time_init_dbe' => $time_init_dbe,
			'time_end_dbe' => $time_end_dbe,
			'choose_info' => $choose_info,
			'offset' => $offset,
			'entries' => $entries,
			'dbprefix' => $dbprefix,
			'action_type' => $action,
			'resource_type' => $subtype);
		ranking($vars);
	}
	return $ret;
}

function paint_time($container_guid,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$entries){
	global $query2,$query1,$result2,$result1,$values,$names,$size_int,$size_ext;

    if (empty($offset)) {
		$offset=0;
	}
	if(!empty($ini_date)){
		$time_init_dbi = strtotime($ini_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_init_dbi);
		$time_init_dbe = date_format($aux, 'Ymd');
	}else{
		$time_init_dbi = 0;
		$time_init_dbe = '19700101';
	}
	if(!empty($end_date)){
		$time_end_dbi = strtotime($end_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}else{
		$time_end_dbi = time();
		$aux = date_create();
		date_timestamp_set($aux,$time_end_dbi);
		$time_end_dbe = date_format($aux, 'Ymd');
	}
  
    $dbprefix = elgg_get_config('dbprefix');
    $query1 = "SELECT * from {$dbprefix}events_log where 1 and container_guid=$container_guid";
    $query2 = "SELECT * from {$dbprefix}lrs_import where 1 and group_id=$container_guid";
    if ($choose_graf=='tables') {
		get_db($ini_date,$end_date,$entries,$offset);
		
		if ($choose_info=='external') {
			$vars = array(
				'events_entries' => $result2,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_ext,
							'limit' => $entries,
						);
			echo elgg_view('page/table_externa',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
		else{
			$vars = array(
				'events_entries' => $result1,
				'container_guid_selected' => $container_guid,
				'num_rows' => $entries
				);
			$vars1 = array(
							'offset' => $offset,
							'count' => $size_int,
							'limit' => $entries,
							);
			echo elgg_view('page/table',$vars);
			echo elgg_view('navigation/pagination', $vars1);
		}
	}elseif($choose_graf == 'graphics'){
	    $array_select = array("resource_type","actor_guid","action_type");
	    $no_data = true;
	    $ret = array();
	    foreach ($array_select as $select) {
	        $query1 = "SELECT $select, COUNT($select) AS count from {$dbprefix}events_log where 1 and container_guid=$container_guid and time_created<$time_end_dbi and time_created>=$time_init_dbi GROUP BY $select";
	        $query2 = "SELECT $select, COUNT($select) AS count from {$dbprefix}lrs_import where 1 and group_id=$container_guid and time_created<$time_end_dbe and time_created>=$time_init_dbe GROUP BY $select";
	        $query3 = "SELECT $select, COUNT($select) AS count FROM ( 
	        	SELECT $select FROM {$dbprefix}events_log where 1 and container_guid=$container_guid and time_created<$time_end_dbi and time_created>=$time_init_dbi 
	        	UNION ALL 
	        	SELECT $select from {$dbprefix}lrs_import where 1 and group_id=$container_guid and time_created<$time_end_dbe and time_created>=$time_init_dbe
	        	)t GROUP BY $select";

	        $names = array();
	   		$values = array();
	   		$result = array();
		  	switch($choose_info){
		  		case 'external':
		   			$result = get_data($query2);
		    		break;
		   		case 'internal':
		   			$result = get_data($query1);
		   			break;
		   		default:
	    			$result = get_data($query3);
	       			break;
		   	}

		   	$i=0;
	    	foreach($result as $res){
	    		if($select == 'actor_guid'){
		    		$user = get_entity((int)$res->actor_guid);
		    		if($user instanceof ElggUser){
		    			if($i<10){
				   			$names[$i] = $user->getDisplayName();
				   			$values[$i] = (int)$res->count;
				   			$i++;
			   			}
			   			else{
			   				$names[9] = 'Otros';
				   			$values[9] += (int)$res->count;
			   			}
			   		}
			   	} else{
			   		$names[$i] = elgg_echo('lrs_viewer:' . strtolower($res->$select));
   					$values[$i] = (int)$res->count;
   					$i++;
			   	}
	    		
			}
	    

		    if (!empty($values)) {    
		       	$no_data = false;
		       	$graph_name = elgg_echo('lrs_viewer:' . $select);
		        $path = grafic_circle($values,$names,$container_guid,$graph_name);
		        array_push($ret, $path);
		    }
		}
	}elseif($choose_graf == 'ranking'){
		$vars = array('container_guid' => $container_guid,
			'time_init_dbi' => $time_init_dbi,
			'time_end_dbi' => $time_end_dbi,
			'time_init_dbe' => $time_init_dbe,
			'time_end_dbe' => $time_end_dbe,
			'choose_info' => $choose_info,
			'offset' => $offset,
			'entries' => $entries,
			'dbprefix' => $dbprefix);
		ranking($vars);
	}
	return $ret;
}

function grafic_circle($values,$names,$group_guid,$title=''){

	// Create the Pie Graph. 
	$graph = new PieGraph(1000,600);

	// Set A title for the plot
	$graph->title->Set($title);
	$graph->title->SetFont(FF_DEFAULT,FS_NORMAL,20);
	$graph->SetBox(true);

	// Create
	$p1 = new PiePlot($values);
	$graph->Add($p1);

	$p1->SetColor('black');
	$p1->SetLegends($names);
	$p1->SetSize(0.4);
	$p1->SetLabelPos(0.5);
	$graph->legend->SetPos(0.10,0.5,'right','center');
	$graph->legend->SetColumns(1);
	$graph->legend->SetFont(FF_DEFAULT,FS_NORMAL,16);
	$p1->SetCenter(0.3,0.5);

	$path = elgg_get_data_path();	
	$directory = $path . 'lrs_viewer';
	
	$old_pass = elgg_get_config('pass');

	$pass = substr(md5(uniqid(mt_rand(), true)) , 0, 8);
	if(!empty($old_pass)){
		$aux= array();
		if(gettype($old_pass)=='array'){
			foreach ($old_pass as $value) {
				array_push($aux, $value);
			}
		}
		else
			array_push($aux,$old_pass);
		array_push($aux,$old_pass);
		array_push($aux, $group_guid.'/'.$pass.'.png');
		elgg_save_config('pass', $aux);
	}else{

		elgg_save_config('pass', $group_guid.'/'.$pass.'.png');
	}

	$dir = $directory . "/" . $group_guid;
	if (!file_exists($dir)) {
		mkdir($dir, 0755, true);
	}

	$filename = $directory.'/'.$group_guid.'/' . $pass .'.png';
	$image = $graph->Stroke();

	@imagepng($image,$filename);

	
	return 'lrs_viewer/getImg/' . $group_guid . '/' . $pass . '.png';
}



function grafic_bars($values,$names,$group_guid,$graph_title=''){

	// Create the graph. These two calls are always required
	$graph = new Graph(2000,900,'auto');
	$graph->SetScale("textlin");

	$theme_class=new UniversalTheme;
	$graph->SetTheme($theme_class);

	$graph->SetBox(false);

	$graph->ygrid->SetFill(false);
	$graph->xaxis->SetTickLabels($names);
	$graph->xaxis->SetLabelAngle(60);
	$graph->xaxis->SetFont(FF_DEFAULT,FS_NORMAL,20);
	$graph->yaxis->SetFont(FF_DEFAULT,FS_NORMAL,20);
	$graph->yaxis->HideLine(false);
	$graph->yaxis->HideTicks(false,false);
	$graph->img->SetMargin(200,0,0,400);

	// Create the bar plots
	$b1plot = new BarPlot($values);
	
	// ...and add it to the graPH
	$graph->Add($b1plot);
	$graph->title->Set($graph_title);
	$graph->title->SetFont(FF_DEFAULT,FS_NORMAL,56);

	$path = elgg_get_data_path();	
	$directory = $path . 'lrs_viewer';
	
	$old_pass = elgg_get_config('pass');

	$pass = substr(md5(uniqid(mt_rand(), true)) , 0, 8);
	if(!empty($old_pass)){
		$aux= array();
		if (gettype($old_pass)=='array') {
			foreach ($old_pass as $value) {
			array_push($aux, $value);
			}
		}
		else
			array_push($aux,$old_pass);
		array_push($aux, $group_guid.'/'.$pass.'.png');
		elgg_save_config('pass', $aux);
	}else{

		elgg_save_config('pass', $group_guid.'/'.$pass.'.png');
	}
	
	$dir = $directory . "/" . $group_guid;
	if (!file_exists($dir)) {
		mkdir($dir, 0755, true);
	}
	
	$filename = $directory.'/'.$group_guid.'/' . $pass .'.png';
	
	$image = $graph->Stroke();

	@imagepng($image,$filename);
	return 'lrs_viewer/getImg/' . $group_guid . '/' . $pass . '.png';

}

function getImg($group,$img){
	header("Content-Type: image/gif");
	$base = elgg_get_data_path() . "lrs_viewer/" . $group . "/" . $img;
	$image = imagecreatefrompng($base);
	@imagepng($image);
}

function view($path){
	echo elgg_view('output/img', array(
    'src' => $path,
    'alt' => 'prueba',
    'title' => 'titulo',
    'width' => '100%',
    'height' => '100%'
    ));
}

function check_date($ini_date,$end_date){
	global $query1,$query2;

	if (!empty($ini_date)){
		$time_init = strtotime($ini_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_init);
		$time_db2 = date_format($aux, 'Ymd');
		$query1 .= ' and time_created>'.$time_init;
		$query2 .= ' and time_created>'.$time_db2;
	}
	if (!empty($end_date)){
		$time_end = strtotime($end_date . ' 00:00:00 UTC+1');
		$aux = date_create();
		date_timestamp_set($aux,$time_end);
		$time_db2 = date_format($aux, 'Ymd');
		$query1 .= ' and time_created<'.$time_end;
		$query2 .= ' and time_created<'.$time_db2;
	}
}

function get_db($init_date,$end_date,$limit,$offset=''){
	global $query1,$query2,$result1,$result2,$size_int,$size_ext;
	$result1 = array();
	$result2 = array();
	$size_ext = $size_int = 0;

	check_date($init_date,$end_date);
	$new_str1 = str_replace('*', 'count(*) as count', $query1);
	$new_str2 = str_replace('*', 'count(*) as count', $query2);
	if($limit!=0){
		if(empty($offset))
			$offset = 0;		
		$query1.=" ORDER BY time_created DESC LIMIT {$limit} OFFSET ".$offset;
		$query2.=" ORDER BY time_created DESC LIMIT {$limit} OFFSET ".$offset;
	}
	$result1= get_data($query1);
	$result2 = get_data($query2);
	$resp = get_data($new_str1);
	foreach ($resp as $key => $value) {
		$size_int=$value->count;
	}
	$resp = get_data($new_str2);
	foreach ($resp as $key => $value) {
		$size_ext = $value->count;
	}	
}

function create_floder_is_not_exist(){
	$page_owner = elgg_get_page_owner_entity();
    $group_guid = $page_owner->getGUID(); //GUID del grupo
    $directory_root =  elgg_get_plugins_path() ."lrs_viewer/graphics/".$group_guid."/";  
    if (!file_exists($directory_root))
         mkdir($directory_root, 0755);
}
/**
*obtiene la cantidad de resultados por cada usuario ,tipo de dato etc.
*/
function get_arrays($choose_info,$aux){
	global $data1,$data2,$result1,$result2;
	$data1 = array();
	$data2 = array();
	switch ($choose_info) {
		case 'internal':
						foreach ($result1 as $value) {
							if(!empty($value->$aux)){
								if(isset($data1[$value->$aux]))
									$data1[$value->$aux]++;
								else
									$data1[$value->$aux]=1;
									
								}
						}		
						break;
		case 'external':
						foreach ($result2 as $value) {
							if(!empty($value->$aux)){
								if(isset($data2[$value->$aux]))
									$data2[$value->$aux]++;
								else
									$data2[$value->$aux]=1;
							}
						}	
						break;
		default:
				foreach ($result1 as $value) {
						if(!empty($value->$aux)){
							if(isset($data1[$value->$aux]))
								$data1[$value->$aux]++;
							else
								$data1[$value->$aux]=1;
						}
				}
				foreach ($result2 as $value) {
						if(!empty($value->$aux)){
								if(isset($data2[$value->$aux]))
									$data2[$value->$aux]++;
								else
									$data2[$value->$aux]=1;
						}
				}		
			break;
	}

	arsort($data1);
	arsort($data2);
}

/**
*obtiene los arrays de valores y nombres a partir de los arrays de datos. si la key del array de datos es el nombre opcion=1 si es un GUID opcion=0
*/
function make_arrays($opcion){
    global $data1,$data2,$values,$names;
    $values = array();
    $names  = array();


    foreach ($data1 as $key => $value) {
        if($opcion){
            array_push($names, /*'#' . $value." ".*/$key);
        }else{
            $user     = get_entity($key);
            if($user instanceof ElggUser){
            	$username = $user->getDisplayName();
            	array_push($names, /*"#" . $value." ".*/$username);
            } else{
            	$username = 'Otros';
            	array_push($names, /*"#" . $value." ".*/$username);
            }           
        }
        array_push($values, $value);
    }

    foreach ($data2 as $key => $value) {
        if($opcion){
            array_push($names, /*'#' . $value." ".*/$key);
        }else{
            $user     = get_entity($key);
            if($user instanceof ElggUser){
            	$username = $user->getDisplayName();
            	array_push($names, /*"#" . $value." ".*/$username); 
            }else{
            	$username = 'Otros';
            	array_push($names, /*"#" . $value." ".*/$username); 
            }           
        }
        array_push($values, $value);
    }

}

function group_time($choose_info){
	global $data1,$data2,$result1,$result2;
	$data1 = array();
	$data2 = array();
	$format = 'd/m/Y';
	switch ($choose_info){
		case 'internal':
						$first_day = time();
						foreach ($result1 as $value) {
							if(!empty($value->time_created)){
								$fecha = date($format,$value->time_created);
								if($value->time_created<$first_day)
									$first_day = $value->time_created;
								if(isset($data1[$fecha]))
									$data1[$fecha]++;
								else
									$data1[$fecha]=1;
							}

								$day_aux = $first_day;
								$day_aux_str = date($format,$day_aux);

							while ($day_aux_str != date($format,time())) {
								if (!isset($data1[$day_aux_str])) {
									$data1[$day_aux_str] = 0;
								}
								$day_aux = strtotime('+1 day',$day_aux);
								$day_aux_str = date($format,$day_aux);
							}
						}
						break;
		case 'external':
						foreach ($result2 as $value) {
							if(!empty($value->time_created)){
								$fecha = date($format,strtotime($value->time_created));
								if(isset($data2[$fecha]))
									$data2[$fecha]++;
								else
									$data2[$fecha] = 1;
							}
						}
						break;
		default:
	}
}


function delete_img(){
   $path       = elgg_get_data_path();
   $directory  = $path . 'lrs_viewer';
   $old_pass   = elgg_get_config('pass');
	if (!empty($old_pass)) {
		if(gettype($old_pass)=='array'){
		   foreach ($old_pass as $value) {
		   		unlink($directory.'/'. $value);	
		   }
		}else{
			unlink($directory.'/'. $old_pass);
		}
	   unset_config('pass');
	}
}

function check_users_number(){
	global $data1,$data2;

	arsort($data1);
	arsort($data2);
	if (count($data1)>21) {
		$i=0;
		$aux = 0;
		foreach ($data1 as $key => $value) {
			if ($i>19){
				$aux += $value;
				unset($data1[$key]);
			}
			$i++;
		}
		$data1[0] = $aux;
	}

	if (count($data2)>21) {
		$i=0;
		$aux = 0;
		foreach ($data2 as $key => $value) {
			if ($i>19){
				$aux += $value;
				unset($data2[$key]);
			}
			$i++;
		}
		$data2[0] = $aux;
	}
}

function getAdmins($group_guid){
	$group = get_entity($group_guid);
    if (!empty($group) && elgg_instanceof($group, "group")) {
        $options = array(
            "relationship" => "group_admin",
            "relationship_guid" => $group->getGUID(),
            "inverse_relationship" => true,
            "type" => "user",
            "limit" => false,
            "list_type" => "gallery",
            "gallery_class" => "elgg-gallery-users",
            "wheres" => array("e.guid <> " . $group->owner_guid)
        );
        //Coge todos los administradores de grupo, excepto el "dueño" del grupo
        $group_admins = elgg_get_entities_from_relationship($options);
        // add owner to the beginning of the list
        array_unshift($group_admins, $group->getOwnerEntity());
        return $group_admins;
    }
    
}


function generate_pdf($path,$path2,$title,$docname){
	$aux = false;
	$i=0;
	if(!empty($path)){
		$aux=true;
		$pdf=new PDF($title);
		$pdf->AliasNbPages();
		$pdf->SetAutoPageBreak(true,100);
		$pdf->SetFont('Times','B',20);

		$pdf->AddPage();
		$pdf->Cell(0,10,elgg_echo('lrs_viewer:internal'),0,2,'C');
		$pdf->Ln();
		foreach ($path as $value) {
			$ruta = elgg_get_data_path();
			$value = str_replace("/getImg", "", $value);
			if(($i % 2) == 0 && $i!=0) $pdf->AddPage();
			$pdf->Image($ruta.''.$value,$pdf->GetX(),$pdf->GetY(),180,100);
			$pdf->Ln(100);
			$i++;
		}
	}

	if(!empty($path2)){
		if(!$aux){
			$aux=true;
			$pdf=new PDF($title);
			$pdf->AliasNbPages();
			$pdf->SetAutoPageBreak(true,100);
			$pdf->SetFont('Times','B',15);
		}
		$pdf->AddPage();
		$pdf->Cell(0,10,elgg_echo('lrs_viewer:external'),0,2,'C');
		$pdf->Ln();
		$i=0;
		foreach ($path2 as $value) {
			$ruta = elgg_get_data_path();
			$value = str_replace("/getImg", "", $value);
			if(($i % 2) == 0 && $i!=0) $pdf->AddPage();
			$pdf->Image($ruta.''.$value,$pdf->GetX(),$pdf->GetY(),180,100);
			$pdf->Ln(100);
			$i++;
		}
	}
	if($aux){
        $pdf->Output($docname . '.pdf','D');//D para descargar,I ver online
    }else{
    	register_error('No hay datos para crear el pdf');
    }
}

function export_actor($container_guid,$actor,$ini_date,$end_date,$choose_info){
    global $query2,$query1,$result2,$result1,$values,$names,$size_int,$size_ext;

    $ret = array();

    $dbprefix = elgg_get_config('dbprefix');
    $query1 = "SELECT * from {$dbprefix}events_log where 1 and container_guid=$container_guid and actor_guid=$actor";
    $query2 = "SELECT * from {$dbprefix}lrs_import where 1 and group_id=$container_guid and actor_guid=$actor";
    
	    get_db($ini_date,$end_date,0);
	    $array_select = array("resource_type","action_type");
	    $no_data=true;
	    foreach ($array_select as $select) {

	            get_arrays($choose_info,$select);
	            make_arrays(true);
	            $i=0;
	            foreach ($names as $value) {
	            	$names[$i] = elgg_echo('lrs_viewer:'.strtolower($value));
	            	$i++;
	            }

	            if (!empty($values)) {
	            	$no_data=false;
	            	if ($select == 'resource_type')
	            		$graph_name = elgg_echo('lrs_viewer:resource_type');
	            	else
	            		$graph_name = elgg_echo('lrs_viewer:action');
	                $path = grafic_bars($values,$names,$container_guid,$graph_name);
	                array_push($ret, $path);
	            }  
	    }
	return $ret;
}

function export_time($container_guid,$ini_date,$end_date,$choose_info){
	global $query2,$query1,$result2,$result1,$values,$names,$size_int,$size_ext;

    $dbprefix = elgg_get_config('dbprefix');
    $query1 = "SELECT * from {$dbprefix}events_log where 1 and container_guid=$container_guid";
    $query2 = "SELECT * from {$dbprefix}lrs_import where 1 and group_id=$container_guid";

	    get_db($ini_date,$end_date,0);
	    $array_select = array("resource_type","actor_guid","action_type");
	    $no_data = true;
	    $ret = array();
	    foreach ($array_select as $select) {
	            get_arrays($choose_info,$select);
	            if($select == 'actor_guid')
	            	make_arrays(false);
	        	else{
	            	make_arrays(true);
	            	$i=0;
		            foreach ($names as $value) {
		            	$names[$i] = elgg_echo('lrs_viewer:'.strtolower($value));
		            	$i++;
		            }
	            }
	            if (!empty($values)) {    
	            	$no_data = false;
	                $path = grafic_bars($values,$names,$container_guid);
	                array_push($ret, $path);
	            }
	    }
	return $ret;
}


function generate_pdf_2($path,$path2,$title,$docname,$database){
	$aux = false;
	$i=0;
	if(!empty($path)){
		$aux=true;
		$pdf=new PDF($title);
		$pdf->AliasNbPages();
		$pdf->SetAutoPageBreak(true,100);
		$pdf->SetFont('Times','B',15);

		$pdf->AddPage();
		$pdf->Ln();
			$ruta = elgg_get_data_path();
			$path = str_replace("/getImg", "", $path);
			if(($i % 2) == 0 && $i!=0) $pdf->AddPage();
			$pdf->Image($ruta.''.$path,$pdf->GetX(),$pdf->GetY(),180,100);
			$pdf->Ln(100);
			$i++;

	}

	if(!empty($path2)){
		if(!$aux){
			$aux=true;
			$pdf=new PDF($title);
			$pdf->AliasNbPages();
			$pdf->SetAutoPageBreak(true,100);
			$pdf->SetFont('Times','B',15);
		}
		$pdf->AddPage();
        $text = elgg_echo('lrs_viewer:'.$database);
		$pdf->Cell(0,10,$text,0,2,'C');
		$pdf->Ln();
		$i=0;
		foreach ($path2 as $value) {
			$ruta = elgg_get_data_path();
			$value = str_replace("/getImg", "", $value);
			if(($i % 2) == 0 && $i!=0) $pdf->AddPage();
			$pdf->Image($ruta.''.$value,$pdf->GetX(),$pdf->GetY(),180,100);
			$pdf->Ln(100);
			$i++;
		}
	}
	if($aux){
        $pdf->Output($docname . '.pdf','D');//D para descargar,I ver online
    }else{
    	register_error('No hay datos para crear el pdf');
    }
}


function ranking($vars){
	
	$container_guid = $vars['container_guid'];
	$time_init_dbi = $vars['time_init_dbi'];
	$time_init_dbe = $vars['time_init_dbe'];
	$time_end_dbi = $vars['time_end_dbi'];
	$time_end_dbe = $vars['time_end_dbe'];
	$choose_info = $vars['choose_info'];
	$offset = $vars['offset'];
	$entries = $vars['entries'];
	$dbprefix = $vars['dbprefix'];

	$query1 = "SELECT actor_guid, COUNT(actor_guid) AS count  FROM {$dbprefix}events_log WHERE 1 and container_guid=$container_guid and time_created<$time_end_dbi and time_created>=$time_init_dbi ";
	$query2 = "SELECT actor_guid, COUNT(actor_guid) AS count  FROM {$dbprefix}lrs_import WHERE 1 and group_id=$container_guid and time_created<$time_end_dbe and time_created>=$time_init_dbe ";
	$query3_1 = "SELECT actor_guid FROM {$dbprefix}events_log WHERE 1 and container_guid=$container_guid and time_created<$time_end_dbi and time_created>=$time_init_dbi ";
	$query3_2 =	"SELECT actor_guid FROM {$dbprefix}lrs_import WHERE 1 and group_id=$container_guid and time_created<$time_end_dbe and time_created>=$time_init_dbe ";

	$query_count_1 = "SELECT COUNT(*) AS count FROM( SELECT actor_guid FROM {$dbprefix}events_log WHERE 1 and container_guid=$container_guid and time_created<$time_end_dbi and time_created>=$time_init_dbi ";
	$query_count_2 = "SELECT COUNT(*) AS count FROM( SELECT actor_guid FROM {$dbprefix}events_log WHERE 1 and container_guid=$container_guid and time_created<$time_end_dbi and time_created>=$time_init_dbi ";
	$query_count_3_1 = "SELECT actor_guid FROM {$dbprefix}events_log WHERE 1 and container_guid=$container_guid and time_created<$time_end_dbi and time_created>=$time_init_dbi ";
	$query_count_3_2 = "SELECT actor_guid FROM {$dbprefix}lrs_import WHERE 1 and group_id=$container_guid and time_created<$time_end_dbe and time_created>=$time_init_dbe ";

	$action_type = $vars['action_type'];
	if(!empty($action_type)){
		$query1 = $query1 . "AND action_type='$action_type' ";
		$query2 = $query2 . "AND action_type='$action_type' ";
		$query3_1 = $query3_1 . "AND action_type='$action_type' ";
		$query3_2 = $query3_2 . "AND action_type='$action_type' ";
		$query_count_1 = $query_count_1 . "AND action_type='$action_type' ";
		$query_count_2 = $query_count_2 . "AND action_type='$action_type' ";
		$query_count_3_1 = $query_count_3_1 . "AND action_type='$action_type' ";
		$query_count_3_2 = $query_count_3_2 . "AND action_type='$action_type' ";
	}
	$resource_type = $vars['resource_type'];
	if(!empty($resource_type)){
		$query1 = $query1 . "AND resource_type='$resource_type' ";
		$query2 = $query2 . "AND resource_type='$resource_type' ";
		$query3_1 = $query3_1 . "AND resource_type='$resource_type' ";
		$query3_2 = $query3_2 . "AND resource_type='$resource_type' ";
		$query_count_1 = $query_count_1 . "AND resource_type='$resource_type' ";
		$query_count_2 = $query_count_2 . "AND resource_type='$resource_type' ";
		$query_count_3_1 = $query_count_3_1 . "AND resource_type='$resource_type' ";
		$query_count_3_2 = $query_count_3_2 . "AND resource_type='$resource_type' ";
	}

	$query1 = $query1 . "GROUP BY actor_guid ORDER BY count DESC LIMIT $offset,$entries";
	$query2 = $query2 . "GROUP BY actor_guid ORDER BY count DESC LIMIT $offset,$entries";
	$query3 = "SELECT actor_guid, COUNT(actor_guid) AS count  FROM ( 
		$query3_1  
		UNION ALL 
		$query3_2)t 
		GROUP BY actor_guid ORDER BY count DESC LIMIT $ofset,$entries";

	$names = array();
	$values = array();
	$result = array();
	$result_number = array();
	switch($choose_info){
		case 'external':
			$result = get_data($query2);
			$query_count_2 = $query_count_2 . " GROUP BY actor_guid)t";
			$result_number = get_data($query_count_2);
	    		break;
	   	case 'internal':
	   		$result = get_data($query1);
	   		$query_count_1 = $query_count_1 . " GROUP BY actor_guid)t";
	   		$result_number = get_data($query_count_1);
	   		break;
	   	default:
	   		$result = get_data($query3);
	   		$query = "SELECT COUNT(*) AS count FROM( SELECT actor_guid FROM ( 
					$query_count_3_1  
					UNION ALL 
					$query_count_3_2 )t 
					GROUP BY actor_guid)r";
			$result_number = get_data($query);
	   		break;
	}

	$vars = array(
			'events_entries' => $result,
			'container_guid_selected' => $container_guid,
			'num_rows' => $entries,
			'offset' => $offset,
	);
	$vars1 = array(
				'offset' => $offset,
				'count' => $result_number[0]->count,
				'limit' => $entries,
	);
		//elgg_log('PROBANDO','ERROR');
	echo elgg_view('page/table_ranking',$vars);
	echo elgg_view('navigation/pagination', $vars1);
}