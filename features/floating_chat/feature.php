<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleFloatingChat extends PluginFeature{
    public function add_filters(){
        // No filters needed for widget area approach
    }

    public function add_actions(){
        //register the widget area
        add_action('widgets_init', array($this, 'register_floating_chat_widget_area'));

        //register the settings
        add_action('admin_init', array($this, 'register_floating_site_chat_settings'));

        //register the settings page
        add_action('admin_menu', array($this, 'register_floating_site_chat_settings_page'));

        //render floating chat on frontend
        add_action('wp_footer', array($this, 'render_floating_chat_frontend'));

        //add default widget content
        add_action('widgets_init', array($this, 'add_default_floating_chat_widget'), 20);

        //enqueue floating chat styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_floating_chat_styles'));

    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    /*
    This feature will handle the creation of a sitewide floating chat button, which will
    open a chat block in the bottom right corner of the screen.

    The button color and background will be 100% customizable, and the chat 
    block itself will be customizable using a widget area that can accept
    any widgets including coai chat blocks.

    The widget area will be used to create the floating chat button and the chat block.
    
    This will be a separate entry in the tabs on the admin section to manage it.

    There will be a single setting, boolean, enable global site chat.  this will determine whether the 
    the floating chat button and block appear on the site frontend, and whether the admin can 
    access the widget area to edit the floating chat button and block.
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
    * Register the widget area for the floating site chat.
    */
    public function register_floating_chat_widget_area(){
        register_sidebar(array(
            'name'          => __('Floating Site Chat', 'contentoracle-ai-chat'),
            'id'            => $this->prefixed('floating_chat_widget_area'),
            'description'   => __('Widget area for the floating site chat. Add your chat widgets here.', 'contentoracle-ai-chat'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ));
    }


    /*
    * Render floating chat on frontend pages.
    */
    public function render_floating_chat_frontend(){
        // Check if floating site chat is enabled
        $enable_floating_site_chat = get_option($this->prefixed('enable_floating_site_chat'));
        
        if (!$enable_floating_site_chat) {
            return;
        }
        
        // Include the floating chat template
        require_once plugin_dir_path(__FILE__) . 'elements/floating_chat.php';
    }

    /*
    * Add default widget content to the floating chat widget area.
    */
    public function add_default_floating_chat_widget(){
        // Check if floating site chat is enabled
        $enable_floating_site_chat = get_option($this->prefixed('enable_floating_site_chat'));
        
        if (!$enable_floating_site_chat) {
            return;
        }
        
        // Check if the widget area already has widgets
        $widget_area_id = $this->prefixed('floating_chat_widget_area');
        if (is_active_sidebar($widget_area_id)) {
            return;
        }
        
        // Get the current widget settings
        $sidebars_widgets = get_option('sidebars_widgets', array());
        
        // Check if we've already added the default widget
        $default_added = get_option($this->prefixed('default_widget_added'), false);
        if ($default_added) {
            return;
        }
        
        // Add a default HTML widget with ContentOracle AI chat block
        $widget_id = 'html-' . time();
        $widget_content = '<!-- wp:contentoracle/ai-chat {"height":"36rem","userMsgBgColor":"#3232FD","style":{"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}},"border":{"radius":"4px","width":"1px"}},"textColor":"base-2","borderColor":"contrast"} /-->';
        
        // Get existing HTML widget settings
        $html_widgets = get_option('widget_html', array());
        
        // Add the new widget
        $html_widgets[$widget_id] = array(
            'title' => 'ContentOracle AI Floating Chat',
            'text' => $widget_content
        );
        
        // Update widget settings
        update_option('widget_html', $html_widgets);
        
        // Add widget to the sidebar
        if (!isset($sidebars_widgets[$widget_area_id])) {
            $sidebars_widgets[$widget_area_id] = array();
        }
        $sidebars_widgets[$widget_area_id][] = 'html-' . $widget_id;
        
        // Update sidebar widgets
        update_option('sidebars_widgets', $sidebars_widgets);
        
        // Mark that we've added the default widget
        update_option($this->prefixed('default_widget_added'), true);
    }

    /*
    * Enqueue floating chat styles.
    */
    public function enqueue_floating_chat_styles() {
        // Check if floating site chat is enabled
        $enable_floating_site_chat = get_option($this->prefixed('enable_floating_site_chat'));
        
        if (!$enable_floating_site_chat) {
            return;
        }

        wp_enqueue_style(
            $this->prefixed('floating_chat_styles'),
            plugin_dir_url(__FILE__) . 'assets/css/floating-chat.css',
            array(),
            '1.0.0'
        );
    }
}