<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap p-1">

    <h1>ContentOracle AI Chat Filters & Sorts</h1>

    <form method="post" action="options.php">
        <?php
            settings_fields('coai_chat_filters_sorts_settings');
            do_settings_fields('contentoracle-ai-settings', 'coai_chat_filters_sorts_settings');
        ?>
        <br>
        <?php submit_button(); ?>
    </form>
</div> 