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

        //register the chat log cpt
        add_action('init', array($this, 'register_chat_log_cpt'));

        //register the chat log admin page
        add_action('admin_menu', array($this, 'register_chat_log_admin_page'));

        //add the tab bar to the chat log page
        add_action('admin_notices', array($this, 'add_tab_bar_to_chat_log_page'));

        //disable editing capabilities
        add_action('admin_init', array($this, 'disable_chat_log_editing'));
        add_action('admin_head', array($this, 'hide_chat_log_edit_buttons'));
        add_filter('user_can_edit_post', array($this, 'prevent_chat_log_editing'), 10, 2);
        add_action('load-post.php', array($this, 'prevent_chat_log_edit_screen'));
        add_action('load-post-new.php', array($this, 'prevent_chat_log_edit_screen'));
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\
    public function add_menu(){
        add_submenu_page(
            'contentoracle-hidden', // Parent menu slug (this page does not appear in the sidebar menu)
            __('Analytics', 'contentoracle-ai-chat'), // page title
            __('Analytics', 'contentoracle-ai-chat'), // menu title
            'manage_options', // capability
            'edit.php?post_type=' . $this->prefixed('chatlog')
        );
    }

    public function render_page(){
        $this->get_feature('admin_menu')->render_tabbed_admin_page(
            require_once plugin_dir_path(__FILE__) . 'elements/analytics_page.php'
        );
    }

    //register a cpt for chat logs
    public function register_chat_log_cpt(){
        $labels = array(
            'name' => 'Chat Logs',
            'singular_name' => 'Chat Log',
            'menu_name' => 'Chat Logs',
            'all_items' => 'All Chat Logs',
            'view_item' => 'View Chat Log',
            'add_new_item' => 'Add New Chat Log',
            'edit_item' => 'View Chat Log',
            'update_item' => 'Update Chat Log',
            'search_items' => 'Search Chat Logs',
            'not_found' => 'No chat logs found',
            'not_found_in_trash' => 'No chat logs found in trash',
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_admin_bar' => false,
            'show_in_nav_menus' => false,
            'show_in_rest' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => false, // Disable creating new posts
                'edit_posts' => 'manage_options',
                'edit_others_posts' => 'manage_options',
                'publish_posts' => 'manage_options',
                'read_private_posts' => 'manage_options',
                'delete_posts' => 'manage_options',
            ),
            'map_meta_cap' => true,
            'supports' => array('title'), // Only title, no editor or other fields
            'hierarchical' => false,
            'has_archive' => false,
            'rewrite' => false,
            'query_var' => false,
            'can_export' => false,
        );

        register_post_type($this->prefixed('chatlog'), $args);
    }

    //register the admin page for the chat logs
    public function register_chat_log_admin_page(){
        add_submenu_page(
            'contentoracle-hidden',
            'Chat Logs',
            'Chat Logs',
            'manage_options',
            'edit.php?post_type=' . $this->prefixed('chatlog')
        );
    }

    public function add_tab_bar_to_chat_log_page(){
        //only add the tab bar if we are on the chat log page
        if (!isset($_GET['post_type']) || $_GET['post_type'] !== $this->prefixed('chatlog')) {
            return;
        }

        //get the admin menu feature to render the tab bar
        $admin_menu_feature = $this->get_feature('admin_menu');
        if ($admin_menu_feature) {
            //render the tab bar
            $admin_menu_feature->render_tabbed_admin_page('');
        }
    }

    /**
     * Disable chat log editing capabilities
     */
    public function disable_chat_log_editing() {
        // Remove edit capabilities for chat logs
        if (isset($_GET['post_type']) && $_GET['post_type'] === $this->prefixed('chatlog')) {
            // Remove edit actions from bulk actions
            add_filter('bulk_actions-edit-' . $this->prefixed('chatlog'), '__return_empty_array');
            
            // Remove row actions (edit, quick edit, trash, etc.)
            add_filter('post_row_actions', array($this, 'remove_chat_log_row_actions'), 10, 2);
        }
    }

    /**
     * Hide edit buttons and other editing UI elements
     */
    public function hide_chat_log_edit_buttons() {
        if (isset($_GET['post_type']) && $_GET['post_type'] === $this->prefixed('chatlog')) {
            echo '<style>
                .page-title-action,
                .add-new-h2,
                .edit-post-link,
                .post-edit-link,
                .row-actions .edit,
                .row-actions .inline,
                .row-actions .trash,
                .bulk-edit,
                .quick-edit,
                .delete,
                .editinline { display: none !important; }
                
                .wp-list-table .check-column input[type="checkbox"] { display: none !important; }
                .tablenav .bulkactions { display: none !important; }
                .tablenav .tablenav-pages { margin-left: 0 !important; }
            </style>';
        }
    }

    /**
     * Prevent editing of chat log posts
     */
    public function prevent_chat_log_editing($can_edit, $post) {
        if ($post && $post->post_type === $this->prefixed('chatlog')) {
            return false;
        }
        return $can_edit;
    }

    /**
     * Remove row actions for chat logs
     */
    public function remove_chat_log_row_actions($actions, $post) {
        if ($post->post_type === $this->prefixed('chatlog')) {
            // Keep only view action, remove all others
            $actions = array(
                'view' => '<a href="' . get_permalink($post->ID) . '" target="_blank">' . __('View', 'contentoracle-ai-chat') . '</a>'
            );
        }
        return $actions;
    }

    /**
     * Prevent access to edit screen for chat logs
     */
    public function prevent_chat_log_edit_screen() {
        global $post;
        
        if ($post && $post->post_type === $this->prefixed('chatlog')) {
            // Redirect to the chat logs list page
            wp_redirect(admin_url('edit.php?post_type=' . $this->prefixed('chatlog')));
            exit;
        }
    }
    
    

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

//TODO: make the view button actually show the chat log