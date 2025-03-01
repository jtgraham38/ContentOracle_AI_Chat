<?php
// exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
//see the 'core/search' block in for the reference I used: https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/search/index.php

//include autoload
require_once plugin_dir_path(__FILE__) . '../../../../vendor/autoload.php';

//use jtgraham38\jgwordpressstyle\BlockStyle;

//echo the volors
// echo "<pre>";
// print_r(contentoracle_ai_chat_block_get_border_attrs($attributes));
// echo "</pre>";


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

//source box border attributes
$sources_border_attrs = contentoracle_ai_chat_block_get_border_attrs($attributes);
$sources_border_inline_styles = implode(";", array_map(
    function ($v, $k) {
        return sprintf("%s:%s", $k, $v);
    },
    $sources_border_attrs['inline_styles'],
    array_keys($sources_border_attrs['inline_styles'])
));
$sources_border_classnames = $sources_border_attrs['classnames'];
$sources_border_classnames[] = "contentoracle-source_list";
$sources_border_classnames = implode(" ", $sources_border_classnames);

//action box border attributes
$action_border_attrs = contentoracle_ai_chat_block_get_border_attrs($attributes);
$action_border_inline_styles = implode(";", array_map(
    function ($v, $k) {
        return sprintf("%s:%s", $k, $v);
    },
    $action_border_attrs['inline_styles'],
    array_keys($action_border_attrs['inline_styles'])
));
$action_border_classnames = $action_border_attrs['classnames'];
$action_border_classnames[] = "contentoracle-action_container";
$action_border_classnames = implode(" ", $action_border_classnames);

//action button attributes
 $action_btn_inline_styles = $button_inline_styles;
$action_btn_classnames = $button_classnames;
$action_btn_classnames .= " contentoracle-action_button";

//generate unique id for the chat
$chat_id = wp_unique_id('contentoracle-ai_chat_');

