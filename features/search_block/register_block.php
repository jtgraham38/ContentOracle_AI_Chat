<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleSearchBlock extends PluginFeature{
    public function add_filters(){
        //todo: add filters here
    }

    public function add_actions(){
        add_action('init', array($this, 'register_search_blocks'));
        add_action('pre_get_posts', array($this, 'handle_ai_search_request'));
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //register the custom search block
    public function register_search_blocks(){
        //include seach block utils
        require_once plugin_dir_path( __FILE__ ) . '/util.php';

        //register search block
        register_block_type(plugin_dir_path( __FILE__ ) . '/block/build');
    }

    //handle a search request
    public function handle_ai_search_request($query){
        // Check if it's the main query and not in the admin area
        if ($query->is_main_query() && !is_admin() && isset($_GET['contentoracle_ai_search'])) {
            // Get the ai_search query parameter
            $ai_search_query = sanitize_text_field($_GET['contentoracle_ai_search']);

            //redirect to the ai search results page, if set, with the ai search query
            $ai_results_page_id = get_option($this->get_prefix() . 'ai_results_page', null);
            $ai_results_page = get_post($ai_results_page_id);

            if ($ai_results_page && $ai_results_page->post_type == 'page' && $ai_results_page->post_status == 'publish') { 
                $should_redirect = isset( $_GET['contentoracle_ai_search_should_redirect'] );
                //ensure we only redirect if we are not already on the ai search results page
                if ( 
                    $should_redirect
                ) {
                    $ai_results_page_url = get_permalink($ai_results_page_id);
                    $ai_search_url = add_query_arg('contentoracle_ai_search', $ai_search_query, $ai_results_page_url);
                    wp_redirect($ai_search_url );
                    exit;
                }

            }
            //otherwise, redirect to orinary search results page
            else {
                $search_url = home_url('/?s=' . $ai_search_query);
                wp_redirect($search_url);
                exit;
            }
        }
    }

    //placeholder uninstall method to identify this block
    public function uninstall(){
        
    }
}