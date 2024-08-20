<?php
// exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
//see the 'core/search' block in for the reference I used: https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/search/index.php

//get the instance id
$instance_id = uniqid();//include seach block utils

//root element attributes
$root_attrs = contentoracle_ai_chat_block_get_root_attrs($attributes);
$root_classnames = implode(" ", $root_attrs['classnames']) . " contentoracle-ai_chat_root";
$root_inline_styles = implode(";", array_map(
    function ($v, $k) {
        return sprintf("%s:%s", $k, $v);
    },
    $root_attrs['inline_styles'],
    array_keys($root_attrs['inline_styles'])
));

//label attributes
$label_attrs = contentoracle_ai_chat_block_get_label_attrs($attributes);
$label_classnames = implode(" ", $label_attrs['classnames']) . " contentoracle-ai_chat_label" ;
$label_inline_styles = implode(";", array_map(
    function ($v, $k) {
        return sprintf("%s:%s", $k, $v);
    },
    $label_attrs['inline_styles'],
    array_keys($label_attrs['inline_styles'])
));
?>

<div style="<?php echo esc_attr($root_inline_styles) ?>" class="<?php echo esc_attr($root_classnames) ?>">
    <div class="contentoracle-ai_chat_header">
        <h3 
            class="<?php echo esc_attr($label_classnames) ?>"
            style="<?php echo esc_attr($label_inline_styles) ?>"
        >
            header
        </h3>
    </div>

    <div class="contentoracle-ai_chat_conversation">
        chat body
    </div>

    <div class="contentoracle-ai_chat_input_container">
        <input type="text" class="contentoracle-ai_chat_input">
        <button class="contentoracle-ai_chat_button">Go</button>
    </div>
</div>

<pre>
    <?php print_r($attributes); ?>
    <hr>
    <?php print_r(contentoracle_ai_chat_block_get_label_attrs($attributes)); ?>
    <hr>
    <?php print_r($root_inline_styles); ?>
    <hr>
    <?php print_r($root_classnames); ?>
    <hr>
    <?php print_r(get_block_wrapper_attributes()); ?>
</pre>
