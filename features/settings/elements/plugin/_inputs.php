<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>


<div class="wrap postbox p-1">

    <h1>ContentOracle AI Search</h1>

    <form method="post" action="options.php">
        <?php
            settings_fields('contentoracle_plugin_settings');
            do_settings_fields('contentoracle-ai-settings', 'contentoracle_plugin_settings');
        ?>
        <br>
        <strong>            TODO: add setting for showing a popup to draw attention to the ai searchbar to new users here
        </strong>
        <?php submit_button(); ?>
    </form>
</div>

