<?php

if (!defined('ABSPATH')) {
    exit;
}

$value =  get_option($this->get_prefix() . 'auto_generate_embeddings', false);
?>
<div>
    <input
        id="<?php echo esc_attr($this->get_prefix()) ?>auto_generate_embeddings"
        name="<?php echo esc_attr($this->get_prefix()) ?>auto_generate_embeddings"
        title="Select whether new embeddings should be auto generated for posts.  If this is set, new embeddings will be generated for posts weekly.  If this is not set, no new embeddings will be generated."
        type="checkbox"
        <?php echo esc_attr($value) ? "checked" : "" ?>
    >
</div>