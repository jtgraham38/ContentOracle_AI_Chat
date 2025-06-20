<?php
// If uninstall not called from WordPress, exit
// if (!defined('WP_UNINSTALL_PLUGIN')) {
//     exit;
// }

// // Define plugin prefix
// $prefix = 'coai_chat_';

// // Clean up all plugin options
// $options_to_delete = array(
//     $prefix . 'api_token',
//     $prefix . 'debug_mode',
//     $prefix . 'display_credit_link',
//     $prefix . 'organization_name',
//     $prefix . 'ai_results_page',
//     $prefix . 'post_types',
//     $prefix . 'ai_tone',
//     $prefix . 'ai_jargon',
//     $prefix . 'ai_goal_prompt',
//     $prefix . 'ai_extra_info_prompt',
//     $prefix . 'chunking_method',
//     $prefix . 'auto_generate_embeddings',
//     $prefix . 'api_url',
//     $prefix . 'setup_wizard_latest_step_completed',
// );

// foreach ($options_to_delete as $option) {
//     delete_option($option);
// }

// // Clean up post meta
// global $wpdb;
// $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '{$prefix}%'");

// // Clean up custom post types
// $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'coai_chat_shortcode'");

// // Clean up custom tables (if they exist)
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}coai_chat_vectors");
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}coai_chat_vector_queue");

// // Clear any scheduled cron jobs
// wp_clear_scheduled_hook($prefix . 'embed_batch_cron_hook');
// wp_clear_scheduled_hook($prefix . 'clean_queue_cron_hook');
// wp_clear_scheduled_hook($prefix . 'auto_enqueue_embeddings_cron_hook');

// // Clear transients
// delete_transient($prefix . 'plugin_activated');

// echo "ContentOracle AI Chat plugin uninstalled successfully!";
?> 