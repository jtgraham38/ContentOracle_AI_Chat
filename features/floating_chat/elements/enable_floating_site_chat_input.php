<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$enable_floating_site_chat = get_option($this->prefixed('enable_floating_site_chat'));
?>
<div>
    <input 
        type="checkbox" 
        id="<?php $this->pre('enable_floating_site_chat_input') ?>" 
        name="<?php $this->pre('enable_floating_site_chat') ?>" 
        <?php checked($enable_floating_site_chat); ?> 
        title="Enable global site chat" 
    />
</div>