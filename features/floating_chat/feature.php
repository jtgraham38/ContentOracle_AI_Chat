<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleFloatingChat extends PluginFeature{
    public function add_filters(){
        //ensure only one post of the global site chat can be created
        add_filter('wp_insert_post_data', array($this, 'ensure_only_one_floating_site_chat_post_exists'));

        //set default content for new floating site chat posts
        add_filter('default_content', array($this, 'set_default_floating_site_chat_content'), 10, 2);
        add_filter('default_title', array($this, 'set_default_floating_site_chat_title'), 10, 2);
        
        //redirect from post list page to ContentOracle admin page
        add_action('admin_enqueue_scripts', array($this, 'enqueue_floating_chat_redirect_script'));
    }

    public function add_actions(){
        //register the cpt
        add_action('init', array($this, 'register_floating_site_chat_cpt'), 10);

        //register the settings
        add_action('admin_init', array($this, 'register_floating_site_chat_settings'));

        //register the settings page
        add_action('admin_menu', array($this, 'register_floating_site_chat_settings_page'));

    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    /*
    This feature will handle the creation of a sitewide floating chat button, which will
    open a chat block in the bottom right corner of the screen.

    The button color and background will be 100% customizable, and the chat 
    block itself will be customizable using a custom post type that can only accept
    coai chat blocks.

    Only one post of that custom post type will be allowed, and it will be used to create the floating chat button and the chat block.
    
    This will be a spearte entry in the tabs on the admin section to manage it.

    There will be a single setting, boolean, enable global site chat.  this will determine whether the 
    the floating chat button and block appear on the site frontend, and whether the admin can 
    access the cpt to edit the floating chat button and block.
    */

    /*
    * Register the settings for the global site chat.
    */
    public function register_floating_site_chat_settings(){
        //first, add the settings section
        add_settings_section(
            'coai_chat_floating_site_chat_settings', // id
            'Global Site Chat Settings', // title
            function(){ // callback
                echo 'Manage your global site chat settings here.';
            },
            'contentoracle-ai-global-site-chat-settings' // page (matches menu slug)
        );

        //then, register the setting field for the setting
        add_settings_field(
            $this->prefixed('enable_floating_site_chat'), // id
            'Enable Global Site Chat', // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/enable_floating_site_chat_input.php';
            },
            'contentoracle-ai-global-site-chat-settings', // page (matches menu slug)
            'coai_chat_floating_site_chat_settings', // section
            array(
                'label_for' => $this->prefixed('enable_floating_site_chat_input')
            )
        ); 

        //then, register the setting
        register_setting(
            'coai_chat_floating_site_chat_settings', // option group
            $this->prefixed('enable_floating_site_chat'), // option name
            array(  // args
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => function($value){
                    return $value ? true : false;
                }
            )
        );


    }

    /*
    * Register the settings page for the global site chat.
    */
    public function register_floating_site_chat_settings_page(){
        add_submenu_page(
            'contentoracle-hidden', // parent slug
            'Global Site Chat Settings', // page title
            'Global Site Chat', // menu title
            'manage_options', // capability
            'contentoracle-ai-chat-global-site-chat', // menu slug
            function(){
                require_once plugin_dir_path(__FILE__) . 'elements/_inputs.php';
            }
        );
    }

    /*
    * Register the custom post type for the global site chat.
    */
    public function register_floating_site_chat_cpt(){

        //register the cpt to manage the global site chat
        $labels = array(
            'name'               => _x('Floating Site Chat', 'post type general name', 'contentoracle-ai-chat'),
            'singular_name'      => _x('Floating Site Chat', 'post type singular name', 'contentoracle-ai-chat'),
            'menu_name'          => _x('Floating Site Chat', 'admin menu', 'contentoracle-ai-chat'),
            'name_admin_bar'     => _x('Floating Site Chat', 'add new on admin bar', 'contentoracle-ai-chat'),
            'add_new'            => _x('Add New', 'Floating Site Chat', 'contentoracle-ai-chat'),
            'add_new_item'       => __('Add New Floating Site Chat', 'contentoracle-ai-chat'),
            'new_item'           => __('New Floating Site Chat', 'contentoracle-ai-chat'),
            'edit_item'          => __('Edit Floating Site Chat', 'contentoracle-ai-chat'),
            'view_item'          => __('View Floating Site Chat', 'contentoracle-ai-chat'),
            'all_items'          => __('All Floating Site Chats', 'contentoracle-ai-chat'),
            'search_items'       => __('Search Floating Site Chats', 'contentoracle-ai-chat'),
            'parent_item_colon'  => __('Parent Floating Site Chats:', 'contentoracle-ai-chat'),
            'not_found'          => __('No floating site chats found.', 'contentoracle-ai-chat'),
            'not_found_in_trash' => __('No floating site chats found in Trash.', 'contentoracle-ai-chat')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'floating-site-chat'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('editor', 'title'),
            'show_in_rest'       => true,
        );

        register_post_type($this->prefixed('float_chat'), $args);
        //^it has to be this short string, because longer would exceed the character limit for cpt keys
    }

    /*
    * Ensure only one post of the floating site chat can be created.
    */
    public function ensure_only_one_floating_site_chat_post_exists(array $data){

        //get the post type
        $float_chat_type = $this->prefixed('float_chat');

        //check if the post is of the correct post type
        if ($data['post_type'] === $float_chat_type && $data['post_status'] !== 'trash'){
            //check for existing posts of the floating site chat type
            $existing_posts = get_posts(array(
                'post_type' => $float_chat_type,
                'post_status' => ['publish', 'draft', 'pending'],
                'numberposts' => 1,
                'fields' => 'ids',
            ));

            //if a post exists, and this is a new post (not an update), die with an error
            if (!empty($existing_posts) && empty($postarr['ID'])) {
                wp_die('Only one floating site chat interface can be created.');
            }
        }

       return $data;
    }

    /*
    * Set default content for new floating site chat posts.
    */
    public function set_default_floating_site_chat_content($content, $post){
        // Only set content for new posts of our post type
        if (isset($_GET['post_type']) && $_GET['post_type'] === $this->prefixed('float_chat')) {
            return '<!-- wp:contentoracle/ai-chat {"height":"36rem","userMsgBgColor":"#3232FD","style":{"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}},"border":{"radius":"4px","width":"1px"}},"textColor":"base-2","borderColor":"contrast"} /-->';
        }
        
        return $content;
    }

    /*
    * Set default title for new floating site chat posts.
    */
    public function set_default_floating_site_chat_title($title, $post){
        // Only set title for new posts of our post type
        if (isset($_GET['post_type']) && $_GET['post_type'] === $this->prefixed('float_chat')) {
            return 'ContentOracle AI Floating Site Chat';
        }
        
        return $title;
    }

    /*
    * Enqueue JavaScript to redirect from floating site chat post list page to ContentOracle admin page.
    */
    public function enqueue_floating_chat_redirect_script($hook){
        // Only run on post list pages
        if ($hook !== 'edit.php') {
            return;
        }
        
        // Check if we're on the floating site chat post list page
        if (isset($_GET['post_type']) && $_GET['post_type'] === $this->prefixed('float_chat')) {
            $redirect_url = admin_url('admin.php?page=contentoracle-ai-chat-global-site-chat');
            
            // Add inline script to redirect
            $script = "
                window.location.href = '" . esc_js($redirect_url) . "';
            ";
            
            wp_add_inline_script('jquery', $script);
        }
    }
}