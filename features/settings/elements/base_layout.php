<?php
/**
 * Base Layout for ContentOracle AI Chat Settings Page
 * 
 * @package ContentOracle_AI_Chat
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Define available tabs
$tabs = array(
    '?page=contentoracle-ai-chat-prompt' => array(
        'label' => __('General', 'contentoracle-ai-chat'),
        'icon' => 'dashicons-format-chat'
    ),
    '?page=contentoracle-ai-chat-embeddings' => array(
        'label' => __('Embeddings', 'contentoracle-ai-chat'),
        'icon' => 'dashicons-format-status'
    ),
    '?page=contentoracle-ai-chat-analytics' => array(
        'label' => __('Analytics', 'contentoracle-ai-chat'),
        'icon' => 'dashicons-admin-tools'
    ),
    admin_url('edit.php?post_type=coai_chat_shortcode') => array(
        'label' => __('Shortcodes', 'contentoracle-ai-chat'),
        'icon' => 'dashicons-admin-appearance'
    ),
    '?page=contentoracle-ai-chat-settings' => array(
        'label' => __('Advanced', 'contentoracle-ai-chat'),
        'icon' => 'dashicons-admin-settings'
    )
);

// Get saved options
$enable_chat = get_option('contentoracle_enable_chat', 1);
$chat_title = get_option('contentoracle_chat_title', __('AI Assistant', 'contentoracle-ai-chat'));
$welcome_message = get_option('contentoracle_welcome_message', __('Hello! How can I help you today?', 'contentoracle-ai-chat'));
$max_messages = get_option('contentoracle_max_messages', 50);
$auto_scroll = get_option('contentoracle_auto_scroll', 1);
$show_timestamp = get_option('contentoracle_show_timestamp', 1);
$api_key = get_option('contentoracle_api_key', '');
$model = get_option('contentoracle_model', 'gpt-3.5-turbo');
$temperature = get_option('contentoracle_temperature', 0.7);
$primary_color = get_option('contentoracle_primary_color', '#0073aa');
$chat_position = get_option('contentoracle_chat_position', 'bottom-right');
$chat_width = get_option('contentoracle_chat_width', 350);
$debug_mode = get_option('contentoracle_debug_mode', 0);
$log_retention = get_option('contentoracle_log_retention', 30);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-comments" style="font-size: 30px; width: 30px; height: 30px; margin-right: 10px;"></span>
        <?php _e('ContentOracle AI Chat Settings', 'contentoracle-ai-chat'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($tabs as $tab_id => $tab) : ?>
            <a href="<?php echo esc_attr($tab_id); ?>" 
               class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons <?php echo esc_attr($tab['icon']); ?>" style="margin-right: 5px;"></span>
                <?php echo esc_html($tab['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <!-- Page Content here -->
    <div>
        Tab content here
    </div>
    
</div>