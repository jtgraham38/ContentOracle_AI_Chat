<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//autoloader
require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\wpvectordb\VectorTable;

//include the response exception class
require_once plugin_dir_path(__FILE__) . 'ResponseException.php';

//include the util file
require_once plugin_dir_path(__FILE__) . 'util.php';

class ContentOracleApiConnection{

    use ContentOracleChunkingMixin;
    use ContentOracleBulkContentEmbeddingMixin;

    protected $prefix;
    protected $base_url;
    protected $base_dir;
    protected $client_ip;
    protected $chat_timeout;
    protected $embed_timeout;

    public function __construct($prefix, $base_url, $base_dir, $client_ip, $chat_timeout=250, $embed_timeout=7200){
        $this->prefix = $prefix;
        $this->base_url = get_option('coai_chat_api_url', 'https://app.contentoracleai.com/api') ?? 'https://app.contentoracleai.com/api';
        $this->base_dir = $base_dir;
        $this->client_ip = $client_ip;
        $this->chat_timeout = $chat_timeout;
        $this->embed_timeout = $embed_timeout;
    }

    //static function to get the base url
    public static function get_base_url(){
        return get_option('coai_chat_api_url', 'https://app.contentoracleai.com/api') ?? 'https://app.contentoracleai.com/api';
    }

    //getter for the prefix
    public function get_prefix(){
        return $this->prefix;
    }

    //getter for the chat timeout
    public function get_chat_timeout(){
        return $this->chat_timeout;
    }

    //getter for the embed timeout
    public function get_embed_timeout(){
        return $this->embed_timeout;
    }
    

