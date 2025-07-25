<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'util.php';

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


//TODO
////TODO
//////TODO I need to implement my styling library into this plugin in order to appropriatle style the featured_content artfiacts
//////TODO as well as for general quality of life improvements
////TODO
//TODO

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
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //register the custom search block
    public function register_chat_blocks(){
        //include chat block utils
        //include seach block utils
        require_once plugin_dir_path( __FILE__ ) . '/util.php';

        //register chat block
        register_block_type(plugin_dir_path( __FILE__ ) . '/block/build');
    }

    //use the render block filter to add the styles
    public function add_render_styles($block_content, $block){
        // Check if this is the specific block you want to target
        if ($block['blockName'] === 'contentoracle/ai-chat') {
            // Extract attributes
            $attributes = $block['attrs'];

            // Generate dynamic styles
            //determine the link color
            //if a preset border color is set, use that
            $link_color = "";
            if (!empty($attributes['borderColor'])) {
                $link_color = 'var( --wp--preset--color--' . $attributes['borderColor'] . ')';
            } 
            //otherwise, if a custom border color is set, use that
            else if (!empty($attributes['style']['border']['color'])){
                $link_color = $attributes['style']['border']['color'];
            }

            $scrollbar_color = $link_color;

            //get the other border styles: width and radius
            $border_width = isset($attributes['style']['border']['width']) ? $attributes['style']['border']['width'] : '1px';
            $border_radius = isset($attributes['style']['border']['radius']) ? $attributes['style']['border']['radius'] : '5px';

            //generate the styles for the chat block
            $user_bg = isset($attributes['userMsgBgColor']) ? $attributes['userMsgBgColor'] : '#3232fd';
            $user_text = isset($attributes['userMsgTextColor']) ? $attributes['userMsgTextColor'] : '#eeeeff';
            $bot_bg = isset($attributes['botMsgBgColor']) ? $attributes['botMsgBgColor'] : '#d1d1d1';
            $bot_text = isset($attributes['botMsgTextColor']) ? $attributes['botMsgTextColor'] : '#111111';

            //get border color, width, and radius
            $border_styles = contentoracle_ai_chat_block_get_border_attrs($attributes)['inline_styles'];
            $border_width = $border_styles['border-width'];
            $border_radius = $border_styles['border-radius'];
            $border_color = $border_styles['border-color'] ?? "none";

            //get button color and text color
            $button_styles = contentoracle_ai_chat_block_get_button_attrs($attributes)['inline_styles'];
            $button_bg = $button_styles['background-color'] ?? $border_color;
            $button_text = $button_styles['color'] ?? "none";

            $style_string = sprintf('
                .contentoracle-ai_chat_bubble_user {
                    background-color: %s;
                    color: %s;
                }
                .contentoracle-ai_chat_bubble_bot {
                    background-color: %s;
                    color: %s;
                }
                a.contentoracle-inline_citation {
                    color: %s !important;
                }
                a.contentoracle-footer_citation_link {
                    color: %s !important;
                }
                .contentoracle-ai_chat_conversation {
                    overflow-y: auto;
                    scrollbar-color: %s rgba(0, 0, 0, 0.01);
                }
                .coai_chat-featured_content{
                    border: %s solid %s;
                    border-radius: %s;
                }
                .coai_chat-featured_content a{
                    background-color: %s;
                    color: %s;
                    border-radius: %s;
                }
            ', $user_bg, $user_text, $bot_bg, $bot_text, $link_color, $link_color, $scrollbar_color, $border_width, $border_color, $border_radius, $button_bg, $button_text, $border_radius);

            // Sanitize the CSS string
            $allowed_css = array(
                'background-color' => true,
                'color' => true,
                'overflow-y' => true,
                'scrollbar-color' => true,
            );

            $sanitized_css = wp_kses($style_string, array(), $allowed_css);

            //register the stylesheet
            if ( !empty( $sanitized_css ) ){
                //register and enqueue a blank stylsheet to attach inline styles to
                wp_register_style('contentoracle-ai-chat-block-styles', plugin_dir_url( __FILE__ ) . '/block/assets/css/extra.css');
                wp_enqueue_style('contentoracle-ai-chat-block-styles');

                //attach the inline styles from the render_block filter to the stylesheet
                wp_add_inline_style( 'contentoracle-ai-chat-block-styles', $sanitized_css );
            }
        }
        

        return $block_content;
    }

    //placeholder uninstall method to identify this block
    public function uninstall(){
        
    }
}