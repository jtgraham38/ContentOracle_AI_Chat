<?php

if (!defined('ABSPATH')) {
    exit;
}

//handle a step being completed
$step_completed = isset($_GET['complete']) ? intval($_GET['complete']) : 0;
if ($step_completed) {
    update_option($this->get_prefix() . 'setup_wizard_latest_step_completed', $step_completed);
}

echo "latest step completed";
echo "<br>";
echo $this->get_prefix() . 'setup_wizard_latest_step_completed';
echo "<br>";
echo get_option($this->get_prefix() . 'setup_wizard_latest_step_completed');

//get the current step from the url
//or, if it is not set there, get it from the latest step completed
$last_completed_step = get_option($this->get_prefix() . 'setup_wizard_latest_step_completed');
$current_step = isset($_GET['step']) ? intval($_GET['step']) : $last_completed_step + 1;

//urls for next and prev buttons
//create the next step url
$next_step_url = '/wp-admin/admin.php?page=contentoracle-ai-chat-setup-wizard&complete=' . $current_step . '&step=' . ($current_step + 1);
$prev_step_url = '/wp-admin/admin.php?page=contentoracle-ai-chat-setup-wizard&complete=' . $current_step - 1 . '&step=' . ($current_step - 1);

?>
<div class="wrap coai_chat-setup-wizard">

    <div class="coai_chat-setup-wizard-slide-container">
        
        <div>    
            <?php
            switch($current_step){
                case 1:
                    require_once plugin_dir_path(__FILE__) . 'setup_wizard_step1.php';
                    break;
                case 2:
                    require_once plugin_dir_path(__FILE__) . 'setup_wizard_step2.php';
                    break;
                case 3:
                    require_once plugin_dir_path(__FILE__) . 'setup_wizard_step3.php';
                    break;
                case 4:
                    require_once plugin_dir_path(__FILE__) . 'setup_wizard_step4.php';
                    break;
                case 5:
                    require_once plugin_dir_path(__FILE__) . 'setup_wizard_step5.php';
                    break;
                default:
                    require_once plugin_dir_path(__FILE__) . 'setup_wizard_step1.php';
                    break;
            }
            ?>
        </div>

        <div class="coai_chat-setup-wizard-slide-container-buttons">
            <?php if ($current_step > 1) { ?>
                <a href="<?php echo esc_url($prev_step_url); ?>" class="button button-secondary">Previous Step</a>
            <?php } ?>
            <a href="<?php echo esc_url($next_step_url); ?>" class="button button-primary">Next Step</a>
        </div>
    </div>


</div>
