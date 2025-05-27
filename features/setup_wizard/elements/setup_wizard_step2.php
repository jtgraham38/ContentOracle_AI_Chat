<?php

if (!defined('ABSPATH')) {
    exit;
}


//handle post request from the step2 form
$error_msg = '';
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST'):
    //check the nonce
    if (!wp_verify_nonce($_POST['nonce'], $this->get_prefix() . 'setup_wizard_step2')) {
        $error_msg = 'Invalid nonce';
    }
    
    //get the token from the input field
    $token = $_POST['token'];
    
    //save the token to the database
    update_option($this->get_prefix() . 'api_token', $token);
    
    if ($error_msg == '') {
        $success_msg = 'Token saved successfully';
    }
endif;


//get current token value
$current_token = get_option($this->get_prefix() . 'api_token');
?>

<div>
    <h1>Step 2</h1>

    <p>
        After you have logged in to your ContentOracle AI account, you can generate an API token.  
    </p>

    <p>This will be used to link your website to your ContentOracle AI account.</p>

    <p>
        From your account dashboard, click the button that says "Add New Token".  Enter a name for your token, and then click the button that says "Create"
    </p>

    <p>Then, the token will appear.  Copy the token, and paste it into the input field below.</p>

    <form action="" method="POST" id="<?php echo $this->get_prefix(); ?>step2-form">
        <input type="text" id="coai_api_token" name="token" placeholder="API Token" required value="<?php echo $current_token; ?>">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce($this->get_prefix() . 'setup_wizard_step2'); ?>">
        <button type="submit" class="button button-primary">Save Token</button>
    </form>

    <?php if ($success_msg != '') { ?>
        <span class="success-msg"><?php echo $success_msg; ?></span>
    <?php } ?>

    <?php if ($error_msg != '') { ?>
        <span class="error-msg"><?php echo $error_msg; ?></span>
    <?php } ?>

    <p>Excellent!  Now, you have linked your website to your ContentOracle AI account.</p>

    <p>
        You can now proceed to the next step.
    </p>
</div>