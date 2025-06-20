<?php

if (!defined('ABSPATH')) {
    exit;
}

// Check if search results page exists
$search_page_id = get_option($this->prefixed('search_results_page_id'));
$search_page_url = $search_page_id ? get_edit_post_link($search_page_id) : admin_url('post-new.php?post_type=page');

?>

<div>
    <h1>Step 5: Add Chat to Your Site</h1>

    <p>
        Congratulations! The ai chat agent is now connected to your site, and it can start chatting with your visitors.
    </p>

    <p>All you need to do now is use the AI Chat and AI Search blocks included with the plugin to add ai chat features to your website.</p>
    
    <div class="postbox" style="padding: 1rem;">
        <h2>Using the Block Editor</h2>
        <p>
            You can add the AI Chat and AI Search blocks to any page, post, or template on your site using the WordPress block editor:
        </p>
        <ol>
            <li>Edit any page or post using the block editor.</li>
            <li>Click the "+" button to add a new block.</li>
            <li>Search for "AI Chat" or "AI Search".</li>
            <li>Select the block you want to add.</li>
            <li>Configure the block settings and styles as needed using the block settings panel.</li>
        </ol>
        <?php 
            $search_results_page_id = get_option($this->prefixed('ai_results_page'), null);
            $search_page = $search_results_page_id ? get_post($search_results_page_id) : null;
            if ($search_page && $search_page->post_type == 'page') {
        ?>
                <p>
                    Start by customizing the chat block on the default AI Chat page.
                    <div>
                        <a href="<?php echo esc_url(get_edit_post_link($search_page->ID)); ?>" class="button button-primary">Edit AI Chat Page</a>
                    </div>
                </p>
            <?php } else { ?>
                <p>
                    Create a new page to add a chat block to!
                    <div>
                        <a href="<?php echo esc_url(admin_url('post-new.php?post_type=page')); ?>" class="button button-primary">Create New Page</a>
                    </div>
                </p>
            <?php } ?>
    </div>

    <p>ContentOracle AI Chat is fully compatible with other page builders too, through the use of shortcodes.</p>

    <div class="postbox" style="padding: 1rem; margin-top: 1rem;">
        <h2>Using Shortcodes</h2>
        <p>
            You can use shortcodes to place ContentOracle AI Chat UI elements anywhere on your site, including in other page builders.
        </p>
        <p>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=coai_chat_shortcode')); ?>" class="button button-primary">Manage Shortcodes</a>
        </p>
        <p>
            <small>
                From there, you can create, edit, and copy shortcodes to add to your pages and posts.
                Each shortcode can be populated with a customizable ContentOracle AI Chat UI element, with its own unique settings and styles.
            </small>
        </p>
    </div>


    <h2>Next Steps</h2>
    <p>
        Congratulations! Your AI Chat assistant is now set up and ready to use. Here are some recommended next steps:
    </p>
    <ul>
        <li>Add the chat interface to your most important pages.</li>
        <li>Once embeddings have been generated, ask your agent an organization specific question, and see how it uses your site content to respond.</li>
        <li>Fine-tune your prompt settings based on the chat interactions.</li>
        <li>Consider adding the search block in your header or footer to help visitors find your ai chat agent more easily.</li>
    </ul>
    <p>
        Remember, you can always adjust your settings later through the ContentOracle AI Chat menu in your WordPress admin panel.
    </p>
</div>