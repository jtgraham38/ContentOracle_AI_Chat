<?php

if (!defined('ABSPATH')) {
    exit;
}

// Get the widget area ID
$widget_area_id = $this->prefixed('floating_chat_widget_area');

// Check if the widget area has widgets
if (is_active_sidebar($widget_area_id)) {
    ?>
    <div id="coai_chat_floating_chat_container" class="floating-chat-container">
        <?php dynamic_sidebar($widget_area_id); ?>
    </div>
    <?php
}