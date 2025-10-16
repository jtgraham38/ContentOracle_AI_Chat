<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleFloatingChat extends PluginFeature{
    public function add_filters(){
        // No filters needed for widget area approach
    }

    public function add_actions(){
        //register the widget area
        add_action('widgets_init', array($this, 'register_floating_chat_widget_area'));

        //register the settings
        add_action('admin_init', array($this, 'register_floating_site_chat_settings'));

        //register the settings page
        add_action('admin_menu', array($this, 'register_floating_site_chat_settings_page'));

        //render floating chat on frontend
        add_action('wp_footer', array($this, 'render_floating_chat_frontend'));

        //add default widget content
        add_action('widgets_init', array($this, 'add_default_floating_chat_widget'), 20);

        //enqueue floating chat styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_floating_chat_styles'));

        //add theme customizer controls
        add_action('customize_register', array($this, 'add_floating_chat_customizer_controls'));

        //output customizer CSS variables
        add_action('wp_head', array($this, 'output_floating_chat_customizer_css'));

    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    /*
    This feature will handle the creation of a sitewide floating chat button, which will
    open a chat block in the bottom right corner of the screen.

    The button color and background will be 100% customizable, and the chat 
    block itself will be customizable using a widget area that can accept
    any widgets including coai chat blocks.

    The widget area will be used to create the floating chat button and the chat block.
    
    This will be a separate entry in the tabs on the admin section to manage it.

    There will be a single setting, boolean, enable global site chat.  this will determine whether the 
    the floating chat button and block appear on the site frontend, and whether the admin can 
    access the widget area to edit the floating chat button and block.
    */

    /*
    * Register the settings for the global site chat.
    */
    public function register_floating_site_chat_settings(){
        //first, add the settings section
        add_settings_section(
            'coai_chat_floating_site_chat_settings', // id
            'Global Site Chat Settings', // title
            function(){ // callback
                echo 'Manage your global site chat settings here.';
            },
            'contentoracle-ai-global-site-chat-settings' // page (matches menu slug)
        );

        //then, register the setting field for the setting
        add_settings_field(
            $this->prefixed('enable_floating_site_chat'), // id
            'Enable Global Site Chat', // title
            function(){ // callback
                require_once plugin_dir_path(__FILE__) . 'elements/enable_floating_site_chat_input.php';
            },
            'contentoracle-ai-global-site-chat-settings', // page (matches menu slug)
            'coai_chat_floating_site_chat_settings', // section
            array(
                'label_for' => $this->prefixed('enable_floating_site_chat_input')
            )
        ); 

        //then, register the setting
        register_setting(
            'coai_chat_floating_site_chat_settings', // option group
            $this->prefixed('enable_floating_site_chat'), // option name
            array(  // args
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => function($value){
                    return $value ? true : false;
                }
            )
        );


    }

    /*
    * Register the settings page for the global site chat.
    */
    public function register_floating_site_chat_settings_page(){
        add_submenu_page(
            'contentoracle-hidden', // parent slug
            'Global Site Chat Settings', // page title
            'Global Site Chat', // menu title
            'manage_options', // capability
            'contentoracle-ai-chat-global-site-chat', // menu slug
            function(){
                require_once plugin_dir_path(__FILE__) . 'elements/_inputs.php';
            }
        );
    }

    /*
    * Register the widget area for the floating site chat.
    */
    public function register_floating_chat_widget_area(){
        register_sidebar(array(
            'name'          => __('Floating Site Chat', 'contentoracle-ai-chat'),
            'id'            => $this->prefixed('floating_chat_widget_area'),
            'description'   => __('Widget area for the floating site chat. Add your chat widgets here.', 'contentoracle-ai-chat'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>',
        ));
    }


    /*
    * Render floating chat on frontend pages.
    */
    public function render_floating_chat_frontend(){
        // Check if floating site chat is enabled
        $enable_floating_site_chat = get_option($this->prefixed('enable_floating_site_chat'));
        
        if (!$enable_floating_site_chat) {
            return;
        }
        
        // Include the floating chat template
        require_once plugin_dir_path(__FILE__) . 'elements/floating_chat.php';
    }

    /*
    * Add default widget content to the floating chat widget area.
    */
    public function add_default_floating_chat_widget(){
        // Check if floating site chat is enabled
        $enable_floating_site_chat = get_option($this->prefixed('enable_floating_site_chat'));
        
        if (!$enable_floating_site_chat) {
            return;
        }
        
        // Check if the widget area already has widgets
        $widget_area_id = $this->prefixed('floating_chat_widget_area');
        if (is_active_sidebar($widget_area_id)) {
            return;
        }
        
        // Get the current widget settings
        $sidebars_widgets = get_option('sidebars_widgets', array());
        
        // Check if we've already added the default widget
        $default_added = get_option($this->prefixed('default_widget_added'), false);
        if ($default_added) {
            return;
        }
        
        // Add a default HTML widget with ContentOracle AI chat block
        $widget_id = 'html-' . time();
        $widget_content = '<!-- wp:contentoracle/ai-chat {"height":"36rem","userMsgBgColor":"#3232FD","style":{"elements":{"link":{"color":{"text":"var:preset|color|base-2"}}},"border":{"radius":"4px","width":"1px"}},"textColor":"base-2","borderColor":"contrast"} /-->';
        
        // Get existing HTML widget settings
        $html_widgets = get_option('widget_html', array());
        
        // Add the new widget
        $html_widgets[$widget_id] = array(
            'title' => 'ContentOracle AI Floating Chat',
            'text' => $widget_content
        );
        
        // Update widget settings
        update_option('widget_html', $html_widgets);
        
        // Add widget to the sidebar
        if (!isset($sidebars_widgets[$widget_area_id])) {
            $sidebars_widgets[$widget_area_id] = array();
        }
        $sidebars_widgets[$widget_area_id][] = 'html-' . $widget_id;
        
        // Update sidebar widgets
        update_option('sidebars_widgets', $sidebars_widgets);
        
        // Mark that we've added the default widget
        update_option($this->prefixed('default_widget_added'), true);
    }

    /*
    * Enqueue floating chat styles.
    */
    public function enqueue_floating_chat_styles() {
        // Check if floating site chat is enabled
        $enable_floating_site_chat = get_option($this->prefixed('enable_floating_site_chat'));
        
        if (!$enable_floating_site_chat) {
            return;
        }

        wp_enqueue_style(
            $this->prefixed('floating_chat_styles'),
            plugin_dir_url(__FILE__) . 'assets/css/floating-chat.css',
            array(),
            '1.0.0'
        );
    }

    /*
    * Add floating chat customizer controls.
    */
    public function add_floating_chat_customizer_controls($wp_customize) {
        // Check if floating site chat is enabled
        $enable_floating_site_chat = get_option($this->prefixed('enable_floating_site_chat'));
        
        if (!$enable_floating_site_chat) {
            return;
        }

        // Add floating chat panel
        $wp_customize->add_panel(
            'contentoracle-ai-chat',
            array(
                'title'    => __('ContentOracle AI Chat', 'contentoracle-ai-chat'),
                'priority' => 160,
                'description' => __('Customize your ContentOracle AI Chat settings and appearance.', 'contentoracle-ai-chat'),
            )
        );

        // Add floating chat section within the panel
        $wp_customize->add_section(
            $this->prefixed('floating_chat_customizer_section'),
            array(
                'title'    => __('Floating Chat Styling', 'contentoracle-ai-chat'),
                'priority' => 10,
                'panel'    => 'contentoracle-ai-chat',
                'description' => __('Customize the appearance of your floating chat widget.', 'contentoracle-ai-chat'),
            )
        );

        // Add floating button background color setting
        $wp_customize->add_setting(
            $this->prefixed('floating_button_bg_color'),
            array(
                'default'           => '#6c757d',
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'refresh',
            )
        );

        // Add floating button background color control with color presets
        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize,
                $this->prefixed('floating_button_bg_color_control'),
                array(
                    'label'    => __('Floating Button Background Color', 'contentoracle-ai-chat'),
                    'section'  => $this->prefixed('floating_chat_customizer_section'),
                    'settings' => $this->prefixed('floating_button_bg_color'),
                    'palette'  => $this->get_color_palette(),
                )
            )
        );

        // Add floating button hover background color setting
        $wp_customize->add_setting(
            $this->prefixed('floating_button_hover_bg_color'),
            array(
                'default'           => '#5a6268',
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'refresh',
            )
        );

        // Add floating button hover background color control with color presets
        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize,
                $this->prefixed('floating_button_hover_bg_color_control'),
                array(
                    'label'    => __('Floating Button Hover Background Color', 'contentoracle-ai-chat'),
                    'section'  => $this->prefixed('floating_chat_customizer_section'),
                    'settings' => $this->prefixed('floating_button_hover_bg_color'),
                    'palette'  => $this->get_color_palette(),
                )
            )
        );

        // Add chat container background color setting
        $wp_customize->add_setting(
            $this->prefixed('chat_container_bg_color'),
            array(
                'default'           => '#f8f9fa',
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'refresh',
            )
        );

        // Add chat container background color control with color presets
        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize,
                $this->prefixed('chat_container_bg_color_control'),
                array(
                    'label'    => __('Chat Container Background Color', 'contentoracle-ai-chat'),
                    'section'  => $this->prefixed('floating_chat_customizer_section'),
                    'settings' => $this->prefixed('chat_container_bg_color'),
                    'palette'  => $this->get_color_palette(),
                )
            )
        );

        // Add chat header background color setting
        $wp_customize->add_setting(
            $this->prefixed('chat_header_bg_color'),
            array(
                'default'           => '#6c757d',
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'refresh',
            )
        );

        // Add chat header background color control with color presets
        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize,
                $this->prefixed('chat_header_bg_color_control'),
                array(
                    'label'    => __('Chat Header Background Color', 'contentoracle-ai-chat'),
                    'section'  => $this->prefixed('floating_chat_customizer_section'),
                    'settings' => $this->prefixed('chat_header_bg_color'),
                    'palette'  => $this->get_color_palette(),
                )
            )
        );

        // Add chat header text color setting
        $wp_customize->add_setting(
            $this->prefixed('chat_header_text_color'),
            array(
                'default'           => '#ffffff',
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'refresh',
            )
        );

        // Add chat header text color control with color presets
        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize,
                $this->prefixed('chat_header_text_color_control'),
                array(
                    'label'    => __('Chat Header Text Color', 'contentoracle-ai-chat'),
                    'section'  => $this->prefixed('floating_chat_customizer_section'),
                    'settings' => $this->prefixed('chat_header_text_color'),
                    'palette'  => $this->get_color_palette(),
                )
            )
        );

        // Add chat header text setting
        $wp_customize->add_setting(
            $this->prefixed('chat_header_text'),
            array(
                'default'           => 'AI Chat',
                'sanitize_callback' => 'sanitize_text_field',
                'transport'         => 'refresh',
            )
        );

        // Add chat header text control
        $wp_customize->add_control(
            $this->prefixed('chat_header_text_control'),
            array(
                'label'    => __('Chat Header Text', 'contentoracle-ai-chat'),
                'section'  => $this->prefixed('floating_chat_customizer_section'),
                'settings' => $this->prefixed('chat_header_text'),
                'type'     => 'text',
            )
        );

        // Add chat container border color setting
        $wp_customize->add_setting(
            $this->prefixed('chat_container_border_color'),
            array(
                'default'           => '#dee2e6',
                'sanitize_callback' => 'sanitize_hex_color',
                'transport'         => 'refresh',
            )
        );

        // Add chat container border color control with color presets
        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize,
                $this->prefixed('chat_container_border_color_control'),
                array(
                    'label'    => __('Chat Container Border Color', 'contentoracle-ai-chat'),
                    'section'  => $this->prefixed('floating_chat_customizer_section'),
                    'settings' => $this->prefixed('chat_container_border_color'),
                    'palette'  => $this->get_color_palette(),
                )
            )
        );

        // Add chat container border radius setting
        $wp_customize->add_setting(
            $this->prefixed('chat_container_border_radius'),
            array(
                'default'           => '8px',
                'sanitize_callback' => 'sanitize_text_field',
                'transport'         => 'refresh',
            )
        );

        // Add chat container border radius control
        $wp_customize->add_control(
            $this->prefixed('chat_container_border_radius_control'),
            array(
                'label'       => __('Chat Container Border Radius', 'contentoracle-ai-chat'),
                'section'     => $this->prefixed('floating_chat_customizer_section'),
                'settings'    => $this->prefixed('chat_container_border_radius'),
                'type'        => 'text',
                'description' => __('Enter border radius value (e.g., 8px, 12px, 50%).', 'contentoracle-ai-chat'),
            )
        );

        // Add chat container border width setting
        $wp_customize->add_setting(
            $this->prefixed('chat_container_border_width'),
            array(
                'default'           => '1px',
                'sanitize_callback' => 'sanitize_text_field',
                'transport'         => 'refresh',
            )
        );

        // Add chat container border width control
        $wp_customize->add_control(
            $this->prefixed('chat_container_border_width_control'),
            array(
                'label'       => __('Chat Container Border Width', 'contentoracle-ai-chat'),
                'section'     => $this->prefixed('floating_chat_customizer_section'),
                'settings'    => $this->prefixed('chat_container_border_width'),
                'type'        => 'text',
                'description' => __('Enter border width value (e.g., 1px, 2px, 0).', 'contentoracle-ai-chat'),
            )
        );

        // Add floating button icon setting
        $wp_customize->add_setting(
            $this->prefixed('floating_button_icon'),
            array(
                'default'           => 'chat-bubble',
                'sanitize_callback' => 'sanitize_text_field',
                'transport'         => 'refresh',
            )
        );

        // Add floating button icon control
        $wp_customize->add_control(
            $this->prefixed('floating_button_icon_control'),
            array(
                'label'       => __('Floating Button Icon', 'contentoracle-ai-chat'),
                'section'     => $this->prefixed('floating_chat_customizer_section'),
                'settings'    => $this->prefixed('floating_button_icon'),
                'type'        => 'select',
                'choices'     => $this->get_floating_button_icon_choices(),
                'description' => __('Choose the icon to display on the floating chat button.', 'contentoracle-ai-chat'),
            )
        );
    }

    /*
    * Output floating chat customizer CSS variables.
    */
    public function output_floating_chat_customizer_css() {
        // Check if floating site chat is enabled
        $enable_floating_site_chat = get_option($this->prefixed('enable_floating_site_chat'));
        
        if (!$enable_floating_site_chat) {
            return;
        }

        // Get customizer values
        $button_bg_color = get_theme_mod($this->prefixed('floating_button_bg_color'), '#6c757d');
        $button_hover_bg_color = get_theme_mod($this->prefixed('floating_button_hover_bg_color'), '#5a6268');
        $container_bg_color = get_theme_mod($this->prefixed('chat_container_bg_color'), '#f8f9fa');
        $header_bg_color = get_theme_mod($this->prefixed('chat_header_bg_color'), '#6c757d');
        $header_text_color = get_theme_mod($this->prefixed('chat_header_text_color'), '#ffffff');
        $container_border_color = get_theme_mod($this->prefixed('chat_container_border_color'), '#dee2e6');
        $container_border_radius = get_theme_mod($this->prefixed('chat_container_border_radius'), '8px');
        $container_border_width = get_theme_mod($this->prefixed('chat_container_border_width'), '1px');

        // Convert hex colors to rgba for shadows
        $button_shadow_color = $this->hex_to_rgba($button_bg_color, 0.3);
        $button_hover_shadow_color = $this->hex_to_rgba($button_hover_bg_color, 0.4);
        $container_shadow_color = $this->hex_to_rgba($container_bg_color, 0.15);

        // Output CSS custom properties
        echo '<style id="coai-floating-chat-customizer-css">';
        echo ':root {';
        echo '--coai-floating-button-bg-color: ' . esc_attr($button_bg_color) . ';';
        echo '--coai-floating-button-hover-bg-color: ' . esc_attr($button_hover_bg_color) . ';';
        echo '--coai-floating-button-shadow-color: ' . esc_attr($button_shadow_color) . ';';
        echo '--coai-floating-button-hover-shadow-color: ' . esc_attr($button_hover_shadow_color) . ';';
        echo '--coai-chat-container-bg-color: ' . esc_attr($container_bg_color) . ';';
        echo '--coai-chat-container-shadow-color: ' . esc_attr($container_shadow_color) . ';';
        echo '--coai-chat-container-custom-border-color: ' . esc_attr($container_border_color) . ';';
        echo '--coai-chat-container-border-radius: ' . esc_attr($container_border_radius) . ';';
        echo '--coai-chat-container-border-width: ' . esc_attr($container_border_width) . ';';
        echo '--coai-chat-header-bg-color: ' . esc_attr($header_bg_color) . ';';
        echo '--coai-chat-header-text-color: ' . esc_attr($header_text_color) . ';';
        echo '--coai-close-button-color: ' . esc_attr($this->get_contrast_color($header_bg_color)) . ';';
        echo '--coai-close-button-hover-bg-color: ' . esc_attr($this->hex_to_rgba($this->get_contrast_color($header_bg_color), 0.1)) . ';';
        echo '}';
        echo '</style>';
    }

    /*
    * Convert hex color to rgba.
    */
    private function hex_to_rgba($hex, $alpha = 1) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        return "rgba($r, $g, $b, $alpha)";
    }

    /*
    * Get contrast color (black or white) based on background color.
    */
    private function get_contrast_color($hex) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        
        // Calculate luminance
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        
        // Return black for light backgrounds, white for dark backgrounds
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }

    /*
    * Get color palette from theme or WordPress defaults.
    */
    private function get_color_palette() {
        // Try to get theme color palette first
        $theme_colors = get_theme_support('editor-color-palette');
        
        if ($theme_colors && isset($theme_colors[0])) {
            $palette = array();
            foreach ($theme_colors[0] as $color) {
                $palette[] = $color['color'];
            }
            return $palette;
        }
        
        // Fallback to WordPress default colors
        return array(
            '#000000', // Black
            '#ffffff', // White
            '#2271b1', // WordPress Blue
            '#72aee6', // Light Blue
            '#00a32a', // Green
            '#00ba37', // Light Green
            '#d63638', // Red
            '#f86368', // Light Red
            '#826eb4', // Purple
            '#9ea3a8', // Gray
            '#50575e', // Dark Gray
            '#f0f0f1', // Light Gray
        );
    }

    /*
    * Get floating button icon choices.
    */
    private function get_floating_button_icon_choices() {
        return array(
            'chat-bubble' => __('💬 Chat Bubble', 'contentoracle-ai-chat'),
            'question-mark' => __('❔ Question Mark', 'contentoracle-ai-chat'),
            'thought-bubble' => __('💭 Thought Bubble', 'contentoracle-ai-chat'),
            'robot' => __('🤖 Robot', 'contentoracle-ai-chat'),

        );
    }

    /*
    * Get emoji for icon key.
    */
    public function get_icon_emoji($icon_key) {
        $icon_map = array(
            'chat-bubble' => '💬',
            'question-mark' => '❔',
            'thought-bubble' => '💭',
            'robot' => '🤖',
        );
        
        return isset($icon_map[$icon_key]) ? $icon_map[$icon_key] : '💬';
    }
}