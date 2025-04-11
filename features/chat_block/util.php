<?php
// exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';
use jtgraham38\jgwordpressstyle\BlockStyle;


//
////
////// NOTE: the classnames building will still need some work, to assemble the proper wordpress classname, instead of just the raw attr value!
////
//


/* function that assembles the style and class attributes for the root element of the block */
function contentoracle_ai_chat_block_get_root_attrs($attributes) {
    $classnames = [];
    $inline_styles = [];

    //get and apply border attributes
    $border_attrs = contentoracle_ai_chat_block_get_border_attrs($attributes);
    $classnames = array_merge($classnames, $border_attrs['classnames']);
    $inline_styles = array_merge($inline_styles, $border_attrs['inline_styles']);

    //get and apply color attributes
    $color_attrs = contentoracle_ai_chat_block_get_color_attrs($attributes);
    $classnames = array_merge($classnames, $color_attrs['classnames']);
    $inline_styles = array_merge($inline_styles, $color_attrs['inline_styles']);

    //get and apply padding attributes
    $padding_attrs = contentoracle_ai_chat_block_get_padding_attrs($attributes);
    $classnames = array_merge($classnames, $padding_attrs['classnames']);
    $inline_styles = array_merge($inline_styles, $padding_attrs['inline_styles']);

    //return the classnames and inline styles
    return [
        'classnames' => $classnames,
        'inline_styles' => $inline_styles
    ];
}
/* function that assembles the style and class attributes for the label element of the block */
function contentoracle_ai_chat_block_get_label_attrs($attributes){
    $classnames = [];
    $inline_styles = [];

    //get and apply color attributes
    $color_attrs = contentoracle_ai_chat_block_get_color_attrs($attributes);

    //only include classnames/inline styles for the text color
    if (in_array('has-text-color', $color_attrs['classnames'])) {
        $classnames = array_merge(
            ['has-text-color'], 
            preg_grep('/has-((?!text-color)[a-z]+|[a-z]+-\d+)-color/', $color_attrs['classnames'])
        );
    } else if (array_key_exists('color', $color_attrs['inline_styles'])) {
        $inline_styles['color'] = $color_attrs['inline_styles']['color'];
    }

    //return the classnames and inline styles
    return [
        'classnames' => $classnames,
        'inline_styles' => $inline_styles
    ];
}

/* function that assembles the style and class attributes for the chat body element of the block */
function contentoracle_ai_chat_block_get_chat_body_attrs($attributes){
    $classnames = [];
    $inline_styles = [];

    //get and apply border attributes
    $border_attrs = contentoracle_ai_chat_block_get_border_attrs($attributes);
    $classnames = array_merge($classnames, $border_attrs['classnames']);
    $inline_styles = array_merge($inline_styles, $border_attrs['inline_styles']);

    //return the classnames and inline styles
    return [
        'classnames' => $classnames,
        'inline_styles' => $inline_styles
    ];
}

/* function that assembles the style and class attributes for the input container element of the block */
function contentoracle_ai_chat_block_get_input_container_attrs($attributes){
    $classnames = [];
    $inline_styles = [];

    //get and apply border attributes
    $border_attrs = contentoracle_ai_chat_block_get_border_attrs($attributes);
    $classnames = array_merge($classnames, $border_attrs['classnames']);
    $inline_styles = array_merge($inline_styles, $border_attrs['inline_styles']);

    //return the classnames and inline styles
    return [
        'classnames' => $classnames,
        'inline_styles' => $inline_styles
    ];
}

/* function that assembles the style and class attributes for the input element of the block */
function contentoracle_ai_chat_block_get_input_attrs($attributes){
    $classnames = [];
    $inline_styles = [];

    //get border attributes
    $border_attrs = contentoracle_ai_chat_block_get_border_attrs($attributes);

    //only apply the border radius
    if (array_key_exists('border-radius', $border_attrs['inline_styles'])) {
        $inline_styles['border-radius'] = $border_attrs['inline_styles']['border-radius'];
    }

    //return the classnames and inline styles
    return [
        'classnames' => $classnames,
        'inline_styles' => $inline_styles
    ];
}

