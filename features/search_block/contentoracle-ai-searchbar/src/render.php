<?php
// exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//generate an instance id
$instance_id = uniqid();

//create styles for each element
$button_styles = [];

$input_styles = [];

$label_styles = [];

$root_styles = [];
?>


<div class="contentoracle-ai_search_root">
    <label class="contentoracle-ai_search_label" for="contentoracle_search_input_<?php echo esc_attr($instance_id) ?>">Search</label>
    <form action="#" method="POST" class="contentoracle-ai_search_container">
        <input class="contentoracle-ai_search_input" type="search" id="contentoracle_search_input_<?php echo esc_attr($instance_id) ?>" required>
        <input type="submit" class="contentoracle-ai_search_button" value="Search">
    </form>
</div>