<?php
/*
Plugin Name: Cerseilabs Database Synchronization
Plugin URI: http://www.cerseilabs.com/
Version: 1.0
Author: Bogoljub Gojkovic
Description: Syncronize databases between live and dev websites
*/

define('DBS_ADMIN_CAPABILITY', 'manage_options');
define('SOURCE_TYPE_OF_DATABASE', 'source-database');
define('DESTINATION_TYPE_OF_DATABASE', 'destination-database');

//delete options and page with JSON after deactivate plugin
function myplugin_deactivate()
{
    delete_option('type_of_database');
    delete_option('cdb_source_url');

    $pullPagewithJSON = get_page_by_title('clbs-list-all-posts');
    wp_delete_post($pullPagewithJSON->ID, true);

}

register_deactivation_hook(__FILE__, 'myplugin_deactivate');

require_once 'functions.php';

add_action('admin_menu', 'add_plugin_to_menu_items');

function add_plugin_to_menu_items()
{
    add_menu_page('Sync plugin', 'Sync database', DBS_ADMIN_CAPABILITY, 'main-screen', 'admin_panel_front');
}

function admin_panel_front()
{
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (get_option('type_of_database') === SOURCE_TYPE_OF_DATABASE) {

        include 'source-databese.php';

    } elseif (get_option('type_of_database') === DESTINATION_TYPE_OF_DATABASE) {

        include 'destination-database.php';

    } else {

        include 'main-screen.php';

    }

}