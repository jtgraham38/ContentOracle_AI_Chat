<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

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
    <div>
        <div>
            <select
                id="<?php echo esc_attr($this->get_prefix()) ?>post_types_input"
                name="<?php echo esc_attr($this->get_prefix()) ?>post_types[]"
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

        <div>
            <details style="margin-top: 0.5rem; border: 1px solid #ccc; padding: 0.5rem;">
                <summary>
                    Click to reveal meta key settings for each post type.
                </summary>
                <h4>Set Meta Keys to Prompt With</h4>
                <div style="max-height: 18rem; overflow-y: auto;">
                    <p>For each post type, enter the meta keys of the post meta you would like the ai to use to generate a response.  For example, you  might select the date of an event, or the publish date of a post.  Enter each key in the text input for a post type.  Separate multiple keys with commas.</p>
                    <?php
                        if (count($post_types_setting) > 0):
                            foreach ($post_types_setting as $i=>$post_type):
                            ?>
                                <div style="padding: 0.2rem;">
                                    <label for="<?php echo esc_attr($post_type) ?>_prompt_meta_keys">
                                        Meta Keys for Type "<?php echo esc_html($post_types[$post_type]->label) ?>"
                                    </label>
                                    <input 
                                        type="text" 
                                        id="<?php echo esc_attr($post_type) ?>_prompt_meta_keys" 
                                        title="Enter the meta keys of post meta for this post type that should be used by the ai to generate a response.  Separate multiple keys with commas." 
                                        name="<?php echo esc_attr($this->get_prefix() . $post_type); ?>_prompt_meta_keys" 
                                        value="<?php echo esc_attr( implode( "," ,get_option( $this->get_prefix() . $post_type . '_prompt_meta_keys') ) ) ; ?>" 
                                        style="width: 100%;" 
                                    />
                                    <details>
                                        <summary>Options:</summary>
                                        <ul>
                                            <?php
                                                //get all possible values for the post meta for this post type
                                                $meta_keys = $wpdb->get_results("SELECT DISTINCT meta_key FROM $wpdb->postmeta WHERE post_id IN (SELECT ID FROM $wpdb->posts WHERE post_type = '$post_type')", ARRAY_A);
                                                
                                                //remove contentoracle ai chat specific keys
                                                $exclude = array('coai_chat_embeddings', 'coai_chat_should_generate_embeddings');
                                                $meta_keys = array_filter($meta_keys, function($meta_key) use ($exclude){
                                                    return !in_array($meta_key['meta_key'], $exclude);
                                                });
                                                
                                                //show the meta keys
                                                foreach ($meta_keys as $meta_key):
                                                ?>
                                                    <li>
                                                        <code>
                                                        <?php echo esc_html($meta_key['meta_key']); ?>
                                                        </code>
                                                    </li>
                                                <?php
                                                endforeach;
                                            ?>
                                        </ul>
                                    </details>
                                </div>
                            <?php
                            endforeach;
                        else:
                        ?> <em>Save your desired post types to set meta keys!</em> <?php
                        endif;
                    ?>
                </div>
            </details>
        </div>
    </div>

    <small style="display: block; margin-top: 0.5rem;"> Note that any content and post meta from posts of the types selected could be used to generate a response.  Do not select post types that contain sensitive information. </small>

</div>