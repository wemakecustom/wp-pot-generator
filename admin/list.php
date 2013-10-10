<?php

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

use WMC\Wordpress\PotGenerator\Translatable;
use WMC\Wordpress\PotGenerator\Core;
use WMC\Wordpress\PotGenerator\Theme;
use WMC\Wordpress\PotGenerator\Plugin;

class POT_Generator_Table extends WP_List_Table
{
    /**
     * Constructor, we override the parent to pass our own arguments
     * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
     */
     function __construct()
     {
         parent::__construct( array(
            'singular'=> 'theme', //Singular label
            'plural' => 'themes', //plural label, also this well be one of the table css class
            'ajax'  => false //We won't support Ajax for this table
            )
         );
     }

    // function get_table_classes() {
    //     return array('widefat', 'wp-list-table', 'themes');
    // }

    public function get_columns()
    {
        $columns = array(
            'cb'      => '<input type="checkbox" />',
            'type'    => __('Type'),
            'name'    => __('Name'),
            'strings' => __('Strings'),
        );

        $languages = Translatable::getLanguages();
        foreach ($languages as $code => $locale) {
            $columns["lang_{$code}"] = $code;
        }

        $columns['actions'] = __('Actions');

        return $columns;
    }

    public function column_default($item, $column_name)
    {
        if (preg_match('/^lang_([a-z]+)/', $column_name, $matches)) {
            $languages = Translatable::getLanguages();
            $locale = $languages[$matches[1]];
            $stats = $item->getStats($locale);

            $label = '';
            $completed = floor($stats['po'] / $stats['pot'] * 100);
            $label .= "PO: " . ((int) $stats['po']) . " / $completed%<br>";

            $completed = floor($stats['mo'] / $stats['pot'] * 100);
            if ($stats['po'] == $stats['mo']) {
                $label .= "MO: " . ((int) $stats['mo']) . " / $completed%\n";
            } else {
                $label .= "MO: <b style='color: red'>" . ((int) $stats['mo']) . " / $completed%</b>";
            }

            return $label;
        }

        return $item->$column_name;
    }

    public function column_strings($item)
    {
        $pot = $item->getPot();

        return $pot ? count($pot->entries) : '?';
    }

    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="export[%1$s][%2$s]" value="1" />',
            $item->type,
            $item->id
        );
    }

    public function column_actions($item)
    {
        return sprintf(
            '<input type="submit" class="button-primary" name="export[%1$s][%2$s]" value="%3$s" />',
            $item->type,
            $item->id,
            __('Update')
        );
    }

    public function prepare_items()
    {
        global $_wp_column_headers;

        $this->items = array_merge(
            Core::findAll(),
            Theme::findAll(),
            Plugin::findAll()
        );

        $columns               = $this->get_columns();
        $hidden                = array();
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
    }

    public function get_bulk_actions()
    {
        $actions = array(
            'update' => __('Update'),
        );

        return $actions;
    }
}
