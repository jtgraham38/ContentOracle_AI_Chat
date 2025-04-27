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

        //register scripts
        add_action('admin_enqueue_scripts', array($this, 'register_scripts'));
        
        //add meta box
        add_action('add_meta_boxes', array($this, 'add_embedding_meta_box'));

        
        //register these only for the post types that are indexed by the AI
        $post_types = get_option($this->get_prefix() . 'post_types', []);
        foreach ($post_types as $post_type) {
            //mark the post as needing new embeddings
            add_action('save_post_' . $post_type, array($this, 'flag_post_for_embedding_generation'), 10, 3);  //this one runs before the generate_embeddings_on_save hook


            //generate new embeddings for a saved post
            //TODO: make this hook only register on the specific post types that are indexed by the AI
            add_action('save_post_' . $post_type, array($this, 'generate_embeddings_on_save'), 20, 3);  //this one runs after the flag_post_for_embedding_generation hook
    
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

        //delete embeddings when a post is deleted
        add_action('delete_post', array($this, 'delete_embeddings_for_post'));

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

        //check if the post is of the correct post type
        if (!in_array($post->post_type, get_option($this->get_prefix() . 'post_types', []))) {
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

        //check if the post is of the correct post type
        if (!in_array($post->post_type, get_option($this->get_prefix() . 'post_types', []))) {
            return;
        }

        //ensure the post is being published
        if ($post->post_status != 'publish') {
            return;
        }

        //check the chunking method setting (TODO: integrate this into the embedding generation process)
        $chunking_method = get_option($this->get_prefix() . 'chunking_method');
        if ($chunking_method == 'none' || $chunking_method == '') {
            return;
        }

        //generate the embeddings for the post
        $api = new ContentOracleApiConnection(
            $this->get_prefix(), 
            $this->get_base_url(), 
            $this->get_base_dir(), 
            $this->get_client_ip()
        );
        $api->bulk_generate_embeddings($post->ID);
        
        return $post;
    }


    //  \\  //  \\  //  \\  //  \\ CALLBACKS NOT RELATED TO EMBEDDING GENERATION (DIRECTLY)  //  \\  //  \\  //  \\  // \\

    //delete embeddings when a post is deleted
    public function delete_embeddings_for_post($post_id){
        //create vector table
        $vt = new ContentOracle_VectorTable($this->get_prefix());

        //get ids of all vectors for the post
        $vectors = $vt->get_all_for_post($post_id);
        
        //delete the vectors
        foreach ($vectors as $vector) {
            $vt->delete($vector->id);
        }
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
    //    \\    SETTINGS PAGE \\    //
    public function render_page(){
        require_once plugin_dir_path(__FILE__) . 'elements/_inputs.php';
    }
    
    public function add_menu(){
            add_submenu_page(
                'contentoracle-ai-chat', // parent slug
                'Embeddings', // page title
                'Embeddings', // menu title
                'manage_options', // capability
                'contentoracle-ai-chat-embeddings', // menu slug
                array($this, 'render_page') // callback function
            );
        }

        public function register_settings(){
            add_settings_section(
                'coai_chat_embeddings_settings', // id
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
                'coai_chat_embeddings_settings',  // section
                array(
                'label_for' => $this->get_prefix() .'chunking_method'
                )
            );

            add_settings_field(
                $this->get_prefix() . "auto_generate_embeddings_interval",    // id of the field
                'Auto-generate Embeddings Weekly',   // title
                function(){ // callback
                    require_once plugin_dir_path(__FILE__) . 'elements/auto_generate_embeddings_input.php';
                },
                'contentoracle-ai-settings', // page (matches menu slug)
                'coai_chat_embeddings_settings',  // section
                array(
                'label_for' => $this->get_prefix() .'auto_generate_embeddings'
                )
            );

            add_settings_field(
                $this->get_prefix() . "auto_generate_only_new_embeddings",    // id of the field
                'Auto-generate Embeddings Weekly for Posts that are not Already Embedded',   // title
                function(){ // callback
                    require_once plugin_dir_path(__FILE__) . 'elements/auto_generate_only_new_embeddings_input.php';
                },
                'contentoracle-ai-settings', // page (matches menu slug)
                'coai_chat_embeddings_settings',  // section
                array(
                'label_for' => $this->get_prefix() .'auto_generate_only_new_embeddings'
                )
            );

            // create the settings themselves
            register_setting(
                'coai_chat_embeddings_settings', // option group
                $this->get_prefix() . 'chunking_method',    // option name
                array(  // args
                    'type' => 'string',
                    'default' => 'token:256',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            );

            register_setting(
                'coai_chat_embeddings_settings', // option group
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
                'coai_chat_embeddings_settings', // option group
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
    
        public function register_scripts(){
        //if we are on the embeddings page
        if (strpos(get_current_screen()->base, 'contentoracle-ai-chat-embeddings') === false) {
            return;
        }

        //enqueue the scripts
        wp_enqueue_script('contentoracle-ai-chat-embeddings-api', plugin_dir_url(__FILE__) . 'assets/js/api.js', []);
        wp_enqueue_script('contentoracle-ai-chat-embeddings-page', plugin_dir_url(__FILE__) . 'assets/js/embedding_explorer.js', ['contentoracle-ai-chat-embeddings-api']);

        //localize the api script with the base url
        wp_localize_script('contentoracle-ai-chat-embeddings-api', 'contentoracle_ai_chat_embeddings', array(
            'api_base_url' => rest_url(),
            'nonce' => wp_create_nonce('wp_rest')   //TODO: apply this to other rest api calls
        ));
    }

    public function register_styles(){
        if (strpos(get_current_screen()->base, 'contentoracle-ai-chat-embeddings') === false) {
            return;
        }
        wp_enqueue_style('contentoracle-ai-chat-embeddings', plugin_dir_url(__FILE__) . 'assets/css/explorer.css');
    }

    //    \\    add meta box to post editor    //    \\
    public function add_embedding_meta_box(){
        //only add the meta box if the post type is in the list of post types that are indexed by the AI
        if (!in_array(get_post_type(), get_option($this->get_prefix() . 'post_types', []))) {
            return;
        }

        add_meta_box(
            'contentoracle-ai-chat-embeddings',
            'ContentOracle AI Embeddings',
            function(){
                require_once plugin_dir_path(__FILE__) . 'elements/_meta_box.php';
            },
            get_option($this->get_prefix().'_post_types') ?? [],
            'side',
            'high'
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

            $generate_embeddings_url = admin_url('admin.php?page=contentoracle-ai-chat-embeddings');
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
        $for = get_option($this->get_prefix() . 'auto_generate_only_new_embeddings', false) ? 'not_embedded' : 'all';

        //generate embeddings for the posts
        $api = new ContentOracleApiConnection(
            $this->get_prefix(), 
            $this->get_base_url(), 
            $this->get_base_dir(), 
            $this->get_client_ip()
        );
        $api->bulk_generate_embeddings($for);
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