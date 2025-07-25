<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

?>
<div>
    <textarea 
        type="text" 
        name="<?php $this->pre('ai_goal_prompt') ?>" 
        value=""
        id="<?php $this->pre('ai_goal_prompt_input') ?>"
        title="Enter a succinct description of what you would like the ai to try to do in its interactions with users.  Ex. 'If it makes sense in the context of the conversation, share a link to a product you were given and encourage them to add it to their cart.'"
        maxlength="511"
        rows="4"
        cols="50"
    >
<?php echo esc_html( get_option($this->prefixed('ai_goal_prompt')) ) ?>
</textarea>
</div>