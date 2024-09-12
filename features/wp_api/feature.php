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
        //
        ////NOTE: for now, this is sufficient, but we need to eventually sort the results by relevance
        //
        add_filter('posts_where', array($this, 'find_content_with_keywords'), 10, 2);
        add_filter('posts_orderby', array($this, 'order_content_by_relevance_with_keywords'), 10, 2);
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
                        if (!is_array($param)) return false;

                        //validate each element of the conversation array
                        foreach ($param as $msg){
                            if (!is_array($msg)) return false;
                            if (!isset($msg['role']) || !is_string($msg['role'])) return false;
                            if (!in_array($msg['role'], ['user', 'assistant', 'tool', 'system'])) return false;
                            if (!isset($msg['content']) || !is_string($msg['content'])) return false;
                        }

                        return is_array($param);
                    }
                )
            )
        ));
    }

    //search callback
    public function ai_search($request){
        
        //get the query
        $message = $request->get_param('message');

        //get the content to use in the response
        $content = $this->keyword_content_search($message);
        $content = array_slice($content, 0, 10); //NOTE: magic number, make it configurable later!

        //get the conversation from the request
        $conversation = $request->get_param('conversation');

        //send a request to the ai to generate a response
        $api = new ContentOracleApiConnection($this->get_prefix(), $this->get_base_url(), $this->get_base_dir());
        $response = $api->ai_chat($message, $content, $conversation);


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

        //create array holding ids of posts used in the response
        $label_num = 1;

        //apply post processing to the ai_response
        $ai_connection = $response['ai_connection'];
        $ai_response = $response['generated']['message'];
        $ai_action = $response['generated']['action'];
        $ai_content_ids_used = $response['generated']['content_used'] ?? [];

        //add the post link, excerpt, and featured image to the action
        if ( isset( $ai_action['content_id'] ) ){
            $ai_action['content_url'] = get_post_permalink($ai_action['content_id']);
            $ai_action['content_excerpt'] = get_the_excerpt($ai_action['content_id']);
            $ai_action['content_featured_image'] = get_the_post_thumbnail_url($ai_action['content_id']);
        }

        switch ($ai_connection) {
            case 'anthropic':
                //escape html entities, leaving <br> tags
                $ai_response['content'][0]['text']= htmlspecialchars($ai_response['content'][0]['text']);

                //revert br tags to html breaks
                $ai_response['content'][0]['text'] = str_replace('&lt;br&gt;', '<br>', $ai_response['content'][0]['text']);

                //replace newlines with html breaks
                $ai_response['content'][0]['text'] = nl2br($ai_response['content'][0]['text']);
                //wrap the main idea of the response (returned wrapped in |>#<|) in a span with a class "contentoracle-ai_chat_bubble_bot_main_idea"
                //TODO
                $ai_response['content'][0]['text'] = preg_replace('/\|\[#\]\|([^*]+)\|\[#\]\|/', '<span class="contentoracle-ai_chat_bubble_bot_main_idea">$1</span>', $ai_response['content'][0]['text']);

                //apply a hyperlink to the cited posts in the ai response, and put the in text citation in a sub tag
                //NOTE: I want to replace the thing in the parentheses, of strings meeting this form: |[$]|lorem ipsum|[$]||[@]|580|[@]|
                $ai_response['content'][0]['text'] = preg_replace_callback(
                    '/\|\[\$\]\|([^|]+)\|\[\$\]\|\|\[@\]\|(\d+)\|\[@\]\|/',
                    function ($matches) use (&$label_num, &$content) { //& = pass by reference
                        //get the text and post_id from the matches
                        $text = $matches[1];
                        $post_id = $matches[2];
                        //get the post url
                        $url = get_post_permalink($post_id);

                        //find the post in the content array, and give it a label
                        $label = "";
                        foreach ($content as &$post){   //& = pass by reference
                            if ( $post['id'] == $post_id ){
                                //account for the case where the post has already been cited
                                if ( !isset( $post['label'] ) ){
                                    $post['label'] = $label_num;
                                }
                                $label = $post['label'];
                                $label_num++;
                                break;
                            }
                        }

                        return "$text <a href=\"$url\" class=\"contentoracle-inline_citation\" target=\"_blank\">$label</a>";
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


        //filter the content to remove any posts that were not used in the response
        $ai_content_used = array_filter($content, function($post) use ($ai_content_ids_used, $ai_action){
            return in_array($post['id'], $ai_content_ids_used) || $post['id'] == $ai_action['content_id'];
        });

        //return the response
        return new WP_REST_Response(array(
            'message' => $message,
            'context_supplied' => $content,
            'context_used' => $ai_content_used,
            'response' => $ai_response,
            'action' => $ai_action
        ));
    }

    //simple keyword search to find relevant posts
    function keyword_content_search($message){

        //process the message
        $stop_words = [
            'a',
            'about',
            'above',
            'after',
            'again',
            'against',
            'all',
            'am',
            'an',
            'and',
            'any',
            'are',
            "aren't",
            'as',
            'at',
            'be',
            'because',
            'been',
            'before',
            'being',
            'below',
            'between',
            'both',
            'but',
            'by',
            "can't",
            'cannot',
            'could',
            "couldn't",
            'did',
            "didn't",
            'do',
            'does',
            "doesn't",
            'doing',
            "don't",
            'down',
            'during',
            'each',
            'few',
            'for',
            'from',
            'further',
            'had',
            "hadn't",
            'has',
            "hasn't",
            'have',
            "haven't",
            'having',
            'he',
            "he'd",
            "he'll",
            "he's",
            'her',
            'here',
            "here's",
            'hers',
            'herself',
            'him',
            'himself',
            'his',
            'how',
            "how's",
            'i',
            "i'd",
            "i'll",
            "i'm",
            "i've",
            'if',
            'in',
            'into',
            'is',
            "isn't",
            'it',
            "it's",
            'its',
            'itself',
            "let's",
            'me',
            'more',
            'most',
            "mustn't",
            'my',
            'myself',
            'no',
            'nor',
            'not',
            'of',
            'off',
            'on',
            'once',
            'only',
            'or',
            'other',
            'ought',
            'our',
            'ours',
            'ourselves',
            'out',
            'over',
            'own',
            'same',
            "shan't",
            'she',
            "she'd",
            "she'll",
            "she's",
            'should',
            "shouldn't",
            'so',
            'some',
            'such',
            'than',
            'that',
            "that's",
            'the',
            'their',
            'theirs',
            'them',
            'themselves',
            'then',
            'there',
            "there's",
            'these',
            'they',
            "they'd",
            "they'll",
            "they're",
            "they've",
            'this',
            'those',
            'through',
            'to',
            'too',
            'under',
            'until',
            'up',
            'very',
            'was',
            "wasn't",
            'we',
            "we'd",
            "we'll",
            "we're",
            "we've",
            'were',
            "weren't",
            'what',
            "what's",
            'when',
            "when's",
            'where',
            "where's",
            'which',
            'while',
            'who',
            "who's",
            'whom',
            'why',
            "why's",
            'with',
            "won't",
            'would',
            "wouldn't",
            'you',
            "you'd",
            "you'll",
            "you're",
            "you've",
            'your',
            'yours',
            'yourself',
            'yourselves'
        ];

        $message_words = explode(" ", strtolower($message));

        $message_words = array_filter($message_words, function($word) use ($stop_words){
            return !in_array($word, $stop_words);
        });
        $message_words = array_slice($message_words, 0, 32);
        

        //account for plurarls by adding a pluralized copy of each word
        //TODO: this is a placeholder, replace with a more robust solution
        $message_words = array_merge($message_words, array_map(function($word){
            return $word . 's';
        }, $message_words));

        //find all posts of the types specified by the user that are relavent to the query
        $post_types = get_option($this->get_prefix() . 'post_types');
        if (!$post_types) $post_types = array('post', 'page');

        $relavent_posts = [];
        //by default, the wp_query s attribute needs all search terms to be in either the title, excerpt, or content of the post
        //I need to change this to allow capture of posts that do not contain every search term in either the title, excerpt, or content
        $wp_query = new WP_Query(array(
            'post_type' => $post_types,
            'coai_search' => $message_words,
            'posts_per_page' => 10,   //NOTE: magic number, make it configurable later!
            'post_status' => 'publish',
            'orderby' => 'relevance'
        ));

        //NOTE: currently, the api only returns content used in the response.  I plan to change this to flag used content when I revamp the api
        //NOTE: to return an ai-generated json object.  FOr now, some content that is supplied to the ai is not returned in the response.

        //locate the 10 most relavent posts, prioritizing the user's goals
        //NOTE: this is a placeholder for now, will be replaced with a call to the ai
        $content = [];
        while ($wp_query->have_posts()){
            $wp_query->the_post();
            $entry = [
                'id' => get_the_ID(),
                'title' => get_the_title(),
                'url' => get_the_permalink(),
                'body' => get_the_content(),
                'type' => get_post_type()
            ];
            $content[] = $entry;
        }

        return $content;
    }


    //query the content to use to generate a response
    function find_content_with_keywords( $where, $wp_query ){

        //
        ////NOTE: for now, this is sufficient, but we need to eventually sort the results by relevance
        //

        //return if not the correct query
        if ( !isset( $wp_query->query_vars['coai_search'] ) ) return $where;

        global $wpdb;

        //get search terms from the wp_query
        $search_terms = $wp_query->query_vars['coai_search'];



        //add to WHERE clause...
        if ( !empty( $search_terms ) && is_array( $search_terms ) ){
            //escape the search terms
            $search_terms = array_map(function($term) use ($wpdb){
                return esc_html($term);
            }, $search_terms);

            //build the where clause
            $where .= " AND (";
            $where .= " {$wpdb->posts}.post_title LIKE '%" . implode("%' OR {$wpdb->posts}.post_title LIKE '%", $search_terms) . "%'";
            $where .= " OR {$wpdb->posts}.post_excerpt LIKE '%" . implode("%' OR {$wpdb->posts}.post_excerpt LIKE '%", $search_terms) . "%'";
            $where .= " OR {$wpdb->posts}.post_content LIKE '%" . implode("%' OR {$wpdb->posts}.post_content LIKE '%", $search_terms) . "%'";
            $where .= ")";
        }

        return $where;
    }

    //order the content in the keyword search
    function order_content_by_relevance_with_keywords($orderby, $wp_query){
        /*
        Sort the posts by relevance.  
        First include ones that include all the search terms in the title and content.
        Then include ones that include all the search terms in the title or content or excerpt.
        Then include ones that include some of the search terms in the title or content or excerpt.
        */

        //return if not the correct query
        if ( !isset( $wp_query->query_vars['coai_search'] ) ) return $orderby;

        global $wpdb;

        //get search terms from the wp_query
        $search_terms = $wp_query->query_vars['coai_search'];

        //add to ORDER BY clause...
        if ( !empty( $search_terms ) && is_array( $search_terms ) ){
            
            //escape the search terms
            $search_terms = array_map(function($term) use ($wpdb){
                return esc_html($term);
            }, $search_terms);

            //build the where clause
            $orderby = "IF(";
            $orderby .= " {$wpdb->posts}.post_title LIKE '%" . implode("%' AND {$wpdb->posts}.post_title LIKE '%", $search_terms) . "%'";
            $orderby .= " OR {$wpdb->posts}.post_excerpt LIKE '%" . implode("%' AND {$wpdb->posts}.post_excerpt LIKE '%", $search_terms) . "%'";
            $orderby .= " OR {$wpdb->posts}.post_content LIKE '%" . implode("%' AND {$wpdb->posts}.post_content LIKE '%", $search_terms) . "%'";
            $orderby .= ", 1, 0) DESC, ";
            $orderby .= "IF(";
            $orderby .= " {$wpdb->posts}.post_title LIKE '%" . implode("%' OR {$wpdb->posts}.post_title LIKE '%", $search_terms) . "%'";
            $orderby .= " OR {$wpdb->posts}.post_excerpt LIKE '%" . implode("%' OR {$wpdb->posts}.post_excerpt LIKE '%", $search_terms) . "%'";
            $orderby .= " OR {$wpdb->posts}.post_content LIKE '%" . implode("%' OR {$wpdb->posts}.post_content LIKE '%", $search_terms) . "%'";
            $orderby .= ", 1, 0) DESC, ";
            $orderby .= "IF(";
            $orderby .= " {$wpdb->posts}.post_title LIKE '%" . implode("%' OR {$wpdb->posts}.post_title LIKE '%", $search_terms) . "%'";
            $orderby .= " OR {$wpdb->posts}.post_excerpt LIKE '%" . implode("%' OR {$wpdb->posts}.post_excerpt LIKE '%", $search_terms) . "%'";
            $orderby .= " OR {$wpdb->posts}.post_content LIKE '%" . implode("%' OR {$wpdb->posts}.post_content LIKE '%", $search_terms) . "%'";
            $orderby .= ", 1, 0) DESC";
        }

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