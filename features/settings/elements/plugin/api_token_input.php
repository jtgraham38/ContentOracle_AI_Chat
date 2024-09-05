<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>
<div>
    <input 
        type="text" 
        id="<?php echo $this->get_prefix() ?>api_token_input" 
        name="<?php echo $this->get_prefix() ?>api_token" 
        value="<?php echo esc_attr(get_option($this->get_prefix() . 'api_token')); ?>"
        title="Enter your ContentOracle API token.  This token identifies requests from your website, ensuring only you can make requests for your users."
        required    
    />
</div>