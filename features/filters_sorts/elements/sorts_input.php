<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//load the current value of the sorts setting
$sorts_setting = get_option($this->prefixed('sorts'), array());
?>

<div id="<?php $this->pre('sorts_input') ?>">
    <p class="description">
        Configure sorting options for AI search results. This feature is coming soon.
    </p>
    
    <div class="notice notice-info">
        <p><strong>Sorting functionality is not yet implemented.</strong></p>
        <p>This feature will allow you to define custom sorting rules for AI search results, such as sorting by relevance, date, title, or custom fields.</p>
    </div>
    
    <div class="sorts-placeholder">
        <p>Future sorting options will include:</p>
        <ul>
            <li>Sort by relevance score</li>
            <li>Sort by post date (newest/oldest first)</li>
            <li>Sort by post title (alphabetical)</li>
            <li>Sort by custom field values</li>
            <li>Sort by comment count</li>
            <li>Custom sorting rules</li>
        </ul>
    </div>
</div> 