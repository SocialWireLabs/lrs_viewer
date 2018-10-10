<?php

$path = elgg_get_plugins_path();

require_once $path . 'lrs_viewer/lib/functions.php';

$container_guid = get_input('container_guid');
$actor       = get_input('actor');
$actions      = get_input('action');
$subtypes     = get_input('subtype');
$ini_date    = get_input('ini_date');
$end_date    = get_input('end_date');
$choose_info = get_input('choose_info');
$choose_graf = get_input('choose_graf');
$offset      = get_input('offset');
$limit       = get_input('limit');


// $activos = usuarios_mas_activos($container_guid);
// tabla_activos($activos, $container_guid, $limit);

$img_template = '<img border="0" width="30" height="30" alt="%s" title="%s" src="'.elgg_get_site_url().'mod/lrs_viewer/graphics/%s" />';
      $url_get_pdf=elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/lrs_viewer/get_pdf?container_guid=".$container_guid.'&actor='.$actor.'&action='.$actions.'&subtype='.$subtypes.'&ini_date='.$ini_date.'&end_date='.$end_date.'&choose_info='.$choose_info);
      $text_get_pdf=elgg_echo("lrs_viewer:get_pdf");
      $img_get_pdf = sprintf($img_template,$text_get_pdf,$text_get_pdf,"pdf-icon.png");
      $link_get_pdf="<a href=\"{$url_get_pdf}\">{$img_get_pdf}</a>";
      echo $link_get_pdf;

$img_template = '<img border="0" width="30" height="30" alt="%s" title="%s" src="'.elgg_get_site_url().'mod/lrs_viewer/graphics/%s" />';
      $url_get_csv=elgg_add_action_tokens_to_url(elgg_get_site_url() . "action/lrs_viewer/get_csv?container_guid=".$container_guid);
      $text_get_csv=elgg_echo("lrs_viewer:get_csv");
      $img_get_csv = sprintf($img_template,$text_get_csv,$text_get_csv,"csv_icon.jpeg");
      $link_get_csv="<a href=\"{$url_get_csv}\">{$img_get_csv}</a>";
      echo $link_get_csv;

refinar($actor,$action,$subtype,$ini_date,$end_date,$choose_info,$choose_graf);
delete_img();
// si no hay nada marcado
if(empty($actor) && empty($action) && empty($subtype) && empty($ini_date) && empty($end_date) && empty($choose_graf)){

  create_floder_is_not_exist();
  elgg_log('AQUI ESTOY','ERROR');
  paint_time($container_guid,$ini_date,$end_date,"internal","tables",$offset,50);

}
else{

    
    $aux = ""; 

	if(!empty($actor))
        $aux .= "actor/";
    if(!empty($actions))
        $aux .= "action/";
    if(!empty($subtypes))
        $aux .= "subtype/";
  elgg_log($aux,'ERROR');
  $action = explode(',', $actions);
  $subtype = explode(',', $subtypes); 

    switch ($aux) {
        case 'actor/':
                                        $path = paint_actor($container_guid,$actor,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$limit);
                                        break;
        case 'action/':
                                        $path = paint_action($container_guid,$action,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$limit);
                                        break;
        case 'subtype/':
                                        $path = paint_subtype($container_guid,$subtype,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$limit);
                                        break;
        case 'actor/action/':
                                        $path = paint_actor_action($container_guid,$actor,$action,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$limit);
                                        break;
        case 'actor/subtype/':
                                        $path = paint_actor_subtype($container_guid,$actor,$subtype,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$limit);
                                        break;
        case 'actor/action/subtype/':
                                        $path = paint_actor_action_subtype($container_guid,$actor,$action,$subtype,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$limit);
                                        break;
        case 'action/subtype/':
                                        $path = paint_action_subtype($container_guid,$action,$subtype,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$limit);
                                        break;                            
        default:
                    $path = paint_time($container_guid,$ini_date,$end_date,$choose_info,$choose_graf,$offset,$limit);
                    break;
    }

    if(!empty($path)){
      foreach ($path as $value) {
        view($value);
      }
    }else if($choose_graf=='graphics'){
       echo "<br><br>";
       echo elgg_echo('lrs_viewer:no_data');
    }

}
?>