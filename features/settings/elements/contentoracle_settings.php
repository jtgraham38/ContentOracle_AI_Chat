<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap">
    <h1>ContentOracle AI Search</h1>
    <p>ContentOracle AI Search seamlessly blends the power of generative AI with your website’s search feature.</p>
    <p>For more information, visit the <a href="https://jacob-t-graham.com/contentoracle-ai-search-a-website-add-on-that-uses-ai-to-boost-the-power-of-your-web-content/">ContentOracle AI Search plugin page</a>.</p>
    <p>For support, visit the <a href="https://jacob-t-graham.com/contact/">Jacob Graham contact page</a>.</p>

    <form method="post" action="options.php">
        <?php
        // Output the settings fields.
        settings_fields('contentoracle_settings');
        do_settings_sections('contentoracle-ai');
        submit_button();
        ?>
    </form>
</div>

