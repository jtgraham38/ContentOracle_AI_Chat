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
require_once plugin_dir_path(__FILE__) . 'ResponseException.php';
require_once plugin_dir_path(__FILE__) . 'WPAPIErrorResponse.php';
require_once plugin_dir_path(__FILE__) . 'chat_logging/ChatLogger.php';

use jtgraham38\jgwordpresskit\PluginFeature;
use jtgraham38\wpvectordb\VectorTable;
use jtgraham38\wpvectordb\VectorTableQueue;
use jtgraham38\wpvectordb\query\QueryBuilder;

class ContentOracleApi extends PluginFeature{
    use ContentOracleChunkingMixin;
    use ContentOracle_ChatLoggerTrait;

    public function add_filters(){
        add_filter('posts_clauses', array($this, 'find_relevant_content_by_score'), 10, 2);
    }

    public function add_actions(){
        add_action('rest_api_init', array($this, 'register_search_rest_routes'));
        add_action('rest_api_init', array($this, 'register_healthcheck_rest_route'));
        add_action('rest_api_init', array($this, 'register_bulk_generate_embeddings_route'));
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //register the search route
    public function register_search_rest_routes(){
        //non-streamed route
        register_rest_route('contentoracle-ai-chat/v1', '/chat', array(
            'methods' => 'POST',
            'permission_callback' => function($request){
                // Verify the nonce
                $nonce = $request->get_header('X-WP-Nonce');
                if (!wp_verify_nonce($nonce, 'wp_rest')) {
                    return new WP_Error('rest_invalid_nonce', 'Invalid nonce', array('status' => 403));
                }
                
                // Check user capabilities
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

        //streamed route
        register_rest_route('contentoracle-ai-chat/v1', '/chat/stream', array(
            'methods' => 'POST',             //TODO: change to post on going live
            'permission_callback' => function($request){
                // Verify the nonce
                $nonce = $request->get_header('X-WP-Nonce');
                if (!wp_verify_nonce($nonce, 'wp_rest')) {
                    return new WP_Error('rest_invalid_nonce', 'Invalid nonce', array('status' => 403));
                }
                
                // Check user capabilities
                return true;
            },
            'callback' => array($this, 'streamed_ai_chat'),
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
                // 'conversation' => array(
                //     'required' => true,
                //     'validate_callback' => function($param, $request, $key){
                //         if (!is_array($param)) return false;

                //         //validate each element of the conversation array
                //         foreach ($param as $msg){
                //             if (!is_array($msg)) return false;
                //             if (!isset($msg['role']) || !is_string($msg['role'])) return false;
                //             if (!in_array($msg['role'], ['user', 'assistant', 'tool', 'system'])) return false;
                //             if (!isset($msg['content']) || !is_string($msg['content'])) return false;
                //         }

                //         return is_array($param);
                //     },
                //     'sanitize_callback' => function($param, $request, $key){
                //         return array_map(function($msg){
                //             return array(
                //                 'role' => sanitize_text_field($msg['role']),
                //                 'content' => sanitize_text_field($msg['content'])
                //             );
                //         }, $param);
                //     }
                // )
            )
        ));
    }

    

    //streamed chat callback
    public function streamed_ai_chat($request){
        //divider character, to separate the fragments of the response
        $private_use_char = "\u{E000}"; // U+E000 is the start of the private use area in Unicode

        // //get the query
        $message = $request->get_param('message');

        //get the content to use in the response
        //switch based on the chunking method
        $chunking_method = get_option($this->prefixed('chunking_method'), "token:256");
        try{
            switch ($chunking_method){
                case 'token:256':
                    $content = $this->token256_content_search($message);
                    $content = array_slice($content, 0, $this->config('token:256_content_limit')); //NOTE: magic number, make it configurable later!
                    break;
                default:
                    $content = $this->keyword_content_search($message);
                    $content = array_slice($content, 0, $this->config('keyword_content_limit')); //NOTE: magic number, make it configurable later!
                    break;
            }
        }
        catch (ContentOracle_ResponseException $e){
            $error_body = json_decode($e->response['body'], true);

            //returns the error response, with the raw error object, from the api
            return new Contentoracle_WPAPIErrorResponse(
                $e->response,
                $e->getMessage() ?? $error_body['message'] ?? 'An error occurred while generating a response.',
                $error_body['error'] ?? 'EMBED_ERR',
                $e->error_source
            );
        } 
        catch (Exception $e){
            //no error response supplied, so none is returned
            return new Contentoracle_WPAPIErrorResponse(
                [],
                $e->getMessage(),
                'EMBED_ERR'
            );
        }

        // Set headers for streaming
        // Ensure headers are sent before output
        if (!headers_sent()) {
            //header('Content-Type: text/plain'); // Adjust as needed
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // For Nginx
        }
        
        //TODO: move this to after the first fragment, so it only is sent if a response is generated
        //send the content supplied to the client block
        $id2post = [];
        foreach ($content as $post){
            //check if the post is already in id2post
            if (isset($id2post[$post['id']]))
            {
                //if the post is already in id2post, add the new content to the existing post
                $id2post[$post['id']]['body'] .= $post['body'];
            }
            //if the post is not in id2post, add it
            else{
                $id2post[$post['id']] = $post;
            }
        }
        $content_supplied = json_encode(["content_supplied" => $id2post]);
        echo $content_supplied;
        echo $private_use_char; // Send a private use character to signal the end of the fragment
        flush();

        //log the user message and the ai response
        $chat_log_id = $this->logUserChat($request, $id2post);

        //send the chat log id to the client
        if (isset($chat_log_id)){
            echo json_encode(["chat_log_id" => $chat_log_id]);
            echo $private_use_char; // Send a private use character to signal the end of the fragment
            flush();
        }

        //get the conversation from the request
        $conversation = $request->get_param('conversation');

        //get the ip address of the client for COAI rate limiting
        $client_ip = $this->get_client_ip();
        

        // Disable buffering to send output directly
        @ini_set('output_buffering', 'On');
        @ini_set('zlib.output_compression', 'Off');
        //@ini_set('implicit_flush', 'On');
        //ob_implicit_flush(1);


        //send a request to the ai to generate a response
        $api = new ContentOracleApiConnection(
            $this->get_prefix(), 
            $this->get_base_url(), 
            $this->get_base_dir(), 
            $client_ip,
            $this->config('chat_timeout'),
            $this->config('embed_timeout')
        );
        $full_response = [];
        $response = $api->streamed_ai_chat($message, $content, $conversation, 
            function($data) use (&$full_response){
                //divider character, to separate the fragments of the response
                $private_use_char = "\u{E000}"; // U+E000 is the start of the private use area in Unicode

                //start output buffering if it is not already started
                if (ob_get_level() == 0){
                    //start ouput buffering
                    ob_start();
                }

                //echo data to the output buffer
                echo $data;

                //get the full content of the output buffer, and clear it
                $full_data = ob_get_clean();

                //split the data out into discrete json objects
                //they will not be separated by the private use character
                //so find them where json objects butt up against each other
                $fragments = $this->get_json_fragments($full_data); 
                

                foreach ($fragments as $_fragment){
                    
                    //attempt to parse the data from the output buffer
                    $parsed = json_decode($_fragment, true);

                    //if there is an error, a valid fragment is not found
                    //so return the data to the output buffer and return
                    if (json_last_error() != JSON_ERROR_NONE){
                        //start output buffering
                        ob_start();

                        //echo the partial fragment
                        echo $data;
                        
                        //stop executing here
                        return;
                    }

                    //if we reach this point, a valid fragment was found
                    //and the output buffer is empty, and output buffering is stopped

                    //start output buffering again
                    ob_start();

                    //set error to none to start
                    $error = null;

                    //    \\    //    \\  make updates to the parsed data below  //    \\    //    \\
                    //handle an action fragment
                    if ( isset($parsed['action']) && isset($parsed['action']['content_id']) && get_post($parsed['action']['content_id']) ){

                        //add special handling for woocommerce posts for reliability
                        if (get_post_type($parsed['action']['content_id']) == 'product'){
                            //use woocommerce-specific functions to get the data
                            try{
                                $product = wc_get_product($parsed['action']['content_id']);
                                $parsed['action']['content_type'] = 'product';
                                $parsed['action']['content_url'] = $product->get_permalink();
                                $parsed['action']['content_excerpt'] = $product->get_short_description();
                                $parsed['action']['content_featured_image'] = $product->get_image();
                            }
                            catch (Exception $e){
                                //if there is an error, use the default behavior
                                $parsed['action']['content_type'] = get_post_type($parsed['action']['content_id']);
                            $parsed['action']['content_url'] = get_post_permalink($parsed['action']['content_id']);
                            $parsed['action']['content_excerpt'] = get_the_excerpt($parsed['action']['content_id']);
                            $parsed['action']['content_featured_image'] = get_the_post_thumbnail_url($parsed['action']['content_id']);
                            }
                        } else {
                            $parsed['action']['content_type'] = get_post_type($parsed['action']['content_id']);
                            $parsed['action']['content_url'] = get_post_permalink($parsed['action']['content_id']);
                            $parsed['action']['content_excerpt'] = get_the_excerpt($parsed['action']['content_id']);
                            $parsed['action']['content_featured_image'] = get_the_post_thumbnail_url($parsed['action']['content_id']);
                        }
                    }
                    //handle an engineered_prompt fragment
                    else if ( isset($parsed['engineered_prompt']) ){
                        //encode and echo the engineered input
                        //modifications to $parsed here...
                    }
                    //handle an error fragment
                    else if ( isset($parsed['error']) ){
                        //don't use an exception, create the response here directly
                        $error = new Contentoracle_WPAPIErrorResponse(
                            $parsed,
                            $parsed['message'],
                            $parsed['error'],
                            'coai'
                        );
                    }
                    //handle multiple errors
                    else if (isset($parsed['errors'])){
                        //don't use an exception, create the response here directly
                        $error = new Contentoracle_WPAPIErrorResponse(
                            $parsed,
                            'Multiple errors in response from the AI',
                            'AI_CHAT_ERR',
                            'coai'
                        );
                    }
                    //handle unauthenticated
                    else if (isset($parsed['message']) && $parsed['message'] == 'Unauthenticated.'){
                        //don't use an exception, create the response here directly
                        $error = new Contentoracle_WPAPIErrorResponse(
                            $parsed,
                            'Unauthenticated.',
                            'AI_CHAT_ERR',
                            'coai'
                        );
                    }
                    //handle chat response fragment
                    else{
                        //modifications to $parsed here...
                    }
                    //    \\    //    \\  make updates to the parsed data above  //    \\    //    \\

                    //when we reach here, the parsed data is ready to be sent to the client
                
                    //check if $error is set
                    if ($error){
                        //encode and echo the error
                        echo json_encode($error);
                    }
                    //if no error, echo the parsed data
                    else{
                        //queue the response for logging
                        $full_response[] = $parsed;

                        //encode and echo the parsed data
                        echo json_encode($parsed);
                    }

                    //looks like maybe the streaming will wokr irregulary on certain hosts, where each chunk is not a full json object.
                    //TODO: brainstorm ideas to fix this
                    //streaming does work, but "hoobadooba" appears at irregular intrevals on the client console

                    //separator and flush
                    echo $private_use_char;
                    ob_flush();    //flush to buffer
                    flush();    //flush to stream
                    //ensure output is flushed
                    while (ob_get_level() > 0) ob_end_clean();
                }
            }
        );




        //log the user message and the ai response
        // $this->logUserChat($request, $id2post);
        // $this->logAiChat($request, $ai_chat_response);

        //assemble the full text of the response from the full_response array
        $ai_chat_response = "";
        foreach ($full_response as $fragment){
            $ai_chat_response .= $fragment['generated']['message'];
        }

        //log the ai response
        $this->logAiChat($ai_chat_response, $chat_log_id);


        //NOTE: previously had a die here, but I could not get the full response with it there for some reason
    }

    //search callback
    public function ai_chat($request){
        //get the query
        $message = $request->get_param('message');

        //get the content to use in the response
        //switch based on the chunking method
        $chunking_method = get_option($this->prefixed('chunking_method'));
        try{
            switch ($chunking_method){
                case 'token:256':
                    $content = $this->token256_content_search($message);
                    $content = array_slice($content, 0, $this->config('token:256_content_limit')); //NOTE: magic number, make it configurable later!
                    break;
                default:
                    $content = $this->keyword_content_search($message);
                    $content = array_slice($content, 0, $this->config('keyword_content_limit')); //NOTE: magic number, make it configurable later!
                    break;
            }
        } 
        catch (ContentOracle_ResponseException $e){
            $error_body = json_decode($e->response['body'], true);
            //returns the error response, with the raw error object, from the api
            return new Contentoracle_WPAPIErrorResponse(
                $e->response,
                $e->getMessage() ?? $error_body['message'] ?? 'An error occurred while generating a response.',
                $error_body['error'] ?? 'EMBED_ERR',
                $e->error_source
            );
        }
        catch (Exception $e){
            //no error response supplied, so none is returned
            return new Contentoracle_WPAPIErrorResponse(
                [],
                $e->getMessage(),
                'EMBED_ERR'
            );
        }

        //get the configured post meta for each post
        $content = $this->add_meta_attributes($content);

        //get the conversation from the request
        $conversation = $request->get_param('conversation');

        //get the ip address of the client for COAI rate limiting
        $client_ip = $this->get_client_ip();
        
        //send a request to the ai to generate a response
        $api = new ContentOracleApiConnection(
            $this->get_prefix(), 
            $this->get_base_url(), 
            $this->get_base_dir(), 
            $client_ip,
            $this->config('chat_timeout'),
            $this->config('embed_timeout')
        );
        try{
            //get an ai response
            $response = $api->ai_chat($message, $content, $conversation);
        }
        catch (ContentOracle_ResponseException $e){
            //returns the error response, with the raw error object, from the api
            $error_body = json_decode($e->response['body'], true);

            return new Contentoracle_WPAPIErrorResponse(
                $e->response,
                $e->getMessage() ?? $error_body['message'] ?? 'An error occurred while generating a response.',
                $error_body['error'] ?? 'AI_CHAT_ERR',
                $e->error_source
            );
        }
        catch (Exception $e){
            //no error response supplied, so none is returned
            return new Contentoracle_WPAPIErrorResponse(
                [],
                $e->getMessage(),
                'AI_CHAT_ERR'
            );
        }

        //apply post processing to the ai_response
        $ai_connection = $response['ai_connection'];
        $ai_response = $response['generated']['message'];
        $ai_engineered_input = $response['generated']['engineered_input'];
        $ai_action = $response['generated']['action'];

        //add the post link, excerpt, and featured image to the action
        if ( isset( $ai_action['content_id'] ) && get_post($ai_action['content_id']) ){
            $ai_action['content_type'] = get_post_type($ai_action['content_id']);
            $ai_action['content_url'] = get_post_permalink($ai_action['content_id']);
            $ai_action['content_excerpt'] = get_the_excerpt($ai_action['content_id']);
            $ai_action['content_featured_image'] = get_the_post_thumbnail_url($ai_action['content_id']);
        }

        //convert the content used to a id-to-post array
        $id2post = [];
        foreach ($content as $post){
            //check if the post is already in id2post
            if (isset($id2post[$post['id']]))
            {
                //if the post is already in id2post, add the new content to the existing post
                $id2post[$post['id']]['body'] .= $post['body'];
            }
            //if the post is not in id2post, add it
            else{
                $id2post[$post['id']] = $post;
            }
        }

        //log the user message and the ai response
        $chat_log_id = $this->logUserChat($request, $id2post);
        $this->logAiChat($ai_response, $chat_log_id);
        
        //return the response
        return new WP_REST_Response(array(
            'message' => $message,
            'content_supplied' => $id2post,
            'response' => $ai_response,
            'action' => $ai_action,
            'engineered_prompt' => $ai_engineered_input,
            'chat_log_id' => $chat_log_id,
        ));
    }

    // register the bulk generate embeddings route
    public function register_bulk_generate_embeddings_route(){
        register_rest_route('contentoracle-ai-chat/v1', '/content-embed', array(
            'methods' => 'POST',
            'permission_callback' => function($request){
                // Verify the nonce
                $nonce = $request->get_header('X-WP-Nonce');
                if (!wp_verify_nonce($nonce, 'wp_rest')) {
                    return new WP_Error('rest_invalid_nonce', 'Invalid nonce', array('status' => 403));
                }
                
                // Check user capabilities
                return current_user_can('edit_posts');
            },
            'callback' => array($this, 'bulk_generate_embeddings'),
            'args' => array(
                'for' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key){
                        //check if it is in "all", "not_embedded", or a single post id
                        return in_array($param, ['all', 'not_embedded']) || is_numeric($param);
                    },
                    'sanitize_callback' => function($param, $request, $key){
                        if (in_array($param, ['all', 'not_embedded'])){
                            return sanitize_text_field($param);
                        }
                        else{
                            return intval($param);
                        }
                    }
                )
            )
        ));
    }

    //bulk generate embeddings
    public function bulk_generate_embeddings($request){
        global $wpdb;

        $for = $request->get_param('for');

        //create a queue
        $queue = new VectorTableQueue($this->get_prefix());

        //get the posts based on the parameter
        $posts = [];

        $post_types = get_option($this->prefixed('post_types'));
        $post_types_str = "'" . implode("','", $post_types) . "'";
        switch ($for){
            case 'all':
                //get the ids of all posts of the correct post type that are published
                //and where the body has no chunks
                //and are not in the embed queue
                //with a prepared statement
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts} 
                        WHERE post_type IN ($post_types_str) 
                        AND post_status = 'publish'
                        AND ID NOT IN (SELECT post_id FROM {$queue->get_table_name()})"
                    ),
                    OBJECT
                );

