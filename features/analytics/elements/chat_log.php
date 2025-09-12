<?php

if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;

//define the table name
$table_name = $wpdb->prefix . $this->prefixed('chatlog');

//get the id of the chat log
$chat_log_id = isset($_GET['chat_log_id']) ? $_GET['chat_log_id'] : null;

//retrieve the chat log data from the custom table
$chat_log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $chat_log_id));

//get the conversation from the chat log
$conversation = json_decode($chat_log->conversation, true);
$created_at = $chat_log->created_at;
$updated_at = $chat_log->updated_at;

//EDIT NOTHING ABOVE THIS LINE
?>

<script>
    const first_header = document.querySelector('h1');
    if (first_header){
        first_header.style.display = 'none';
    }
</script>

<div class="chat-log-content-display">
    <h2><?php _e('Chat Log Content', 'contentoracle-ai-chat'); ?></h2>
    
    <?php if (isset($chat_log_id) && isset($created_at)): ?>
        <div class="chat-log-meta">
            <p><strong><?php _e('Chat ID:', 'contentoracle-ai-chat'); ?></strong> <?php echo esc_html($chat_log_id); ?></p>
            <p><strong><?php _e('Created:', 'contentoracle-ai-chat'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($created_at))); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($conversation && is_array($conversation)): ?>
        <div class="<?php echo $this->prefixed('chat-conversation'); ?>">
            <?php foreach ($conversation as $index => $message): 
                //get the message from the array
                $role = $message['role'] ?? 'unknown';

                //switch based on the role
                switch ($role){
                    case 'user':
                        $content = $message['message'] ?? '';
                        $content_supplied = $message['content_supplied'] ?? [];
                        $role_class = $this->prefixed('user-message');
                        $timestamp = $message['timestamp'] ?? '';
                        break;
                    case 'assistant':
                        //strip all html tags from the message other than coai-artifact and br tags
                        $sanitized_content = wp_kses($message['message'], array(
                            'coai-artifact' => array(
                                'artifact_type' => array(),
                                'content_id' => array(),
                                'button_text' => array(),
                            ),
                            'br' => array(),
                        ));

                        $content = $sanitized_content ?? '';
                        $content_supplied = null;
                        $role_class = $this->prefixed('assistant-message');
                        $timestamp = $message['timestamp'] ?? '';
                        break;
                    default:
                        $content = $message['message'] ?? '';
                        $content_supplied = null;
                        $role_class = $this->prefixed('unknown-message');
                        $timestamp = $message['timestamp'] ?? '';
                        break;
                }
            ?>
                <div class="<?php echo $this->prefixed('chat-message'); ?> <?php echo esc_attr($role_class); ?>">
                    <div class="<?php echo $this->prefixed('message-header'); ?>">
                        <strong><?php echo esc_html(ucfirst($role)); ?></strong>
                        <span class="<?php echo $this->prefixed('message-number'); ?>">Message <?php echo ($index + 1); ?></span>
                        <?php if ($timestamp): ?>
                            <span class="<?php echo $this->prefixed('message-timestamp'); ?>"><?php echo esc_html(date('M j, Y g:i:s A', $timestamp)); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="<?php echo $this->prefixed('message-content'); ?>">
                        <?php 

                        //replace multiple newlines with a single newline
                        $content = preg_replace('/\n+/', "\n", $content);

                        //strip whitespace off front and end
                        $content = trim($content);

                        echo esc_html($content); 
                        ?>
                    </div>
                    <?php if ($content_supplied && is_array($content_supplied)): ?>
                        <div class="<?php echo $this->prefixed('content-supplied-label'); ?>">Content Sent to Agent:</div>
                        <ol class="<?php echo $this->prefixed('content-supplied'); ?>">
                            <?php foreach ($content_supplied as $content_item): ?>
                                <li>
                                    <span class="<?php echo $this->prefixed('content-item-title'); ?>"><?php echo esc_html($content_item['title']); ?></span>
                                    <span>
                                        <a 
                                            href="<?php echo esc_url($content_item['url']); ?>" 
                                            target="_blank"
                                            class="<?php echo $this->prefixed('content-item-link'); ?>"
                                        >
                                            →
                                        </a>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($post && $post->post_content): ?>
        <p><em>Invalid chat data</em></p>
    <?php else: ?>
        <p><em>No logs</em></p>
    <?php endif; ?>
</div>