<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;
use \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;

require_once plugin_dir_path(__FILE__) . 'VectorTable.php';
require_once plugin_dir_path(__FILE__) . '../wp_api/ContentOracleApiConnection.php';

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

        //show a notice to generate embeddings
        add_action('admin_notices', array($this, 'show_generate_embeddings_notice'));

        //add a cron hook to hook into 
        //check if the task is scheduled already (to prevent duplicate scheduling)
        if (! wp_next_scheduled($this->get_prefix() . 'auto_generate_embeddings')) {            
            //schedule the task
            wp_schedule_event(time(), 'weekly', $this->get_prefix() . 'auto_generate_embeddings');
        }

        //auto generate embeddings
        add_action($this->get_prefix() . 'auto_generate_embeddings', array($this, 'auto_generate_embeddings'));

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
    public function generate_embeddings($posts){
        //ensure posts is an array
        if (!is_array($posts)){
            $posts = [$posts];
        }
        
        //prepare chunks for each post
        $chunked_posts = [];
        foreach ($posts as $post){
            //get the post id
            $post_ID = $post->ID;
    
            //ensure the post is not empty
            if (empty($post->post_content)) {
                echo "Post " . esc_html($post_ID) . " has no content, skipping embedding generation!<br>";
                continue;
            }
    
            //group the tokens into chunks
            $chunks = $this->chunk_post($post);

            //add the chunked post to the array
            $chunked_posts[] = $chunks;
        }

        //send an embeddings request to ContentOracle AI
        try{
            $embeddings = $this->coai_api_generate_embeddings($chunked_posts);
            if (!is_array($embeddings)) {
                $embeddings = [];
            }
        }
        catch (Exception $e){
            //if there is an error (usually, no embeddings are returned), return the post
            //
            ////NOTE: this error is usually triggered when the rate limit for the coai api is hit
            ////NOTE: I need to fix this by making this function able to handle batches of posts in a single request
            ////NOTE: the api can already handle this, but the plugin does not yet
            //
            echo esc_html("Error: " . $e->getMessage());
            echo "<br>";
            return $posts;
        }

        //save the embeddings to the embeddings table
        $vt = new ContentOracle_VectorTable($this->get_prefix());
        foreach ($embeddings as $post_id => $vectors){
            $vectors = array_map(function($v){
                return [
                    'vector' => json_encode( $v['embedding'] ), 
                    'vector_type' => get_option($this->get_prefix() . 'chunking_method')
                ];
            }, $vectors);
            //inserts them with the sequence numbers inserted in order
            $embedding_ids = $vt->insert_all($post_id, $vectors);

            //save the ids of generated embeddings as post meta
            if (count($embeddings) > 0) {
                update_post_meta($post_id, $this->get_prefix() . 'embeddings', $embedding_ids);
                update_post_meta($post_id, $this->get_prefix() . 'should_generate_embeddings', false);
            }
        }

        //return the content
        return $posts;
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
            case '':
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
    public function coai_api_generate_embeddings($cps){
        //if cps is not an array, make it into a single-element array
        if (!is_array($cps)) {
            $cps = [$cps];
        }

        //add each record to the payload
        $content = [];
        foreach ($cps as $cp) {
            //create chunks
            $_chunks = array_map(
                function($chunk){
                    return implode(' ', $chunk);
                },
                $cp->chunks
            );

            //skip generation if no chunks exist
            if (empty($_chunks)) {
                echo "No chunks found for post " . esc_html($cp->post_id) . ", skipping! <br>";
                continue;
            }

            //add record to content
            $content[] = [
                'id' => $cp->post_id,
                'url' => get_permalink($cp->post_id),
                'title' => get_the_title($cp->post_id),
                'chunks' => $_chunks,   //convert chunk from array of tokens to a string
                'type' => get_post_type($cp->post_id),
            ];
        }

        //ensure content is not empty
        if (empty($content)){
            echo "No posts viable for embedding generation found, no embeddings generated!<br>";
            return [];
        }

        //create an array of content to send to the coai api
        $payload = [
            'headers' => array(
                'Authorization' => 'Bearer ' . get_option($this->get_prefix() . 'api_token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode([
                'chunking_method' => get_option($this->get_prefix() . 'chunking_method', 'none'),
                'client_ip' => $this->get_client_ip(),
                'content' => $content
            ]),
        ];

        //make the request
        $url = ContentOracleApiConnection::API_BASE_URL . '/v1/ai/embed';
        $response = wp_remote_post($url, $payload);

        //handle wordpress errors
        if (is_wp_error($response)){
            throw new Exception($response->get_error_message());
        }
        
        //retrieve and format the response
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        //ensure the response is valid
        if (empty($data['embeddings'])) {
            throw new Exception('Invalid response from ContentOracle AI: embeddings key not set');
        }

        return $data['embeddings'];
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
            'Embedding Method',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/chunking_method_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'contentoracle_embeddings_settings',  // section
            array(
            'label_for' => $this->get_prefix() .'chunking_method'
            )
        );

        add_settings_field(
            $this->get_prefix() . "auto_generate_embeddings_interval",    // id of the field
            'Auto-generate Embeddings',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/auto_generate_embeddings_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'contentoracle_embeddings_settings',  // section
            array(
            'label_for' => $this->get_prefix() .'auto_generate_embeddings'
            )
        );

        add_settings_field(
            $this->get_prefix() . "auto_generate_only_new_embeddings",    // id of the field
            'Auto-generate Only New Embeddings',   // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/auto_generate_only_new_embeddings_input.php';
            },
            'contentoracle-ai-settings', // page (matches menu slug)
            'contentoracle_embeddings_settings',  // section
            array(
            'label_for' => $this->get_prefix() .'auto_generate_only_new_embeddings'
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

        register_setting(
            'contentoracle_embeddings_settings', // option group
            $this->get_prefix() . 'auto_generate_embeddings',    // option name
            array(  // args
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => function($value){
                    return $value ? true : false;
                }
            )
        );

        register_setting(
            'contentoracle_embeddings_settings', // option group
            $this->get_prefix() . 'auto_generate_only_new_embeddings',    // option name
            array(  // args
                'type' => 'boolean',
                'default' => true,
                'sanitize_callback' => function($value){
                    return $value ? true : false;
                }
            )
        );


    }

    //  \\  //  \\  //  \\  //  SHOW NOTICES TO PROMPT USER TO GENERATE EMBEDDINGS  //  \\  //  \\  //  \\  //  \\
    public function show_generate_embeddings_notice(){
        //check if the user has the capability to edit posts
        if (!current_user_can('edit_posts')) {
            return;
        }

        //check if we are on a coai admin page
        if ( strpos(get_current_screen()->base, 'contentoracle' ) === false) {
            return;
        }

        //show an admin notice to generate embeddings if the chunking method is set
        if (get_option($this->get_prefix() . 'chunking_method') != 'none') {

            //check if embeddings have been generated
            $vt = new ContentOracle_VectorTable($this->get_prefix());
            $embeddings = $vt->get_all();
            if (!empty($embeddings)) {
                return;
            }

            $generate_embeddings_url = admin_url('admin.php?page=contentoracle-embeddings');
            echo '<div class="notice notice-info is-dismissible">';
            echo '<h2>You must generate embeddings to use Semantic Search!</h2>';
            echo '<p>Visit <a href="' . esc_url($generate_embeddings_url) . '" >this page </a> to generate embeddings for your posts, or set the embedding chunking method to none.</p>';
            echo '</div>';
        }
    }

    //  \\  //  \\  //  \\  //  AUTO GENERATE EMBEDDINGS  //  \\  //  \\  //  \\  //  \\
    public function auto_generate_embeddings(){

        //check if the auto-generate embeddings setting is set
        if (!get_option($this->get_prefix() . 'auto_generate_embeddings', false)) {
            return;
        }

        //get the post types that are indexed by the AI
        $post_types = get_option($this->get_prefix() . 'post_types', []);
        if (empty($post_types)) {
            return;
        }

        //get the posts to generate embeddings for
        $for_all_posts = get_option($this->get_prefix() . 'auto_generate_only_new_embeddings', false) ? 'new' : 'all';

        //if we are generating all new embeddings, get all posts
        if ($for_all_posts){
            //get all posts
            $posts = get_posts(array(
                'post_type' => $post_types,
                'numberposts' => -1,
                'orderby' => 'post_type',
                'order' => 'ASC'
            ));
        }
        //otherwise, get the posts that do not have embeddings
        else{
            //get all posts that do not have embeddings
            $posts = get_posts(array(
                'post_type' => $post_types,
                'numberposts' => -1,
                'orderby' => 'post_type',
                'order' => 'ASC',
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => $this->get_prefix() . 'embeddings',
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => $this->get_prefix() . 'embeddings',
                        'value' => 'false',
                        'compare' => '='
                    )
                )
            ));
        }

        //generate embeddings for the posts
        $this->generate_embeddings($posts);
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