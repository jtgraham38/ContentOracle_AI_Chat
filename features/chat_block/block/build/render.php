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
// print_r($attributes);
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

//featured content border color and button class names (Styles handled in the register_block.php file)
$featured_content_border_classes = contentoracle_ai_chat_block_get_border_attrs($attributes)['classnames'];
$featured_content_border_classes = implode(" ", $featured_content_border_classes);

$featured_content_button_classes = implode(" ", $button_attrs['classnames']);

//generate unique id for the chat
$chat_id = wp_unique_id('contentoracle-ai_chat_');

?>
<div 
    id="<?php echo esc_attr( $chat_id ) ?>" 
    style="<?php echo esc_attr($root_inline_styles) ?>" 
    class="<?php echo esc_attr($root_classnames) ?>"
    coai-x-data="contentoracle_ai_chat"
    data-contentoracle_rest_url="<?php echo esc_url( get_rest_url() ) ?>"
    data-contentoracle_chat_nonce="<?php echo esc_attr( wp_create_nonce('wp_rest') ) ?>"
    data-contentoracle_stream_responses="<?php echo esc_attr( $attributes['streamResponses'] ) ?>"
    data-contentoracle_scroll_block_into_view="<?php echo esc_attr( $attributes['scrollBlockIntoView'] ) ?>"
    data-contentoracle_chat_message_seeder_items="<?php echo esc_attr( json_encode( $attributes['chatMessageSeederItems'] ) ) ?>"
    data-contentoracle_featured_content_border_classes="<?php echo esc_attr( $featured_content_border_classes ) ?>"
    data-contentoracle_featured_content_button_classes="<?php echo esc_attr( $featured_content_button_classes ) ?>"
>
    <div class="contentoracle-ai_chat_header">
        <h3 
            class="<?php echo esc_attr($label_classnames) ?>"
            style="<?php echo esc_attr($label_inline_styles) ?>"
        >
            <?php echo esc_html($attributes['header']); ?>
        </h3>
        <button
            class="contentoracle-ai_chat_reset_button"
            coai-x-on:click="resetChat"
            title="Reset chat"
        >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path>
                <path d="M21 3v5h-5"></path>
                <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path>
                <path d="M3 21v-5h5"></path>
            </svg>
        </button>
    </div>

    <div 
		class="<?php echo esc_attr( $chat_body_classnames ) ?>"
		style="<?php echo esc_attr( $chat_body_inline_styles ) ?>"
        coai-x-ref="chatBody"
	>

        <template coai-x-if="conversation.length == 0">
            <div class="contentoracle-ai_chat_greeter_container">
                <div class="contentoracle-ai_chat_greeter">
                    <p>
                        <?php echo esc_html($attributes['greeterMsg']); ?>
                    </p>
                    <div class="contentoracle-ai_chat_message_seeder">
                        <template coai-x-for="(item, index) in chat_message_seeder_items">
                            <div 
                                class="contentoracle-ai_chat_message_seeder_item"
                                coai-x-on:click="useChatMessageSeederItem(item)"
                                style="border-radius: <?php echo esc_attr($sources_border_attrs['inline_styles']['border-radius'] ?? '4px'); ?>"
                            >
                                <span coai-x-text="item"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </template>

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
                                    <span coai-x-html="source.title"></span>
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
                    <template coai-x-if="error.error_code == 'SUBSC_OUT_CHAT_USAGE' || error.error_code == 'SUBSC_OUT_EMBED_USAGE'">
                        <div>
                            <span>This site has reached its limit of AI chat requests.  Please try again later, and contact the site administrator to increase the limit.</span>
                            <?php if ( current_user_can('manage_options') ){ ?>
                                <div>
                                    Head over to 
                                    <a href="https://app.contentoracleai.com/dashboard" target="_blank" style="color: white;">
                                        app.contentoracleai.com
                                    </a>
                                    to upgrade your subscription.
                            </div>
                            <?php } ?>
                        </div>
                        <!-- <span coai-x-text="error.error_msg"></span> -->
                    </template>
                    <template coai-x-if="!( error.error_code == 'SUBSC_OUT_CHAT_USAGE' || error.error_code == 'SUBSC_OUT_EMBED_USAGE' )">
                        <div>
                            <span>乁(⁰͡ Ĺ̯ ⁰͡ )ㄏ</span>
                            Sorry, something went wrong.  Please try again later.
                        </div>
                    </template>
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
        <span style="color: red;" coai-x-text="error.error_code"></span>
        <span style="color: red;" coai-x-text="error.error_msg"></span>
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

