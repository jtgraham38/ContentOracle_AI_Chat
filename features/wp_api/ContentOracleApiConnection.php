<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ContentOracleApiConnection{

    const API_BASE_URL = 'https://app.contentoracleai.com/api';

    private $prefix;
    private $base_url;
    private $base_dir;
    private $client_ip;

    public function __construct($prefix, $base_url, $base_dir, $client_ip){
        $this->prefix = $prefix;
        $this->base_url = $base_url;
        $this->base_dir = $base_dir;
        $this->client_ip = $client_ip;
    }

    //get a chat response from content oracle api
    public function ai_chat(string $query, array $content, array $conversation){
        //build the request
        $url = self::API_BASE_URL . '/v1/ai/chat';
        
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
            'timeout' => 250,
        );

        


        //make the request
        $response = wp_remote_post($url, $args);

        //handle wordpress errors
        if (is_wp_error($response)){
            return [ 'error' => $response->get_error_message() ];
        }
        
        //parse the response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        return $data;
    }

    //get a streamed chat response from content oracle api
    public function streamed_ai_chat(string $query, array $content, array $conversation, callable $callback){
        //build the request
        $url = self::API_BASE_URL . '/v1/ai/chat/stream';

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
            CURLOPT_TIMEOUT => 250,
            CURLOPT_WRITEFUNCTION => function($ch, $data) use ($callback){
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

        //handle wordpress errors
        if (is_wp_error($response)){
            return [ 'error' => $response->get_error_message() ];
        }
    }

    //get a user query embedded by content oracle api
    public function query_vector(string $query){
        //build the request
        $url = self::API_BASE_URL . '/v1/ai/query_vector';
        
        $payload = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . get_option($this->prefix . 'api_token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'chunking_method' => get_option($this->prefix . 'chunking_method', 'none'),
                'client_ip' => $this->client_ip,
                'query' => $query,
            )),
            'timeout' => 250,
        );

        //make the request
        $url = ContentOracleApiConnection::API_BASE_URL . '/v1/ai/embedquery';
        $response = wp_remote_post($url, $payload);

        //handle wordpress errors
        if (is_wp_error($response)){
            throw new Exception($response->get_error_message());
        }

        //handle non-2XX responses
        if (wp_remote_retrieve_response_code($response) < 200 || wp_remote_retrieve_response_code($response) >= 300) {
            throw new Exception(wp_remote_retrieve_response_message($response));
        }
        
        //retrieve and format the response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data;
    }
}