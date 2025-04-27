<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleShortcodes extends PluginFeature{
    public function add_filters(){
        add_filter('use_block_editor_for_post_type', array($this, 'enable_block_editor'), 10, 2);
        add_filter('allowed_block_types_all', array($this, 'restrict_allowed_blocks'), 10, 2);
        add_filter('enter_title_here', array($this, 'change_title_placeholder'), 10, 2);
        add_filter('block_editor_settings_all', array($this, 'restrict_single_block'), 10, 2);
    }

    public function add_actions(){
        add_action('init', array($this, 'register_coai_chat_shortcode_post_type'));
        add_action('admin_menu', array($this, 'register_shortcodes_post_type_page'));
        add_action('init', array($this, 'register_shortcodes'));
        add_action('manage_coai_chat_shortcode_posts_columns', array($this, 'add_shortcode_column'));
        add_action('manage_coai_chat_shortcode_posts_custom_column', array($this, 'display_shortcode_column'), 10, 2);
        add_action('admin_footer', array($this, 'add_copy_script'));
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //register a custom post type, which can only contain coai chat blocks
    public function register_coai_chat_shortcode_post_type(){
        $labels = array(
            'name'               => _x('ContentOracle AI Chat Shortcodes', 'post type general name', 'contentoracle-ai-chat'),
            'singular_name'      => _x('ContentOracle AI Chat Shortcode', 'post type singular name', 'contentoracle-ai-chat'),
            'menu_name'          => _x('ContentOracle AI Chat Shortcodes', 'admin menu', 'contentoracle-ai-chat'),
            'name_admin_bar'     => _x('ContentOracle AI Chat Shortcode', 'add new on admin bar', 'contentoracle-ai-chat'),
            'add_new'            => _x('Add New', 'ContentOracle AI chat shortcode', 'contentoracle-ai-chat'),
            'add_new_item'       => __('Add New ContentOracle AI Chat Shortcode', 'contentoracle-ai-chat'),
            'new_item'           => __('New ContentOracle AI Chat Shortcode', 'contentoracle-ai-chat'),
            'edit_item'          => __('Edit ContentOracle AI Chat Shortcode', 'contentoracle-ai-chat'),
            'view_item'          => __('View ContentOracle AI Chat Shortcode', 'contentoracle-ai-chat'),
            'all_items'          => __('All ContentOracle AI Chat Shortcodes', 'contentoracle-ai-chat'),
            'search_items'       => __('Search ContentOracle AI Chat Shortcodes', 'contentoracle-ai-chat'),
            'parent_item_colon'  => __('Parent ContentOracle AI Chat Shortcodes:', 'contentoracle-ai-chat'),
            'not_found'          => __('No contentoracle AI chat shortcodes found.', 'contentoracle-ai-chat'),
            'not_found_in_trash' => __('No Contentoracle AI chat shortcodes found in Trash.', 'contentoracle-ai-chat')
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'chat-shortcode'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('editor', 'title'),
            'show_in_rest'       => true,
        );

        register_post_type('coai_chat_shortcode', $args);
    }

    // Enable block editor for our custom post type
    public function enable_block_editor($use_block_editor, $post_type) {
        if ($post_type === 'coai_chat_shortcode') {
            return true;
        }
        return $use_block_editor;
    }

    // register a settings page for managing the custom post type that will be shown in the shortcode
    public function register_shortcodes_post_type_page(){
        // Add submenu for listing all shortcodes
        add_submenu_page(
            'contentoracle-ai-chat', // Parent menu slug
            __('Shortcodes', 'contentoracle-ai-chat'),
            __('Shortcodes', 'contentoracle-ai-chat'),
            'manage_options',
            'edit.php?post_type=coai_chat_shortcode'
        );
    }

    // Restrict allowed blocks for chat shortcode post type
    public function restrict_allowed_blocks($allowed_blocks, $block_editor_context) {
        // Only restrict blocks for our custom post type
        if ($block_editor_context->post->post_type !== 'coai_chat_shortcode') {
            return $allowed_blocks;
        }

        // List of allowed blocks
        return array(
            'contentoracle/ai-chat', // coai chat block
            'contentoracle/ai-search', // coai search block
        );
    }

    // Change the title placeholder
    public function change_title_placeholder($placeholder, $post) {
        if ($post->post_type === 'coai_chat_shortcode') {
            return __('Enter shortcode name...', 'contentoracle-ai-chat');
        }
        return $placeholder;
    }

    // Restrict to single block
    public function restrict_single_block($settings, $context) {
        if ($context->post->post_type === 'coai_chat_shortcode') {
            $settings['hasFixedToolbar'] = true;
            $settings['focusMode'] = true;
        }
        return $settings;
    }

    // Add shortcode column to the post list table
    public function add_shortcode_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['shortcode'] = __('Shortcode', 'contentoracle-ai-chat');
            }
        }
        return $new_columns;
    }

    // Display shortcode in the custom column
    public function display_shortcode_column($column, $post_id) {
        if ($column === 'shortcode') {
            $post = get_post($post_id);
            $shortcode_name = 'coai_chat_' . sanitize_title($post->post_title);
            echo '<code>[' . esc_html($shortcode_name) . ']</code>';
            echo '<button class="button button-small copy-shortcode" data-shortcode="[' . esc_attr($shortcode_name) . ']" style="margin-left: 8px;">' . __('Copy', 'contentoracle-ai-chat') . '</button>';
        }
    }

    // Register shortcodes for each custom post
    public function register_shortcodes() {
        // Get all published shortcode posts
        $shortcodes = get_posts(array(
            'post_type' => 'coai_chat_shortcode',
            'post_status' => 'publish',
            'numberposts' => -1
        ));

        // Register a shortcode for each post
        foreach ($shortcodes as $shortcode) {
            $shortcode_name = 'coai_chat_' . sanitize_title($shortcode->post_title);
            add_shortcode($shortcode_name, function($atts) use ($shortcode) {
                return $this->render_shortcode($shortcode);
            });
        }
    }

    // Render the shortcode content
    private function render_shortcode($post) {
        // Start output buffering
        ob_start();
        
        // Get the post content
        $content = $post->post_content;
        
        // Apply WordPress filters
        $content = apply_filters('the_content', $content);
        
        // Output the content
        echo $content;
        
        // Return the buffered content
        return ob_get_clean();
    }

    // Add JavaScript for copy functionality
    public function add_copy_script() {
        // Only add script on the shortcodes list page
        if (!isset($_GET['post_type']) || $_GET['post_type'] !== 'coai_chat_shortcode') {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.copy-shortcode').on('click', function(event) {
                event.preventDefault();
                var shortcode = $(this).data('shortcode');
                var tempInput = $('<input>');
                $('body').append(tempInput);
                tempInput.val(shortcode).select();
                document.execCommand('copy');
                tempInput.remove();
                
                // Change button text temporarily
                var $button = $(this);
                var originalText = $button.text();
                $button.text('<?php _e('Copied!', 'contentoracle-ai-chat'); ?>');
                setTimeout(function() {
                    $button.text(originalText);
                }, 2000);
            });
        });
        </script>
        <?php
    }
}