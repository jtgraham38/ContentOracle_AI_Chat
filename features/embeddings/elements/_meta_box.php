<?php 
// exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//create a nonce field
wp_nonce_field($this->get_prefix() . 'save_generate_embeddings', $this->get_prefix() . 'generate_embeddings_nonce');

?>

<div style="display: flex; flex-direction: row; justify-content: space-between; align-items: center;">
    <label for="<?php echo esc_attr($this->get_prefix()) ?>generate_embeddings">
        (Re)Generate Embeddings
    </label>
    <input
        type="checkbox" 
        id="<?php echo esc_attr($this->get_prefix()) ?>generate_embeddings" 
        name="<?php echo esc_attr($this->get_prefix()) ?>generate_embeddings" 
        title='If checked, the embeddings for this post will be (re)generated and stored in the database.'
        checked
    >
</div>