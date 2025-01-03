<?php

if (!defined('ABSPATH')) {
    exit;
}

//get all post types
$options = [
    'token:256' => "Token (". $this->get_chunk_size() ." tokens/chunk)",
];

$value =  get_option($this->get_prefix() . 'chunking_method', null);
?>

<div>
    <select
        id="<?php echo esc_attr($this->get_prefix()) ?>chunking_method"
        name="<?php echo esc_attr($this->get_prefix()) ?>chunking_method"
        title="Select the chunking method that should be used when generating embeddings of your post content.  This will determine how the content is broken up into smaller pieces for embedding generation.  If no chunking method is set, embeddings will not be generated."
    >
        <option value="" selected>None (Do not generate embeddings).</option>
        <?php foreach ($options as $key => $value) { ?>
            <option value="<?php echo esc_attr($key) ?>" <?php echo esc_attr(get_option($this->get_prefix() . 'chunking_method', null)) == $key ? "selected" : "" ?>  ><?php echo esc_html($value) ?></option>
        <?php } ?>
    </select>
</div>