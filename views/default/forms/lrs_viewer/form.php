<?php

    $container_guid     = get_input("container_guid");//ElggGroup::getMembers
    $actor       = get_input('actor');
    $action      = get_input('action');
    $action = explode(',', $action);
    $subtype     = get_input('subtype');
    $subtype = explode(',', $subtype);
    $ini_date    = get_input('ini_date');
    $end_date    = get_input('end_date');
    $choose_info = get_input('choose_info');
    $choose_graf = get_input('choose_graf');
    $limit       = get_input('limit');
    if (empty($limit) || $limit == 0){
        $limit = 10;
    }
    $entidad 		    = get_entity($container_guid);
    $members      		= $entidad->getMembers(array('limit' => 2000));

    foreach ($members as $aux) {
    	$actor_list[$aux->guid] = $aux->name;
    }

    function callback($name1,$name2){
        $patterns = array(
            'a' => '(á|à|â|ä|Á|À|Â|Ä)',
            'e' => '(é|è|ê|ë|É|È|Ê|Ë)',
            'i' => '(í|ì|î|ï|Í|Ì|Î|Ï)',
            'o' => '(ó|ò|ô|ö|Ó|Ò|Ô|Ö)',
            'u' => '(ú|ù|û|ü|Ú|Ù|Û|Ü)'
        );          
        $name1 = preg_replace(array_values($patterns), array_keys($patterns), $name1);
        $name2 = preg_replace(array_values($patterns), array_keys($patterns), $name2);          
        return strcasecmp($name1, $name2);
    }
    uasort($actor_list,"callback");

    $none = array(0 => elgg_echo('lrs_viewer:all'),
        'no_admins' => elgg_echo('lrs_viewer:no_admins'));

    $actor_list = $none + $actor_list;


    $form    = "<br><h3>".elgg_echo('lrs_viewer:search')."</h3><br>";

    $username_name = elgg_echo('lrs_viewer:username');
    $username_values = elgg_view('input/dropdown', array(
                                                 'name'           => 'actor',
                                                 'value'          => $actor,
                                                 'options_values' => $actor_list,
                                             ));


    $actions = array(
        elgg_echo("lrs_viewer:viewed") => 'viewed',
        elgg_echo("lrs_viewer:uploaded") => 'uploaded',
        // 'logged'=> elgg_echo("lrs_viewer:logged"), 
        elgg_echo("lrs_viewer:responsed") => 'responsed',
        elgg_echo("lrs_viewer:created") => 'created',
        elgg_echo("lrs_viewer:updated") => 'updated',
        elgg_echo("lrs_viewer:download") => 'download',
        elgg_echo("lrs_viewer:removed") => 'removed',
        elgg_echo("lrs_viewer:commented") => 'commented',
        elgg_echo("lrs_viewer:liked") => 'liked',
        elgg_echo("lrs_viewer:unliked") => 'unliked',
        // elgg_echo("lrs_viewer:followed") => 'followed',
        // 'unfollowed'=>elgg_echo("lrs_viewer:unfollowed")
    );   
    uasort($actions,"callback");

    $action_name = elgg_echo('lrs_viewer:action');
    $action_values = elgg_view('input/checkboxes', array(
                                                  'name'    => 'action[]',
                                                  'value'   => $action,
                                                  'options' => $actions,
                                                  'align' => 'vertical',
                                                ));


    $subtypes = array();

    // Set options from group tool options
    $tool_options = elgg_get_config('group_tool_options');
    if ($tool_options) {
        foreach ($tool_options as $group_option) {
            $option_toggle_name = $group_option->name . "_enable";
            if ($entidad->$option_toggle_name == 'yes'){
                switch($group_option->name){
                    case "blog":
                        $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:blog") => 'blog'));
                        break;
                    case "file":
                        $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:file") =>'file'));
                        break;
                    case "questions":
                        $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:question") =>'question'));
                        break;
                    case "forum":
                        $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:groupforumtopic") =>'groupforumtopic'));
                        break;
                    case "bookmarks":
                        $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:bookmarks") =>'bookmarks'));
                        break;
                    case "task":
                        $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:task") =>'task'));
                        break;
                    case "form":
                        $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:form") =>'form'));
                        break;
                    case "event_manager":
                        $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:event") =>'event'));
                        break;
                    case "pages":
                        $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:page_top") =>'page_top'));
                        break;
                    case "contest":
                        $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:contest") =>'contest' ));
                        break;
                    case "e_portfolio":
                        $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:e_portfolio") =>'e_portfolio'));
                        break;
                }
            }
        }
    }
    $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:thewire") =>'thewire'));
    $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:test") =>'test'));
    $subtypes = array_merge($subtypes, array(elgg_echo("lrs_viewer:poll") =>'poll'));

    uasort($subtypes,"callback");

    $subtypes_name = elgg_echo('lrs_viewer:subtype');
    $subtypes_value = elgg_view('input/checkboxes', array(
                                                  'name' => 'subtype[]',
                                                  'value' => $subtype,
                                                  'options' => $subtypes,
                                                  'align' => 'vertical',
                                                ));


    $ini_time_name = elgg_echo('lrs_viewer:ini_time');
    $ini_time_value = elgg_view("input/date",array(
                                                'autocomplete'  =>'off',
                                                'class'         =>'compressed-date',
                                                "name"          => "ini_date",
                                                "value"         => $ini_date
                                            ));


    $end_time_name = elgg_echo('lrs_viewer:end_time');
    $end_time_value = elgg_view("input/date",array(
                                                'autocomplete'  =>'off',
                                                'class'         =>'compressed-date',
                                                "name"          => "end_date",
                                                "value"         => $end_date
                                            ));


    $chooses_info    = array('internal'=>elgg_echo("lrs_viewer:internal"), 'external'=>elgg_echo("lrs_viewer:external"));


    $choose_info_name = elgg_echo('lrs_viewer:choose_DB');
    $choose_info_value = elgg_view('input/dropdown', array(
                                                            'name'    => 'choose_info',
                                                            'value'   => $choose_info,
                                                            'options_values' => $chooses_info,
                                                        ));


    $chooses_graf = array('tables'=>elgg_echo("lrs_viewer:tables"), 'graphics'=>elgg_echo("lrs_viewer:graphics"), 'ranking' => elgg_echo("lrs_viewer:ranking"));


    $choose_graf_name = elgg_echo('lrs_viewer:choose_Gr');
    $choose_graf_value = elgg_view('input/dropdown', array(
                                                        'name'    => 'choose_graf',
                                                        'value'   => $choose_graf,
                                                        'options_values' => $chooses_graf,
                                                    ));


    $numbers = array('10'=>'10', '30'=>'30', '50'=>'50', '100'=>'100', '200' => '200');

    $numbers_name = elgg_echo('lrs_viewer:number_results');
    $numbers_value = elgg_view('input/dropdown', array(
                                                        'name'    => 'limit',
                                                        'value'   => $limit,
                                                        'options_values' => $numbers,
                                                    ));
   

    $container       = elgg_view('input/hidden', array(
                                                    'name'  => 'container_guid',
                                                    'value' => $container_guid,
                                                ));

    $submit       = elgg_view('input/submit', array(
                                                    'value' => elgg_echo('search'),
                                                ));

    $form .= "<table align='center' width='100%'>
                <tr>
                <td style='height:50px;'>$username_name    $username_values</td>
                <td><h3>$action_name</h3></td>
                <td><h3>$subtypes_name</h3></td></tr>
                <tr>
                <td style='height:50px;'>$ini_time_name    $ini_time_value</td>
                <td rowspan='14'>$action_values</td>
                <td rowspan='14'>$subtypes_value</td></tr>
                <tr>
                <td style='height:50px;'>$end_time_name    $end_time_value</td></tr>
                <tr border-spacing='10'>
                <td style='height:50px;'>$choose_info_name    $choose_info_value</td></tr>
                <tr>
                <td style='height:50px;'>$choose_graf_name    $choose_graf_value</td></tr>
                <tr>
                <td style='height:50px;'>$numbers_name    $numbers_value</td></tr>
                <tr>
                <td style='height:50px;'>$submit    $container</td></tr>
                </table>";



    $form       .= '<br><br>';

echo $form;
