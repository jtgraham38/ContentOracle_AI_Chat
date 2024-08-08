<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//set the ai goals options
$options = [
    'promote_products' => 'Promote Products',
    'keep_user_on_site' => 'Keep User on Site',
    'keep_user_reading' => 'Keep User Reading',
    'join_mailing_list' => 'Join Mailing List',
    'encourage_signup' => 'Encourage Signup',
    'encourage_social_share' => 'Encourage Social Share',
    'none' => 'None'
];

//load the current value of the post types setting
$ai_goals_setting = get_option($this->get_prefix() . 'ai_goals');
?>

<div>
    <select
        id="<?php echo esc_attr($this->get_prefix()) ?>ai_goals"
        name="<?php echo esc_attr($this->get_prefix()) ?>ai_goals[]"
        multiple
        title="Select what you want the AI to do in its response (other than answer the user's question).  Promote products will have the ai provide links to relavent products, keep user on site will have the ai provide links to other posts and pages on the site, keep user reading will have the ai provide links to other pages on the site, join mailing list will have the ai provide a link to the mailing list sign up page, and none will give the ai no goal to focus on."
    >
        <?php foreach ($options as $key=>$label): ?>
            <option value="<?php echo esc_attr($key); ?>" <?php echo in_array($key, $ai_goals_setting) ? 'selected' : ''; ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>