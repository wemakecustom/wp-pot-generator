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

// disable update checks for current locale since PO files are managed by composer.
add_filter('core_version_check_locale', function() {
    return 'en_US';
});

function pot_generator_admin_page()
{
    $pot_generator = get_plugin_data(__FILE__);

    require __DIR__ . '/admin/page.php';

    // $generator->export_theme('madeicitte');
    // $generator->export_plugin('gravityforms', 'gravityforms');
    return;
}

if ( defined('WP_CLI') && WP_CLI ) {
    WP_CLI::add_command( 'pot', 'WMC\Wordpress\PotGenerator\Command\PotCommand' );
}
