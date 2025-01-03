<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>
<div>
    <input 
        type="text" 
        id="<?php echo esc_attr( $this->get_prefix() ) ?>organization_name_input" 
        name="<?php echo esc_attr( $this->get_prefix() ) ?>organization_name" 
        value="<?php echo esc_attr(get_option($this->get_prefix() . 'organization_name')); ?>"
        title="Enter the name the chatbot should use to refer to your organization.  Defaults to the site title."
        required
        maxlength="64"
    />
</div>