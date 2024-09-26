<?php

//include autoloader

use NlpTools\Stemmers\PorterStemmer;
use NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
use NlpTools\Utils\StopWords;

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'ContentOracleApiConnection.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleApi extends PluginFeature{
    public function add_filters(){
        add_filter('posts_clauses', array($this, 'find_relevant_content_by_score'), 10, 2);
    }

    public function add_actions(){
        add_action('rest_api_init', array($this, 'register_search_rest_route'));
        add_action('rest_api_init', array($this, 'register_healthcheck_rest_route'));
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //register the search route
    public function register_search_rest_route(){
        register_rest_route('contentoracle/v1', '/chat', array(
            'methods' => 'POST',
            'permission_callback' => function($request){    //nonce validations
                return true; //TODO: fix this one day!
                $nonce = $request->get_header('COAI-X-WP-Nonce');
                if (!wp_verify_nonce($nonce, 'contentoracle_chat_nonce')) {
                    return new WP_Error('rest_invalid_nonce', __('Invalid nonce: contentoracle_chat_nonce'), array('status' => 403));
                }
                return true;
            },
            'callback' => array($this, 'ai_chat'),
            'args' => array(
                'message' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key){
                        return is_string($param) && strlen($param) < 256;
                    },
                    'sanitize_callback' => function($param, $request, $key){
                        return sanitize_text_field($param);
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
                    },
                    'sanitize_callback' => function($param, $request, $key){
                        return array_map(function($msg){
                            return array(
                                'role' => sanitize_text_field($msg['role']),
                                'content' => sanitize_text_field($msg['content'])
                            );
                        }, $param);
                    }
                )
            )
        ));
    }

    //search callback
    public function ai_chat($request){
        //get the query
        $message = $request->get_param('message');

        //get the content to use in the response
        $content = $this->keyword_content_search($message);

        $content = array_slice($content, 0, 10); //NOTE: magic number, make it configurable later!

        //get the conversation from the request
        $conversation = $request->get_param('conversation');

        //get the ip address of the client for COAI rate limiting
        $client_ip = $this->get_client_ip();

        //send a request to the ai to generate a response
        $api = new ContentOracleApiConnection($this->get_prefix(), $this->get_base_url(), $this->get_base_dir(), $client_ip);
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
        //TODO: temporary handler for unauthenticated error, it should return a 401 unauthorized error
        if ( (isset($response['message']) && $response['message'] == 'Unauthenticated.') ){
            return new WP_REST_Response(
                array(
                    'response' => $response['message']
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
        if ( isset( $ai_action['content_id'] ) && get_post($ai_action['content_id']) ){
            $ai_action['content_url'] = get_post_permalink($ai_action['content_id']);
            $ai_action['content_excerpt'] = get_the_excerpt($ai_action['content_id']);
            $ai_action['content_featured_image'] = get_the_post_thumbnail_url($ai_action['content_id']);
        }

        //escape html entities, leaving <br> tags
        $ai_response = wp_kses( $ai_response, array(
            'br' => array(),
            'a' => array(
                'href' => array(),
                'target' => array()
            )
        ));

        //revert escaped br tags to normal br tags
        $ai_response = str_replace('&lt;br&gt;', '<br>', $ai_response);

        //replace newlines with html breaks
        //$ai_response = nl2br($ai_response);
        //wrap the main idea of the response (returned wrapped in |>#<|) in a span with a class "contentoracle-ai_chat_bubble_bot_main_idea"
        //TODO
        $ai_response = preg_replace('/\|\[#\]\|([^*]+)\|\[#\]\|/', '<span class="contentoracle-ai_chat_bubble_bot_main_idea">$1</span>', $ai_response);

        //find citations fitting the form |[$]|lorem ipsum|[$]||[@]|580|[@]|, and place an in-text citation there
        //NOTE: I want to replace the thing in the parentheses, of strings meeting this form: |[$]|lorem ipsum|[$]||[@]|580|[@]|
        $ai_response = preg_replace_callback(
            '/\|\[\$\]\|([^|]+)\|\[\$\]\|\s*\|\[@\]\|(\d+)\|\[@\]\|/',
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
            $ai_response
        );     

        //find citations fitting the form |[@]|580|[@]| (broken |[$]| wrapper), and place an in-text citation there
        $ai_response = preg_replace_callback(
            '/\|\[@\]\|(\d+)\|\[@\]\|/',
            function ($matches) use (&$label_num, &$content) { //& = pass by reference
                //get the post_id from the matches
                $post_id = $matches[1];
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

                return "<a href=\"$url\" class=\"contentoracle-inline_citation\" target=\"_blank\">$label</a>";
            },
            $ai_response
        );

        //find |[@]| with other content inside, and delete them
        $ai_response = preg_replace('/\|\[@\]\|[^|]+\|\[@\]\|/', '', $ai_response);

        //find other |[@]| wrappers and remove them
        $ai_response = preg_replace('/\|\[@\]\|/', '', $ai_response);

        //find |[$]| (broken wrappers) and delete them
        $ai_response = preg_replace('/\|\[\$\]\|/', '', $ai_response);


        //filter the content to remove any posts that were not used in the response
        $ai_content_used = array_filter($content, function($post) use ($ai_content_ids_used, $ai_action){
            return in_array($post['id'], $ai_content_ids_used) || $post['id'] == $ai_action['content_id'] ?? false;
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
        //tokenize the message
        $tok = new WhitespaceAndPunctuationTokenizer();
        $message_tokens = $tok->tokenize($message);

        //remove punctuation-only tokens
        $message_tokens = array_filter($message_tokens, function($token){
            return preg_match('/[a-zA-Z0-9]/', $token);
        });

        //only use the first 16 tokens
        $message_tokens = array_slice($message_tokens, 0, 16);

        //convert to lowercase
        $message_tokens = array_map('strtolower', $message_tokens);

        //get the stopwords from the file
        //these are official nltk stopwords
        $file = plugin_dir_path(__FILE__) . 'stopwords.txt';
        $file_content = file_get_contents($file);
        $stop_words = explode("\n", $file_content);
        $sw_filter = new StopWords($stop_words);

        //apply stopwords to search
        $search_terms = [];
        foreach ($message_tokens as $word) {
            $search_terms[] = $sw_filter->transform($word);
        }
        $search_terms = array_values(array_filter($search_terms));

        //stem the search terms
        $stemmer = new PorterStemmer();
        $stems = $stemmer->stemAll($search_terms);

        //find all posts of the types specified by the user that are relavent to the query
        $post_types = get_option($this->get_prefix() . 'post_types');
        if (!$post_types) $post_types = array('post', 'page');

        $relavent_posts = [];
        //by default, the wp_query s attribute needs all search terms to be in either the title, excerpt, or content of the post
        //I need to change this to allow capture of posts that do not contain every search term in either the title, excerpt, or content
        $wp_query = new WP_Query(array(
            'post_type' => $post_types,
            //'s' => implode(' ', $message_words),
            'coai_search' => $stems,
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

    /*
        modify the query to find the most relevant posts, using the following criteria:

            A score based system, where we apply each search term to the title, excerpt, type, and body of a post.
            Score will be added as a custom field, called coai_score.
            These search terms have already been stemmed and had stopwords removed.
                +8 for a post if a search term is found in the type
                +5 for a post if a search term is found in the title
                +3 for a post if a search term is found in the excerpt
                +1 for a post if a search term is found in the body
            These rules will be applied for every instance of a search term found in a field.  We will then order the 
            Query by the coai_score in descending order.
    */
    function find_relevant_content_by_score( $clauses, $wp_query ){
         //return if not the correct query
        if ( !isset( $wp_query->query_vars['coai_search'] ) ) return $clauses;

        global $wpdb;

        //get search terms from the wp_query, and escape them
        $search_terms = $wp_query->query_vars['coai_search'];
        array_map(function($term) use ($wpdb){
             return $wpdb->esc_like($term);
        }, $search_terms);

        //create the scoring system
        if ( !empty( $search_terms ) && is_array( $search_terms ) ){
            //create an extra select field to generate a score for each post
            $score_clauses = [];
            foreach($search_terms as $term){
                //subtract the length of the string with the search term removed from the length of the string to determine if it is present,
                //and divide by search term length to determine how many times it is present
                //divide each score by the overall length of the filed to avoid favoring longer 
                //TODO: add some kind of normalization to avoid heavily favoring longer posts
                $score_clauses[] = "((LENGTH({$wpdb->posts}.post_type) - LENGTH(REPLACE(LOWER({$wpdb->posts}.post_type), LOWER('{$term}'), ''))) / LENGTH('{$term}') * 8)";
                $score_clauses[] = "((LENGTH({$wpdb->posts}.post_title) - LENGTH(REPLACE(LOWER({$wpdb->posts}.post_title), LOWER('{$term}'), ''))) / LENGTH('{$term}') * 5)";
                $score_clauses[] = "((LENGTH({$wpdb->posts}.post_excerpt) - LENGTH(REPLACE(LOWER({$wpdb->posts}.post_excerpt), LOWER('{$term}'), ''))) / LENGTH('{$term}') * 3)";
                $score_clauses[] = "((LENGTH({$wpdb->posts}.post_content) - LENGTH(REPLACE(LOWER({$wpdb->posts}.post_content), LOWER('{$term}'), ''))) / LENGTH('{$term}') * 1)";
            }
            $score_clause = implode(' + ', $score_clauses);

            //add the score clause to the select statement
            $clauses['fields'] .= ", ({$score_clause}) as coai_score";

            //add where clauses to reduce the amount of posts we need to score by only getting posts with search terms that can be scored
            $clauses['where'] .= " AND (";
            $clauses['where'] .= " {$wpdb->posts}.post_title LIKE '%" . implode("%' OR {$wpdb->posts}.post_title LIKE '%", $search_terms) . "%'";
            $clauses['where'] .= " OR {$wpdb->posts}.post_excerpt LIKE '%" . implode("%' OR {$wpdb->posts}.post_excerpt LIKE '%", $search_terms) . "%'";
            $clauses['where'] .= " OR {$wpdb->posts}.post_content LIKE '%" . implode("%' OR {$wpdb->posts}.post_content LIKE '%", $search_terms) . "%'";
            $clauses['where'] .= " OR {$wpdb->posts}.post_type LIKE '%" . implode("%' OR {$wpdb->posts}.post_type LIKE '%", $search_terms) . "%'";
            $clauses['where'] .= ")";

            //add an order_by clause for the coai_score
            $clauses['orderby'] = "coai_score DESC";
        }

        return $clauses;
    }

    //get the ip address of the client
    function get_client_ip(){
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            // Check for IP from shared internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Check for IP passed from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } else {
            // Default fallback to REMOTE_ADDR
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Handle multiple IPs (e.g., "client IP, proxy IP")
        if (strpos($ip, ',') !== false)
            $ip = explode(',', $ip)[0];

        // Sanitize IP address
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'UNKNOWN';
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