<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap p-1">

    <h1>Embeddings Settings</h1>

    <form method="post" action="options.php">

        <?php
            settings_fields('coai_chat_embeddings_settings');
            do_settings_fields('contentoracle-ai-settings', 'coai_chat_embeddings_settings');
        ?>
        <?php submit_button(); ?>
    </form>

    <hr>

    <?php require_once plugin_dir_path(__FILE__) . 'embeddings_explorer.php'; ?>
</div>
