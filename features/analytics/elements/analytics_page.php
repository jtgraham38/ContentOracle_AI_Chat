<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');

// Create a WordPress list table for chat logs
class COAI_ChatLogs_Table extends WP_List_Table {

    private $table_name;

    public function __construct($args = []) {
        parent::__construct($args);
        
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'coai_chat_chatlog';
        
        // Set column headers
        $this->_column_headers = [
            $this->get_columns(),
            [],
            $this->get_sortable_columns(),
            'chat_id'
        ];
    }

    public function get_columns() {
        return [
            'cb' => '<input type="checkbox" />',
            'chat_id' => __('Chat ID', 'contentoracle-ai-chat'),
            'user_info' => __('User', 'contentoracle-ai-chat'),
            'user_messages' => __('User Messages', 'contentoracle-ai-chat'),
            'ai_messages' => __('AI Messages', 'contentoracle-ai-chat'),
            'created_at' => __('Date', 'contentoracle-ai-chat'),
        ];
    }

    public function get_sortable_columns() {
        return [
            'created_at' => ['created_at', true], // true = descending by default
        ];
    }

    public function prepare_items() {
        global $wpdb;
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") != $this->table_name) {
            $this->items = [];
            $this->set_pagination_args([
                'total_items' => 0,
                'per_page' => 20,
                'total_pages' => 0
            ]);
            return;
        }

        // Set up pagination
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Get total count
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");

        // Handle sorting
        $orderby = 'created_at';
        $order = 'DESC';
        
        if (isset($_GET['orderby'])) {
            $orderby = sanitize_sql_orderby($_GET['orderby']);
            if (!$orderby) {
                $orderby = 'created_at';
            }
        }
        
        if (isset($_GET['order'])) {
            $order = strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';
        }

        // Get paginated results
        $chat_logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        // Process chat logs to extract message counts
        foreach ($chat_logs as $log) {
            $conversation = json_decode($log->conversation, true);
            $log->user_message_count = 0;
            $log->ai_message_count = 0;
            
            if (isset($conversation) && is_array($conversation)) {
                foreach ($conversation as $message) {
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

        // Set the items
        $this->items = $chat_logs;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'chat_id':
                return sprintf(
                    '<strong>%s</strong>',
                    esc_html($item->id)
                );
            case 'user_info':
                return esc_html($item->user_info ?: __('Anonymous', 'contentoracle-ai-chat'));
            case 'user_messages':
                return intval($item->user_message_count);
            case 'ai_messages':
                return intval($item->ai_message_count);
            case 'created_at':
                return esc_html(
                    date_i18n(
                        get_option('date_format') . ' ' . get_option('time_format'), 
                        strtotime($item->created_at)
                    )
                );
            default:
                return isset($item->$column_name) ? esc_html($item->$column_name) : '';
        }
    }

    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="chat_log[]" value="%s" />',
            $item->id
        );
    }

    public function get_bulk_actions() {
        return [
            'bulk-delete' => __('Delete', 'contentoracle-ai-chat')
        ];
    }

    public function column_chat_id($item) {
        $actions = array(
            'view' => sprintf(
                '<a href="%s">%s</a>',
                esc_url(
                    add_query_arg(
                        array(
                            'page' => 'contentoracle-ai-chat-analytics',
                            'chat_log_id' => urlencode($item->id)
                        ),
                        admin_url('admin.php')
                    )
                ),
                __('View', 'contentoracle-ai-chat')
            )
        );

        return sprintf(
            '%1$s %2$s',
            sprintf(
                '<strong>%s</strong>',
                esc_html($item->id)
            ),
            $this->row_actions($actions)
        );
    }
}

// Create an instance of the table
$table = new COAI_ChatLogs_Table();

?>

<div class="wrap">
    <h1><?php _e('Analytics', 'contentoracle-ai-chat'); ?></h1>
    
    <?php 
    // Prepare and display the table
    $table->prepare_items();
    
    if (empty($table->items)) {
        echo '<div class="notice notice-info">';
        echo '<p>' . __('No chat logs found.', 'contentoracle-ai-chat') . '</p>';
        echo '</div>';
    } else {
        echo '<form method="post">';
        wp_nonce_field('bulk-' . $table->_args['plural']);
        $table->display();
        echo '</form>';
    }
    ?>
</div>