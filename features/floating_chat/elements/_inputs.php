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
        // Find existing global site chat post
        $global_chat_posts = get_posts(array(
            'post_type' => $this->prefixed('float_chat'),
            'post_status' => ['publish', 'draft', 'pending'],
            'numberposts' => 1,
            'fields' => 'ids',
        ));
        
        if (!empty($global_chat_posts)) {
            // Edit existing post
            $post_id = $global_chat_posts[0];
            $edit_url = get_edit_post_link($post_id);
            $button_text = 'Edit Global Site Chat';
        } else {
            // Create new post
            $post_type = $this->prefixed('float_chat');
            $edit_url = admin_url('post-new.php?post_type=' . $post_type);
            $button_text = 'Create Global Site Chat';
        }
        ?>
        
        <p>
            <a href="<?php echo esc_url($edit_url); ?>" class="button button-primary">
                <?php echo esc_html($button_text); ?>
            </a>
        </p>
    <?php else: ?>
        <h2>Global Site Chat</h2>
        <p>Global site chat is disabled.</p>
        <p>
            <em>Enable global site chat above to access the chat editor.</em>
        </p>
    <?php endif; ?>
</div>

