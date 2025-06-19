<?php

if (!defined('ABSPATH')) {
    exit;
}

//import the embeedings feature object, so we can use it to enqueue posts for embedding
$embeddings_feature = $this->get_feature('embeddings');

//handle form submission
$error_msg = '';
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST'):
    //check the nonce
    if (!wp_verify_nonce($_POST['nonce'], $this->get_prefix() . 'setup_wizard_step4')) {
        $error_msg = 'Security check failed. Please try again.';
    } else {
        //enqueue the desired posts
        if ($_POST['bulk_generate_embeddings_option'] == 'not_embedded') {
            $embeddings_feature->enqueue_all_posts_that_are_not_already_embedded();
            $success_msg = 'All posts without embeddings have been queued for embedding.';
        } else if ($_POST['bulk_generate_embeddings_option'] == 'all') {
            $embeddings_feature->enqueue_all_posts();
            $success_msg = 'All posts have been queued for embedding.';
        } else {
            $error_msg = 'Invalid option selected.';
        }
    }
endif;
?>

<div>
    <h1>Step 4: Generate Embeddings</h1>
    <?php if (get_option($this->get_prefix() . 'chunking_method') != 'none'): ?>
        <p>
            Now, we need to generate text embeddings for all of your posts so that the AI agent can use semantic search to match user queries with the most relevant content.
        </p>
        <p>
            This process will run silently in the background, and could take a while to complete.
        </p>
        <p>
            You can check the status of the embedding process by going to the 
            <a href="<?php echo admin_url('admin.php?page=contentoracle-ai-chat-embeddings'); ?>">embeddings</a>
            page in the ContentOracle AI Chat admin section.
        </p>
        <p>
            Please ensure cron jobs are enabled on your site to ensure this process runs smoothly.
        </p>

        <form action="" method="POST">
            <?php wp_nonce_field($this->get_prefix() . 'setup_wizard_step4', 'nonce'); ?>
            <label for="<?php echo esc_attr($this->get_prefix()) ?>bulk_generate_embeddings_select">Add Posts to Queue</label>
            <div style="display: flex;" >
                
                <select 
                    name="bulk_generate_embeddings_option" 
                    id="<?php echo esc_attr($this->get_prefix()) ?>bulk_generate_embeddings_select" 
                    required
                    title="Select an option to generate embeddings for many posts at once.  This will only generate embeddings for posts of the types selected in the prompt settings, and only if a chunking method is set."    
                >
                    <option value="" selected>Select an option...</option>
                    <option value="all">All Posts</option>
                    <option value="not_embedded">All Posts Without Embeddings</option>
                </select>
                <input type="submit" value="Generate Embeddings" class="button-primary">
            </div>
        </form>

        <div>
            <?php if ($success_msg != '') { ?>
                <span class="success-msg"><?php echo $success_msg; ?></span>
            <?php } ?>

            <?php if ($error_msg != '') { ?>
                <span class="error-msg"><?php echo $error_msg; ?></span>
            <?php } ?>
        </div>

        <p>
            Once you have queued all of your posts for embedding, you can move on to the next step.
        </p>
    <?php else: ?>
        <p>
            Here, we would generate text embeddings for all of your posts.  
        </p>
        <p>
            But, you have chosen to use keyword search, so this step is not necessary.
        </p>
        <p>
            You can always switch to semantic search and generate embeddings later in the 
            <a href="<?php echo admin_url('admin.php?page=contentoracle-ai-chat-embeddings'); ?>">embeddings</a>
            page in the CoAI Chat menu.
        </p>
    <?php endif; ?>
</div>

