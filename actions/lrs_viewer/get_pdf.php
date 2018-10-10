<?php
	
	$container_guid = get_input('container_guid');
	$actor = get_input('actor');
	$action = get_input('action');
	$subtype = get_input('subtype');
	$init_date = get_input('ini_date');
	$end_date = get_input('end_date');
    $choose_info = get_input('choose_info');

	$aux = ""; 

	if(!empty($actor))
        $aux .= "actor/";
    if(!empty($action))
        $aux .= "action/";
    if(!empty($subtype))
        $aux .= "subtype/";


    switch($aux){

    	case 'actor/':
                                        $path = export_actor($container_guid,$actor,$ini_date,$end_date,'internal');
                                        $path2 = export_actor($container_guid,$actor,$ini_date,$end_date,'external');
                                        $user = get_entity($actor);
                                        $username = $user->getDisplayName();
                                        $title = elgg_echo('lrs_viewer:staticsfor') . " " . $username;
                                        generate_pdf($path,$path2,$title,$username);
                                        break;
        case 'action/':
                                        $path = paint_action($container_guid,$action,$ini_date,$end_date,'internal','graphics',$offset);
                                        $path2 = paint_action($container_guid,$action,$ini_date,$end_date,'external','graphics',$offset);
                                        $title = elgg_echo('lrs_viewer:staticsfor') . " " . strtolower(elgg_echo('lrs_viewer:'.$action));
                                        generate_pdf($path,$path2,$title,$action);
                                        break;
        case 'subtype/':
                                        $path = paint_subtype($container_guid,$subtype,$ini_date,$end_date,'internal','graphics',$offset);
                                        $path2 = paint_subtype($container_guid,$subtype,$ini_date,$end_date,'external','graphics',$offset);
                                        $title = elgg_echo('lrs_viewer:staticsfor') . " " . strtolower(elgg_echo('lrs_viewer:'.$subtype));
                                        generate_pdf($path,$path2,$title,$subtype);
                                        break;
        case 'actor/action/':
                                        $path = paint_actor_action($container_guid,$actor,$action,$ini_date,$end_date,'internal','graphics',$offset);
                                        $path2 = paint_actor_action($container_guid,$actor,$action,$ini_date,$end_date,'external','graphics',$offset);
                                        $title = elgg_echo('lrs_viewer:staticsfor') . " " . $actor . " " . strtolower(elgg_echo('lrs_viewer:'. $action));
                                        $doctitle = $actor . "_" . $action;
                                        generate_pdf($path,$path2,$title,$doctitle);
                                        break;
        case 'actor/subtype/':
                                        $path = paint_actor_subtype($container_guid,$actor,$subtype,$ini_date,$end_date,'internal','graphics',$offset);
                                        $path2 = paint_actor_subtype($container_guid,$actor,$subtype,$ini_date,$end_date,'external','graphics',$offset);
                                        $user = get_entity($actor);
                                        $username = $user->getDisplayName();
                                        $title = elgg_echo('lrs_viewer:staticsfor') . " " . $username . " " . strtolower(elgg_echo('lrs_viewer:'. $subtype));
                                        $doctitle = $username . "_" . $subtype;
                                        generate_pdf($path,$path2,$title,$doctitle);
                                        break;
        case 'actor/action/subtype/':
                                        $path = paint_actor_action_subtype($container_guid,$actor,$action,$subtype,$ini_date,$end_date,'internal','graphics',$offset);
                                        $path2 = paint_actor_action_subtype($container_guid,$actor,$action,$subtype,$ini_date,$end_date,'external','graphics',$offset);
                                        $user = get_entity($actor);
                                        $username = $user->getDisplayName();
                                        $title = elgg_echo('lrs_viewer:staticsfor') . " " . $username . " " . strtolower(elgg_echo('lrs_viewer:'.$action)) . " " . strtolower(elgg_echo('lrs_viewer:'.$subtype));
                                        $doctitle = $username . "_" . $action . "_" .$subtype;
                                        generate_pdf($path,$path2,$title,$doctitle);
                                        break;
        case 'action/subtype/':
                                        $path = paint_action_subtype($container_guid,$action,$subtype,$ini_date,$end_date,'internal','graphics',$offset);
                                        $path2 = paint_action_subtype($container_guid,$action,$subtype,$ini_date,$end_date,'external','graphics',$offset);
                                        $title = elgg_echo('lrs_viewer:staticsfor') . " " . strtolower(elgg_echo('lrs_viewer:'. $action)) . " " . strtolower(elgg_echo('lrs_viewer:'. $subtype));
                                        $doctitle = $action . "_" . $subtype;
                                        generate_pdf($path,$path2,$title,$doctitle);
                                        break;                            
        default:
                    $actividad = obtain_10days_activity($container_guid);

                    $names = array();
                    $time = time();
                    $format = 'd/m/Y';
                    $day = date($format,$time);
                    for ($i=0; $i <11 ; $i++) { 
                       array_push($names, $day);
                       $time = strtotime('-1 day',$time);
                       $day = date($format,$time);
                    }
                    $names = array_reverse($names);

                    $path = grafica_actividad($actividad,$names,$container_guid,elgg_echo('lrs_viewer:10_days_activity'));
                    $path2 = export_time($container_guid,$ini_date,$end_date,$choose_info);
                    $group = get_entity($container_guid);
                    $title = elgg_echo('lrs_viewer:staticsfor') . " " . $group->getDisplayName();
                    $doctitle = $group->getDisplayName();
                    generate_pdf_2($path,$path2,$title,$doctitle,$choose_info);
                    break;

    }

    delete_img();
?>