<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleSetupWizard extends PluginFeature{
    public function add_filters(){
        //todo: add filters here
    }

    public function add_actions(){
        //create admin page
        add_action('admin_menu', array($this, 'create_admin_page'));

        //enqueue styles and scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles_and_scripts'));
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //create a page on the admin menu that will display the setup wizard
    public function create_admin_page(){
        add_submenu_page(
            'contentoracle-hidden', // Parent menu slug (this page does not appear in the sidebar menu)
            'Content Oracle Setup Wizard',
            'Content Oracle Setup Wizard',
            'manage_options',
            'contentoracle-ai-chat-setup-wizard',
            function(){
                require_once plugin_dir_path(__FILE__) . 'elements/setup_wizard_container.php';
            }
        );
    }

    //enqueue styles and scripts
    public function enqueue_styles_and_scripts(){

        if (isset($_GET['page']) && $_GET['page'] == 'contentoracle-ai-chat-setup-wizard'){
            //main stylesheet for the setup wizard
            wp_enqueue_style('coai_chat-setup-wizard', plugin_dir_url(__FILE__) . 'assets/css/setup_wizard.css');

            //remove all notices from the page body
            wp_enqueue_script('coai_chat-setup-wizard-remove-notices', plugin_dir_url(__FILE__) . 'assets/js/remove_notices.js', array('jquery'), '1.0.0', true);


        }
    }

}