<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleMenu extends PluginFeature{
    public function add_filters(){
        //todo: add filters here
    }

    public function add_actions(){
        add_action('admin_menu', array($this, 'create_menu'));  //create the admin menu
        add_action('admin_enqueue_scripts', array($this, 'enqueue_icon_style')); //enqueue scripts
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\
    public function create_menu(){
        // add the settings page
        add_menu_page(
            'ContentOracle AI', // page title
            'ContentOracle',        // menu title
            'manage_options',   // capability
            'contentoracle-ai-chat', // menu slug
            function(){ // callback function
                ob_start();
                require_once plugin_dir_path(__FILE__) . 'elements/main_page.php';
                $content = ob_get_clean();
                
                $this->render_tabbed_admin_page($content);
            },
            plugin_dir_url( __FILE__ ) . "/assets/images/coai_icon_light.png"    // icon
        );
    }

    public function enqueue_icon_style(){
        wp_enqueue_style('contentoracle-icon', plugin_dir_url( __FILE__ ) . '/assets/css/icon.css');
        
        // Enqueue main page styles if we're on the ContentOracle main page
        if (isset($_GET['page']) && $_GET['page'] === 'contentoracle-ai-chat') {
            wp_enqueue_style('contentoracle-main-page', plugin_dir_url( __FILE__ ) . '/assets/css/main_page.css');
        }
    }

    //prefix all content of admin menu pages with the base_layout.php file
    public function add_base_layout(){
        require_once plugin_dir_path(__FILE__) . 'elements/base_layout.php';
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //render a page with the tab bar at the top
    //called by various other features to render all admin pages with the tab bar at the top
    public function render_tabbed_admin_page(string $content){
        require_once plugin_dir_path(__FILE__) . 'elements/base_layout.php';
    }
    /*
    TODO: make this function work for putting the tab bar at the top of the admin pages
    */

    //placeholder uninstall method to identify this feature
    public function uninstall(){
        
    }
}