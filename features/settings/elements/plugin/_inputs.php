<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>


<div class="wrap">

    <h1>ContentOracle AI Search</h1>

    <form method="post" action="options.php">
        
        <fieldset style="border: 1px solid gray; padding: 1rem;">
            <legend style="margin-left: 1rem;">ContentOracle AI Settings</legend>
            <?php
                settings_fields('contentoracle_plugin_settings');
                do_settings_fields('contentoracle-ai-settings', 'contentoracle_plugin_settings');
            ?>
            <br>
            <strong>            TODO: add setting for showing a popup to draw attention to the ai searchbar to new users here
            </strong>
            <?php submit_button(); ?>
        </fieldset>
    </form>
</div>

