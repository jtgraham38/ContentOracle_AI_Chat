<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;
use jtgraham38\wpvectordb\query\parts\Filter;

class ContentOracleFiltersSorts extends PluginFeature{
    public function add_filters(){
        //todo: add filters here
    }
 
    public function add_actions(){
        //add submenu page
        add_action('admin_menu', array($this, 'add_menu'));
        
        //register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        //register styles
        add_action('admin_enqueue_scripts', array($this, 'register_styles'));
    }

    public function add_menu(){
        add_submenu_page(
            'contentoracle-hidden', // parent slug (this page does not appear in the sidebar menu)
            'Filters & Sorts', // page title
            'Filters & Sorts', // menu title
            'manage_options', // capability
            'contentoracle-ai-chat-filters-sorts', // menu slug
            array($this, 'render_page') // callback function
        );
    }

    public function render_page(){
        ob_start();
        require_once plugin_dir_path(__FILE__) . 'elements/_inputs.php';
        $content = ob_get_clean();
        
        $this->get_feature('admin_menu')->render_tabbed_admin_page($content);
    }

    public function register_settings(){
        add_settings_section(
            'coai_chat_filters_sorts_settings', // id
            '', // title
            function(){ // callback
                echo 'Manage your AI search filters and sorting options here.';
            },
            'contentoracle-ai-settings'  // page (matches menu slug)
        );

        // create the settings fields
        add_settings_field(
            $this->prefixed('filters'),    // id of the field
            'ContentOracle AI Search Filters',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/filters_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'coai_chat_filters_sorts_settings',  // section
            array(
                'label_for' => $this->prefixed('filters_input')
            )
        );

        add_settings_field(
            $this->prefixed('sorts'),    // id of the field
            'ContentOracle AI Search Sorts',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/sorts_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'coai_chat_filters_sorts_settings',  // section
            array(
                'label_for' => $this->prefixed('sorts_input')
            )
        );

        // create the settings themselves
        register_setting(
            'coai_chat_filters_sorts_settings', // option group
            $this->prefixed('filters'),    // option name
            array(  // args
                'type' => 'array',
                'default' => array(),
                'sanitize_callback' => array($this, 'sanitize_filters')
            )
        );

        register_setting(
            'coai_chat_filters_sorts_settings', // option group
            $this->prefixed('sorts'),    // option name
            array(  // args
                'type' => 'array',
                'default' => array(),
                'sanitize_callback' => 'wp_parse_args'
            )
        );
    }

    public function sanitize_filters($value) {
        if (!is_array($value)) {
            return array();
        }

        $sanitized_filters = array();
        
        foreach ($value as $filter_group) {
            if (!is_array($filter_group)) {
                continue;
            }
            
            $sanitized_group = array();
            foreach ($filter_group as $filter_data) {
                if (!is_array($filter_data) || 
                    !isset($filter_data['field_name']) || 
                    !isset($filter_data['operator']) || 
                    !isset($filter_data['compare_value'])) {
                    continue;
                }

                //ensure the operator is in the allowed operators array
                if (!in_array($filter_data['operator'], ['=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'])) {
                    continue;
                }
                
                //preserve the type of the compare value
                $compare_type = isset($filter_data['compare_type']) ? $filter_data['compare_type'] : 'text';
                $compare_value = $filter_data['compare_value'];
                switch ($compare_type) {
                    case 'number':
                        if (is_numeric($compare_value)) {
                            $compare_value = 0 + $compare_value;
                        } else {
                            continue;
                        }
                        break;
                    case 'date':
                        try {
                            $compare_value = strtotime($compare_value);
                        } catch (Exception $e) {
                            continue;
                        }
                        break;
                    // text is default
                }

                $sanitized_filter = array(
                    'field_name' => sanitize_text_field($filter_data['field_name']),
                    'operator' => $filter_data['operator'],
                    'compare_value' => $filter_data['compare_value'],
                    'compare_type' => $compare_type,
                    'is_meta_filter' => false
                );
                
                // If field is 'meta', use meta_key as field_name and set is_meta_filter to true
                if ($filter_data['field_name'] === 'meta' && isset($filter_data['meta_key']) && !empty($filter_data['meta_key'])) {
                    $sanitized_filter['field_name'] = sanitize_text_field($filter_data['meta_key']);
                    $sanitized_filter['is_meta_filter'] = true;
                }
                
                $sanitized_group[] = $sanitized_filter;
            }
            
            if (!empty($sanitized_group)) {
                $sanitized_filters[] = $sanitized_group;
            }
        }
        
        return $sanitized_filters;
    }

    public function register_styles(){
        //if we are on the filters and sorts page
        if (strpos(get_current_screen()->base, 'contentoracle-ai-chat-filters-sorts') === false) {
            return;
        }

        wp_enqueue_style('contentoracle-ai-chat-filters-sorts', plugin_dir_url(__FILE__) . 'assets/css/filters_sorts.css');
    }

    public function uninstall(){
        //todo: uninstall here
    }
}