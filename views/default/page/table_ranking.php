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
$offset = $vars['offset'];
if(empty($offset))
    $offset = 0;
?>

<table class="lrs_viewer_rank-table">
    <tr>
        <th><?php echo elgg_echo('lrs_viewer:actor_guid'); ?></th>
        <th><?php echo elgg_echo('lrs_viewer:times'); ?></th>
    </tr>
    <?php
    $alt = '';
    $i = 1;
    foreach ($events_entries as $entry) {
        $user = get_entity($entry->actor_guid);
        if ($user instanceof ElggUser) {
            $user_link = elgg_view('output/url', array(
                // 'href' => $user->getURL(),
                'href' => elgg_get_site_url() . 'lrs_viewer/lrsbrowser?container_guid=' . $container_guid_selected . '&actor=' . $entry->actor_guid . '&choose_graf=tables&limit=' . $num_rows,
                'text' => $user->name,
                'is_trusted' => true,
            ));
        } else {
            $user_guid_link = $user_link = '&nbsp;';
        }

        $count = $entry->count;
        
        ?>
        <tr <?php echo $alt; ?>>
            <td>
                <?php
                $j = $offset + $i;
                echo "<h3><b>$j. </b>".elgg_view_entity_icon($user, 'small')."</h3><h3> $user_link</h3>";
                $i++;
                ?>
            </td>
            <td>
                <?php echo elgg_echo($count); ?>
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