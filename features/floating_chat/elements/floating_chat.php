<?php

if (!defined('ABSPATH')) {
    exit;
}

// Get the widget area ID
$widget_area_id = $this->prefixed('floating_chat_widget_area');

// Get the customizable header text
$header_text = get_option($this->prefixed('chat_header_text'), 'AI Chat');

// Get the customizable button icon
$button_icon = get_option($this->prefixed('floating_button_icon'), 'chat-bubble');
$icon_emoji = $this->get_icon_emoji($button_icon);

// Check if the widget area has widgets
if (is_active_sidebar($widget_area_id)) {
    ?>
    <!-- Floating Action Button -->
    <button id="coai_floating_chat_toggle" class="floating-chat-toggle coai-floating-chat-button" onclick="toggleFloatingChat()">
        <span class="chat-icon"><?php echo esc_html($icon_emoji); ?></span>
    </button>
    
    <!-- Floating Chat Container (initially hidden) -->
    <div id="coai_chat_floating_chat_container" class="floating-chat-container coai-floating-chat-container" style="display: none;">
        <div class="floating-chat-header coai-floating-chat-header">
            <h3 class="coai-chat-title"><?php echo esc_html($header_text); ?></h3>
            <button class="close-chat-btn coai-floating-chat-close-button" onclick="toggleFloatingChat()">×</button>
        </div>
        <div class="floating-chat-content">
            <?php dynamic_sidebar($widget_area_id); ?>
        </div>
    </div>

    <script>
    function toggleFloatingChat() {
        const container = document.getElementById('coai_chat_floating_chat_container');
        const toggle = document.getElementById('coai_floating_chat_toggle');
        
        if (container.style.display === 'none' || container.style.display === '') {
            container.style.display = 'block';
            toggle.style.display = 'none';
            // Save state to localStorage
            localStorage.setItem('coai_floating_chat_open', 'true');
        } else {
            container.style.display = 'none';
            toggle.style.display = 'block';
            // Save state to localStorage
            localStorage.setItem('coai_floating_chat_open', 'false');
        }
    }

    // Restore chat state on page load
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('coai_chat_floating_chat_container');
        const toggle = document.getElementById('coai_floating_chat_toggle');
        
        if (container && toggle) {
            const isOpen = localStorage.getItem('coai_floating_chat_open');
            
            if (isOpen === 'true') {
                container.style.display = 'block';
                toggle.style.display = 'none';
            } else {
                container.style.display = 'none';
                toggle.style.display = 'block';
            }
        }
    });
    </script>
    <?php
}