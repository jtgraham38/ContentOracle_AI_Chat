<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>
<div>
    <input 
        type="text" 
        id="<?php $this->pre('organization_name_input') ?>" 
        name="<?php $this->pre('organization_name') ?>" 
        value="<?php echo esc_attr(get_option($this->prefixed('organization_name'))); ?>"
        title="Enter the name the chatbot should use to refer to your organization.  Defaults to the site title."
        required
        maxlength="64"
    />
</div>