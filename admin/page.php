<?php

require_once __DIR__ . '/list.php';

    if (!empty($_POST['export'])) {
        $messages = array();

        foreach ($_POST['export'] as $type => $items) {
            foreach (array_keys($items) as $id) {
                $class = "WMC\Wordpress\PotGenerator\\$type";
                if (class_exists($class)) {
                    $item = new $class($id);
                    $item->export();
                    $messages[] = "$type {$item->name} has been exported";
                }
            }
        }

        if ($messages) {
            echo
            "<div class=\"updated\">
                <p>".implode('</p><p>', $messages)."</p>
            </div>";
        }
    }
?>

<div class="wrap">
<?php screen_icon(); ?>
<h2><?php _e($pot_generator['Name'], 'pot-generator'); ?></h2>

<p>
    <?php _e($pot_generator['Description'], 'pot-generator'); ?>
</p>

<form method="post">
<?php
    $wp_list_table = new POT_Generator_Table;
    $wp_list_table->prepare_items();
    $wp_list_table->display();
?>

</form>
</div>
