<?php

if (!defined('ABSPATH')) {
    exit;
}

//get all post types
$options = [
    'token:256' => "Token (256 tokens/chunk)",
];

$value =  get_option($this->get_prefix() . 'chunking_method', null);
?>

<div>
    <select
        id="<?php echo esc_attr($this->get_prefix()) ?>chunking_method"
        name="<?php echo esc_attr($this->get_prefix()) ?>chunking_method"
        title="Select the chunking method that should be used when generating embeddings of your post content.  This will determine how the content is broken up into smaller pieces for embedding generation.  If no chunking method is set, embeddings will not be generated, and keyword search will be used instead."
    >
        <option value="" selected>None (Use keyword search).</option>
        <?php foreach ($options as $key => $value) { ?>
            <option value="<?php echo esc_attr($key) ?>" <?php echo esc_attr(get_option($this->get_prefix() . 'chunking_method', null)) == $key ? "selected" : "" ?>  ><?php echo esc_html($value) ?></option>
        <?php } ?>
    </select>
    <small style="display: block;">Note: it is heavily recommended to use the embeddings instead of keyword search, as it will provide a more accurate and relevant search.</small>
</div>