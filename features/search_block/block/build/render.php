<?php
// exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
//see the 'core/search' block in for the reference I used: https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/search/index.php

//get the instance id
$instance_id = uniqid();

//get all the attributes
$classnames          = contentoracle_ai_search_classnames_for_block( $attributes );
$inline_styles       = contentoracle_ai_search_styles_for_block( $attributes );
$color_classes       = contentoracle_ai_search_get_color_classes_for_block( $attributes );
$typography_classes  = contentoracle_ai_search_get_typography_classes_for_block( $attributes );
$border_color_classes = get_border_color_classes_for_block_contentoracle_ai_search( $attributes );

//format the attributes for each element
//button should have bg color, text color, and border radius style
$button_classes = 'contentoracle-ai_search_button ' . $color_classes;
$button_styles = $inline_styles['button'];

//override the default if a custom color is set
//TODO: this is a hacky way to do this.  I should find a better way to do this.
if ( ! empty( $attributes['style']['color']['background'] ) ) {
    $button_classes = str_replace( 'has-contrast-background-color', '', $button_classes );
    //"contrast" is the default value for the background color from block.json, so I am removing it if a custom color is set
}
if ( ! empty( $attributes['style']['color']['text'] ) ) {
    $button_classes = str_replace( 'has-base-2-color', '', $button_classes );
    //"base-2" is the default value for the text color from block.json, so I am removing it if a custom color is set
}

//input should have defaults, plus border radius style
$input_classes = 'contentoracle-ai_search_input';
$input_styles = $inline_styles['input'];

//container (form) should have border width, border color, and border radius style
$container_classes = 'contentoracle-ai_search_container ' . $border_color_classes;
$container_styles = $inline_styles['wrapper'];  //use wrapper key for container

//label should have typography styles, but not text color
$label_classes = 'contentoracle-ai_search_label ' . $typography_classes;
$label_styles = $inline_styles['label'];

//create styles for the root
//create width style for the container
$root_styles = '';
if ( ! empty( $attributes['width'] ) ) {
    $root_styles .= 'style="width: ' . $attributes['width'] . ';"';
}

//create styles for the notice element
$notice_classes = 'contentoracle-ai_search_notice';
$notice_styles = [];

//apply the text color to the notice
//apply the border color as the notice bg color
if ( isset( $attributes['style']['color']['text'])){

    //TODO: handle presets here!
    $notice_styles['color'] = $attributes['style']['color']['text'];
}
else if ( isset( $attributes['textColor'])){
    $notice_classes .= ' has-text-color has-' . $attributes['textColor'] . '-color';  
}
else {
    $notice_styles['color'] = '#111111';
}

//apply the border color as the notice bg color
if ( isset( $attributes['borderColor'])){
    $notice_classes .= ' has-background has-' . $attributes['borderColor'] . '-background-color';  
}
else if ( isset( $attributes['style']['border']['color'])){

    //TODO: handle presets here!
    $notice_styles['background-color'] = $attributes['style']['border']['color'];
}
else {
    //try to match the button's color if no border color is set
    if (isset($attributes['backgroundColor'])){
        $notice_classes .= ' has-background has-' . $attributes['backgroundColor'] . '-background-color';
    } else if ( isset( $attributes['style']['color']['background'] ) ){
        $notice_styles['background-color'] = $attributes['style']['color']['background'];
    }
    else{
        $notice_styles['background-color'] = '#EEEEEE';
    }
}
//create classes and styles
$notice_styles['border-radius'] = $attributes['style']['border']['radius'] ?? '0px';
$notice_styles['z-index'] = '1000'; //make sure the notice is on top of everything

$notice_styles = implode( ';', array_map(
    function ( $value, $key ) {
        return $key . ':' . $value;
    },
    $notice_styles,
    array_keys( $notice_styles )
) );

//create notice arrow style
$arrow_classes = 'contentoracle-ai_search_arrow';
$arrow_styles = [];
if ( isset( $attributes['borderColor'])){
    $arrow_classes .= ' has-background has-' . $attributes['borderColor'] . '-background-color';  
}
else if ( isset( $attributes['style']['border']['color'])){
    $arrow_styles['background-color'] = $attributes['style']['border']['color'];
}
else {
    //try to match the button's color if no border color is set
    if (isset($attributes['backgroundColor'])){
        $arrow_classes .= ' has-background has-' . $attributes['backgroundColor'] . '-background-color';
    } else if ( isset( $attributes['style']['color']['background'] ) ){
        $arrow_styles['background-color'] = $attributes['style']['color']['background'];
    }
    else{
        $arrow_styles['background-color'] = '#EEEEEE';
    }
}
$arrow_styles = implode( ';', array_map(
    function ( $value, $key ) {
        return $key . ':' . $value;
    },
    $arrow_styles,
    array_keys( $arrow_styles )
) );
    

//echoes for debugging
// echo "<pre>";
// // echo "Attrs<br>";
// print_r($attributes);
// echo "<hr>";
// print_r($typography_classes);
// // echo "classnames<br>";
// // print_r($classnames);
// // echo "<hr>";
// // echo "inline_styles<br>";
// // print_r($inline_styles);
// // echo "<hr>";
// // echo "color_classes<br>";
// // print_r($color_classes);
// // echo "<hr>";
// // echo "typography_classes<br>";
// // print_r($typography_classes);
// // echo "<hr>";
// // echo "border_color_classes<br>";
// // print_r($border_color_classes);

// // echo "</pre>";
?>
<div style="display:flex; justify-content:center;">
    <div
        class="contentoracle-ai_search_root"
        <?php echo esc_attr( $root_styles )  ?>
    >
        <label 
            class="<?php echo esc_attr( $label_classes ) ?>" 
            style="<?php echo esc_attr( $label_styles ) ?>"
            for="contentoracle_search_input_<?php echo esc_attr($instance_id) ?>"
        >
            <?php echo esc_html( $attributes['label'] ) ?>
        </label>
        <form 
            action="<?php echo esc_url( home_url( '/' ) ) ?>" 
            method="GET" 
            role="search" 
            class="<?php echo esc_attr( $container_classes ) ?>" 
            style="<?php echo esc_attr($container_styles) ?>"
        >
            <input 
                class="<?php echo esc_attr( $input_classes ) ?>" 
                style="<?php echo esc_attr( $input_styles ) ?>" type="search" 
                id="contentoracle_search_input_<?php echo esc_attr($instance_id) ?>" 
                name="contentoracle_ai_search"
                required
                placeholder="<?php echo esc_attr($attributes['placeholder']) ?>"
            >
            <input type="hidden" name="contentoracle_ai_search_should_redirect" value="1">
            <input 
                type="submit" 
                class="<?php echo esc_attr( $button_classes ) ?>"
                 style="<?php echo esc_attr( $button_styles ) ?>"
                 value="Search"
                 id=
            >
        </form>

        <?php if ( isset( $attributes['noticeText'] ) && $attributes['noticeText'] != "" ) : ?>
            <div class="<?php echo esc_attr( $notice_classes )?>" style="<?php echo esc_attr( $notice_styles ) ?>">
                
                <label for="contentoracle_search_input_<?php echo esc_attr($instance_id) ?>">
                    <?php echo esc_html( $attributes['noticeText'] ) ?>
                </label>
                <div class="<?php echo esc_attr( $arrow_classes )?>" style="<?php echo esc_attr( $arrow_styles ) ?>"></div>
                <span class="contentoracle-ai_search_notice_close">&times;</span>
            </div>
        <?php endif ?>
    </div>
</div>
