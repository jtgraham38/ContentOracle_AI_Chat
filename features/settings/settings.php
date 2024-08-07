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
            'contentoracle_plugin_settings', // id
            '', // title
            function(){ // callback
                echo 'Manage your ContentOracle settings here.';
            },
            'contentoracle-ai-settings'  // page (matches menu slug)
        );

        add_settings_section(
            'contentoracle_ai_settings', // id
            '', // title
            function(){ // callback
                echo 'Manage your AI search settings here.';
            },
            'contentoracle-ai-settings'  // page (matches menu slug)
        );

        // create the settings fields
        add_settings_field(
            $this->get_prefix() . "api_token",    // id of the field
            'ContentOracle API Token',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/api_token_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'contentoracle_plugin_settings'  // section
        );

        add_settings_field(
            $this->get_prefix() . "post_types",    // id of the field
            'ContentOracle Post Types to Use',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/post_types_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'contentoracle_plugin_settings'  // section
        );

        add_settings_field(
            $this->get_prefix() . "ai_tone",    // id of the field
            'ContentOracle AI Tone',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/ai_tone_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'contentoracle_ai_settings'  // section
        );

        add_settings_field(
            $this->get_prefix() . "ai_jargon",    // id of the field
            'ContentOracle AI Jargon',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/ai_jargon_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'contentoracle_ai_settings'  // section
        );

        add_settings_field(
            $this->get_prefix() . "ai_goals",    // id of the field
            'ContentOracle AI Goals',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/ai_goals_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'contentoracle_ai_settings'  // section
        );



        // create the settings themselves
        register_setting(
            'contentoracle_plugin_settings', // option group
            $this->get_prefix() . 'api_token',    // option name
            array(  // args
                'type' => 'string',
                'default' => ''
            )
        );

        register_setting(
            'contentoracle_plugin_settings', // option group
            $this->get_prefix() . 'post_types',    // option name
            array(  // args
                'type' => 'array',
                'default' => array('post', 'page', 'media'),
                'sanitize_callback' => 'wp_parse_args'
            )
        );

        register_setting(
            'contentoracle_ai_settings', // option group
            $this->get_prefix() . 'ai_tone',    // option name
            array(  // args
                'type' => 'string',
                'default' => 'none'
            )
        );

        register_setting(
            'contentoracle_ai_settings', // option group
            $this->get_prefix() . 'ai_jargon',    // option name
            array(  // args
                'type' => 'string',
                'default' => 'none'
            )
        );

        register_setting(
            'contentoracle_ai_settings', // option group
            $this->get_prefix() . 'ai_goals',    // option name
            array(  // args
                'type' => 'array',
                'default' => array('none'),
                'sanitize_callback' => 'wp_parse_args'
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