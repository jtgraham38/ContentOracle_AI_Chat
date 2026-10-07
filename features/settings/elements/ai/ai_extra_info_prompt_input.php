<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>

<div>
    <textarea 
        type="text" 
        name="<?php $this->pre('ai_extra_info_prompt') ?>" 
        id="<?php $this->pre('ai_extra_info_prompt_input') ?>"
        title="Enter any extra information about your organization that the chat assistant might need during its conversation.  Ex. 'We are a small business and can only ship to the US.'"
        maxlength="511"
        rows="4"
        cols="50"
    >
<?php echo esc_html( get_option($this->prefixed('ai_extra_info_prompt')) ) ?>
</textarea>
</div>