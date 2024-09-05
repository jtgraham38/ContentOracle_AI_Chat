<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ContentOracleApiConnection{

    const API_BASE_URL = 'https://contentoracle.jacob-t-graham.com/api';

    private $prefix;
    private $base_url;
    private $base_dir;

    public function __construct($prefix, $base_url, $base_dir){
        $this->prefix = $prefix;
        $this->base_url = $base_url;
        $this->base_dir = $base_dir;
    }

    public function ai_search(string $query, array $content, array $conversation){
        //build the request
        $url = self::API_BASE_URL . '/v1/ai/search';
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . get_option($this->prefix . 'api_token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'message' => $query,
                'conversation' => $conversation,
                'content' => $content,
                'modifiers' => array(
                    'general' => array(
                        'post_types' => get_option($this->prefix . 'post_types')
                    ),
                    'ai' => array(
                        'tone' => get_option($this->prefix . 'ai_tone'),
                        'jargon' => get_option($this->prefix . 'ai_jargon'),
                        'goal_prompt' => get_option($this->prefix . 'ai_goal_prompt', ''),
                        'extra_info_prompt' => get_option($this->prefix . 'ai_extra_info_prompt', '')
                    
                    )
                )
            )),
            'timeout' => 30,
        );
        //make the request

        //var_dump(json_decode($args['body']));

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
}