    //get a chat response from content oracle api
    public function ai_chat(string $query, array $content, array $conversation){
        //build the request
        $url = $this->base_url . '/v1/ai/chat';
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . get_option($this->prefix . 'api_token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode(array(
                'message' => $query,
                'conversation' => $conversation,
                'content' => $content,
                'modifiers' => array(
                    'general' => array(
                        'post_types' => get_option($this->prefix . 'post_types'),
                        'organization_name' => get_option($this->prefix . 'organization_name', get_bloginfo('name') ?? 'No Name Provided')
                    ),
                    'ai' => array(
                        'tone' => get_option($this->prefix . 'ai_tone'),
                        'jargon' => get_option($this->prefix . 'ai_jargon'),
                        'goal_prompt' => get_option($this->prefix . 'ai_goal_prompt', ''),
                        'extra_info_prompt' => get_option($this->prefix . 'ai_extra_info_prompt', '')
                    
                    )
                ),
                'client_ip' => $this->client_ip
                
            )),
            'timeout' => $this->get_chat_timeout(),
        );

        


        //make the request
        $response = wp_remote_post($url, $args);

        //handle wordpress errors
        if (is_wp_error($response)){
            throw new ContentOracle_ResponseException(
                $response->get_error_message(),
                $response
            );
        }

        //throw exception if response is not 2XX
        if (wp_remote_retrieve_response_code($response) < 200 || wp_remote_retrieve_response_code($response) >= 300) {
            throw new ContentOracle_ResponseException(
                wp_remote_retrieve_response_message($response),
                $response,
                "coai"
            );
        }

        //parse the response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        //throw excpetion if $response['error'] is set
        if (isset($data['error'])){
            throw new ContentOracle_ResponseException(
                $data['error'],
                $response,
                "coai"
            );
        }

        //throw excpetion if $response['errors'] is set
        if (isset($data['errors'])){
            throw new ContentOracle_ResponseException(
                $data['errors'],    //TODO: how to merge these to a string?
                $response,
                "coai"
            );
        }

        //check if message is "Unauthenticated."
        if (isset($data['message']) && $data['message'] === "Unauthenticated."){
            throw new ContentOracle_ResponseException(
                "Unauthenticated.",
                $response,
                "coai"
            );
        }

        //if we reach here, the response is valid
        return $data;
    }

    //get a streamed chat response from content oracle api
    public function streamed_ai_chat(string $query, array $content, array $conversation, callable $callback){
        //build the request
        $url = $this->base_url . '/v1/ai/chat/stream';

        //initialize the curl request
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . get_option($this->prefix . 'api_token'),
                'Accept: application/json',
                'Content-Type: application/json'
            ),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => wp_json_encode(array(
                'message' => $query,
                'conversation' => $conversation,
                'content' => $content,
                'modifiers' => array(
                    'general' => array(
                        'post_types' => get_option($this->prefix . 'post_types'),
                        'organization_name' => get_option($this->prefix . 'organization_name', get_bloginfo('name') ?? 'No Name Provided')
                    ),
                    'ai' => array(
                        'tone' => get_option($this->prefix . 'ai_tone'),
                        'jargon' => get_option($this->prefix . 'ai_jargon'),
                        'goal_prompt' => get_option($this->prefix . 'ai_goal_prompt', ''),
                        'extra_info_prompt' => get_option($this->prefix . 'ai_extra_info_prompt', '')
                    
                    )
                ),
                'client_ip' => $this->client_ip
                
            )),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->chat_timeout,
            CURLOPT_WRITEFUNCTION => function($ch, $data) use ($callback){
                //add the data to the response buffer
                $callback($data);
                return strlen($data);
            }


        ));

        //execute the request
        $response = curl_exec($ch);

        //handle curl errors
        if (curl_errno($ch)){
            return [ 'error' => curl_error($ch) ];
        }

        //close the curl request
        curl_close($ch);

        //handle wordpress errors
        if (is_wp_error($response)){
            return [ 'error' => $response->get_error_message() ];
        }

        //return the response
        return $response;
    }

    //get a user query embedded by content oracle api
    public function query_vector(string $query){
        
        //build the request
        $url = $this->base_url . '/v1/ai/query_vector';
        
        $payload = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . get_option($this->prefix . 'api_token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'chunking_method' => get_option($this->prefix . 'chunking_method', 'token:256'),
                'client_ip' => $this->client_ip,
                'query' => $query,
            )),
            'timeout' => $this->get_chat_timeout(),
        );

        //make the request
        $url = $this->base_url . '/v1/ai/embedquery';
        $response = wp_remote_post($url, $payload);


        //handle wordpress errors
        if (is_wp_error($response)){
            throw new ContentOracle_ResponseException(
                $response->get_error_message(),
                $response->errors  //because this response is of type WP_Error, not Array, so need to get the errors array
            );
        }


        //handle non-2XX responses
        if (wp_remote_retrieve_response_code($response) < 200 || wp_remote_retrieve_response_code($response) >= 300) {
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            throw new ContentOracle_ResponseException(
                "Query Vector Returned Non-2XX Response: " . (isset($body['message']) ? $body['message'] : wp_remote_retrieve_response_message($response)),
                $response,
                "coai"
            );
        }


        //retrieve and format the response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        //check if message is "Unauthenticated."
        if (isset($data['message']) && $data['message'] === "Unauthenticated."){
            throw new ContentOracle_ResponseException(
                "Unauthenticated.",
                $response,
                "coai"
            );
        }

        return $data;
    }

    //rest route to bulk generate embeddings
    public function bulk_generate_embeddings($posts){
        //get all the posts of the type to embed

        //break the posts into chunks
        $chunked_posts = [];
        foreach ($posts as $post) {

            //remove null entries from the posts array
            if ($post == null){
                continue;
            }

            $chunked_post = $this->chunk_post($post);

            //add post titles and types to the beginning of each chunk
            foreach ($chunked_post->chunks as $i => $chunk){
                //ensure chunk is an array
                if (!is_array($chunk)){
                    $chunk = [$chunk];
                }

                //merge the title and type with the chunk
                $chunked_post->chunks[$i] = array_merge(
                    ["Title: ". get_the_title($post->ID)],
                    ["Type: " . get_post_type($post->ID)],
                    $chunk
                );
            }

            $chunked_posts[] = $chunked_post;
        }

        //send the chunks to the api (we are letting exceptions bubble up)
        $embeddings = $this->coai_api_generate_embeddings($chunked_posts);
        
        //save the embeddings to the database
        $vt = new VectorTable($this->prefix);
        foreach ($embeddings as $post_id => $vectors){
            $vectors = array_map(function($v){
                return [
                    'vector' => json_encode( $v['embedding'] ), 
                    'vector_type' => get_option($this->prefix . 'chunking_method')
                ];
            }, $vectors);

            //inserts them with the sequence numbers inserted in order
            $embedding_ids = $vt->insert_all($post_id, $vectors);

            //save the ids of generated embeddings as post meta
            update_post_meta($post_id, $this->prefix . 'embeddings', $embedding_ids);
            update_post_meta($post_id, $this->prefix . 'should_generate_embeddings', false);
        }

        //return success
        return [
            'success' => true,
            'embedding_ids' => $embedding_ids,
            'message' => 'Embeddings generated successfully.'
        ];
    }
}