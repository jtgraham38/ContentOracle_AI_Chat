<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$cleanup_db = get_option($this->prefixed('cleanup_db'));
?>
<div>
    <input 
        type="checkbox" 
        id="<?php $this->pre('cleanup_db_input') ?>" 
        name="<?php $this->pre('cleanup_db') ?>" 
        <?php checked($cleanup_db); ?>
        title="When checked, all data in the database registered by this plugin will be deleted when the plugin is uninstalled.  This cannot be undone!"
    />
</div>