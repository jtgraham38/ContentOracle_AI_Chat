<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//get urls
$prompt_url = admin_url('admin.php?page=contentoracle-ai-chat-prompt');
$embeddings_url = admin_url('admin.php?page=contentoracle-ai-chat-embeddings');
$analytics_url = '#';//admin_url('admin.php?page=contentoracle-ai-chat-analytics');
$filters_url = admin_url('admin.php?page=contentoracle-ai-chat-filters-sorts');
$settings_url = admin_url('admin.php?page=contentoracle-ai-chat-settings');
$learn_more_url = "https://contentoracleai.com/contentoracle-ai-chat";
$support_url = 'https://contentoracleai.com/contact';

?>

<h1>ContentOracle AI Chat</h1>
<p style="margin-top: 2rem">ContentOracle AI chat seamlessly blends the power of generative AI with your website’s unique content.</p>

<div class="grid-container" style="margin-top: 3rem">
    <div class="grid-item postbox">
        <h2>Prompt</h2>
        <i>Optimize the flow of you ai's conversations.</i>
        <div class="button-container">
            <a href="<?php echo esc_url($prompt_url) ?>" class="button">Go to Prompt</a>
        </div>
    </div>
    <div class="grid-item postbox">
        <h2>Embeddings</h2>
        <i>Manage embeddings to let your ai better index your content.</i>
        <div class="button-container">
            <a href="<?php echo esc_url($embeddings_url) ?>" class="button">Go to Embeddings</a>
        </div>
    </div>
    <div class="grid-item postbox">
        <h2>Filters & Sorts</h2>
        <i>Filter and sort the content the ai is prompted with.</i>
        <div class="button-container">
            <a href="<?php echo esc_url($filters_url) ?>" class="button" >Go to Filters & Sorts</a>
        </div>
    </div>
    <div class="grid-item postbox">
        <h2>Settings</h2>
        <i>Manage plugin and connection settings.</i>
        <div class="button-container">
            <a href="<?php echo esc_url($settings_url) ?>" class="button">Go to Settings</a>
        </div>
    </div>
    <div class="grid-item postbox">
        <h2>Learn more</h2>
        <i>Read more about where ContentOracle AI Chat is heading.</i>
        <div class="button-container">
            <a href="<?php echo esc_url($learn_more_url) ?>" target="_blank" class="button">Read more</a>
        </div>
    </div>
    <div class="grid-item postbox">
        <h2>Support</h2>
        <i>Get help with ContentOracle AI Chat plugin.</i>
        <div class="button-container">
            <a href="<?php echo esc_url($support_url) ?>" target="_blank" class="button">Get support</a>
        </div>
    </div>
</div>
