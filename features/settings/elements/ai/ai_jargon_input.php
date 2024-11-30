<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//set jargon options
$options = [
    'healthcare' => 'Healthcare',
    'legal' => 'Legal',
    'finance' => 'Finance',
    'tech' => 'Tech',
    'education' => 'Education',
    'general' => 'General',
    'none' => 'None'
];

?>

<div>

    <select
        id="<?php echo esc_attr( $this->get_prefix() ) ?>ai_jargon_input" 
        name="<?php echo esc_attr( $this->get_prefix() ) ?>ai_jargon" 
        title="Select the jargon of the AI's responses.  Healthcare jargon is more medical, legal jargon is more legal, finance jargon is more financial, tech jargon is more technical, education jargon is more educational, general jargon is more general, and none is no jargon."
    >
        <?php foreach ($options as $value=>$label): ?>
            <option value="<?php echo esc_attr($value); ?>" <?php echo get_option($this->get_prefix() . 'ai_jargon') == $value ? 'selected' : ''; ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>