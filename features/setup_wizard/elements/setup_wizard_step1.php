<?php

if (!defined('ABSPATH')) {
    exit;
}

//get the base url from the api url
$api_url = get_option($this->prefixed('api_url'));

//get the base url from the api url
$protocol = parse_url($api_url, PHP_URL_SCHEME);
$base_url = parse_url($api_url, PHP_URL_HOST);
$port = parse_url($api_url, PHP_URL_PORT);

//combine protocol and base url
$base_url = $protocol . '://' . $base_url;

//if there is a port, add it to the base url
if ($port) {
    $base_url .= ':' . $port;
}

//create the login url and the register url
$login_url = $base_url . '/login';
$register_url = $base_url . '/register';


?>

<div>

    <h1>Step 1: Create an Account</h1>
    
    <p>Welcome to ContentOracle AI Chat!  We are excited to have you on board!</p>
    
    <p>Throughout the next few steps, we will get you up and running with powerful retrieval-augmented ai chat on your website.</p>
    
    <p>
        Begin by creating a ContentOracle AI account.  This is the first step in connecting to our powerful ai services.
    </p>

    <p>
        <a href="<?php echo esc_url($register_url); ?>" class="button button-primary" target="_blank">Create Account</a>
    </p>

    <p>If you already have an account, you can skip this step, and log in to your account.</p>

    <p>
        <a href="<?php echo esc_url($login_url); ?>" class="button button-secondary" target="_blank">Log In</a>
    </p>

    <p>
        Once you have logged in to your account, proceed to the next step.
    </p>

</div>


