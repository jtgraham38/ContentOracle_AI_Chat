<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//set tone options
$options = [
    'formal' => 'Formal',
    'casual' => 'Casual',
    'neutral' => 'Neutral'
];

?>

<div>

    <select
        id="<?php echo $this->get_prefix() ?>ai_tone_input" 
        name="<?php echo $this->get_prefix() ?>ai_tone" 
        title="Select the tone of the AI's responses.  Formal tones are more professional, casual tones are more friendly, and neutral tones are more... neutral."
    >
        <?php foreach ($options as $value=>$label): ?>
            <option value="<?php echo esc_attr($value); ?>" <?php echo get_option($this->get_prefix() . 'ai_tone') == $value ? 'selected' : ''; ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>