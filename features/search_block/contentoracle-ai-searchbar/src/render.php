<?php
// exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
//see the 'core/search' block in for the reference I used: https://github.com/WordPress/gutenberg/blob/trunk/packages/block-library/src/search/index.php

//get the instance id
$instance_id = uniqid();

//get all the attributes
$classnames          = classnames_for_block_contentoracle_ai_search( $attributes );
$inline_styles       = styles_for_block_contentoracle_ai_search( $attributes );
$color_classes       = get_color_classes_for_block_contentoracle_ai_search( $attributes );
$typography_classes  = get_typography_classes_for_block_contentoracle_ai_search( $attributes );
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


//echoes for debugging
// echo "<pre>";
// echo "Attrs<br>";
// print_r($attributes);
// // echo "<hr>";
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

// echo "</pre>";
?>

<div
    class="contentoracle-ai_search_root"
    <?php echo $root_styles ?>
>
    <label 
        class="<?php echo esc_attr( $label_classes ) ?>" 
        <?php echo $label_styles ?>
        for="contentoracle_search_input_<?php echo esc_attr($instance_id) ?>"
    >
        <?php echo esc_html( $attributes['label'] ) ?>
    </label>
    <form 
        action="<?php echo esc_url( home_url( '/' ) ) ?>" 
        method="GET" 
        role="search" 
        class="<?php echo esc_attr( $container_classes ) ?>" 
        <?php echo $container_styles ?>
    >
        <input 
            class="<?php echo esc_attr( $input_classes ) ?>" 
            <?php echo $input_styles ?> type="search" 
            id="contentoracle_search_input_<?php echo esc_attr($instance_id) ?>" 
            name="contentoracle_ai_search"
            required
            placeholder="<?php echo $attributes['placeholder'] ?>"
        >
        <input 
            type="submit" 
            class="<?php echo esc_attr( $button_classes ) ?>"
             <?php echo $button_styles ?>
             value="Search"
        >
    </form>
</div>
