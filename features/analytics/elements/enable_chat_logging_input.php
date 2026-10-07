<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$enable_chat_logging = get_option($this->prefixed('enable_chat_logging'));
?>
<div>
    <input 
        type="checkbox" 
        id="<?php $this->pre('enable_chat_logging_input') ?>" 
        name="<?php $this->pre('enable_chat_logging') ?>" 
        <?php checked($enable_chat_logging); ?>
        title="When checked, all chat logs will be saved to the database."
    />
</div>