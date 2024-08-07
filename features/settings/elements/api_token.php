<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<div>
    <label for="<?php echo $this->get_prefix() ?>_api_token">API Token:</label>
    <input type="text" id="<?php echo $this->get_prefix() ?>_api_token" name="<?php echo $this->get_prefix() ?>api_token" value="<?php echo esc_attr(get_option('contentoracle_api_token')); ?>" />
</div>