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
                'href' => elgg_get_site_url() . 'lrs_viewer/lrsbrowser?container_guid=' . $container_guid_selected . '&actor=' . $entry->actor_guid . '&choose_graf=tables' . '&choose_info=external&limit=' . $num_rows,
                'text' => $user->name,
                'is_trusted' => true,
            ));
        } else {
            $user_link = '&nbsp;';
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
        $nombre = explode("(", $entry->object_name);
        $object = elgg_view('output/url', array(
                // 'href' => $user->getURL(),
                'href' => $entry->url_object,
                'text' => $nombre[0],
                'is_trusted' => true,
            ));

        ?>
        <tr <?php echo $alt; ?>>
            <td>
                <?php
                echo date("d-m-Y H:i:s",strtotime($entry->time_created));
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
                <?php echo $object ?>
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