<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;



class ContentOracleAnalytics extends PluginFeature{
    public function add_filters(){
        //todo: add filters here
    }

    public function add_actions(){
        //add submenu page
        add_action('admin_menu', array($this, 'add_menu'));
        
        //enqueue chat log styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_chat_log_scripts_styles'));

        //register a cron hook
        add_action('init', array($this, 'schedule_cron_jobs'));

        //register a cron job to remove chat logs that are older than 30 days
        add_action($this->prefixed('remove_old_chat_logs_cron_hook'), array($this, 'remove_old_chat_logs'));
    }

    
    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\
    /*
    * Schedule cron jobs
    */
    public function schedule_cron_jobs(){
        //schedule a cron job to remove chat logs that are older than 30 days
        if (!wp_next_scheduled($this->prefixed('remove_old_chat_logs_cron_hook'))) {
            wp_schedule_event(time(), 'daily', $this->prefixed('remove_old_chat_logs_cron_hook'));
        }
    }

    /*
    * Remove chat logs that are older than 30 days
    */
    public function remove_old_chat_logs(){
        //elete all entries from the chat log table created more than 30 days ago
        global $wpdb;
        $table_name = $wpdb->prefix . $this->prefixed('chatlog');
        $wpdb->query("DELETE FROM {$table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    }

    public function add_menu(){
        add_submenu_page(
            'contentoracle-hidden', // Parent menu slug (this page does not appear in the sidebar menu)
            __('Analytics', 'contentoracle-ai-chat'), // page title
            __('Analytics', 'contentoracle-ai-chat'), // menu title
            'manage_options', // capability
            'contentoracle-ai-chat-analytics',
            function(){
                // Handle delete action first
                if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['chat_log_id'])) {
                    $this->handle_delete_chat_log();
                    return;
                }
                
                //check if the chat_log_id param 
                if (isset($_GET['chat_log_id'])) {
                    // Get page content
                    ob_start();
                    require_once plugin_dir_path(__FILE__) . 'elements/chat_log.php';
                    $page_content = ob_get_clean();
                    
                    // Render the page
                    $this->get_feature('admin_menu')->render_tabbed_admin_page($page_content);
                } else {
                    // Get page content
                    ob_start();
                    require_once plugin_dir_path(__FILE__) . 'elements/analytics_page.php';
                    $page_content = ob_get_clean();
                    
                    // Render the page
                    $this->get_feature('admin_menu')->render_tabbed_admin_page($page_content);
                }
                
            }
        );
    }


    /**
     * Enqueue chat log styles for admin area
     */
    public function enqueue_chat_log_scripts_styles() {
        global $post;
        
        //only enqueue them if we are on the analytics page, and chat_log_id is set in the
        if (isset($_GET['page']) && $_GET['page'] === 'contentoracle-ai-chat-analytics' && isset($_GET['chat_log_id'])) {
            //enqueue styles
            wp_enqueue_style(
                'contentoracle-chat-log-styles',
                plugin_dir_url(__FILE__) . 'assets/css/chat_log.css',
                array(),
                '1.0.0'
            );
        }
    }

    /**
     * Handle delete chat log action
     */
    private function handle_delete_chat_log() {
        $chat_log_id = intval($_GET['chat_log_id']);
        $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
        
        if (wp_verify_nonce($nonce, 'delete_chat_log_' . $chat_log_id)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'coai_chat_chatlog';
            
            $result = $wpdb->delete(
                $table_name,
                array('id' => $chat_log_id),
                array('%d')
            );
            
            if ($result !== false) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                         __('Chat log deleted successfully.', 'contentoracle-ai-chat') . 
                         '</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . 
                         __('Failed to delete chat log.', 'contentoracle-ai-chat') . 
                         '</p></div>';
                });
            }
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . 
                     __('Security check failed. Please try again.', 'contentoracle-ai-chat') . 
                     '</p></div>';
            });
        }
        
        // Redirect to the analytics page
        wp_redirect(admin_url('admin.php?page=contentoracle-ai-chat-analytics'));
        exit;
    }



    //register a cpt for chat logs


    /*
    This feature will consist of two major parts:
    1) We will keep chat logs of all recent user interactions with the ai.
    These will be able to be read by the site admin to help them understand the pain points users are having with the site, and what they are looking for.
    We will store each chat log as a custom post type called coai_chat_chatlog.
    This cpt should not be editable by the admin in any way.  It is a read-only post type.
    The body of the post will be a json object that represents the chat log.
    The json object will have the following fields:
    - chat_log: a json array of objects, each representing either a chat message from the user or the ai.
    They will look like this from a user:
    {
        "role": "user",
        "content": "I'm looking for a new pair of shoes."
    }
    They will look like this from the ai:
    {
        "role": "assistant",
        "content": "I recommend the Nike Air Force 1."
        //we will upgrade to include other fields of the ai response later.
    }
    Then, on the admin dashboard, we will have an option to view a chat log in a wordpress post table sorted from most recent to least recent.
    When they view it, we will render the json body of the chat log in a format similar to how the chat block displays them.
    We will also store a piece of postmeta about each chat log that will be used to store other helpful data,
    such as user agent, ip address, etc.  This will be implemented as a single postmeta field containing an array of objects.

    2) We will also keep track of a number of quick stats about the ai's performance, and display them on the analytics dashboard.
    These will include:
    - Total number of conversations in the last 7 days.
    - The most often cited content in the last 7 days.
    - The content most often visited from feature_content artifacts in the last 7 days.
    - The content most often visited from inline_citation artifacts in the last 7 days.
    - Number of chat errors in the last 7 days.
    And more to come. 
    */

    //placeholder uninstall method to identify this feature
    public function uninstall(){
        echo "ContentOracle Analytics Feature uninstalling...";
    }
}