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
        add_action('admin_menu', array($this, 'create_menu'));
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
                require_once plugin_dir_path(__FILE__) . 'assets/main_page.php';
            },
            'dashicons-smiley'    // icon
        );
        }
}