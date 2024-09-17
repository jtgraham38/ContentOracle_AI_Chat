<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$display_credit_link = get_option($this->get_prefix() . 'display_credit_link');
?>
<div>
    <input 
        type="checkbox" 
        id="<?php echo $this->get_prefix() ?>display_credit_link_input" 
        name="<?php echo $this->get_prefix() ?>display_credit_link" 
        <?php checked($display_credit_link); ?>
        title="When checked, all chat blocks will display a link to the to the ContentOracle AI homepage.  This helps spread the word about the plugin and is greatly appreciated."
    />
</div>