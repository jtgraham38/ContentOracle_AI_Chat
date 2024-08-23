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

//chat body attributes
$chat_body_attrs = contentoracle_ai_chat_block_get_chat_body_attrs($attributes);
$chat_body_classnames = implode(" ", $chat_body_attrs['classnames']) . " contentoracle-ai_chat_conversation" ;
$chat_body_inline_styles = implode(";", array_map(
    function ($v, $k) {
        return sprintf("%s:%s", $k, $v);
    },
    $chat_body_attrs['inline_styles'],
    array_keys($chat_body_attrs['inline_styles'])
));
//apply height to chat body styles
$chat_body_inline_styles .= ";height:" . $attributes['height'] . ";";

//input container attributes
$input_container_attrs = contentoracle_ai_chat_block_get_input_container_attrs($attributes);
$input_container_classnames = implode(" ", $input_container_attrs['classnames']) . " contentoracle-ai_chat_input_container" ;
$input_container_inline_styles = implode(";", array_map(
    function ($v, $k) {
        return sprintf("%s:%s", $k, $v);
    },
    $input_container_attrs['inline_styles'],
    array_keys($input_container_attrs['inline_styles'])
));

//input attributes
$input_attrs = contentoracle_ai_chat_block_get_input_attrs($attributes);
$input_classnames = implode(" ", $input_attrs['classnames']) . " contentoracle-ai_chat_input" ;
$input_inline_styles = implode(";", array_map(
    function ($v, $k) {
        return sprintf("%s:%s", $k, $v);
    },
    $input_attrs['inline_styles'],
    array_keys($input_attrs['inline_styles'])
));

//button attributes
$button_attrs = contentoracle_ai_chat_block_get_button_attrs($attributes);
$button_classnames = implode(" ", $button_attrs['classnames']) . " contentoracle-ai_chat_button" ;
$button_inline_styles = implode(";", array_map(
    function ($v, $k) {
        return sprintf("%s:%s", $k, $v);
    },
    $button_attrs['inline_styles'],
    array_keys($button_attrs['inline_styles'])
));

?>

<div style="<?php echo esc_attr($root_inline_styles) ?>" class="<?php echo esc_attr($root_classnames) ?>">
    <div class="contentoracle-ai_chat_header">
        <h3 
            class="<?php echo esc_attr($label_classnames) ?>"
            style="<?php echo esc_attr($label_inline_styles) ?>"
        >
            <?php echo esc_html($attributes['header']); ?>
        </h3>
    </div>

    <div class="<?php echo esc_attr( $chat_body_classnames ) ?>" style="<?php echo esc_attr( $chat_body_inline_styles ) ?>">
        <div class="contentoracle-ai_chat_bubble contentoracle-ai_chat_bubble_user" style="<?php echo esc_attr('background-color:' . $attributes['userMsgBgColor'] .';color:' . $attributes['userMsgTextColor'] . ';') ?>">
            <p>How do I grow a tomato plant?</p>
        </div>

        <div class="contentoracle-ai_chat_bubble contentoracle-ai_chat_bubble_bot" style="<?php echo esc_attr('background-color:' . $attributes['botMsgBgColor'] .';color:' . $attributes['botMsgTextColor'] . ';') ?>">
            <p>Tomato plants grow best in full sun, in soil that is rich in organic matter, and well-drained. They need a lot of water, but not too much. They also need a lot of nutrients, so you should fertilize them regularly. You should also prune them regularly to keep them healthy and productive. If you follow these tips, you should have a healthy and productive tomato plant.</p>
        </div>
    </div>

    <div style="<?php echo esc_attr($input_container_inline_styles) ?>" class="<?php echo esc_attr($input_container_classnames) ?>">
        <input type="text" style="<?php echo esc_attr($input_inline_styles) ?>" class="<?php echo esc_attr($input_classnames) ?>" placeholder="<?php echo esc_attr( $attributes['placeholder'] ) ?>">
        <button style="<?php echo esc_attr($button_inline_styles) ?>" class="<?php echo esc_attr($button_classnames) ?>">Send</button>
    </div>
</div>

<!-- <pre>
    <?php //print_r($attributes); ?>
    <hr>
    <?php //print_r(contentoracle_ai_chat_block_get_label_attrs($attributes)); ?>
    <hr>
    <?php //print_r($root_inline_styles); ?>
    <hr>
    <?php //print_r($root_classnames); ?>
    <hr>
    <?php //print_r(get_block_wrapper_attributes()); ?>
</pre> -->
