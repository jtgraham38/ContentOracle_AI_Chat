<?php
// exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$new_attributes = WP_Block_Supports::get_instance()->apply_block_supports();
var_dump($new_attributes);
echo "<br>";
echo "<br>";

//generate an instance id
$instance_id = uniqid();

var_dump(get_block_wrapper_attributes());
echo "<br>";
echo "<br>";
var_dump($attributes);
echo "<br>";
echo "<br>";
//var_dump($content);
//echo "<br>";
//echo "<br>";
//var_dump($block);


$input_styles = [];

$label_styles = [];

$root_styles = [
    'border' => ( isset($style_attrs['border']['width']) ? $style_attrs['border']['width'] : "0px" )
    . " " . 
    ( isset($style_attrs['border']['color']) ? $style_attrs['border']['color'] : "black" ),
];

?>


<div class="contentoracle-ai_search_root">
    <label class="contentoracle-ai_search_label" for="contentoracle_search_input_<?php echo esc_attr($instance_id) ?>">Search</label>
    <form action="#" method="POST" class="contentoracle-ai_search_container">
        <input class="contentoracle-ai_search_input" type="search" id="contentoracle_search_input_<?php echo esc_attr($instance_id) ?>" required>
        <input type="submit" class="<?php echo esc_attr( $button_classes ) ?>" style="<?php echo esc_attr($button_styles) ?>" value="Search">
    </form>
</div>