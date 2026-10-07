<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//set tone options
$options = [
    'formal' => 'Formal',
    'casual' => 'Casual',
    'neutral' => 'Neutral',
    'friendly' => 'Friendly',
    'professional' => 'Professional',
    'humorous' => 'Humorous',
    'empathetic' => 'Empathetic',
    'confident' => 'Confident',
    'concise' => 'Concise',
    'enthusiastic' => 'Enthusiastic',
];

?>

<div>

    <select
        id="<?php esc_attr( $this->pre('ai_tone_input') ) ?>" 
        name="<?php $this->pre('ai_tone') ?>" 
        title="Select the tone of the AI's responses.  Formal tones are more professional, casual tones are more friendly, and neutral tones are more... neutral."
    >
        <?php foreach ($options as $value=>$label): ?>
            <option value="<?php echo esc_attr($value); ?>" <?php echo esc_attr( get_option($this->prefixed('ai_tone')) == $value ? 'selected' : '' ); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>