<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;
use \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;

require_once plugin_dir_path(__FILE__) . 'VectorTable.php';
require_once plugin_dir_path(__FILE__) . 'VectorQueueTable.php';
require_once plugin_dir_path(__FILE__) . '../wp_api/ContentOracleApiConnection.php';
require_once plugin_dir_path(__FILE__) . '../wp_api/util.php';

class ContentOracleEmbeddings extends PluginFeature{
    use ContentOracleChunkingMixin;

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

        //show a notice to generate embeddings
        add_action('admin_notices', array($this, 'show_generate_embeddings_notice'));


        //delete embeddings when a post is deleted
        add_action('delete_post', array($this, 'delete_embeddings_for_post'));


        //queue posts for embedding generation from the editor, based on the type of post
        add_action('save_post', array($this, 'enqueue_embedding_from_editor'), 10, 3);

        //    \\    //    CREATE NEW SYSTEM FOR EMBEDDINGS    //    \\
        //schedule the cron job
        add_action('init', array($this, 'schedule_cron_jobs'));

        //hook into the cron job, to consume a batch of posts from the queue
        add_action($this->get_prefix() . 'embed_batch_cron_hook', array($this, 'consume_batch_from_queue'));

        //hook into the cron job, to clean the queue
        add_action($this->get_prefix() . 'clean_queue_cron_hook', array($this, 'clean_queue'));

        //hook into the cron job, to enqueue posts for embedding generation if they are not already embedded
        add_action($this->get_prefix() . 'auto_enqueue_embeddings_cron_hook', array($this, 'auto_enqueue_embeddings'));

    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //  \\  //  \\  //  \\  //  \\ MANAGE QUEUE FOR POSTS REQUIRING EMBEDDINGS  //  \\  //  \\  //  \\  // \\
    //schedule the cron job
    public function schedule_cron_jobs(){
        //schedule the cron job to consume a batch of posts from the queue every 15 seconds
        if (!wp_next_scheduled($this->get_prefix() . 'embed_batch_cron_hook')) {
            wp_schedule_event(time(), 'every_minute', $this->get_prefix() . 'embed_batch_cron_hook');
        }

        //schedule a daily cron job to remove posts that have been completed for more than 3 days
        if (!wp_next_scheduled($this->get_prefix() . 'clean_queue_cron_hook')) {
            wp_schedule_event(time(), 'daily', $this->get_prefix() . 'clean_queue_cron_hook');
        }

        //schedule a weekly cron job to enqueue posts for embedding generation if they are not already embedded
        if (!wp_next_scheduled($this->get_prefix() . 'auto_enqueue_embeddings_cron_hook')) {
            wp_schedule_event(time(), 'weekly', $this->get_prefix() . 'auto_enqueue_embeddings_cron_hook');
        }
    }

    //get a batch of posts from the queue, and send it to the embedding service
    public function consume_batch_from_queue(){
        global $wpdb;
        //get a batch of posts from the queue
        $queue = new VectorTableQueue($this->get_prefix());
        $post_ids = $queue->get_next_batch();

        //get all posts with the indicated ids
        $post_types = '"' . implode('","', get_option($this->get_prefix() . 'post_types', [])) . '"';
        $post_ids = implode(',', $post_ids);

        //return if there are no post types or post ids
        if ($post_types == '' || $post_ids == ''){
            return;
        }

        //get the posts
        $posts = $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE ID IN ($post_ids) AND post_status = 'publish' AND post_type IN ($post_types)");

        //return if there are no posts
        if (empty($posts)) {
            return;
        }

        //return if there are no chunks in any of the posts
        $chunks_exist = false;
        foreach ($posts as $post) {
            $chunks = $this->chunk_post($post);
            if (!empty($chunks)) {
                $chunks_exist = true;
            }
        }
        if (!$chunks_exist) {
            return;
        }

        //send the posts to the embedding service
        try{
            $api = new ContentOracleApiConnection($this->get_prefix(), $this->get_base_url(), $this->get_base_dir(), $this->get_client_ip());
            $api->bulk_generate_embeddings($posts);
        } catch (Exception $e){
            //log the error
            error_log($e->getMessage());

            //mark each post in the batch as failed
            $queue->update_status($post_ids, 'failed', $e->getMessage());
            return;
        }

        //mark each post in the batch as completed
        $queue->update_status($post_ids, 'completed');
    }

    //clean the queue
    public function clean_queue(){
        //get the queue
        $queue = new VectorTableQueue($this->get_prefix());
        $queue->cleanup();
    }

