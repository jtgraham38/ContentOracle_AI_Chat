<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//create options
$current_page_id = get_option($this->prefixed('ai_results_page'), null);
$pages = get_pages();

?>
<div>
    <select
        name="<?php $this->pre('ai_results_page') ?>" 
        class="<?php $this->pre('ai_results_page') ?>" 
        id="<?php $this->pre('ai_results_page_input') ?>" 
        title="Please select the page you want your users to be redirected to after an ai search.  Ensure that page contains the AI Chat block!">
        <option value="none" <?php echo esc_attr( $current_page_id == "none" ? "selected" : "" ) ?>  >None</option>
        <option value="">Create New Page</option>
        <?php
        foreach ($pages as $page) {
            $selected = $current_page_id == $page->ID ? 'selected' : '';
            ?><option value="<?php echo esc_attr($page->ID)?>" <?php echo esc_attr($selected) ?>> <?php echo esc_html($page->post_title) ?></option><?php
        }
        ?>
    </select>
</div>