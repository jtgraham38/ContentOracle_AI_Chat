<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../../vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . '../VectorTable.php';
require_once plugin_dir_path(__FILE__) . '../chunk_getters.php';

use \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
//access to vector table
$VT = new ContentOracle_VectorTable($this->get_prefix() ?? "coai_chat_");

//$result = $VT->search(json_encode( $vector ));
//this file shows an input, and uses it to display the raw embeddings values for a given post


//set default values
$post_id = null;
$embeddings = [];

//get the selected post, if one is selected
//get the post id of the selected post
if (isset($_REQUEST['post_id'])) {
    $post_id = $_REQUEST['post_id'];
    $selected_post = get_post($post_id);

    //get the embeddings of the post
    $embedding_ids = get_post_meta($post_id, $this->get_prefix() . 'embeddings', true) ?? [];
    if (!is_array($embedding_ids)) {    //ensure the embeddings are an array
        $embedding_ids = [];
    }
    $embeddings = $VT->ids($embedding_ids);
}

//ensure the embeddings are an array
if (!is_array($embeddings)) {
    $embeddings = [];
}

//get the types of posts that are indexed by the AI
$post_types = get_option('coai_chat_post_types');

//get all posts of the indexed types
$posts = get_posts(array(
    'post_type' => $post_types,
    'numberposts' => -1,
    'orderby' => 'post_type',
    'order' => 'ASC'
));
?>
<details>
    <summary>Tips</summary>
    <ul>
        <li>
            Select a post from the dropdown to view its embeddings.
        </li>
        <li>
            Click "Generate Embeddings" to generate embeddings for the selected post.
        </li>
        <li>
            Click "Re-Generate Embeddings" to re-generate embeddings for the selected post.
        </li>
        <li>
            Click "Bulk Generate Embeddings" to generate embeddings for many posts at once.
        </li>
        <li>
            If a post does not have any body saved to the database outside of block comments, no embeddings will be generated for it.
        </li>
    </ul>
</details>
<strong>Note: Embeddings will only be generated for posts of the types set in the "Prompt" settings.  They will also only be generated if a chunking method is set.</strong>
<br>
<br>

