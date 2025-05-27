<?php
if (!defined('ABSPATH')) {
    exit;
}


?>
<div class="wrap contentoracle-setup-wizard">
    <h1><?php _e('ContentOracle AI Chat Setup Wizard', 'contentoracle-ai-chat'); ?></h1>
    
    <div class="wizard-progress">
        <?php for ($i = 1; $i <= $total_steps; $i++) : ?>
            <div class="progress-step <?php echo $i <= $current_step ? 'active' : ''; ?>">
                <span class="step-number"><?php echo $i; ?></span>
                <span class="step-label">
                    <?php
                    switch ($i) {
                        case 1:
                            _e('API Account', 'contentoracle-ai-chat');
                            break;
                        case 2:
                            _e('API Token', 'contentoracle-ai-chat');
                            break;
                        case 3:
                            _e('Prompts', 'contentoracle-ai-chat');
                            break;
                        case 4:
                            _e('Embeddings', 'contentoracle-ai-chat');
                            break;
                        case 5:
                            _e('Usage', 'contentoracle-ai-chat');
                            break;
                    }
                    ?>
                </span>
            </div>
        <?php endfor; ?>
    </div>

    <div class="wizard-content">
        <form method="post" action="">
            <?php wp_nonce_field('contentoracle_wizard_nonce', 'contentoracle_wizard_nonce'); ?>
            <input type="hidden" name="wizard_step" value="<?php echo esc_attr($current_step); ?>">
            
            <?php
            $template_path = plugin_dir_path(__FILE__) . 'templates/step' . $current_step . '.php';
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                echo '<p>' . __('Step template not found.', 'contentoracle-ai-chat') . '</p>';
            }
            ?>
        </form>
    </div>
</div>