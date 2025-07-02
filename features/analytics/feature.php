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

    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\
    public function add_menu(){
        add_submenu_page(
            'contentoracle-ai-chat', // parent slug
            'Analytics', // page title
            'Analytics', // menu title
            'manage_options', // capability
            'contentoracle-ai-chat-analytics', // menu slug
            array($this, 'render_page') // callback function
        );
    }

    public function render_page(){
        echo "<h2>Analytics</h2> <strong>Coming soon...</strong>";
    }

    //placeholder uninstall method to identify this feature
    public function uninstall(){
        echo "ContentOracle Analytics Feature uninstalling...";
    }
}