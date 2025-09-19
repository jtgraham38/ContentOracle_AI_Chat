<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//get the enable global site chat setting
$enable_global_site_chat = get_option($this->prefixed('enable_global_site_chat'));
?>


<div class="wrap p-1">

    <h1>ContentOracle AI Chat</h1>

    <form method="post" action="options.php">
        <?php
            settings_fields('coai_chat_global_site_chat_settings');
            do_settings_fields('contentoracle-ai-global-site-chat-settings', 'coai_chat_global_site_chat_settings');
        ?>
        <br>
        <?php submit_button(); ?>
    </form>

    <?php if ($enable_global_site_chat): ?>
        <h2>Global Site Chat</h2>
        <p>Global site chat is enabled.</p>
        <p>
            (add button to edit global site chat widget)
        </p>
    <?php else: ?>
        <h2>Global Site Chat</h2>
        <p>Global site chat is disabled.</p>
        <p>
            (hide button to enable global site chat)
        </p>
    <?php endif; ?>
</div>