/* function that assembles the style and class attributes for the button element of the block */
function contentoracle_ai_chat_block_get_button_attrs($attributes){
    $classnames = [];
    $inline_styles = [];

    //make a style parser
    $style_parser = new BlockStyle($attributes);

    //get border attributes
    $border_attrs = contentoracle_ai_chat_block_get_border_attrs($attributes);
    
    //only apply the border radius
    if (array_key_exists('border-radius', $border_attrs['inline_styles'])) {
        $inline_styles['border-radius'] = $border_attrs['inline_styles']['border-radius'];
    }

    //lets get the background color
    //get the button color, applying the border color as a fallback
    $button_bg_color = $style_parser->btnBgColor();
    if (isset($button_bg_color->value)) {
        //check if the button bg color is a preset color
        if ($button_bg_color->isPreset) {
            $classnames[] = 'has-background';
            $classnames[] = $style_parser->presetVarToClass($button_bg_color->value, 'has-', '-background-color');
        }
        //otherwise, use the raw value
        else{
            $inline_styles['background-color'] = $button_bg_color->value;
        }
    }
    //otherwise use the border color as the button bg color
    else{
        if (array_key_exists('border-color', $border_attrs['inline_styles'])) {
            $inline_styles['background-color'] = $border_attrs['inline_styles']['border-color'];
        }
        //otherwise, check for classnames
        else if (in_array('has-border-color', $border_attrs['classnames'])) {
            $classnames[] = 'has-background';
    
            foreach ($border_attrs['classnames'] as $classname) {
                if (preg_match('/has-((?!border-color)[a-z]+|[a-z]+-\d+)-border-color/', $classname, $matches)) {
                    $classnames[] = 'has-' . $matches[1] . '-background-color';
                }
            }
        }
    }
    
    //now, we apply the text color
    $button_text_color = $style_parser->btnTextColor();
    if (isset($button_text_color->value)) {
        //check if the button text color is a preset color
        if ($button_text_color->isPreset) {
            $classnames[] = 'has-text-color';
            $classnames[] = $style_parser->presetVarToClass($button_text_color->value, 'has-', '-color');
        }
        //otherwise, use the raw value
        else{
            $inline_styles['color'] = $button_text_color->value;
        }
    }
    //if nothing was found, apply the text color of the block to the button
    else{
        //apply only the text color
        if (in_array('has-text-color', $color_attrs['classnames'])) {
            $classnames[] = 'has-text-color';
            $classnames[] = preg_grep('/has-((?!text-color)[a-z]+|[a-z]+-\d+)-color/', $color_attrs['classnames'])[1] ?? 'contrast';
    
        } else if (array_key_exists('color', $color_attrs['inline_styles'])) {
            $inline_styles['color'] = $color_attrs['inline_styles']['color'];
        }
    }

    //return the classnames and inline styles
    return [
        'classnames' => $classnames,
        'inline_styles' => $inline_styles
    ];
}

