<?php

if (!defined('ABSPATH')) {
    exit;
}

//    \\    //    GET POST TYPE OPTIONS    \\    //    \\
//get all post types
$post_types = get_post_types(array(), 'objects');

//exclude certain useless (for purposes of ai generation) post types
$exclude = array(
    'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'acf-field-group', 'acf-field', 'wp_font_family', 'wp_font_face', 'wp_global_styles',
    'coai_chat_shortcode'
);
foreach ($exclude as $ex){
    unset($post_types[$ex]);
}

//chunking method options
//get all post types
$chunking_method_options = [
    'token:256' => "Token (256 tokens/chunk)",
];

//other options
//set jargon options
$jargon_options = [
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

//tone options
$tone_options = [
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

//handle post request from the step2 form
$error_msg = '';
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST'):
    //check the nonce
    if (!wp_verify_nonce($_POST['nonce'], $this->prefixed('setup_wizard_step3'))) {
        $error_msg = 'Security check failed. Please try again.';
    } else {
        // Validate post types
        if (!isset($_POST['post_types']) || empty($_POST['post_types'])) {
            $error_msg = 'Please select at least one post type.';
        } else {
            $post_types_setting = array_map('sanitize_key', (array)$_POST['post_types']);
            // Validate that all selected post types exist
            $valid_post_types = array_keys($post_types);
            $invalid_types = array_diff($post_types_setting, $valid_post_types);
            if (!empty($invalid_types)) {
                $error_msg = 'Invalid post type(s) selected: ' . implode(', ', $invalid_types);
            }
        }

        // Validate chunking method
        //custom sanitizer to allow colons in the chunking method
        $chunking_method_setting = isset($_POST['chunking_method']) ? preg_replace('/[^a-zA-Z0-9:]/', '', $_POST['chunking_method']) : 'none';
        if (!in_array($chunking_method_setting, array_keys($chunking_method_options)) && $chunking_method_setting != 'none') {
            $error_msg = 'Invalid chunking method selected.';
        }

        // Validate tone
        if (!isset($_POST['ai_tone'])) {
            $error_msg = 'Please select a tone.';
        } else {
            $tone_setting = sanitize_key($_POST['ai_tone']);
            if (!in_array($tone_setting, array_keys($tone_options))) {
                $error_msg = 'Invalid tone selected.';
            }
        }

        // Validate jargon
        if (!isset($_POST['ai_jargon'])) {
            $error_msg = 'Please select a jargon level.';
        } else {
            $jargon_setting = sanitize_key($_POST['ai_jargon']);
            if (!in_array($jargon_setting, array_keys($jargon_options))) {
                $error_msg = 'Invalid jargon level selected.';
            }
        }

        // Validate goal prompt
        if (!isset($_POST['ai_goal_prompt'])) {
            $error_msg = 'Please enter a goal prompt.';
        } else {
            $goals_setting = sanitize_textarea_field($_POST['ai_goal_prompt']);
            if (strlen($goals_setting) > 255) {
                $error_msg = 'Goal prompt must be 255 characters or less.';
            }
        }

        // Validate extra info prompt
        if (!isset($_POST['ai_extra_info_prompt'])) {
            $error_msg = 'Please enter extra information.';
        } else {
            $extra_info_setting = sanitize_textarea_field($_POST['ai_extra_info_prompt']);
            if (strlen($extra_info_setting) > 255) {
                $error_msg = 'Extra information must be 255 characters or less.';
            }
        }

        // If no errors, save the settings
        if (empty($error_msg)) {
            update_option($this->prefixed('post_types'), $post_types_setting);
            update_option($this->prefixed('chunking_method'), $chunking_method_setting);
            update_option($this->prefixed('tone'), $tone_setting);
            update_option($this->prefixed('jargon'), $jargon_setting);
            update_option($this->prefixed('ai_goal_prompt'), $goals_setting);
            update_option($this->prefixed('ai_extra_info_prompt'), $extra_info_setting);
            $success_msg = 'Prompt settings saved successfully!';
        }
    }
endif;
//    \\    //    GET CURRENT SETTINGS
$post_types_setting = get_option($this->prefixed('post_types'));
$chunking_method_setting = get_option($this->prefixed('chunking_method'));
$tone_setting = get_option($this->prefixed('tone'));
$jargon_setting = get_option($this->prefixed('jargon'));
$goals_setting = get_option($this->prefixed('ai_goal_prompt'));
$extra_info_setting = get_option($this->prefixed('ai_extra_info_prompt'));
?>

<div>
    <h1>Step 3: Customize the Prompt</h1>

    <p>Now, you will customize the prompt settings for your AI assistant.</p>
    <p>This will affect the way your AI assistant will respond to your visitors.</p>
    <p>Don't worry, you can always change these later in the plugin prompt and embeddings settings.</p>
    <p>
        <a
            id="prompt-guide-trigger"
            style="cursor: pointer;"
            onclick="document.getElementById('prompt-guide').showModal();"
        >Click here to read more about what each setting does.</a> 
    </p>

    <dialog id="prompt-guide" style="max-width: 600px;">
        <h2>Prompt Guide</h2>
        <p>
            <strong>Post Types</strong>
            <br>
            This determines which types of content our AI will use to generate its responses to visitors.  
            You can choose from any post type on the site, including custom post types introduced by other plugins.
            You can give the agent access to post meta on the prompt settings page.
            Ensure you only select publicly accessible post types, and take special care with posts that are time sensitive (i.e. events, news, etc.).
        </p>
        <p>
            <strong>Chunking Method</strong>
            <br>
            This determines how the content on the site is indexed and searched by your agent.
            The Token (256 tokens/chunk) option will use semantic search, which is more accurate and relevant than keyword search.
            The None option will use keyword search, which simpler but generally inferior.
            Token (256 tokens/chunk) is the recommended option.
        </p>
        <p>
            <strong>Tone</strong>
            <br>
            This determines the tone of the AI's responses.
            You can use this setting to help your agent match the tone of your brand.
        </p>
        <p>
            <strong>Jargon</strong>
            <br>
            This determines the jargon of the AI's responses.
            You can use this setting to make your agent respond using industry specific terminology.
        </p>
        <p>
            <strong>Goals</strong>
            <br>
            This determines the goals of the AI's responses.
            This setting can be used to give your agent a goal to work towards in its conversations with visitors.
            Ex. "If it makes sense in the context of the conversation, share a link to a product you were given and encourage them to add it to their cart."
        </p>
        <p>
            <strong>Extra Info</strong>
            <br>
            This determines any extra information about your organization that the chat assistant might need during its conversation.
            Ex. "We are a small business and can only ship to the US.  Our business hours are 9am-5pm Monday-Friday."
        </p>

        <button type="button" onclick="document.getElementById('prompt-guide').close();" class="button button-primary">Close</button>
    </dialog>

    <div class="postbox" style="padding: 0.25rem;">
        <h2>Prompt Settings</h2>
    <form action="" method="POST"  style="padding: 0.5rem;">
        <?php wp_nonce_field($this->prefixed('setup_wizard_step3'), 'nonce'); ?>
        <input type="hidden" name="action" value="setup_wizard_step3">

        <div style="margin-top: 1rem;">
            <label for="<?php $this->pre('post_types_input') ?>">Post Types</label>
                        <select
                id="<?php $this->pre('post_types_input') ?>"
                name="post_types[]"
                multiple
                title="Select which post types our AI should use to generate its search response.  It will use the title, contents, links, media, and more to generate a response."
                required
                style="height: 12rem;"
            >
                <?php foreach ($post_types as $label=>$post_type): ?>
                    <option value="<?php echo esc_attr($label); ?>" <?php echo esc_attr( in_array($post_type->name, $post_types_setting) ? 'selected' : '' ); ?>>
                        <?php echo esc_html($post_type->label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="margin-top: 1rem;">
            <label for="<?php $this->pre('chunking_method_input') ?>">Chunking Method</label>
            <select
                id="<?php $this->pre('chunking_method_input') ?>"
                name="chunking_method"
                title="Select the chunking method that should be used when generating embeddings of your post content.  This will determine how the content is broken up into smaller pieces for embedding generation.  If no chunking method is set, embeddings will not be generated, and keyword search will be used instead."
            >
                <option value="none" selected>None (Use keyword search)</option>
                <?php foreach ($chunking_method_options as $key => $value) { ?>
                    <option value="<?php echo esc_attr($key) ?>" <?php echo esc_attr( $chunking_method_setting == $key ? "selected" : "" ) ?>  ><?php echo esc_html($value) ?></option>
                <?php } ?>
            </select>
            <small style="display: block; margin-top: 0.25rem;">Note: it is heavily recommended to use select <code>Token (256 tokens/chunk)</code> as the chunking method.</small>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 0.5rem;">
            <div>
            <label for="<?php $this->pre('tone_input_input') ?>">Tone</label>
            <select
                id="<?php $this->pre('tone_input_input') ?>" 
                name="ai_tone" 
                title="Select the tone of the AI's responses.  Formal tones are more professional, casual tones are more friendly, and neutral tones are more... neutral."
            >
                <?php foreach ($tone_options as $value=>$label): ?>
                    <option 
                        value="<?php echo esc_attr($value); ?>" 
                        <?php echo esc_attr( $tone_setting == $value ? 'selected' : ''); ?>
                    >
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            </div>

            <div>
            <label for="<?php $this->pre('jargon_input') ?>">Jargon</label>
            <select
                id="<?php $this->pre('jargon_input') ?>" 
                name="ai_jargon" 
                title="Select the jargon of the AI's responses.  Healthcare jargon is more medical, legal jargon is more legal, finance jargon is more financial, tech jargon is more technical, education jargon is more educational, general jargon is more general, and none is no jargon."
            >
                <?php foreach ($jargon_options as $value=>$label): ?>
                    <option 
                        value="<?php echo esc_attr($value); ?>" 
                        <?php echo esc_attr( $jargon_setting == $value ? 'selected' : ''); ?>
                    >
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            </div>
        </div>

        <div style="margin-top: 1rem;">
            <label for="<?php $this->pre('ai_goal_prompt_input') ?>">Goals</label>
            <textarea 
                type="text" 
                name="ai_goal_prompt" 
                id="<?php $this->pre('ai_goal_prompt_input') ?>"
                title="Enter a succinct description of what you would like the ai to try to do in its interactions with users.  Ex. 'If it makes sense in the context of the conversation, share a link to a product you were given and encourage them to add it to their cart.'"
                maxlength="255"
                rows="4"
                cols="50"
            >
<?php echo esc_html( $goals_setting ) ?>
</textarea>
        </div>

        <div style="margin-top: 1rem;">
            <label for="<?php $this->pre('ai_extra_info_prompt_input') ?>">Extra Info</label>
            <textarea 
                type="text" 
                name="ai_extra_info_prompt" 
                id="<?php $this->pre('ai_extra_info_prompt_input') ?>"
                title="Enter any extra information about your organization that the chat assistant might need during its conversation.  Ex. 'We are a small business and can only ship to the US.'"
                maxlength="255"
                rows="4"
                cols="50"
            >
<?php echo esc_html( $extra_info_setting ) ?>
</textarea>
        </div>
        <br>

        <?php if ($success_msg != '') { ?>
            <span class="success-msg"><?php echo $success_msg; ?></span>
        <?php } ?>

        <?php if ($error_msg != '') { ?>
            <span class="error-msg"><?php echo $error_msg; ?></span>
        <?php } ?>

        <br>
        <br>

        <div>
            <input type="submit" value="Save" class="button button-primary">
        </div>
    </form>
    </div>


<p>
    Once you've successfully saved your settings, your agent has been configured, and you're ready to move on to the next step.
</p>

</div>

