<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

//get all post types
$post_types = get_post_types(array(), 'objects');

//exclude certain useless (for purposes of ai generation) post types
$exclude = array('attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'acf-field-group', 'acf-field', 'wp_font_family', 'wp_font_face', 'wp_global_styles');
foreach ($exclude as $ex){
    unset($post_types[$ex]);
}

//load the current value of the post types setting
$post_types_setting = get_option($this->get_prefix() . 'post_types');
?>

<div>
    <select
        id="<?php echo esc_attr($this->get_prefix()) ?>post_types_input"
        name="<?php echo esc_attr($this->get_prefix()) ?>post_types[]"
        multiple
        title="Select which post types our AI should use to generate its search response.  It will use the title, contents, links, media, and more to generate a response."
    >
        <?php foreach ($post_types as $label=>$post_type): ?>
            <option value="<?php echo esc_attr($label); ?>" <?php echo in_array($post_type->name, $post_types_setting) ? 'selected' : ''; ?>>
                <?php echo esc_html($post_type->label); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>