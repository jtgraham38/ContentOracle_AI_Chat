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
</div>

<div>
    <?php echo $content; //already escaped ?>
</div>