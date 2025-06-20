<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$show_searchbar_popup = get_option($this->prefixed('show_searchbar_popup'));
?>
<div>
    <input 
        type="checkbox" 
        id="<?php $this->pre('show_searchbar_popup_input') ?>" 
        name="<?php $this->pre('show_searchbar_popup') ?>" 
        <?php checked($show_searchbar_popup); ?>
        title="Determines whether a tooltip drawing attention to your ai searchbar is shown to new visitors to your site."
    />
</div>