<div id="<?php echo esc_attr( $this->get_prefix() ) ?>embeddings_explorer">

    <div>
        <h3>Bulk Generate Embeddings</h3>
        <p>Generate embeddings for many posts at once!</p>

        <form 
            method="POST" 
            id="<?php echo esc_attr($this->get_prefix()) ?>bulk_generate_embeddings_form"
        >
            <label for="bulk_generate_embeddings_select">Bulk Options</label>
            <div style="display: flex;" >
                
                <select 
                    name="bulk_generate_embeddings_option" 
                    id="singular_generate_embeddings_select" 
                    required
                    title="Select an option to generate embeddings for many posts at once.  This will only generate embeddings for posts of the types selected in the prompt settings, and only if a chunking method is set."    
                >
                    <option value="" selected>Select an option...</option>
                    <option value="not_embedded">All Posts Without Embeddings</option>
                    <option value="all">All Posts</option>
                </select>
                <input type="submit" value="Generate Embeddings" class="button-primary">
            </div>
        </form>

        <!-- spinner, success, error -->
        <div id="<?php echo esc_attr($this->get_prefix()) ?>bulk_generate_embeddings_result_container" class="<?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_result_container" >
            <span id="<?php echo esc_attr($this->get_prefix()) ?>bulk_generate_embeddings_spinner" 
                class="
                    <?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_spinner
                    <?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_hidden
            ">
            </span>
        </div>

        <div id="<?php echo esc_attr($this->get_prefix()) ?>bulk_generate_embeddings_success_msg" class="<?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_success_msg <?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_hidden">
            <p>Embeddings generated successfully!</p>
        </div>

        <div id="<?php echo esc_attr($this->get_prefix()) ?>bulk_generate_embeddings_error_msg" class="<?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_error_msg <?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_hidden">
            <p>Error generating embeddings!</p>
        </div>
    </div>

    <br>
    <hr>
    <br>

    <div>
        <h3>View Embeddings</h3>
        <p>Select a post to view and re-generate its embeddings.</p>

        <label for="<?php echo esc_attr($this->get_prefix()) ?>post_embedding_selector">Post</label>
        <select 
            name="post_id" 
            required 
            id="<?php echo esc_attr($this->get_prefix()) ?>post_embedding_selector"
            title="The embeddings shown in the table below are for the selected post.  Embeddings will be generated for this post if the button is pressed."
        >
            <option value="" selected>Select a post...</option>
            <?php foreach ($posts as $post) { ?>
                <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $post_id, $post->ID ); ?>><?php echo esc_html( $post->post_title ); ?> (<?php echo esc_attr( $post->post_type ) ?>)</option>
            <?php } ?>
        </select>

        <script>
            document.addEventListener('DOMContentLoaded', function(){
                let selector = document.getElementById('<?php echo esc_attr($this->get_prefix()) ?>post_embedding_selector');
                console.log(selector, "selector");
                selector.addEventListener('change', function(){
                    window.location.href = '<?php echo esc_url($_SERVER['PHP_SELF']); ?>?page=contentoracle-ai-chat-embeddings&post_id=' + selector.value;
                });
            });
        </script>

        <br>
        <br>

            <?php
                if ($post_id && intval($post_id) > 0) {
                    //get the last time the embeddings were generated
                    $most_recent_embedding = $VT->get_latest_updated($post_id);
        
                    if (isset($most_recent_embedding->updated_at)) {
                        ?>
                            Embeddings last generated at: <?php echo esc_html($most_recent_embedding->updated_at); ?>
                        <?php
                    }
                }
            ?>

        <div
        >
            <form 
                method="POST" 
                id="<?php echo esc_attr($this->get_prefix()) ?>singular_generate_embeddings_form"
            >
                <div
                    style="max-width: 48rem; overflow-x: auto;"
                >
                    <table>
                        <thead>
                            <tr>
                                <th>Content</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                            <?php if (isset($embeddings) && count($embeddings) > 0) { ?>
                                <?php foreach ($embeddings as $i => $embedding) { ?>
                                    <tr>
    
                                        <?php 
                                            $section = token256_get_chunk($selected_post->post_content, $i);
                                        ?>
    
                                        <td title="<?php echo esc_attr($section); ?>">
                                            <?php 
                                                $tokens = explode(' ', $section);
                                                $display_section = implode(' ', array_slice($tokens, 0, 3)) . ' ... ' . implode(' ', array_slice($tokens, -3));
                                                echo esc_html($display_section);
                                            ?>
                                        </td>
                                        <td name="embeddings[]">
                                            <details>
                                                <summary>Click to view vector</summary>
                                                <?php echo esc_html($embedding->vector); ?>
                                            </details>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="2">No embeddings found.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                <input type="hidden" name="post_id" id="post_id_input" value="<?php echo esc_attr( $post_id ) ?>">
                <input type="submit" value="<?php echo count($embeddings) > 0 ? "Re-Generate Embeddings" : "Generate Embeddings"  ?>" class="button-primary">
            </form>

            <!-- spinner, success, error -->
            <div id="<?php echo esc_attr($this->get_prefix()) ?>singular_generate_embeddings_result_container" class="<?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_result_container" >
                <span id="<?php echo esc_attr($this->get_prefix()) ?>singular_generate_embeddings_spinner" 
                    class="
                        <?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_spinner
                        <?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_hidden
            ">
            </span>
        </div>

        <div id="<?php echo esc_attr($this->get_prefix()) ?>singular_generate_embeddings_success_msg" class="<?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_success_msg <?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_hidden">
            <p>Embeddings generated successfully!</p>
        </div>

        <div id="<?php echo esc_attr($this->get_prefix()) ?>singular_generate_embeddings_error_msg" class="<?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_error_msg <?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_hidden">
            <p>Error generating embeddings!</p>
        </div>

    </div>

</div>