<?php

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleSettings extends PluginFeature{
    public function add_filters(){
        //todo: add filters here
    }

    public function add_actions(){
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'init_settings'));
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //register settings
    public function init_settings(){
        // create section for settings
        add_settings_section(
            'contentoracle_settings', // id
            '', // title
            function(){ // callback
                echo 'Manage your AI search settings here.';
            },
            'contentoracle-ai'  // page (matches menu slug)
        );

        // create the settings fields
        add_settings_field(
            'contentoracle_settings',    // id
            'ContentOracle API Token',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/api_token.php';
            },
            'contentoracle-ai', // page (matches menu slug)
            'contentoracle_settings'  // section
        );

        // create the settings themselves
        register_setting(
            'contentoracle_settings', // option group
            $this->get_prefix() . 'api_token',    // option name
            array(  // args
                'default' => ''
            )
        );

    }

    //add settings page
    public function add_settings_page(){


        //add a settings submenu
        add_submenu_page(
            'contentoracle-ai', // $parent_slug
            'Settings', // $page_title
            'Settings', // $menu_title
            'manage_options', // $capability
            'contentoracle-ai-settings', // $menu_slug
            function(){
                require_once plugin_dir_path(__FILE__) . 'elements/contentoracle_settings.php';
            } // $function
        );
    }
}