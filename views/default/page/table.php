<?php
/**
 * Events browser table
 *
 */
//tr -> filas
//th -> columnas primera fila
//td -> resto de columnas

$events_entries = $vars['events_entries'];
$container_guid_selected = $vars['container_guid_selected'];
$num_rows = $vars['num_rows'];
?>

<table class="elgg-table">
    <tr>
        <th><?php echo elgg_echo('events_collector:time_created'); ?></th>
        <th><?php echo elgg_echo('events_collector:actor_guid'); ?></th>
        <th><?php echo elgg_echo('events_collector:action_type'); ?></th>
        <th><?php echo elgg_echo('events_collector:resource_type'); ?></th>
        <th><?php echo elgg_echo('events_collector:resource_guid'); ?></th>
    </tr>
    <?php
    $alt = '';
    foreach ($events_entries as $entry) {
        $user = get_entity($entry->actor_guid);
        if ($user) {
            $user_link = elgg_view('output/url', array(
                // 'href' => $user->getURL(),
                'href' => elgg_get_site_url() . 'lrs_viewer/lrsbrowser?container_guid=' . $container_guid_selected . '&actor=' . $entry->actor_guid . '&choose_graf=tables&limit=' . $num_rows,
                'text' => $user->name,
                'is_trusted' => true,
            ));
        } else {
            $user_guid_link = $user_link = '&nbsp;';
        }

        $container = get_entity($entry->container_guid);
        if ($container instanceof ElggGroup) {//Si no está seleccionado perfil
            $container = get_entity($entry->container_guid);

            if ($container) {
                $container_link = elgg_view('output/url', array(
                    'href' => $container->getURL(),
                    'text' => $container->name,
                    'is_trusted' => true,
                ));
                $container_guid_link = elgg_view('output/url', array(
                    'href' => "admin/administer_utilities/logbrowser?user_guid={$container->guid}",
                    'text' => $container->getGUID(),
                    'is_trusted' => true,
                ));
            } else {
                $container_guid_link = $container_link = '&nbsp;';
            }
        }
        $resource = get_entity($entry->resource_guid);
        if ($resource instanceof ElggUser) {
            if (is_callable(array($resource, 'getURL'))) {
                $resource_link = elgg_view('output/url', array(
                    'href' => $resource->getURL(),
                    'text' => $resource->name, 
                    'is_trusted' => true,
                ));
            } else {
                $resource_link = 'events_collector:resource:not_available';
            }
        } else {
            if (is_callable(array($resource, 'getURL'))) {
                if($entry->resource_type =='form_question' || $entry->resource_type == 'form_answer'){
                    $options = array(                        
                        'owner'=> $entry->actor_guid,
                        'relationship_guid' => $entry->resource_guid,
                        'inverse_relationship' => true,
                    );
                    $entity_r = elgg_get_entities_from_relationship (array_merge($options, array( 'relationship' => $entry->resource_type)));
                    $resource = get_entity($entity_r[0]->guid);
                }
                $resource_link = elgg_view('output/url', array(
                    'href' => $resource->getURL(),
                    'text' => $resource->title, 
                    'is_trusted' => true,
                ));
            } else {
                $resource_link = 'events_collector:resource:not_available';
            }
        }
        ?>
        <tr <?php echo $alt; ?>>
            <td>
                <?php
                if (date_default_timezone_set('Europe/Madrid')) {
                    echo date("d-m-Y H:i:s", $entry->time_created);
                } else {
                    echo "The timezone_identifier used in the function date_default_timezone_set isn't valid";
                }
                ?>
            </td>
            <td>
                <?php
                echo elgg_view_entity_icon($user, 'tiny');
                echo "<h3>$user_link</h3>"; 
                ?>
            </td>
            <td>
                <?php echo elgg_echo('lrs_viewer:' . strtolower($entry->action_type)); ?>
            </td>
            <td>
                <?php echo elgg_echo('lrs_viewer:' . $entry->resource_type); ?>
            </td>
            <td>
                <?php echo elgg_echo($resource_link); ?>
            </td>
            
        </tr>
    <?php
    $alt = $alt ? '' : 'class="alt"';
}
?>
</table>
    <?php
    $num_rows = $vars['num_rows'];
    if ($num_rows == 0) {
        echo elgg_echo('events_collector:no_result');
        return true;
    }