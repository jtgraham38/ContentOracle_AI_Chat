<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//create options
$current_page_id = get_option($this->get_prefix() . 'ai_results_page', null);
$pages = get_pages();

?>
<div>
    <select
        name="<?php echo $this->get_prefix() . 'ai_results_page' ?>" 
        class="<?php echo $this->get_prefix() . 'ai_results_page' ?>" 
        id="<?php echo $this->get_prefix() . 'ai_results_page_input' ?>" 
        title="Please select the page you want your users to be redirected to after an ai search.  Ensure that page contains the AI Chat block!">
        <option value="none" <?php echo $current_page_id == "none" ? "selected" : "" ?>  >None</option>
        <option value="">Create New Page</option>
        <?php
        foreach ($pages as $page) {
            $selected = $current_page_id == $page->ID ? 'selected' : '';
            ?><option value="<?php echo esc_attr($page->ID)?>" <?php echo esc_attr($selected) ?>> <?php echo esc_html($page->post_title) ?></option><?php
        }
        ?>
    </select>
</div>