<?php
//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

//check if the user has the necessary permissions
if (!current_user_can('activate_plugins')) {
    exit;
}

//start uninstalling
echo "Uninstalling ContentOracle AI Chat plugin...<br>";


// Require Composer's autoload file
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';
use jtgraham38\wpvectordb\VectorTable;
use jtgraham38\wpvectordb\VectorTableQueue;

//check if the cleanup_db option is set to true
$cleanup_db = get_option('coai_chat_cleanup_db') ? true : false;
if ($cleanup_db) {
    // Define plugin prefix
    $prefix = 'coai_chat_';
    
    // Clean up all plugin options
    global $wpdb;
    echo "Cleaning up all plugin options...<br>";
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '{$prefix}%' AND option_name != 'coai_chat_cleanup_db'");
    echo "All plugin options cleaned up successfully!<br>";

    // Clean up post meta
    echo "Cleaning up post meta...<br>";
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '{$prefix}%'");
    echo "Post meta cleaned up successfully!<br>";

    
    // Clean up custom post types
    echo "Cleaning up custom post types...<br>";
    $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = '{$prefix}shortcode'");
    echo "Custom post types cleaned up successfully!<br>";
    
    
    // Clean up custom tables (if they exist)
    echo "Cleaning up custom tables...<br>";
    $vt = new VectorTable($prefix);
    $vt->drop_table();
    $vtq = new VectorTableQueue($prefix);
    $vtq->drop_table();
    echo "Custom tables cleaned up successfully!<br>";


    // Clean up chat log table
    echo "Cleaning up chat log table...<br>";
    $tablename = $wpdb->prefix . $prefix . 'chatlog';
    $wpdb->query("DROP TABLE IF EXISTS {$tablename}");
    echo "Chat log table cleaned up successfully!<br>";

    // Clear any scheduled cron jobs
    echo "Clearing scheduled cron jobs...";
    wp_clear_scheduled_hook($prefix . 'embed_batch_cron_hook');
    wp_clear_scheduled_hook($prefix . 'clean_queue_cron_hook');
    wp_clear_scheduled_hook($prefix . 'auto_enqueue_embeddings_cron_hook');
    echo "Scheduled cron jobs cleared successfully!<br>";

    // Clear transients
    echo "Clearing transients...<br>";
    delete_transient($prefix . 'plugin_activated');
    echo "Transients cleared successfully!<br>";

    //delete the cleanup_db option
    delete_option($prefix . 'cleanup_db');

    echo "ContentOracle AI Chat data cleared and plugin uninstalled successfully!<br>";
} else {
    echo "ContentOracle AI Chat plugin uninstalled successfully!<br>";
}
?> 