    //automoatically enqueue posts for embedding generation if they are not already embedded
    public function auto_enqueue_embeddings(){
        //get all posts that are not already embedded
        if (get_option($this->get_prefix() . 'auto_generate_embeddings')){
            $this->enqueue_all_posts_that_are_not_already_embedded();
        }
    }


    //  \\  //  \\  //  \\  //  \\ ENQUEUE POSTS AT DIFFERENT TIMES  //  \\  //  \\  //  \\  // \\

    //callback that flags a post for embedding generation when the checkbox is checked
    public function enqueue_embedding_from_editor($post_ID, $post, $update){
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

        //check if the checkbox is checked
        if (!isset($_POST[$this->get_prefix() . 'generate_embeddings'])) {
            return;
        }
        
        //update flag post meta for embedding generation if the checkbox is checked
        $queue = new VectorTableQueue($this->get_prefix());
        $queue->add_post($post_ID);
    }

    //enqueue all posts that are not already embedded
    public function enqueue_all_posts_that_are_not_already_embedded(){
        //get all posts that are:
        // 1. not already embedded
        // 2. of the correct post type
        // 3. status is publish

        //get post types
        $post_types = get_option($this->get_prefix() . 'post_types');

        //get ids of posts that have embeddings
        $VT = new ContentOracle_VectorTable($this->get_prefix());
        $vecs = $VT->get_all();
        $embedded_ids = array_map(function($vec){
            return $vec->post_id;
        }, $vecs);

        //get posts
        $posts = get_posts(array(
                    'post_type' => $post_types,
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'post__not_in' => $embedded_ids,
                //NOTE: THIS META QUERY IS BROKEN, RETURNS POSTS THAT HAVE EMBEDDINGS
                    // 'meta_query' => array(
                    //     'relation' => 'OR',
                    //     array(
                    //         'key' => $this->get_prefix() . 'embeddings',
                    //         'compare' => 'NOT EXISTS'
                    //     ),
                    //     array(
                    //         'key' => $this->get_prefix() . 'embeddings',
                    //         'value' => "a:0:{}",
                    //         'compare' => '='
                    //     )
                    // )
                ));

        //remove posts that have no chunks
        $posts = array_filter($posts, function($post){
            $chunked_post = $this->chunk_post($post);
            return !empty($chunked_post->chunks);
        });



        //get post ids
        $post_ids = array_map(function($post){
            return $post->ID;
        }, $posts);

        //enqueue the posts
        $queue = new VectorTableQueue($this->get_prefix());
        $queue->add_posts($post_ids);
    }

    //enqueue all posts
    //function here for future use, not currently used!
    public function enqueue_all_posts(){
        //get all posts where
        // 1. of the correct post type
        // 2. status is publish
        $posts = get_posts(array(
            'post_type' => get_option($this->get_prefix() . 'post_types', []),
            'post_status' => 'publish'
        ));

        //remove posts that have no chunks
        $posts = array_filter($posts, function($post){
            $chunked_post = $this->chunk_post($post);
            return !empty($chunked_post->chunks);
        });

        //get post ids
        $post_ids = array_map(function($post){
            return $post->ID;
        }, $posts);

        //enqueue the posts
        $queue = new VectorTableQueue($this->get_prefix());
        $queue->add_posts($post_ids);
    }



    //  \\  //  \\  //  \\  //  \\ EMBEDDING MAINTENANCE  //  \\  //  \\  //  \\  // \\

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

        //delete the queue item
        $queue = new VectorTableQueue($this->get_prefix());
        $queue->delete_post($post_id);
    }

    //  \\  //  \\  //  \\  //  \\ HELPERS  //  \\  //  \\  //  \\  // \\
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
                $this->get_prefix() . "auto_generate_embeddings",    // id of the field
                'Auto-generate Text Embeddings Weekly',   // title
                function(){ // callback
                    require_once plugin_dir_path(__FILE__) . 'elements/auto_generate_embeddings_input.php';
                },
                'contentoracle-ai-settings', // page (matches menu slug)
                'coai_chat_embeddings_settings',  // section
                array(
                'label_for' => $this->get_prefix() .'auto_generate_embeddings'
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


            //WE NO LONGER NEED THIS SETTING, SO UNREGISTER AND DELETE IT
            //unregister and delete the extraneous setting
            unregister_setting('coai_chat_embeddings_settings',$this->get_prefix() . 'auto_generate_only_new_embeddings');
            $deleted = delete_option($this->get_prefix() . 'auto_generate_only_new_embeddings');


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
            echo '<h2>You must generate embeddings for ContentOracle AI!</h2>';
            echo '<p>Visit <a href="' . esc_url($generate_embeddings_url) . '" >this page </a> to generate embeddings for your posts, or switch to keyword search.</p>';
            echo '</div>';
        }
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

