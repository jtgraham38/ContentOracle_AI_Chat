<?php
// exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

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

    //get border attributes
    $border_attrs = contentoracle_ai_chat_block_get_border_attrs($attributes);
    
    //only apply the border radius
    if (array_key_exists('border-radius', $border_attrs['inline_styles'])) {
        $inline_styles['border-radius'] = $border_attrs['inline_styles']['border-radius'];
    }

    //apply the border color as the button bg color
    //check for inline style first
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

    //get the color attributes
    $color_attrs = contentoracle_ai_chat_block_get_color_attrs($attributes);

    //apply only the text color
    if (in_array('has-text-color', $color_attrs['classnames'])) {
        $classnames[] = 'has-text-color';
        $classnames[] = preg_grep('/has-((?!text-color)[a-z]+|[a-z]+-\d+)-color/', $color_attrs['classnames'])[1];

    } else if (array_key_exists('color', $color_attrs['inline_styles'])) {
        $inline_styles['color'] = $color_attrs['inline_styles']['color'];
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

    //get padding classes or inline styles
    if (!empty($attributes['style']['spacing']['padding'])) {
        $padding = $attributes['style']['spacing']['padding'];
        //do top padding
        if (!empty($padding['top'])) {
            //translate any preset values
            $value = $attributes['style']['spacing']['padding']['top'];
            $has_padding_preset = str_contains( $value, 'var:preset|spacing|' );
            if ( $has_padding_preset ) {
                $named_spacing_value = substr( $value, strrpos( $value, '|' ) + 1 );
                $value             = sprintf( 'var(--wp--preset--spacing--%s)', $named_spacing_value );
            }

            $inline_styles['padding-top'] = $value;
        }
        //do right padding
        if (!empty($padding['right'])) {
            //translate any preset values
            $value = $attributes['style']['spacing']['padding']['right'];
            $has_padding_preset = str_contains( $value, 'var:preset|spacing|' );
            if ( $has_padding_preset ) {
                $named_spacing_value = substr( $value, strrpos( $value, '|' ) + 1 );
                $value             = sprintf( 'var(--wp--preset--spacing--%s)', $named_spacing_value );
            }

            $inline_styles['padding-right'] = $value;
        }
        //do bottom padding
        if (!empty($padding['bottom'])) {
            //translate any preset values
            $value = $attributes['style']['spacing']['padding']['bottom'];
            $has_padding_preset = str_contains( $value, 'var:preset|spacing|' );
            if ( $has_padding_preset ) {
                $named_spacing_value = substr( $value, strrpos( $value, '|' ) + 1 );
                $value             = sprintf( 'var(--wp--preset--spacing--%s)', $named_spacing_value );
            }

            $inline_styles['padding-bottom'] = $value;
        }
        //do left padding
        if (!empty($padding['left'])) {
            //translate any preset values
            $value = $attributes['style']['spacing']['padding']['left'];
            $has_padding_preset = str_contains( $value, 'var:preset|spacing|' );
            if ( $has_padding_preset ) {
                $named_spacing_value = substr( $value, strrpos( $value, '|' ) + 1 );
                $value             = sprintf( 'var(--wp--preset--spacing--%s)', $named_spacing_value );
            }

            $inline_styles['padding-left'] = $value;
        }
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

    //get background color classes or inline styles
    //if a preset background color is set, use that
    if (!empty($attributes['backgroundColor'])) {
        $classnames[] = "has-background";
        $classnames[] = "has-" . $attributes['backgroundColor'] . "-background-color";
    } 
    //otherwise, if a custom background color is set, use that
    else if (!empty($attributes['style']['color']['background'])){
        $value = $attributes['style']['color']['background'];
        $has_color_preset = str_contains( $value, 'var:preset|color|' );
		if ( $has_color_preset ) {
            $named_color_value = substr( $value, strrpos( $value, '|' ) + 1 );
			$value             = sprintf( 'var(--wp--preset--color--%s)', $named_color_value );
		}

        
        $inline_styles['background-color'] = $value;
    }

    //get text color classes or inline styles
    //if a preset text color is set, use that
    if (!empty($attributes['textColor'])) {
        $classnames[] = "has-text-color";
        $classnames[] = "has-" . $attributes['textColor'] . "-color";
    } 
    //otherwise, if a custom text color is set, use that
    else if (!empty($attributes['style']['color']['text'])){
        $value = $attributes['style']['color']['text'];
        $has_color_preset = str_contains( $value, 'var:preset|color|' );
		if ( $has_color_preset ) {
            $named_color_value = substr( $value, strrpos( $value, '|' ) + 1 );
			$value             = sprintf( 'var(--wp--preset--color--%s)', $named_color_value );
		}

        $inline_styles['color'] = $attributes['style']['color']['text'];
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

    //get border color classes or inline styles
    //if a preset border color is set, use that
    if (!empty($attributes['borderColor'])) {
        $classnames[] = "has-border-color";
        $classnames[] = "has-" . $attributes['borderColor'] . "-border-color";
    } 
    //otherwise, if a custom border color is set, use that
    else if (!empty($attributes['style']['border']['color'])){
        $inline_styles['border-color'] = $attributes['style']['border']['color'];
    }

    //get border width classes or inline styles
    if (!empty($attributes['style']['border']['width'])) {
        $inline_styles['border-width'] = $attributes['style']['border']['width'];
    }

    //get border radius classes or inline styles
    if (!empty($attributes['style']['border']['radius'])) {
        $inline_styles['border-radius'] = $attributes['style']['border']['radius'];
    }

    //return the classnames and inline styles
    return [
        'classnames' => $classnames,
        'inline_styles' => $inline_styles
    ];
}

?>