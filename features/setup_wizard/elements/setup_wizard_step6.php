<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div>
    <h1>All Done!</h1>
    <p>
        You have successfully completed the setup wizard.
    </p>
    <p>
        You can now start using the ContentOracle AI Chat plugin.
    </p>

    <a href="<?php echo esc_url(admin_url('admin.php?page=contentoracle-ai-chat')); ?>" class="button button-primary">
        Go to the ContentOracle AI Chat plugin
    </a>
</div>