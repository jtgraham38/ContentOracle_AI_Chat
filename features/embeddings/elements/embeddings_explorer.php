<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../../vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . '../VectorTable.php';
require_once plugin_dir_path(__FILE__) . '../VectorQueueTable.php';
require_once plugin_dir_path(__FILE__) . '../chunk_getters.php';

use \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
//access to vector table
$VT = new ContentOracle_VectorTable($this->get_prefix() ?? "coai_chat_");
$Q = new VectorTableQueue($this->get_prefix() ?? "coai_chat_");
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

//get all the embedding queue records
$queue_records = $Q->get_all_records();
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
        <h3>Embedding Queue</h3>
        <p>In this table, you will find all the posts that are scheduled to have embeddings generated.</p>

        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Tries remaining</th>
                    <th>Queued At</th>
                    <th>Started At</th>
                    <th>Finished At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($queue_records as $status => $records) : ?>

                    <?php foreach ($records as $record) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($record->post_id)); ?>">
                                    <?php echo esc_html($record->post_title); ?>
                                </a>
                            </td>
                            <td>
                                <span class="coai_chat_queue_status <?php echo esc_attr($record->status); ?>">
                                    <?php echo esc_html($record->status); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html( 3 - $record->error_count); ?></td>
                            <td><?php echo esc_html($record->queued_time); ?></td>
                            <td><?php echo esc_html($record->start_time ?? 'Not started'); ?></td>
                            <td><?php echo esc_html($record->end_time ?? 'Not finished'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tbody>
            
        </table>
    </div>

    <div>
        <h3>Schedule Posts for Embedding</h3>

        <form 
            method="POST" 
            id="<?php echo esc_attr($this->get_prefix()) ?>bulk_generate_embeddings_form"
        >
            <label for="bulk_generate_embeddings_select">Schedule Options</label>
            <div style="display: flex;" >
                
                <select 
                    name="bulk_generate_embeddings_option" 
                    id="bulk_generate_embeddings_select" 
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
            <p>Posts enqueued for embedding generation.</p>
        </div>

        <div id="<?php echo esc_attr($this->get_prefix()) ?>bulk_generate_embeddings_error_msg" class="<?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_error_msg <?php echo esc_attr($this->get_prefix()) ?>generate_embeddings_hidden">
            <p>Error enqueuing posts for embedding generation!</p>
        </div>
    </div>

</div>