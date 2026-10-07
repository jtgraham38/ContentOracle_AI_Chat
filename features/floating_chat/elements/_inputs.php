<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//get the enable global site chat setting
$enable_floating_site_chat = get_option($this->prefixed('enable_floating_site_chat'));
?>


<div class="wrap p-1">

    <h1>ContentOracle AI Chat</h1>

    <form method="post" action="options.php">
        <?php
            settings_fields('coai_chat_floating_site_chat_settings');
            do_settings_fields('contentoracle-ai-global-site-chat-settings', 'coai_chat_floating_site_chat_settings');
        ?>
        <br>
        <?php submit_button(); ?>
    </form>

    <?php if ($enable_floating_site_chat): ?>
        <h2>Global Site Chat</h2>
        <p>Global site chat is enabled.</p>
        
        <?php
        // Check if widget area has widgets
        $widget_area_id = 'coai_chat_floating_chat_widget_area';
        $has_widgets = is_active_sidebar($widget_area_id);
        
        if ($has_widgets) {
            $button_text = 'Edit Floating Chat Widget Content';
        } else {
            $button_text = 'Add Floating Chat Widget Content';
        }
        
        // Link to widgets page
        $widgets_url = admin_url('widgets.php');
        ?>
        
        <p>
            <a href="<?php echo esc_url($widgets_url); ?>" class="button button-primary">
                <?php echo esc_html($button_text); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('customize.php?autofocus[section]=coai_chat_floating_chat_customizer_section')); ?>" class="button">
                Edit Floating Chat Widget UI
            </a>
        </p>
        
        <p>
            <em>Use the "Floating Site Chat" widget area to add your chat widgets. You can edit widgets in the traditional widgets page or use the customizer for live preview.</em>
        </p>
    <?php else: ?>
        <h2>Global Site Chat</h2>
        <p>Global site chat is disabled.</p>
        <p>
            <em>Enable global site chat above to access the widget area.</em>
        </p>
    <?php endif; ?>
</div>

