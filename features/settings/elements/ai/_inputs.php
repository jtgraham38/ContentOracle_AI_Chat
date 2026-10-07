<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>


<div class="wrap p-1">

    <h1>Prompt Settings</h1>

    <form method="post" action="options.php">
            <?php
                settings_fields('coai_chat_ai_settings');
                do_settings_fields('contentoracle-ai-settings', 'coai_chat_ai_settings');
            ?>
            <?php submit_button(); ?>
    </form>
</div>

