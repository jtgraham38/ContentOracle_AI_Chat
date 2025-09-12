<?php

if (!defined('ABSPATH')) {
    exit;
}


// Get current page and pagination parameters
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($current_page - 1) * $per_page;

// Get chat logs from the custom table
global $wpdb;
$table_name = $wpdb->prefix . $this->prefixed('chatlog');

// Check if table exists, if not show message
if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
    echo '<div class="wrap"><h1>' . __('Analytics', 'contentoracle-ai-chat') . '</h1>';
    echo '<div class="notice notice-info"><p>' . __('Chat log table not found. No chat logs available.', 'contentoracle-ai-chat') . '</p></div></div>';
    return;
}

// Get total count
$total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");

// Get paginated results
$chat_logs = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
        $per_page,
        $offset
    )
);

// Calculate pagination
$total_pages = ceil($total_count / $per_page);

// Process chat logs to extract message counts
foreach ($chat_logs as $log) {
    $conversation = json_decode($log->conversation, true);
    $log->user_message_count = 0;
    $log->ai_message_count = 0;
    
    if (isset($conversation['conversation']) && is_array($conversation['conversation'])) {
        foreach ($conversation['conversation'] as $message) {
            if (isset($message['role'])) {
                if ($message['role'] === 'user') {
                    $log->user_message_count++;
                } elseif ($message['role'] === 'assistant') {
                    $log->ai_message_count++;
                }
            }
        }
    }
}

?>

<div class="wrap">
    <h1><?php _e('Analytics', 'contentoracle-ai-chat'); ?></h1>
    
    <?php if (empty($chat_logs)): ?>
        <div class="notice notice-info">
            <p><?php _e('No chat logs found.', 'contentoracle-ai-chat'); ?></p>
        </div>
    <?php else: ?>
        
        <!-- Top pagination -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <span class="displaying-num">
                    <?php 
                    printf(
                        _n('%s item', '%s items', $total_count, 'contentoracle-ai-chat'),
                        number_format_i18n($total_count)
                    );
                    ?>
                </span>
            </div>
            <?php if ($total_pages > 1): ?>
                <div class="tablenav-pages">
                    <?php
                    $pagination_args = array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page,
                        'type' => 'plain'
                    );
                    echo paginate_links($pagination_args);
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Chat logs table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-chat-id column-primary">
                        <?php _e('Chat ID', 'contentoracle-ai-chat'); ?>
                    </th>
                    <th scope="col" class="manage-column column-user-info">
                        <?php _e('User', 'contentoracle-ai-chat'); ?>
                    </th>
                    <th scope="col" class="manage-column column-user-messages">
                        <?php _e('User Messages', 'contentoracle-ai-chat'); ?>
                    </th>
                    <th scope="col" class="manage-column column-ai-messages">
                        <?php _e('AI Messages', 'contentoracle-ai-chat'); ?>
                    </th>
                    <th scope="col" class="manage-column column-date">
                        <?php _e('Date', 'contentoracle-ai-chat'); ?>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php _e('Actions', 'contentoracle-ai-chat'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($chat_logs as $log): ?>
                    <tr>
                        <td class="column-chat-id column-primary">
                            <strong><?php echo esc_html($log->chat_id); ?></strong>
                            <button type="button" class="toggle-row">
                                <span class="screen-reader-text"><?php _e('Show more details', 'contentoracle-ai-chat'); ?></span>
                            </button>
                        </td>
                        <td class="column-user-info">
                            <?php echo esc_html($log->user_info ?: __('Anonymous', 'contentoracle-ai-chat')); ?>
                        </td>
                        <td class="column-user-messages">
                            <?php echo intval($log->user_message_count); ?>
                        </td>
                        <td class="column-ai-messages">
                            <?php echo intval($log->ai_message_count); ?>
                        </td>
                        <td class="column-date">
                            <?php 
                            echo esc_html(
                                date_i18n(
                                    get_option('date_format') . ' ' . get_option('time_format'), 
                                    strtotime($log->created_at)
                                )
                            ); 
                            ?>
                        </td>
                        <td class="column-actions">
                            <a href="<?php 
                                //add the chat_log_id param to the url
                                echo esc_url(
                                    add_query_arg(
                                        array(
                                            'page' => 'contentoracle-ai-chat-analytics',
                                            'chat_log_id' => urlencode($log->id)
                                        ),
                                        admin_url('admin.php')
                                    )
                                ); 
                            ?>" 
                               class="button button-small">
                                <?php _e('View Chat', 'contentoracle-ai-chat'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Bottom pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php echo paginate_links($pagination_args); ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<style>
.wp-list-table .column-chat-id {
    width: 25%;
}
.wp-list-table .column-user-info {
    width: 20%;
}
.wp-list-table .column-user-messages,
.wp-list-table .column-ai-messages {
    width: 12%;
    text-align: center;
}
.wp-list-table .column-date {
    width: 18%;
}
.wp-list-table .column-actions {
    width: 13%;
}
</style>