?>
<div 
    id="<?php echo esc_attr( $chat_id ) ?>" 
    style="<?php echo esc_attr($root_inline_styles) ?>" 
    class="<?php echo esc_attr($root_classnames) ?>"
    coai-x-data="contentoracle_ai_chat"
    data-contentoracle_rest_url="<?php echo esc_url( get_rest_url() ) ?>"
    data-contentoracle_chat_nonce="<?php echo esc_attr( wp_create_nonce('contentoracle_chat_nonce') ) ?>"
    data-contentoracle_stream_responses="<?php echo esc_attr( $attributes['streamResponses'] ) ?>"
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
        coai-x-ref="chatBody"
	>
        <template coai-x-for="( chat, i ) in conversation" >
            <div
                class="contentoracle-ai_chat_bubble"
                coai-x-bind:class="chat.role == 'user' ? 'contentoracle-ai_chat_bubble_user' : 'contentoracle-ai_chat_bubble_bot'"
            >
                <p coai-x-html="chat.content"></p>

                <template coai-x-if="chat.role == 'assistant' && chat?.action?.content_url">
                    <div style="padding: 0.25rem; display: flex; flex-direction: column; align-items: center;">
                        <span style="text-size: larger; width: 100%;">Take Action!</span>
                        <div class="<?php echo esc_attr( $action_border_classnames ) ?>" style="<?php echo esc_attr( $action_border_inline_styles ) ?>">
                            <label
                                coai-x-text="chat?.action?.prompt ?? 'Learn more today!'" 
                                coai-x-bind:for="'<?php echo esc_attr($chat_id) ?>_action_' + i" 
                                class="contentoracle-action_label"
                            >
                                Action Prompt
                            </label>

                            <template coai-x-if="chat?.action?.content_featured_image">
                                <img 
                                    coai-x-bind:src="chat?.action?.content_featured_image ?? ''"
                                    coai-x-bind:alt="chat?.action?.content_title ?? 'Action Image'"
                                    class="contentoracle-action_image"
                                >
                            </template>

                            <template coai-x-if="chat?.action?.content_excerpt">
                                <p 
                                    coai-x-html="chat?.action?.content_excerpt"
                                    class="contentoracle-action_excerpt"    
                                ></p>
                            </template>


                            <a
                                coai-x-text="chat?.action?.button_text ?? 'Learn more'"
                                coai-x-bind:href="(chat?.action?.content_url) || '<?php echo esc_attr( get_permalink( get_option( 'page_for_posts' ) ) ) ?? '/' ?>'"
                                coasi-x-bind:id="'<?php echo esc_attr($chat_id) ?>_action_' + i"
                                target="_blank"
                                style="<?php echo esc_attr($action_btn_inline_styles) ?>"
                                class="<?php echo esc_attr($action_btn_classnames) ?>"
                            >
                                Action Button
                            </a>

                        </div>
                    </div>
                </template>

                <template coai-x-if="chat?.content_used && chat?.content_used?.length != 0">
                    <div style="padding: 0.25rem; display: flex; flex-direction: column; align-items: center;">

                        <span style="text-size: larger; width: 100%;">Sources</span>
                        
                        <div class="<?php echo esc_attr($sources_border_classnames) ?>" style="<?php echo esc_attr($sources_border_inline_styles) ?>">

                            <template coai-x-for="(source, index) in chat.content_used">
                                <div class="contentoracle-footer_citation">
                                    <span coai-x-text="(parseInt(index) + 1) + '.'"></span>
                                    <span coai-x-text="source.title"></span>
                                    <a coai-x-bind:href="source.url" target="_blank" class="contentoracle-footer_citation_link">→</a>
                                </div>
                            </template>
                        </ol>

                    </div>
                </template>
            </div>
        </template>

        <template coai-x-if="loading">
            <div
                class="contentoracle-ai_chat_bubble contentoracle-ai_chat_bubble_bot contentoracle-ai_chat_bubble_typing"
            >
                    <span>•</span>
                    <span>•</span>
                    <span>•</span>
            </div>
        </template>

        <template coai-x-if="error">
            <div
                class="contentoracle-ai_chat_bubble contentoracle-ai_chat_bubble_bot contentoracle-ai_chat_bubble_error"
            >
                <p>
                    <span>乁( ⁰͡ Ĺ̯ ⁰͡ ) ㄏ</span>
                    Sorry, something went wrong.  Please try again later.
                </p>
            </div>
        </template>
    </div>

    <form style="<?php echo esc_attr($input_container_inline_styles) ?>" class="<?php echo esc_attr($input_container_classnames) ?>">
        <span class="contentoracle-ai_chat_input_wrapper">
            <input 
                type="text" 
                style="<?php echo esc_attr($input_inline_styles) ?>" 
                class="<?php echo esc_attr($input_classnames) ?>" 
                placeholder="<?php echo esc_attr( $attributes['placeholder'] ) ?>"
                coai-x-model:value="userMsg"
                coai-x-ref="chatInput"
                coai-x-bind:disabled="loading"
                required
                maxlength="255"
            >
            <div class="contentoracle-ai_chat_loader" coai-x-show="loading"></div>
        </span>
        <button
            style="<?php echo esc_attr($button_inline_styles) ?>"
            class="<?php echo esc_attr($button_classnames) ?>"
			coai-x-on:click="sendMessage"
        >
            <?php echo esc_html($attributes['buttonText']); ?>
        </button>
    </form>
    <?php if ( get_option('coai_chat_debug_mode', false) ){ ?>
        <span style="color: red;" coai-x-text="error"></span>
        <span style="color: red;">See the console for more debugging info!</span>
    <?php } ?>
    <?php if ( get_option('coai_chat_display_credit_link', false) ){ ?>
        <small style="float: right; margin: 0.2rem 0.1rem;">
            Powered by 
            <a href="https://contentoracleai.com" target="_blank" class="contentoracle-footer_citation_link">
                ContentOracle AI
            </a>
        </small>
    <?php } ?>
</div>

