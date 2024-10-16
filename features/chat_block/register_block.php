<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

/*
NOTE: there is a bit of a special, unusual way used to generate the styles for certain aspects of the block, like the 
bubble colors and scrollbar color. This is because the block directory does not like style tags in the render.php file.
So, I hook into the render_block filter to generate a string of styles from the attributes.
I then save this string to a class property, and then use the enqueue_block_assets action to attach the styles to an empty stylesheet.
This works because the render_block filter is called before the enqueue_block_assets action, so the styles are generated before the stylesheet is enqueued.
I needed to do this because the the render_block hook runs before the enqueue_block_assets hook so the render_block hook could not
be used to enqueue the styles directly since the stylesheet would not be enqueued yet.  
But, the render_block hook is necessary because it has access to the block attributes.  So this combination is used to solver this issue.
*/


class ContentOracleAiBlock extends PluginFeature{

    //this string is used to pass the generated styles from the render_block filter callback to the enqueue_block_assets action callback
    //NOTE: that it will only include the styles for the last block that was rendered.
    //NOTE: this means that multiple differently-styled chat blocks per page will not work as expected
    private string $style_string = "";
    //  \\  //  \\  //  \\  //  \\

    public function add_filters(){
        add_filter('render_block', array($this, 'add_render_styles'), 10, 2);
    }

    public function add_actions(){
        add_action('init', array($this, 'register_chat_blocks'));
        add_action('enqueue_block_assets', array($this, 'register_chat_block_styles'));
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //register the custom search block
    public function register_chat_blocks(){
        //include chat block utils
        //include seach block utils
        require_once $this->get_base_dir() . 'features/chat_block/util.php';

        //register chat block
        register_block_type($this->get_base_dir() . '/features/chat_block/block/build');
    }

    //register the stylesheet for applying attributes like bubble color to the block
    public function register_chat_block_styles($attributes){
        //register the stylesheet
        if ( !empty( $this->style_string ) ){
            //register and enqueue a blank stylsheet to attach inline styles to
            wp_register_style('contentoracle-ai-chat-block-styles', $this->get_base_url() . 'features/chat_block/block/assets/css/extra.css');
            wp_enqueue_style('contentoracle-ai-chat-block-styles');

            //attach the inline styles from the render_block filter to the stylesheet
            wp_add_inline_style( 'contentoracle-ai-chat-block-styles', $this->style_string );
        }
        
    }

    //use the render block filter to add the styles
    public function add_render_styles($block_content, $block){
        // Check if this is the specific block you want to target
        if ($block['blockName'] === 'contentoracle/ai-chat') {
            // Extract attributes
            $attributes = $block['attrs'];

            // Generate dynamic styles
            $user_bg = isset($attributes['userMsgBgColor']) ? $attributes['userMsgBgColor'] : '#ffffff';
            $user_text = isset($attributes['userMsgTextColor']) ? $attributes['userMsgTextColor'] : '#000000';
            $bot_bg = isset($attributes['botMsgBgColor']) ? $attributes['botMsgBgColor'] : '#ffffff';
            $bot_text = isset($attributes['botMsgTextColor']) ? $attributes['botMsgTextColor'] : '#000000';
            $link_color = isset($attributes['linkColor']) ? $attributes['linkColor'] : '#0000ff';
            $scrollbar_color = isset($attributes['scrollbarColor']) ? $attributes['scrollbarColor'] : '#cccccc';

            $style_string = <<<EOT
            .contentoracle-ai_chat_bubble_user {
                background-color: $user_bg;
                color: $user_text;
            }
            .contentoracle-ai_chat_bubble_bot {
                background-color: $bot_bg;
                color: $bot_text;
            }
            a.contentoracle-inline_citation {
                color: $link_color;
            }
            a.contentoracle-footer_citation_link {
                color: $link_color;
            }
            .contentoracle-ai_chat_conversation {
                overflow-y: auto;
                scrollbar-color: $scrollbar_color rgba(0, 0, 0, 0.01);
            }
            EOT;

            // Sanitize the CSS string
            $allowed_css = array(
                'background-color' => true,
                'color' => true,
                'overflow-y' => true,
                'scrollbar-color' => true,
            );

            $sanitized_css = wp_kses($style_string, array(), $allowed_css);

            // save the inline styles for adding to the stylesheet in the enqueue_block_assets action
            $this->style_string = $sanitized_css;

            // Optionally, you can append the styles to the block content
            //$block_content .= '<style>' . $sanitized_css . '</style>';
        }
        

        return $block_content;
    }

}