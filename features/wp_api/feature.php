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
                'message' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key){
                        return is_string($param) && strlen($param) < 256;
                    }
                ),
                'conversation' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key){
                        return is_array($param);
                        //todo: enhance this validation for role and content checks
                    }
                )
            )
        ));
    }

    //search callback
    public function ai_search($request){
        

        //get the query
        $message = $request->get_param('message');

        //find all posts of the types specified by the user that are relavent to the query
        $post_types = get_option($this->get_prefix() . 'post_types');
        if (!$post_types) $post_types = array('post', 'page');
        $relavent_posts = [];
        foreach ($post_types as $post_type){
            //build a query for relavent posts
            $wp_query = new WP_Query(array(
                'post_type' => $post_type,
                's' => $message,
                'posts_per_page' => 10,   //NOTE: magic number, make it configurable later!
                'post_status' => 'publish'
            ));

            //add the posts to the relavent posts array, formatted for the api
            $relavent_posts[$post_type] = [];
            if ($wp_query->have_posts()){
                while ($wp_query->have_posts()){
                    $wp_query->the_post();
                    $relavent_posts[$post_type][] = array(
                        'id' => get_the_ID(),
                        'title' => get_the_title(),
                        'body' => get_the_content(),
                        'url' => get_permalink(),
                        'type' => $post_type
                    );
                }
            }
        }


        //locate the 10 most relavent posts, prioritizing the user's goals
        //NOTE: this is a placeholder for now, will be replaced with a call to the ai
        $content = [];
        foreach ($relavent_posts as $post_type => $posts){
            foreach ($posts as $post){
                $content[] = $post;
            }
        }
        $content = array_slice($content, 0, 10); //NOTE: magic number, make it configurable later!

        //get the conversation from the request
        $conversation = $request->get_param('conversation');

        //send a request to the ai to generate a response
        $api = new ContentOracleApiConnection($this->get_prefix(), $this->get_base_url(), $this->get_base_dir());
        $response = $api->ai_search($message, $content, $conversation);

        //handle error in response
        if ( isset( $response['error'] ) ){
            return new WP_REST_Response(
                array(
                    'error' => $response['error']
                )
            );
        }
        if (isset($response['errors'])){
            return new WP_REST_Response(
                array(
                    'errors' => $response['errors']
                )
            );
        }

        //apply post processing to the ai_response
        $ai_connection = $response['ai_connection'];
        $ai_response = $response['response'];
        switch ($ai_connection) {
            case 'anthropic':
                //escape html entities
                $ai_response['content'][0]['text']= htmlspecialchars($ai_response['content'][0]['text']);
                //replace newlines with html breaks
                $ai_response['content'][0]['text'] = nl2br($ai_response['content'][0]['text']);
                //wrap the main idea of the response (returned wrapped in asterisks) in a span with a class "contentoracle-ai_chat_bubble_bot_main_idea"
                //TODO
                $ai_response['content'][0]['text'] = preg_replace('/\*([^*]+)\*/', '<span class="contentoracle-ai_chat_bubble_bot_main_idea">$1</span>', $ai_response['content'][0]['text']);

                //apply a hyperlink to the cited posts in the ai response, and put the in text citation in a sub tag
                //NOTE: I want to replace the think in the parentheses, of strings meeting this form: `lorem ipsum`(580)

                $ai_response['content'][0]['text'] = preg_replace_callback(
                    '/`([^`]+)`\s*\(([^)]+)\)/',
                    function ($matches) {
                        $text = $matches[1];
                        $post_id = $matches[2];
                        $url = get_post_permalink($post_id);
                        return "$text <a href=\"$url\"><sub>[$post_id]</sub></a>";
                    },
                    $ai_response['content'][0]['text']
                );          
                break;
            default:
                //return 501 not implemented error
                return new WP_REST_Response(array(
                    'error' => 'AI connection "' . $ai_connection . '" not implemented!',
                ), 501);
        }

        //return the response
        return new WP_REST_Response(array(
            'message' => $message,
            'context' => $content,
            'response' => $ai_response
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