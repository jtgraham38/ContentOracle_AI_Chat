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
            'contentoracle-ai', // menu slug
            function(){ // callback function
                require_once plugin_dir_path(__FILE__) . 'elements/main_page.php';
            },
            $this->get_base_url() . "features/admin_menu/assets/images/coai_icon_light.png"    // icon
        );
    }

    public function enqueue_icon_style(){
        wp_enqueue_style('contentoracle-icon', $this->get_base_url() . 'features/admin_menu/assets/css/icon.css');
    }
}