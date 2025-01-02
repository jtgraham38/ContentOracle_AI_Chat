<?php

if (!defined('ABSPATH')) {
    exit;
}

$value =  get_option($this->get_prefix() . 'auto_generate_only_new_embeddings', false);
?>
<div>
    <input
        id="<?php echo esc_attr($this->get_prefix()) ?>auto_generate_only_new_embeddings"
        name="<?php echo esc_attr($this->get_prefix()) ?>auto_generate_only_new_embeddings"
        title="Select whether to only auto generate new embeddings for posts that do not already have embeddings.  If this is set, only posts that do not have embeddings will have new embeddings generated for them.  If this is not set, all posts will have new embeddings generated for them."
        type="checkbox"
        <?php echo esc_attr($value) ? "checked" : "" ?>
    >
</div>