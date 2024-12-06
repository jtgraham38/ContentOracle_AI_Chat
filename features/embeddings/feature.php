<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;
use \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;

require_once plugin_dir_path(__FILE__) . 'VectorTable.php';

class ContentOracleEmbeddings extends PluginFeature{

    private string $UPDATE_TAG = '<!-- coai:generate embeddings -->';
    private int $CHUNK_SIZE = 256;

    public function add_filters(){
        //add filter to generate embeddings for a post (this is triggered when the embedding explorer is used)
        //add_action('wp_insert_post', array($this, 'generate_embeddings_for_post'), 10, 3);
    }
    
    public function add_actions(){
        //add submenu page
        add_action('admin_menu', array($this, 'add_menu'));
        
        //register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        //register styles
        add_action('admin_enqueue_scripts', array($this, 'register_styles'));
        
        //add meta box
        add_action('add_meta_boxes', array($this, 'add_embedding_meta_box'));

        
        //register these only for the post types that are indexed by the AI
        $post_types = get_option($this->get_prefix() . 'post_types', []);
        foreach ($post_types as $post_type) {
            //generate new embeddings for a saved post
            //TODO: make this hook only register on the specific post types that are indexed by the AI
            add_action('save_post_' . $post_type, array($this, 'generate_embeddings_on_save'), 20, 3);
    
            //mark the post as needing new embeddings
            add_action('save_post_' . $post_type, array($this, 'flag_post_for_embedding_generation'), 10, 3);
        }


    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //callback that flags a post for embedding generation when the checkbox is checked
    public function flag_post_for_embedding_generation($post_ID, $post, $update){
        // check if this is an autosave. If it is, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // check the user's permissions.
        if (!current_user_can('edit_post', $post_ID)) {
            return;
        }

        //nonce verification
        if (!isset($_POST[$this->get_prefix() . 'generate_embeddings_nonce']) 
            || 
            !wp_verify_nonce($_POST[$this->get_prefix() . 'generate_embeddings_nonce'], $this->get_prefix() . 'save_generate_embeddings')
        ) {
            return;
        }
        
        //update flag post meta for embedding generation if the checkbox is checked
        if (isset($_POST[$this->get_prefix() . 'generate_embeddings'])) {
            update_post_meta($post_ID, $this->get_prefix() . 'should_generate_embeddings', true);
        }
        else {
            update_post_meta($post_ID, $this->get_prefix() . 'should_generate_embeddings', false);
        }
    }

    // callback that generates embeddings for a post when it is saved, if the should_generate_embeddings flag is set
    public function generate_embeddings_on_save($post_ID, $post, $update){

        // check if this is an autosave. If it is, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // check the user's permissions.
        if (!current_user_can('edit_post', $post->ID)) {
            return;
        }

        //check if we should generate embeddings for this post by checking the prefix_should_generate_embeddings flag
        if (get_post_meta($post->ID, $this->get_prefix() . 'should_generate_embeddings', true ) != true) {
            return;
        }


        //check the chunking method setting (TODO: integrate this into the embedding generation process)
        $chunking_method = get_option($this->get_prefix() . 'chunking_method');
        if ($chunking_method == 'none' || $chunking_method == '') {
            return;
        }

        //generate the embeddings for the post
        $post = $this->generate_embeddings($post);
        return $post;
    }

    //this is the function that is called in various places to generate embeddings for a post
    //note that it does not perform all permission and semantic checks, as it is called in various places
    public function generate_embeddings($post){
        //get the post id
        $post_ID = $post->ID;

        //group the tokens into chunks
        $chunks = $this->chunk_post($post);

        //send an embeddings request to ContentOracle AI
        //$this->coai_api_generate_embeddings($chunks);
        $embeddings = [
            ['vector' => '0.1, 0.2, 0.3', 'vector_type' => get_option($this->get_prefix() . 'chunking_method', 'none')],
            ['vector' => '0.4, 0.5, 0.6', 'vector_type' => get_option($this->get_prefix() . 'chunking_method', 'none')],
        ];   //TODO: make the request to the coai here

        //save the embeddings to the embeddings table
        $vt = new ContentOracle_VectorTable($this->get_prefix());
        $embedding_ids = $vt->insert_all($post_ID, $embeddings);

        //save the generated embeddings
        update_post_meta($post_ID, $this->get_prefix() . 'embeddings', $embedding_ids);
        update_post_meta($post_ID, $this->get_prefix() . 'should_generate_embeddings', false);

        //return the content
        return $post;
    }

    //function that chunks a post into smaller pieces for embedding generation, based on the chunking method
    public function chunk_post($post){
        $post_id = $post->ID;
        $chunking_method = get_option($this->get_prefix() . 'chunking_method', 'none');
        switch ($chunking_method) {
            case 'token:256':
                //get the post content
                $body = strip_tags($post->post_content);

                //split the body into tokens
                $tokenizer = new WhitespaceAndPunctuationTokenizer();
                $tokens = $tokenizer->tokenize($body);

                //group the tokens into chunks
                $chunks = array_chunk($tokens, $this->CHUNK_SIZE);

                //return the post id mapped to the chunks
                $return = new ContentOracle_ChunksForPost($post_id, $chunks);
                break;
            case 'none':
                //return the post id mapped to the entire post content
                $return = new ContentOracle_ChunksForPost($post_id, [$post->post_content]);
                break;
            default:
                throw new Exception('Invalid chunking method: ' . $chunking_method);
                break;
        }

        //return the chunks
        return $return;

    }

    /*
    *  This function makes the call to COAI to generate embeddings for a batch of posts
    *  $cps: an array of ContentOracle_ChunksForPost objects, which contain the post id and the chunks of the post
    */
    public function coai_api_generate_embeddings(array $cps){
        //if cps is not an array, make it into a single-element array
        if (!is_array($cps)) {
            $cps = [$cps];
        }

        //create an array of content to send to the coai api
        $payload = [
            'chunking_method' => get_option($this->get_prefix() . 'chunking_method', 'none'),
            'client_ip' => $this->get_client_ip(),
            'content' => []
        ];

        //add each record to the payload
        foreach ($cps as $cp) {
            $payload['content'][] = [
                'id' => $cp->post_id,
                'url' => get_permalink($cp->post_id),
                'title' => get_post_title($cp->post_id),
                'chunks' => $cp->chunks,
                'type' => get_post_type($cp->post_id),
            ];
        }

        //post to the coai api route

        //format the response

        //return the embeddings
    }

    public function get_update_tag(){
        return $this->UPDATE_TAG;
    }

    public function get_chunk_size(){
        return $this->CHUNK_SIZE;
    }

    //  \\  //  \\  //  \\  //  \\ CALLBACKS NOT RELATED TO EMBEDDING GENERATION (DIRECTLY)  //  \\  //  \\  //  \\  // \\

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

    public function render_page(){
        require_once plugin_dir_path(__FILE__) . 'elements/_inputs.php';
    }

    public function register_styles(){
        if (strpos(get_current_screen()->base, 'contentoracle-embeddings') === false) {
            return;
        }
        wp_enqueue_style('contentoracle-embeddings', plugin_dir_url(__FILE__) . 'assets/css/explorer.css');
    }

    
    public function add_embedding_meta_box(){
        add_meta_box(
            'contentoracle-embeddings',
            'ContentOracle AI Embeddings',
            function(){
                require_once plugin_dir_path(__FILE__) . 'elements/_meta_box.php';
            },
            get_option($this->get_prefix().'_post_types') ?? [],
            'side',
            'high'
        );
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

}

class ContentOracle_ChunksForPost{
    public int $post_id;
    public array $chunks;

    public function __construct(int $post_id, array $chunks){
        $this->post_id = $post_id;
        $this->chunks = $chunks;
    }
}