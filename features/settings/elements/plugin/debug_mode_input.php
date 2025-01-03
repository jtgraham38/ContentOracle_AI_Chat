<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$debug_mode = get_option($this->get_prefix() . 'debug_mode');
?>
<div>
    <input 
        type="checkbox" 
        id="<?php echo esc_attr( $this->get_prefix() ) ?>debug_mode_input" 
        name="<?php echo esc_attr( $this->get_prefix() ) ?>debug_mode" 
        <?php checked($debug_mode); ?>
        title="Toggles whether the chat block should show detailed error messages in the site frontend.  Useful for troubleshooting."
    />
</div>