                break;
            case 'not_embedded':

                //get ids of posts that have embeddings
                $VT = new VectorTable($this->get_prefix());
                $vecs = $VT->get_all();
                $embedded_ids = array_map(function($vec){
                    return intval($vec->post_id);
                }, $vecs);
                $embedded_ids[] = -1;

                //get posts ids of posts of the correct type that have no embeddings
                //include a where not in clause if there are embedded ids, otherwise exclude it
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts} 
                        WHERE post_type IN ($post_types_str) 
                        AND post_status = 'publish'
                        AND ID NOT IN (" . implode(',', $embedded_ids) . ")
                        AND ID NOT IN (SELECT post_id FROM {$queue->get_table_name()})"
                    ),
                    OBJECT
                );

                break;
            case is_numeric($for):
                //TODO: possibly check to make sure the post exists
                $results = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts} 
                        WHERE ID = %d",
                        $for
                    ),
                    OBJECT
                );
                break;
        }

        //read the post ids off of the objects
        $post_ids = array_map(function($result){
            return $result->ID;
        }, $results);
        

        //enqueue the posts for embedding generation
        try{
            $queue->add_posts($post_ids);
        }
        catch (Exception $e){
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $e->getMessage()
            ), 500);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'message' => "Posts enqueued for embedding generation."
        ), 200);
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
        $post_types = get_option($this->prefixed('post_types'));
        if (!$post_types) $post_types = array('post', 'page');

        $relavent_posts = [];
        //by default, the wp_query s attribute needs all search terms to be in either the title, excerpt, or content of the post
        //I need to change this to allow capture of posts that do not contain every search term in either the title, excerpt, or content
        $wp_query = new WP_Query(array(
            'post_type' => $post_types,
            //'s' => implode(' ', $message_words),
            'coai_search' => $stems,
            'posts_per_page' => $this->config('keyword_content_limit'),   //NOTE: magic number, make it configurable later!
            'post_status' => 'publish',
            'orderby' => 'relevance'
        ));

        //NOTE: currently, the api only returns content used in the response.  I plan to change this to flag used content when I revamp the api
        //NOTE: to return an ai-generated json object.  FOr now, some content that is supplied to the ai is not returned in the response.

        //locate the n most relavent posts, prioritizing the user's goals
        //NOTE: this is a placeholder for now, will be replaced with a call to the ai
        $content = [];
        while ($wp_query->have_posts()){
            $wp_query->the_post();
            $entry = [
                'id' => get_the_ID(),
                'title' => esc_html(get_the_title()),
                'url' => esc_url(get_the_permalink()),
                'body' => esc_html(get_the_content()),
                'type' => esc_html(get_post_type()),
                'image' => esc_url(get_the_post_thumbnail_url($post_id)),
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

            //load the filters and sorts from the database into a query builder object
            $query_params = new QueryBuilder($wpdb->posts, $wpdb->postmeta);
            $filters_option = get_option($this->prefixed('filters'), array());
            
            foreach ($filters_option as $i=>$group){
                $query_params->add_filter_group('coai_lexsearch_filter_group_' . $i);

                foreach ($group as $filter){
                    //parse the compare value as the correct type
                    $compare_value = $filter['compare_value'];
                    switch ($filter['compare_type']){
                        case 'number':
                            $compare_value = floatval($compare_value);
                            break;
                        case 'date':
                            $compare_value = new DateTime($compare_value);
                            break;
                        // text is default
                    }

                    //set the compare value to the correct type
                    $filter['compare_value'] = $compare_value;

                    //add the filter to the query builder
                    $query_params->add_filter('coai_lexsearch_filter_group_' . $i, $filter);
                }
            }

            //add a filter to remove posts with empty content
            $query_params->add_filter('coai_lexsearch_filter_group_empty_content', [
                'field_name' => 'post_content',
                'operator' => '!=',
                'compare_value' => ''
            ]);

            //load the sorts from the database into a query builder object
            $sorts_option = get_option($this->prefixed('sorts'), array());
            foreach ($sorts_option as $i=>$sort){   
                //set meta type to "", if it is not already set
                if (!isset($sort['meta_type'])){
                    $sort['meta_type'] = "text";
                }
                $query_params->add_sort($sort);
            }
            
            //add filters for post types and status
            $post_types = get_option($this->prefixed('post_types'));
            if (!$post_types) $post_types = array('post', 'page');


            //apply the filters to the query if there are any
            if ($query_params->has_filters()) {
                // Add LEFT JOIN with wp_postmeta table
                $clauses['join'] .= " LEFT JOIN {$wpdb->postmeta} pm ON ({$wpdb->posts}.ID = pm.post_id)";
                
                
                // Apply the filters to the query
                $clauses['where'] .= ' AND (' . $query_params->get_filters_sql() . ')';
            }
            
            //add an order_by clause for the coai_score
            $clauses['orderby'] = "coai_score DESC";

            //add secondary orderby clauses for each sort
            if ($query_params->has_sorts()){
                $clauses['orderby'] .= ", " . $query_params->get_sorts_sql();

                //create the post meta join statements and attribute select statements
                $post_meta_joins = [];
                $post_meta_selects = [];
                foreach ($query_params->get_sorts() as $i => $sort){
                    if ($sort->is_meta_sort){
                        $post_meta_joins[] = "LEFT JOIN $wpdb->postmeta " .$this->get_prefix() . "_pm$i ON $wpdb->posts.ID = " .$this->get_prefix() . "_pm$i.post_id AND " .$this->get_prefix() . "_pm$i.meta_key = '" . esc_sql($sort->field_name) . "'";
                        $post_meta_selects[] = "MAX(" .$this->get_prefix() . "_pm$i.meta_value) as " . esc_sql($sort->field_name);
                    }
                }

                //add the post meta joins and selects to the clauses
                $clauses['join'] .= " " . implode(" ", $post_meta_joins);
                $clauses['fields'] .= ", " . implode(", ", $post_meta_selects);

                //add group by clause to collapse duplicate posts from post meta records
                $clauses['groupby'] = "$wpdb->posts.ID";
            }
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
        $api = new ContentOracleApiConnection(
            $this->get_prefix(), 
            $this->get_base_url(), 
            $this->get_base_dir(), 
            $this->get_client_ip(),
            $this->config('chat_timeout'),
            $this->config('embed_timeout')
        );

        //get the embedding from the ai
        $response = $api->query_vector($message);

        if (!isset($response['embeddings'][0]['embedding'])){
            throw new ContentOracle_ResponseException(
                'No embeddings returned from the AI',
                $response,
                'coai'
            );
        }

        $embedding = $response['embeddings'][0]['embedding'];

        //load the filters from the database into a query builder object
        //NOTE: the below line causes a silent error, fix it.

        //I have other var dump dies further down the line in vectortable.
        //I have made changes throughout wpvectordb that need to be committed to the main repo, copy/paste would be best.
        $query_params = new QueryBuilder();

        $filters_option = get_option($this->prefixed('filters'), array());
        foreach ($filters_option as $i=>$group){
            $query_params->add_filter_group('coai_semsearch_filter_group_' . $i);
            foreach ($group as $filter){
                //parse the compare value as the correct type
                $compare_value = $filter['compare_value'];
                switch ($filter['compare_type']){
                    case 'number':
                        $compare_value = floatval($compare_value);
                        break;
                    case 'date':
                        $compare_value = new DateTime($compare_value);
                        break;
                    // text is default
                }

                //set the compare value to the correct type
                $filter['compare_value'] = $compare_value;

                //add the filter to the query builder
                $query_params->add_filter('coai_semsearch_filter_group_' . $i, $filter);
            }
        }

        //add filters to the query builder for post type and status
        //add a filter for post types and status and empty content
        $post_types = get_option($this->prefixed('post_types'));
        if (!$post_types) $post_types = array('post', 'page');

        $query_params->add_filter_group('_semsearch_post_types');
        $query_params->add_filter('_semsearch_post_types', [
            'field_name' => 'post_type',
            'operator' => 'IN',
            'compare_value' => $post_types
        ]);

        $query_params->add_filter_group('_semsearch_post_status');
        $query_params->add_filter('_semsearch_post_status', [
            'field_name' => 'post_status',
            'operator' => '=',
            'compare_value' => 'publish'
        ]);

        $query_params->add_filter_group('_semsearch_post_content');
        $query_params->add_filter('_semsearch_post_content', [
            'field_name' => 'post_content',
            'operator' => '!=',
            'compare_value' => ''
        ]);
        //load the sorts from the database into a query builder object
        $sorts_option = get_option($this->prefixed('sorts'), array());
        foreach ($sorts_option as $i=>$sort){
            $query_params->add_sort($sort);
        }

        //then, find the most similar vectors in the database table
        $vt = new VectorTable( $this->get_prefix() );

        $ordered_vec_ids = $vt->search( $embedding, 20, $query_params );
        //then, get the posts and sections each vector corresponds to
        $vecs = $vt->ids( $ordered_vec_ids );
        

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
            $chunk = $this->get_feature('embeddings')->coai_chat_token256_get_chunk($post->post_content, $sequence_no);
            
            //add the chunk to the chunks array
            $chunks[] = [
                'id' => $post_id,
                'title' => esc_html($post->post_title),
                'url' => esc_url(get_the_permalink($post_id)),
                'body' => esc_html($chunk),
                'type' => esc_html(get_post_type($post_id)),
                'image' => esc_url(get_the_post_thumbnail_url($post_id)),
            ];
        }

        //return the post chunks
        return $chunks;
    }

    //get post meta configured by the user for each chunk
    function add_meta_attributes($chunks){
        /*
        Chunks is an array of arrays like this: 
        Array
        (
            [id] => 542
            [title] => Title
            [url] => http://url.com/123
            [body] => lorem ipsum
            [type] => post_type
            [image] => http://image.com/123.jpg
        )
        */
        //get post types
        $post_types = get_option($this->prefixed('post_types'));
        if (!$post_types) $post_types = array('post', 'page');

        //loop over each chunk
        foreach ($chunks as &$chunk){
            //skip posts of types that should not be used
            if (!in_array($chunk['type'], $post_types)) continue;

            //get the post meta for the post
            $meta = get_post_meta($chunk['id']);

            //get the meta keys for that post type configured by the user
            $keys = get_option($this->prefixed( $chunk['type'] . '_prompt_meta_keys' ), []);

            //filter out the meta that is not configured by the user
            $meta = array_filter($meta, function($key) use ($keys){
                return in_array($key, $keys);
            }, ARRAY_FILTER_USE_KEY);

            //add the meta to the chunk
            $chunk['meta'] = $meta;
        }

        //return the chunks with the meta added
        return $chunks;
    }

    //register a contentoracle healthcheck route
    function register_healthcheck_rest_route(){
        register_rest_route('contentoracle-ai-chat/v1', '/healthcheck', array(
            'methods' => 'GET',
            'permission_callback' => '__return_true', // this line was added to allow any site visitor to make an ai healthcheck request
            'callback' => function(){
                return new WP_REST_Response(array(
                    'status' => 'ok'
                ));
            }
        ));
    }

    //get json objects from a string containing multiple json objects
    function get_json_fragments($data){
        //split the data out into discrete json objects
        //they will not be separated by the private use character
        //so find them where json objects butt up against each other
        $fragments = [];
        $start = 0;
        $end = 0;
        $depth = 0;
        for ($i = 0; $i < strlen($data); $i++){
            if ($data[$i] == '{'){
                $depth++;
                if ($depth == 1) $start = $i;
            }
            else if ($data[$i] == '}'){
                $depth--;
                if ($depth == 0){
                    $end = $i;
                    $fragments[] = substr($data, $start, $end - $start + 1);
                }
            }
        }

        return $fragments;
    }

    //placeholder uninstall method to identify this feature
    public function uninstall(){
    }
}