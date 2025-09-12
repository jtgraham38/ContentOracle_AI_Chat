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

// Get current page
$current_page = $_GET['page'] ?? '';

// Define available tabs
$tabs = array(
    'contentoracle-ai-chat-prompt' => array(
        'label' => __('Prompt', 'contentoracle-ai-chat'),
        'icon' => 'dashicons-format-chat',
        'url' => admin_url('admin.php?page=contentoracle-ai-chat-prompt')
    ),
    'contentoracle-ai-chat-filters-sorts' => array(
        'label' => __('Filters & Sorts', 'contentoracle-ai-chat'),
        'icon' => 'dashicons-filter',
        'url' => admin_url('admin.php?page=contentoracle-ai-chat-filters-sorts')
    ),
    'contentoracle-ai-chat-embeddings' => array(
        'label' => __('Embeddings', 'contentoracle-ai-chat'),
        'icon' => 'dashicons-format-status',
        'url' => admin_url('admin.php?page=contentoracle-ai-chat-embeddings')
    ),
    'contentoracle-ai-chat-analytics' => array(
        'label' => __('Analytics', 'contentoracle-ai-chat'),
        'icon' => 'dashicons-admin-tools',
        'url' => admin_url('admin.php?page=contentoracle-ai-chat-analytics')
    ),
    'coai_chat_shortcode' => array(
        'label' => __('Shortcodes', 'contentoracle-ai-chat'),
        'icon' => 'dashicons-admin-appearance',
        'url' => admin_url('edit.php?post_type=coai_chat_shortcode')
    ),
    'contentoracle-ai-chat-settings' => array(
        'label' => __('Advanced', 'contentoracle-ai-chat'),
        'icon' => 'dashicons-admin-settings',
        'url' => admin_url('admin.php?page=contentoracle-ai-chat-settings')
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
            <?php 
            // Check if this is the current tab
            $is_current = false;
            if ($tab_id === 'coai_chat_shortcode') {
                // Special case for shortcodes page
                $is_current = isset($_GET['post_type']) && $_GET['post_type'] === 'coai_chat_shortcode';
            } else {
                $is_current = $current_page === $tab_id;
            }
            ?>
            <a href="<?php echo esc_attr($tab['url']); ?>" 
               class="nav-tab <?php echo $is_current ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons <?php echo esc_attr($tab['icon']); ?>" style="margin-right: 5px;"></span>
                <?php echo esc_html($tab['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div>

<div>
    <?php echo $content; //already escaped ?>
</div>