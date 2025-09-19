<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleFloatingChat extends PluginFeature{
    public function add_filters(){
        //todo: add filters here
    }

    public function add_actions(){
        //register the settings
        add_action('admin_init', array($this, 'register_global_site_chat_settings'));

        //register the settings page
        add_action('admin_menu', array($this, 'register_global_site_chat_settings_page'));
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
    public function register_global_site_chat_settings(){
        //first, add the settings section
        add_settings_section(
            'coai_chat_global_site_chat_settings', // id
            'Global Site Chat Settings', // title
            function(){ // callback
                echo 'Manage your global site chat settings here.';
            },
            'contentoracle-ai-global-site-chat-settings' // page (matches menu slug)
        );

        //then, register the setting field for the setting
        add_settings_field(
            $this->prefixed('enable_global_site_chat'), // id
            'Enable Global Site Chat', // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/enable_global_site_chat_input.php';
            },
            'contentoracle-ai-global-site-chat-settings', // page (matches menu slug)
            'coai_chat_global_site_chat_settings', // section
            array(
                'label_for' => $this->prefixed('enable_global_site_chat_input')
            )
        ); 

        //then, register the setting
        register_setting(
            'coai_chat_global_site_chat_settings', // option group
            $this->prefixed('enable_global_site_chat'), // option name
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
    public function register_global_site_chat_settings_page(){
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
}