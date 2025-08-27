<?php

if (!defined('ABSPATH')) {
    exit;
}

// Get the current post
global $post;

if ($post && $post->post_content) {
    $chat_data = json_decode($post->post_content, true);
    // echo "<pre>";
    // print_r($chat_data);
    // echo "</pre>";
    // die();
    
    if ($chat_data && isset($chat_data['conversation']) && is_array($chat_data['conversation'])) {
        $has_valid_data = true;
        $messages = $chat_data['conversation'];
    } else {
        $has_valid_data = false;
    }
} else {
    $has_valid_data = false;
}
?>

<div class="chat-log-content-display">
    <h2>Chat Log Content</h2>
    
    <?php if ($has_valid_data): ?>
        <div class="<?php echo $this->prefixed('chat-conversation'); ?>">
            <?php foreach ($messages as $index => $message): 
                //get the message from the array
                $role = $message['role'] ?? 'unknown';

                //switch based on the role
                switch ($role){
                    case 'user':
                        $content = $message['message'] ?? '';
                        $content_supplied = $message['content_supplied'] ?? [];
                        $role_class = $this->prefixed('user-message');
                        break;
                    case 'assistant':
                        $content = $message['message'] ?? '';
                        $content_supplied = null;
                        $role_class = $this->prefixed('assistant-message');
                }
            ?>
                <div class="<?php echo $this->prefixed('chat-message'); ?> <?php echo esc_attr($role_class); ?>">
                    <div class="<?php echo $this->prefixed('message-header'); ?>">
                        <strong><?php echo esc_html(ucfirst($role)); ?></strong>
                        <span class="<?php echo $this->prefixed('message-number'); ?>">Message <?php echo ($index + 1); ?></span>
                    </div>
                    <div class="<?php echo $this->prefixed('message-content'); ?>"><?php echo esc_html($content); ?></div>
                    <?php if ($content_supplied): ?>
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