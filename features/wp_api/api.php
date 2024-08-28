<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'ContentOracleApiConnection.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleApi extends PluginFeature{
    public function add_filters(){
        //todo: add filters here
    }

    public function add_actions(){
        add_action('rest_api_init', array($this, 'register_search_rest_route'));
        add_action('rest_api_init', array($this, 'register_healthcheck_rest_route'));
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //register the search route
    public function register_search_rest_route(){
        register_rest_route('contentoracle/v1', '/search', array(
            'methods' => 'POST',
            'permission_callback' => '__return_true', // this line was added to allow any site visitor to make an ai search request
            'callback' => array($this, 'ai_search'),
            'args' => array(
                'query' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key){
                        return is_string($param) && strlen($param) < 256;
                    }
                )
            )
        ));
    }

    //search callback
    public function ai_search($request){
        //get the query
        $query = $request->get_param('query');

        //find all posts of the types specified by the user that are relavent to the query
        $post_types = get_option($this->get_prefix() . 'post_types');
        $relavent_posts = [];
        foreach ($post_types as $post_type){
            //build a query for relavent posts
            $wp_query = new WP_Query(array(
                'post_type' => $post_type,
                's' => $query,
                'posts_per_page' => 5,   //NOTE: magic number, make it configurable later!
                'post_status' => 'publish'
            ));

            //add the posts to the relavent posts array, formatted for the api
            $relavent_posts[$post_type] = [];
            if ($wp_query->have_posts()){
                while ($wp_query->have_posts()){
                    $wp_query->the_post();
                    $relavent_posts[$post_type][] = array(
                        'title' => get_the_title(),
                        'body' => get_the_content(),
                        'url' => get_permalink(),
                        'type' => $post_type
                    );
                }
            }
        }

        //locate the 5 most relavent posts, prioritizing the user's goals
        //NOTE: this is a placeholder for now, will be replaced with a call to the ai
        $p = [];
        foreach ($relavent_posts as $post_type => $posts){
            foreach ($posts as $post){
                $p[] = $post;
            }
        }

        //send a request to the ai to generate a response
        $api = new ContentOracleApiConnection($this->get_prefix(), $this->get_base_url(), $this->get_base_dir());
        $response = $api->ai_search($query, $p);



        //return the response
        return new WP_REST_Response(array(
            'query' => $query,
            'context' => $p,
            'response' => $response
        ));
    }


    //register a contentoracle healthcheck route
    public function register_healthcheck_rest_route(){
        register_rest_route('contentoracle/v1', '/healthcheck', array(
            'methods' => 'GET',
            'permission_callback' => '__return_true', // this line was added to allow any site visitor to make an ai healthcheck request
            'callback' => function(){
                return new WP_REST_Response(array(
                    'status' => 'ok'
                ));
            }
        ));
    }

}