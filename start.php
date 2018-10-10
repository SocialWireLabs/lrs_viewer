<?php

elgg_register_event_handler('init', 'system', 'lrs_viewer_init');

$path = elgg_get_plugins_path();
require_once $path . 'lrs_viewer/lib/functions.php';

$path = elgg_get_plugins_path();
require_once $path . 'lrs_viewer/vendor/jpgraph/src/jpgraph.php';
require_once $path . 'lrs_viewer/vendor/jpgraph/src/jpgraph_bar.php';
require_once $path . 'lrs_viewer/vendor/jpgraph/src/jpgraph_line.php';
require_once $path . 'lrs_viewer/vendor/jpgraph/src/jpgraph_pie.php';
require_once $path . 'lrs_viewer/vendor/jpgraph/src/jpgraph_pie3d.php';

function lrs_viewer_init() {

    if (elgg_is_active_plugin('events_collector')) {
        elgg_unregister_plugin_hook_handler('register', 'menu:owner_block', 'events_collector_owner_block_menu');
        elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'lrs_events_collector_owner_block_menu');
    }

    elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'lrs_owner_block_menu');  
    elgg_register_admin_menu_item('administer', 'lrs_viewer', 'statistics');
    elgg_register_page_handler('lrs_viewer', 'lrs_viewer_page_handler');

    $action_base = elgg_get_plugins_path() . 'lrs_viewer/actions/lrs_viewer';
    elgg_register_action('lrs_viewer/form',$action_base.'/form.php');

    elgg_extend_view('css/elgg', 'mytheme/css');

    $action_base = elgg_get_plugins_path() . 'lrs_viewer/actions/lrs_viewer';
    elgg_register_action("lrs_viewer/get_pdf","$action_base/get_pdf.php");    
    elgg_register_action("lrs_viewer/get_csv","$action_base/get_csv.php");    

    }

function lrs_owner_block_menu($hook, $type, $return, $params) {
    if (elgg_instanceof($params['entity'], 'group')) {
        $page_owner = elgg_get_page_owner_entity();
        $group_guid = $page_owner->getGUID(); //GUID del grupo
        $user = elgg_get_logged_in_user_entity();
        if ($user instanceof ElggUser)
            if (events_collector_is_logged_in_group_admin($group_guid) || $user->isAdmin()) {
                $url = "lrs_viewer/lrsbrowser?container_guid={$group_guid}";
                $item = new ElggMenuItem('lrs_viewer_group_events', elgg_echo('lrs_viewer:activity'), $url);
                $return[] = $item;
            }
    }
    return $return;
}

function lrs_events_collector_owner_block_menu($hook, $type, $return, $params) {
    $user = elgg_get_logged_in_user_entity();
    if (elgg_instanceof($params['entity'], 'group')) {
        $page_owner = elgg_get_page_owner_entity();
        $group_guid = $page_owner->getGUID(); //GUID del grupo
        if ($user instanceof ElggUser)
            if (events_collector_is_logged_in_group_admin($group_guid) || $user->isAdmin()) {
                $url2 = "events_collector/members/{$group_guid}";
                $item2 = new ElggMenuItem('events_collector_group_members', elgg_echo('events_collector:members:recent_activity'), $url2);
                $return[] = $item2;
            }
    } else {
        if ($user instanceof ElggUser)
            if (elgg_instanceof($params['entity'], 'user') && $user->isAdmin()) {
                $user_guid = elgg_get_logged_in_user_guid();
                $url = "events_collector/eventsbrowser?user_guid={$user_guid}";
                $item = new ElggMenuItem('events_collector_user_events', elgg_echo('events_collector:user_button'), $url);
                $return[] = $item;
            }
    }
    return $return;
}

function lrs_viewer_page_handler($page, $identifier){
    switch($page[0]){
        case 'export':
            if (count($page) > 1) {
                if (strcmp($page[1], 'csv') == 0) {
                    $limit = get_input('limit', 20);
                    $offset = get_input('offset');
                    $limit = 0;
                    //Cogiendo parámetros
                    $user_guid = get_input('user_guid', null);
                    $timelower = get_input('timelower');
                    if ($timelower) {
                        $timelower = strtotime($timelower);
                    }
                    $timeupper = get_input('timeupper');
                    if ($timeupper) {
                        $timeupper = strtotime($timeupper);
                    }
                    $container_guid = get_input('container_guid', null);
                    $action_type = get_input('action_type');
                    $resource_type = get_input('resource_type');

                    events_collector_admin_gatekeeper($container_guid,$user_guid);
                    $query = "SHOW COLUMNS FROM ". elgg_get_config('dbprefix') . "events_log";
                    $result = get_data($query);
                    $columns = array();
                    $i = 0;
                    foreach ($result as $column) {
                        $salida_cvs .= $column->Field . ",";
                        $columns[$i] = $column->Field;
                        $i++;
                    }
                    $salida_cvs .= "\n";
                    $values = get_events($user_guid, "", "", "", "", $limit, $offset, false, $timeupper, $timelower, 0, $action_type, $resource_type, $container_guid);
                    foreach ($values as $entry) {
                        //For del tamaño del array
                        foreach ($columns as $column_name) {
                            if ($column_name == 'tags' || $column_name == 'categories') {
                                $salida_cvs .= "\"" . $entry->$column_name . "\",";
                            } else {
                                $salida_cvs .= $entry->$column_name . ",";
                            }
                        }
                        $salida_cvs .= "\n";
                    }
                    header("Content-type: application/vnd.ms-excel");
                    header("Content-disposition: csv" . date("Y-m-d") . ".csv");
                    header("Content-disposition: filename=export_events.csv");
                    print $salida_cvs;
                    exit;
                }
            }
            break;

        case 'getImg':
            getImg($page[1],$page[2]);
            break;

        default:
            $title = elgg_echo('lrs_viewer:' . $page[0]);
            $container_guid = get_input('container_guid');
            $resource_guid = get_input('resource_guid');
            elgg_set_page_owner_guid($container_guid);
            $group = get_entity($container_guid);
            $user = elgg_get_logged_in_user_entity();
            if (!$group || !elgg_instanceof($group, 'group') || !(events_collector_is_logged_in_group_admin($container_guid) || $user->isAdmin())) {
                forward();
            }
            //elgg_gatekeeper();
            elgg_group_gatekeeper();
            $vars = array(
                    'container_guid' => $container_guid,
                    'resource_guid' => $resource_guid,
                );
            $content = elgg_view('page/lrsbrowser',$vars);

            $params = array(
                'content' => $content,
                'title' => $title,
                'filter' => '',
                );

            $body = elgg_view_layout('content',$params);

            echo elgg_view_page($title,$body);
            break;
    }

}