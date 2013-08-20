<?php
/**
 * Plugin Name: POT Generator
 * Description: Allows to generate POT for plugins and themes and compile PO to MO.
 * Version: 0.1
 */

add_action('admin_menu', function(){
    add_management_page(
        __('POT Generator','pot-generator'),
        __('POT Generator','pot-generator'),
        'manage_options',
        'pot-generator',
        'pot_generator_admin_page'
    );
});


function pot_generator_admin_page() {
    $pot_generator = get_plugin_data(__FILE__);

    require __DIR__ . '/admin/page.php';

    // $generator->export_theme('madeicitte');
    // $generator->export_plugin('gravityforms', 'gravityforms');
    return;
}
