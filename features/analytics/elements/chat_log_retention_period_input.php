<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$chat_log_retention_period = get_option($this->prefixed('chat_log_retention_period'));
?>
<div>
    <input 
        type="number" 
        id="<?php $this->pre('chat_log_retention_period_input') ?>" 
        name="<?php $this->pre('chat_log_retention_period') ?>" 
        value="<?php echo esc_attr($chat_log_retention_period); ?>"
        title="Enter the number of days to retain chat logs.  Default is 30 days."
        min="1"
        max="1825"
        step="1"
    />
</div>
