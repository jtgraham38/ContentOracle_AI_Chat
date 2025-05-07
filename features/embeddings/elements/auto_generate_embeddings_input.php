<?php

if (!defined('ABSPATH')) {
    exit;
}

$value =  get_option($this->get_prefix() . 'auto_generate_embeddings');
?>
<div>
    <input
        id="<?php echo esc_attr($this->get_prefix()) ?>auto_generate_embeddings"
        name="<?php echo esc_attr($this->get_prefix()) ?>auto_generate_embeddings"
        title="Select whether embeddings should automatically be generated for posts that do not have embeddings.  If this is set, posts without embeddings will be enqueued for embedding generation.  If this is not set, no posts will be enqueued for embedding generation."
        type="checkbox"
        <?php echo esc_attr($value) ? "checked" : "" ?>
    >
    <small>
        Selecting this option will automatically make sure all your posts have text embeddings.  Text embeddings allow use to use semantic search to intelligently map user queries to the most relevant posts.
    </small>
</div>