<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;
use \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;

class ContentOracleEmbeddings extends PluginFeature{

    private string $UPDATE_TAG = '<!-- coai:generate embeddings -->';
    private int $CHUNK_SIZE = 256;

    public function add_filters(){
        //add filter to generate embeddings for a post
        add_action('wp_insert_post', array($this, 'generate_embeddings_for_post'), 10, 3);
    }
    
    public function add_actions(){
        //add submenu page
        add_action('admin_menu', array($this, 'add_menu'));
        
        //register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        //register styles
        add_action('admin_enqueue_scripts', array($this, 'register_styles'));
        
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\
    public function generate_embeddings_for_post($post_ID, $post, $update){
        // check if this is an autosave. If it is, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // check the user's permissions.
        if (!current_user_can('edit_post', $post->ID)) {
            return;
        }

        //check if we should generate embeddings for this post
        // check if the update tag exists in the post content
        if (strpos($post->post_content, $this->get_update_tag()) === false) {
            return;
        }
        // remove the update tag from the post content
        $post->post_content = str_replace($this->get_update_tag(), '', $post->post_content);

        //begin the process of generating embeddings for the post
        $title = $post->post_title;
        $body = strip_tags($post->post_content);

        //split the body into tokens
        $tokenizer = new WhitespaceAndPunctuationTokenizer();
        $tokens = $tokenizer->tokenize($body);

        //group the tokens into chunks
        $chunks = array_chunk($tokens, $this->CHUNK_SIZE);

        //send an embeddings request to ContentOracle AI
        $embeddings = [
            'em_1234',
            'em_5678',
            'em_91011',
            'em_121314'
        ];   //TODO: make the request to the coai here

        //save the generated embeddings
        update_post_meta($post_ID, $this->get_prefix() . 'embeddings', $embeddings);

        //return the content
        return $post;
    }


    public function add_menu(){
        add_submenu_page(
            'contentoracle-ai', // parent slug
            'Embeddings', // page title
            'Embeddings', // menu title
            'manage_options', // capability
            'contentoracle-embeddings', // menu slug
            array($this, 'render_page') // callback function
        );
    }

    public function register_settings(){
        add_settings_section(
            'contentoracle_embeddings_settings', // id
            '', // title
            function(){ // callback
                echo 'Manage your AI search settings here.';
            },
            'contentoracle-ai-settings'  // page (matches menu slug)
        );

        // create the settings fields
        add_settings_field(
            $this->get_prefix() . "chunking_method",    // id of the field
            'Chunking Method',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/chunking_method_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'contentoracle_embeddings_settings',  // section
            array(
            'label_for' => $this->get_prefix() .'chunking_method'
            )
        );

        // create the settings themselves
        register_setting(
            'contentoracle_embeddings_settings', // option group
            $this->get_prefix() . 'chunking_method',    // option name
            array(  // args
                'type' => 'string',
                'default' => '',
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }

    public function render_page(){
        require_once plugin_dir_path(__FILE__) . 'elements/_inputs.php';
    }

    public function register_styles(){
        if (strpos(get_current_screen()->base, 'contentoracle-embeddings') === false) {
            return;
        }
        wp_enqueue_style('contentoracle-embeddings', plugin_dir_url(__FILE__) . 'assets/css/explorer.css');
    }

    public function get_update_tag(){
        return $this->UPDATE_TAG;
    }

    public function get_chunk_size(){
        return $this->CHUNK_SIZE;
    }
}