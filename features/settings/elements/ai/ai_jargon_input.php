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
    'engineering' => 'Engineering',
    'marketing' => 'Marketing',
    'retail' => 'Retail',
    'construction' => 'Construction',
    'hospitality' => 'Hospitality',
    'aviation' => 'Aviation',
    'energy' => 'Energy',
    'agriculture' => 'Agriculture',
    'horticulture' => 'Horticulture',
    'none' => 'None',
];

?>

<div>

    <select
        id="<?php $this->pre('ai_jargon_input') ?>" 
        name="<?php $this->pre('ai_jargon') ?>" 
        title="Select the jargon of the AI's responses.  Healthcare jargon is more medical, legal jargon is more legal, finance jargon is more financial, tech jargon is more technical, education jargon is more educational, general jargon is more general, and none is no jargon."
    >
        <?php foreach ($options as $value=>$label): ?>
            <option value="<?php echo esc_attr($value); ?>" <?php echo esc_attr( get_option($this->prefixed('ai_jargon')) == $value ? 'selected' : ''); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>