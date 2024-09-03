<?php
/**
 * Plugin Name:       ContentOracle AI Search
 * Plugin URI:        https://jacob-t-graham.com/contentoracle-ai-search-a-website-add-on-that-uses-ai-to-boost-the-power-of-your-web-content/
 * Description:       ContentOracle AI Search seamlessly blends the power of generative AI with your website’s search feature.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.2
 * Author:            Jacob Graham
 * Author URI:        https://jacob-t-graham.com
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Require Composer's autoload file
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Use statements for namespaced classes
use jtgraham38\jgwordpresskit\Plugin;


//create a new plugin manager
$plugin = new Plugin("contentoracle_", plugin_dir_path( __FILE__ ), plugin_dir_url( __FILE__ ));

//register features with the plugin manager here...

//settings feature
require_once plugin_dir_path(__FILE__) . 'features/settings/settings.php';
$feature = new ContentOracleSettings();
$plugin->register_feature($feature); 

//api feature
require_once plugin_dir_path(__FILE__) . 'features/wp_api/api.php';
$feature = new ContentOracleApi();
$plugin->register_feature($feature);

//register searchbar blocks feature
require_once plugin_dir_path(__FILE__) . 'features/search_block/register_block.php';
$feature = new ContentOracleSearchBlock();
$plugin->register_feature($feature);

//register main ai chat block
require_once plugin_dir_path(__FILE__) . 'features/chat_block/register_block.php';
$feature = new ContentOracleAiBlock();
$plugin->register_feature($feature);


//NOTE: this will be moved, here for now
add_action('admin_menu', function(){
    // add the settings page
    add_menu_page(
        'ContentOracle AI', // page title
        'ContentOracle',        // menu title
        'manage_options',   // capability
        'contentoracle-ai', // menu slug
        function(){ // callback function
            echo '    <h1>ContentOracle AI Search</h1>
    <p>ContentOracle AI Search seamlessly blends the power of generative AI with your website’s search feature.</p>
    <p>For more information, visit the <a href="https://jacob-t-graham.com/contentoracle-ai-search-a-website-add-on-that-uses-ai-to-boost-the-power-of-your-web-content/">ContentOracle AI Search plugin page</a>.</p>
    <p>For support, visit the <a href="https://jacob-t-graham.com/contact/">Jacob Graham contact page</a>.</p>
';    //(content added when the custom post type is registered)
        },
        'dashicons-smiley'    // icon
    );
});

//init the plugin
$plugin->init();
