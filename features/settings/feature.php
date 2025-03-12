<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleSettings extends PluginFeature{
    public function add_filters(){
        //todo: add filters here
    }

    public function add_actions(){
        //ai settings
        add_action('admin_menu', array($this, 'add_ai_settings_page'));
        add_action('admin_init', array($this, 'register_ai_settings'));

        //plugin settings
        add_action('admin_menu', array($this, 'add_plugin_settings_page'), 20);
        add_action('admin_init', array($this, 'init_plugin_settings'));
        add_action('init', array($this, 'create_results_page'));
        add_action('init', array($this, 'register_coai_api_url'));

        //register styles
        add_action('admin_enqueue_scripts', array($this, 'register_styles'));

    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //register plugin settings
    public function init_plugin_settings(){
        // create section for settings
        add_settings_section(
            'coai_chat_plugin_settings', // id
            '', // title
            function(){ // callback
                echo esc_html( 'Manage your ContentOracle settings here.' );
            },
            'contentoracle-ai-settings'  // page (matches menu slug)
        );

        // create the settings fields
        add_settings_field(
            $this->get_prefix() . "organization_name",    // id of the field
            'Organization Name',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/plugin/organization_name_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'coai_chat_plugin_settings',  // section
            array(
                'label_for' => $this->get_prefix() .'organization_name_input'
            )
        );

        add_settings_field(
            $this->get_prefix() . "api_token",    // id of the field
            'ContentOracle API Token',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/plugin/api_token_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'coai_chat_plugin_settings',  // section
            array(
                'label_for' => $this->get_prefix() .'api_token_input'
            )
        );

        add_settings_field(
            $this->get_prefix() . "ai_results_page",    // id of the field
            'ContentOracle AI Search Results Page',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/plugin/ai_search_results_page_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'coai_chat_plugin_settings',  // section
            array(
                'label_for' => $this->get_prefix() .'ai_results_page_input',
                'class' => 'contentoracle-ai-results-page-input'
            )
        );

        // add_settings_field(
        //     $this->get_prefix() . "show_searchbar_popup",    // id of the field
        //     'Show AI Search Popup',   // title
        //     function(){ // callback
        //         require_once plugin_dir_path(__FILE__) . 'elements/plugin/ai_search_popup_input.php';
        //     },
        //     'contentoracle-ai-settings', // page (matches menu slug)
        //     'coai_chat_plugin_settings',  // section
        //     array(
        //         'label_for' => $this->get_prefix() .'show_searchbar_popup_input'
        //     )
        // );

        add_settings_field(
            $this->get_prefix() . "debug_mode",    // id of the field
            'Debug Mode',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/plugin/debug_mode_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'coai_chat_plugin_settings',  // section
            array(
                'label_for' => $this->get_prefix() .'debug_mode_input'
            )
        );

        add_settings_field(
            $this->get_prefix() . "display_credit_link",    // id of the field
            'Display Credit Link',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/plugin/display_credit_link_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'coai_chat_plugin_settings',  // section
            array(
                'label_for' => $this->get_prefix() .'display_credit_link_input'
            )
        );

        // create the settings themselves

        register_setting(
            'coai_chat_plugin_settings', // option group
            $this->get_prefix() . 'organization_name',    // option name
            array(  // args
                'type' => 'string',
                'default' => get_bloginfo('name') ?? 'Organization Name',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );

        register_setting(
            'coai_chat_plugin_settings', // option group
            $this->get_prefix() . 'api_token',    // option name
            array(  // args
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );

        register_setting(
            'coai_chat_plugin_settings', // option group
            $this->get_prefix() . 'ai_results_page',    // option name
            array(  // args
                'type' => 'string',
                'default' => 'none',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );

        // register_setting(
        //     'coai_chat_plugin_settings', // option group
        //     $this->get_prefix() . 'show_searchbar_popup',    // option name
        //     array(  // args
        //         'type' => 'boolean',
        //         'default' => true,
        //         'sanitize_callback' => function($value){
        //             return $value ? true : false;
        //         }
        //     )
        // );

        register_setting(
            'coai_chat_plugin_settings', // option group
            $this->get_prefix() . 'debug_mode',    // option name
            array(  // args
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => function($value){
                    return $value ? true : false;
                }
            )
        );

        register_setting(
            'coai_chat_plugin_settings', // option group
            $this->get_prefix() . 'display_credit_link',    // option name
            array(  // args
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => function($value){
                    return $value ? true : false;
                }
            )
        );
    }

    //add plugin settings page
    public function add_plugin_settings_page(){


        //add a settings submenu
        add_submenu_page(
            'contentoracle-ai-chat', // $parent_slug
            'Settings', // $page_title
            'Settings', // $menu_title
            'manage_options', // $capability
            'contentoracle-ai-chat-settings', // $menu_slug
            function(){
                require_once plugin_dir_path(__FILE__) . 'elements/plugin/_inputs.php';
            } // $function
        );
    }

    //create the results page for the ai search if it does not exist
    public function create_results_page(){
        $results_page_id = get_option($this->get_prefix() . 'ai_results_page', null);
        if ($results_page_id == 'none'){
            return;
        }

        $results_page = get_post($results_page_id);

        //if no results page is set...
        if ( !$results_page || $results_page->post_type != 'page'){
            //create the page
            $page = array(
                'post_title' => 'ContentOracle AI Chat Results',
                'post_content' => '<!-- wp:contentoracle/ai-chat {"height":"36rem","userMsgBgColor":"#3232FD","style":{"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}},"border":{"radius":"4px","width":"1px"}},"textColor":"base-2","borderColor":"contrast"} /-->',
                'post_status' => 'publish',
                'post_type' => 'page',
            );
            $page_id = wp_insert_post($page);

            //update the option to create the page
            if ($page_id && !is_wp_error($page_id)) {
                // Update the option with the ID of the new page
                update_option($this->get_prefix() . 'ai_results_page', $page_id);
            }
        }
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //register ai settings
    public function register_ai_settings(){
        add_settings_section(
            'coai_chat_ai_settings', // id
            '', // title
            function(){ // callback
                echo esc_html( 'Manage your AI search settings here.' );
            },
            'contentoracle-ai-settings'  // page (matches menu slug)
        );

        // create the settings fields
        add_settings_field(
            $this->get_prefix() . "post_types",    // id of the field
            'ContentOracle Post Types to Use',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/ai/post_types_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'coai_chat_ai_settings',  // section
            array(
                'label_for' => $this->get_prefix() .'post_types_input'
            )
        );

        add_settings_field(
            $this->get_prefix() . "ai_tone",    // id of the field
            'ContentOracle AI Tone',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/ai/ai_tone_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'coai_chat_ai_settings',  // section
            array(
                'label_for' => $this->get_prefix() .'ai_tone_input'
            )
        );

        add_settings_field(
            $this->get_prefix() . "ai_jargon",    // id of the field
            'ContentOracle AI Jargon',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/ai/ai_jargon_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'coai_chat_ai_settings',  // section
            array(
                'label_for' => $this->get_prefix() .'ai_jargon_input'
            )
        );

        add_settings_field(
            $this->get_prefix() . "ai_goal_prompt",    // id of the field
            'ContentOracle AI Goals',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/ai/ai_goal_prompt_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'coai_chat_ai_settings',  // section
            array(
                'label_for' => $this->get_prefix() .'ai_goal_prompt_input'
            )
        );

        add_settings_field(
            $this->get_prefix() . "ai_extra_info",    // id of the field
            'ContentOracle AI Extra Info',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/ai/ai_extra_info_prompt_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'coai_chat_ai_settings',  // section
            array(
                'label_for' => $this->get_prefix() .'ai_extra_info_prompt_input'
            )
        );

        // create the settings themselves
        register_setting(
            'coai_chat_ai_settings', // option group
            $this->get_prefix() . 'post_types',    // option name
            array(  // args
                'type' => 'array',
                'default' => array('post', 'page', 'media'),
                'sanitize_callback' => 'wp_parse_args'
            )
        );

        register_setting(
            'coai_chat_ai_settings', // option group
            $this->get_prefix() . 'ai_tone',    // option name
            array(  // args
                'type' => 'string',
                'default' => 'none',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );

        register_setting(
            'coai_chat_ai_settings', // option group
            $this->get_prefix() . 'ai_jargon',    // option name
            array(  // args
                'type' => 'string',
                'default' => 'none',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );

        register_setting(
            'coai_chat_ai_settings', // option group
            $this->get_prefix() . 'ai_goal_prompt',    // option name
            array(  // args
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );

        register_setting(
            'coai_chat_ai_settings', // option group
            $this->get_prefix() . 'ai_extra_info_prompt',    // option name
            array(  // args
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );

        //register setting for post meta keys
        $this->register_post_meta_keys_settings();
    }

    //add ai settings page
    public function add_ai_settings_page(){
        //add a settings submenu
        add_submenu_page(
            'contentoracle-ai-chat', // $parent_slug
            'Prompt', // $page_title
            'Prompt', // $menu_title
            'manage_options', // $capability
            'contentoracle-ai-chat-prompt', // $menu_slug
            function(){
                require_once plugin_dir_path(__FILE__) . 'elements/ai/_inputs.php';
            } // $function
        );
    }

    //NOTE: embeddings have been registered in their own feature, "embeddings"

    //NOTE: analytics have been registered in their own feature, "analytics"

    //register setting for post meta keys
    public function register_post_meta_keys_settings(){
        //get all post types used for prompting
        $post_types = get_option($this->get_prefix() . 'post_types', array('post', 'page'));

        //register a setting for each post type
        foreach ($post_types as $label=>$post_type){
            //create the settings themselves
            register_setting(
                'coai_chat_ai_settings', // option group
                $this->get_prefix() . $post_type . '_prompt_meta_keys',    // option name
                array(  // args
                    'type' => 'array',
                    'default' => [],
                    'sanitize_callback' => function($value){
                        if (is_array($value)) $value = implode(',', $value);  //workaround for wordpress wrapping string input value in an array
                        if ($value == NULL) $value = '';
                        return array_map('trim', explode(',', $value));
                    }
                )
            );
        }
    }
    
    //register styles for the settings admin
    public function register_styles(){
        //only register this on pages related to the plugin
        if (strpos($_SERVER['REQUEST_URI'], 'contentoracle') === false){
            return;
        }
        wp_enqueue_style('contentoracle-ai-chat-settings', plugin_dir_url(__FILE__) . 'assets/css/admin.css');
    }

    //register the url of coai api (so it can be changed for testing)
    public function register_coai_api_url(){
        $api_url = get_option($this->get_prefix() . 'api_url', null);
        if (!$api_url){
            update_option($this->get_prefix() . 'api_url', 'https://app.contentoracleai.com/api');
        }
    }
}