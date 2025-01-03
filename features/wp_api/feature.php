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
require_once plugin_dir_path(__FILE__) . '../embeddings/VectorTable.php';
require_once plugin_dir_path(__FILE__) . '../embeddings/chunk_getters.php';

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
                    return new WP_Error('rest_invalid_nonce', 'Invalid nonce: contentoracle_chat_nonce', array('status' => 403));
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
        //switch based on the chunking method
        $chunking_method = get_option($this->get_prefix() . 'chunking_method');
        switch ($chunking_method){
            case 'token:256':
                $content = $this->token256_content_search($message);
                $content = array_slice($content, 0, 10); //NOTE: magic number, make it configurable later!
                break;
            default:
                $content = $this->keyword_content_search($message);
                $content = array_slice($content, 0, 3); //NOTE: magic number, make it configurable later!
                break;
        }

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

        //apply post processing to the ai_response
        $ai_connection = $response['ai_connection'];
        $ai_response = $response['generated']['message'];
        $ai_action = $response['generated']['action'];
        $ai_content_ids_used = $response['generated']['content_used'] ?? [];

        //add the post link, excerpt, and featured image to the action
        if ( isset( $ai_action['content_id'] ) && get_post($ai_action['content_id']) ){
            $ai_action['content_type'] = get_post_type($ai_action['content_id']);
            $ai_action['content_url'] = get_post_permalink($ai_action['content_id']);
            $ai_action['content_excerpt'] = get_the_excerpt($ai_action['content_id']);
            $ai_action['content_featured_image'] = get_the_post_thumbnail_url($ai_action['content_id']);
        }

        //wrap the main idea of the response (returned wrapped in |>#<|) in a span with a class "contentoracle-ai_chat_bubble_bot_main_idea"
        //TODO
        $ai_response = preg_replace('/\|\[#\]\|([^*]+)\|\[#\]\|/', '<span class="contentoracle-ai_chat_bubble_bot_main_idea">$1</span>', $ai_response);

        //find citations fitting the form |[$]|lorem ipsum|[$]||[@]|580|[@]|, and place an in-text citation there
        //NOTE: I want to replace the thing in the parentheses, of strings meeting this form: |[$]|lorem ipsum|[$]||[@]|580|[@]|
        $posts_cited = [];
        $ai_response = preg_replace_callback(
            '/\|\[\$\]\|([^|]+)\|\[\$\]\|\s*\|\[@\]\|(\d+)\|\[@\]\|/',
            function ($matches) use (&$content, &$posts_cited) { //& = pass by reference
                //get the text and post_id from the matches
                $text = $matches[1];
                $post_id = $matches[2];
                //get the post url
                $url = get_post_permalink($post_id);

                //find the post in the content array, and give it a label
                $label = "";
                foreach ($content as &$post){   //& = pass by reference
                    if ( $post['id'] == $post_id ){

                        //check if the post has been used already
                        if ( !in_array($post_id, $posts_cited) ){
                            $posts_cited[] = $post_id;
                        }

                        //get the label for the inline citation
                        $label = array_search($post_id, $posts_cited) + 1;
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
            function ($matches) use (&$content, &$posts_cited) { //& = pass by reference
                //get the post_id from the matches
                $post_id = $matches[1];
                //get the post url
                $url = get_post_permalink($post_id);

                //find the post in the content array, and give it a label
                $label = "";
                foreach ($content as &$post){   //& = pass by reference
                    if ( $post['id'] == $post_id ){
                       
                        //check if the post has been used already
                        if ( !in_array($post_id, $posts_cited) ){
                            $posts_cited[] = $post_id;
                        }

                        //get the label for the inline citation
                        $label = array_search($post_id, $posts_cited) + 1;
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
        $ai_content_used = array_values($ai_content_used);  //ensure the array keys are starting at 0, incrementing by 1

        //convert the posts to cite to an array of post objects
        $posts_cited = array_map(function($id){
            return [
                'id' => $id,
                'title' => get_the_title($id),
                'url' => get_the_permalink($id),
                'type' => get_post_type($id)
            ];
        }, $posts_cited);
        
        //return the response
        return new WP_REST_Response(array(
            'message' => $message,
            'context_supplied' => $content,
            'context_used' => $ai_content_used,     //chunks used in the response
            'citations' => $posts_cited,            //posts cited in the response
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
            //create the score clause, including the division by the total length of the post for normalization
            $score_clause = "(" . implode(' + ', $score_clauses) . ") / (LENGTH({$wpdb->posts}.post_title) + LENGTH({$wpdb->posts}.post_excerpt) + LENGTH({$wpdb->posts}.post_content) + 1)";

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
            $ip = filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Check for IP passed from proxy
            $ip = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = filter_var($_SERVER['HTTP_X_FORWARDED'], FILTER_VALIDATE_IP);
        } elseif (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])) {
            $ip = filter_var($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'], FILTER_VALIDATE_IP);
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = filter_var($_SERVER['HTTP_FORWARDED_FOR'], FILTER_VALIDATE_IP);
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ip = filter_var($_SERVER['HTTP_FORWARDED'], FILTER_VALIDATE_IP);
        } else {
            // Default fallback to REMOTE_ADDR
            $ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
        }

        // Handle multiple IPs (e.g., "client IP, proxy IP")
        if (strpos($ip, ',') !== false)
            $ip = explode(',', $ip)[0];

        // Sanitize IP address
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'UNKNOWN';
    }

    //token:256 embedding search
    function token256_content_search($message){
        //begin by embedding the user's message
        $api = new ContentOracleApiConnection($this->get_prefix(), $this->get_base_url(), $this->get_base_dir(), $this->get_client_ip());
        $response = $api->query_vector($message);
        if (empty($response['embeddings'])){
            throw new Exception('No embeddings returned from the AI');
        }

        $embedding = $response['embeddings'][0]['embedding'];

        
        //then, find the most similar vectors in the database table
        $vt = new ContentOracle_VectorTable( $this->get_prefix() );
        $ordered_vec_ids = $vt->search( $embedding, 10 );



        //then, get the posts and sections each vector corresponds to
        $vecs = $vt->ids( $ordered_vec_ids );

        //sort the vectors into the order returned by the search
        $vecs = array_map(function($id) use ($vecs){
            foreach ($vecs as $vec){
                if ($vec->id == $id){
                    return $vec;
                }
            }
        }, $ordered_vec_ids);

        //create an array of the content embedding data
        $content_embedding_datas = [];
        foreach ($vecs as &$vec){
            $content_embedding_datas[] = [
                'id' => $vec->id,
                'post_id' => $vec->post_id,
                'sequence_no' => $vec->sequence_no,
            ];
        }

        //use the sequence numbers and post metas to retrieve the correct portions of the posts
        $chunks = [];
        foreach ($content_embedding_datas as $data){
            $post_id = $data['post_id'];
            $sequence_no = $data['sequence_no'];
            
            //get the post
            $post = get_post($post_id);

            //get the post content for the sequence number
            $chunk = token256_get_chunk($post->post_content, $sequence_no);

            //add the chunk to the chunks array
            $chunks[] = [
                'id' => $post_id,
                'title' => $post->post_title,
                'url' => get_the_permalink($post_id),
                'body' => $chunk,
                'type' => get_post_type($post_id)
            ];
        }

        //return the post chunks
        return $chunks;
    }



    //register a contentoracle healthcheck route
    function register_healthcheck_rest_route(){
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