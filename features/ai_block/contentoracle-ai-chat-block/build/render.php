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

//generate unique id for the chat
$chat_id = wp_unique_id('contentoracle-ai_chat_');
?>



<div 
    id="<?php echo esc_attr( $chat_id ) ?>" 
    style="<?php echo esc_attr($root_inline_styles) ?>" 
    class="<?php echo esc_attr($root_classnames) ?>"
    x-data="contentoracle_ai_chat"
    data-contentoracle_rest_url="<?php echo get_rest_url() ?>"
>
    <div class="contentoracle-ai_chat_header">
        <h3 
            class="<?php echo esc_attr($label_classnames) ?>"
            style="<?php echo esc_attr($label_inline_styles) ?>"
        >
            <?php echo esc_html($attributes['header']); ?>
        </h3>
    </div>

    <div 
		class="<?php echo esc_attr( $chat_body_classnames ) ?>"
		style="<?php echo esc_attr( $chat_body_inline_styles ) ?>"
        x-ref="chatBody"
	>
        <template x-for="( chat, i ) in conversation" >
            <div
                class="contentoracle-ai_chat_bubble"
                x-bind:class="chat.role == 'user' ? 'contentoracle-ai_chat_bubble_user' : 'contentoracle-ai_chat_bubble_bot'"
            >
                <p x-text="chat.message"></p>
            </div>
        </template>


    </div>

    <form style="<?php echo esc_attr($input_container_inline_styles) ?>" class="<?php echo esc_attr($input_container_classnames) ?>">
        <span class="contentoracle-ai_chat_input_wrapper" style="flex-grow: 1;	/* grow to fill the space */">
            <input 
                type="text" 
                style="<?php echo esc_attr($input_inline_styles) ?>" 
                class="<?php echo esc_attr($input_classnames) ?>" 
                placeholder="<?php echo esc_attr( $attributes['placeholder'] ) ?>"
                x-model:value="userMsg"
                x-ref="chatInput"
                required
                maxlength="255"
            >
            <div class="contentoracle-ai_chat_loader" x-show="loading"></div>
        </span>
        <button
            style="<?php echo esc_attr($button_inline_styles) ?>"
            class="<?php echo esc_attr($button_classnames) ?>"
			x-on:click="sendMessage"
        >
            Send
        </button>
    </form>


</div>

<!-- <pre>
    <?php //print_r(get_rest_url());//print_r($attributes); ?>
    <hr>
    <?php //print_r(contentoracle_ai_chat_block_get_label_attrs($attributes)); ?>
    <hr>
    <?php //print_r($root_inline_styles); ?>
    <hr>
    <?php //print_r($root_classnames); ?>
    <hr>
    <?php //print_r(get_block_wrapper_attributes()); ?>
</pre> -->

<style>
    <?php 
        //temporarily style the speech bubbles using php echoed styles
        $user_bg = $attributes['userMsgBgColor'];
        $user_text = $attributes['userMsgTextColor'];
        $bot_bg = $attributes['botMsgBgColor'];
        $bot_text = $attributes['botMsgTextColor'];
    ?>
    .contentoracle-ai_chat_bubble_user{
        background-color: <?php echo $user_bg; ?>;
        color: <?php echo $user_text; ?>;
    }
    .contentoracle-ai_chat_bubble_bot{
        background-color: <?php echo $bot_bg; ?>;
        color: <?php echo $bot_text; ?>;
    }

    .is-typing{
        background-color: pink;
    }
</style>