/* Gets the classname and inline style attribute values based on the padding attributes of a block. */
function contentoracle_ai_chat_block_get_padding_attrs($attributes) {
    $classnames = [];
    $inline_styles = [];

    //create style parser
    $style_parser = new BlockStyle($attributes);

    //get the values of each attr
    $padding = $style_parser->padding();
    $top_padding = $padding['top'] ?? null;
    $right_padding = $padding['right'] ?? null;
    $bottom_padding = $padding['bottom'] ?? null;
    $left_padding = $padding['left'] ?? null;

    //do top padding
    if (!empty($top_padding->value)) {
        //translate any preset values
        $value = $top_padding->value;
        $has_padding_preset = str_contains( $value, 'var:preset|spacing|' );
        if ( $has_padding_preset ) {
            $named_spacing_value = substr( $value, strrpos( $value, '|' ) + 1 );
            $value             = sprintf( 'var(--wp--preset--spacing--%s)', $named_spacing_value );
        }

        $inline_styles['padding-top'] = $value;
    }
    //do right padding
    if (!empty($right_padding->value)) {
        //translate any preset values
        $value = $right_padding->value;
        $has_padding_preset = str_contains( $value, 'var:preset|spacing|' );
        if ( $has_padding_preset ) {
            $named_spacing_value = substr( $value, strrpos( $value, '|' ) + 1 );
            $value             = sprintf( 'var(--wp--preset--spacing--%s)', $named_spacing_value );
        }

        $inline_styles['padding-right'] = $value;
    }
    //do bottom padding
    if (!empty($bottom_padding->value)) {
        //translate any preset values
        $value = $bottom_padding->value;
        $has_padding_preset = str_contains( $value, 'var:preset|spacing|' );
        if ( $has_padding_preset ) {
            $named_spacing_value = substr( $value, strrpos( $value, '|' ) + 1 );
            $value             = sprintf( 'var(--wp--preset--spacing--%s)', $named_spacing_value );
        }

        $inline_styles['padding-bottom'] = $value;
    }
    //do left padding
    if (!empty($left_padding->value)) {
        //translate any preset values
        $value = $left_padding->value;
        $has_padding_preset = str_contains( $value, 'var:preset|spacing|' );
        if ( $has_padding_preset ) {
            $named_spacing_value = substr( $value, strrpos( $value, '|' ) + 1 );
            $value             = sprintf( 'var(--wp--preset--spacing--%s)', $named_spacing_value );
        }

        $inline_styles['padding-left'] = $value;
    }

    //return the classnames and inline styles
    return [
        'classnames' => $classnames,
        'inline_styles' => $inline_styles
    ];
}

/* Gets the classname and inline style attribute values based on the color attributes of a block. */
function contentoracle_ai_chat_block_get_color_attrs($attributes) {
    $classnames = [];
    $inline_styles = [];

    //create style parser
    $style_parser = new BlockStyle($attributes);

    //get the values of each attr
    $background_color = $style_parser->bgColor();
    $text_color = $style_parser->textColor();

    //get class or inline style of background color
    if ($background_color->isPreset) {
        $classnames[] = "has-background";
        $classnames[] = "has-" . $background_color->value . "-background-color";
    } else {
        //if the background color is a css variable, use that
        if (str_contains($background_color->value ?? "", 'var:preset|color|')){
            $named_color_value = substr( $background_color->value, strrpos( $background_color->value, '|' ) + 1 );
            $inline_styles['background-color'] = sprintf( 'var(--wp--preset--color--%s)', $named_color_value );
        }
        //otherwise, use the raw value
        else{
            $inline_styles['background-color'] = $background_color->value;
        }
    }

    //get class or inline style of text color
    if ($text_color->isPreset) {
        $classnames[] = "has-text-color";
        $classnames[] = "has-" . $text_color->value . "-color";
    } else {
        //if the text color is a css variable, use that
        if (str_contains($text_color->value, 'var:preset|color|')){
            $named_color_value = substr( $text_color->value, strrpos( $text_color->value, '|' ) + 1 );
            $inline_styles['color'] = sprintf( 'var(--wp--preset--color--%s)', $named_color_value );
        }
        //otherwise, use the raw value
        else{
            $inline_styles['color'] = $text_color->value;
        }
    }


    //return the classnames and inline styles
    return [
        'classnames' => $classnames,
        'inline_styles' => $inline_styles
    ];
}

/* Gets the classname and inline style attribute values based on the border attributes of a block. */
function contentoracle_ai_chat_block_get_border_attrs($attributes) {
    $classnames = [];
    $inline_styles = [];

    //create style parser
    $style_parser = new BlockStyle($attributes);

    //get the values of each attr
    $border_color = $style_parser->borderColor();
    $border_width = $style_parser->borderWidth();
    $border_radius = $style_parser->borderRadius();

    //get class or inline style of color
    if ($border_color->isPreset) {
        $classnames[] = "has-border-color";
        $classnames[] = "has-" . $border_color->value . "-border-color";
    } else {
        $inline_styles['border-color'] = $border_color->value;
    }

    //border widht should be inline style
    $inline_styles['border-width'] = $border_width->value;

    //border radius should be inline style
    $inline_styles['border-radius'] = $border_radius->value;

    //return the classnames and inline styles
    return [
        'classnames' => $classnames,
        'inline_styles' => $inline_styles
    ];
}

?>