<?php
/**
 * Plugin Name:       ContentOracle AI Chat
 * Plugin URI:        https://contentoracleai.com/contentoracle-ai-chat/
 * Description:       ContentOracle AI Chat seamlessly blends the power of generative AI with your website’s unique content.
 * Version:           1.10.5
 * Requires at least: 6.0
 * Requires PHP:      7.2
 * Author:            ContentOracle AI
 * Author URI:        https://contentoracleai.com
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

//create config for the plugin
$config = [
    'chat_timeout' => 10,               //10 seconds
    'embed_timeout' => 30,              //30 seconds
    'token_256_content_limit' => 10,    //10 chunks
    'token_512_content_limit' => 3      //3 chunks
];

//create a new plugin manager
$prefix = "coai_chat_";
$plugin = new Plugin($prefix, plugin_dir_path( __FILE__ ), plugin_dir_url( __FILE__ ), $config);

//register features with the plugin manager here...

//admin menu feature
require_once plugin_dir_path(__FILE__) . 'features/admin_menu/feature.php';
$feature = new ContentOracleMenu();
$plugin->register_feature('admin_menu', $feature);

//settings feature
require_once plugin_dir_path(__FILE__) . 'features/settings/feature.php';
$feature = new ContentOracleSettings();
$plugin->register_feature('settings', $feature); 

//api feature
require_once plugin_dir_path(__FILE__) . 'features/wp_api/feature.php';
$feature = new ContentOracleApi();
$plugin->register_feature('wp_api', $feature);

//register searchbar blocks feature
require_once plugin_dir_path(__FILE__) . 'features/search_block/register_block.php';
$feature = new ContentOracleSearchBlock();
$plugin->register_feature('search_block', $feature);

//register main ai chat block
require_once plugin_dir_path(__FILE__) . 'features/chat_block/register_block.php';
$feature = new ContentOracleAiBlock();
$plugin->register_feature('chat_block', $feature);

//register embeddings feature
require_once plugin_dir_path(__FILE__) . 'features/embeddings/feature.php';
$feature = new ContentOracleEmbeddings();
$plugin->register_feature('embeddings', $feature);

//register the analytics feature
require_once plugin_dir_path(__FILE__) . 'features/analytics/feature.php';
$feature = new ContentOracleAnalytics();
$plugin->register_feature('analytics', $feature);

//register the shortcodes feature
require_once plugin_dir_path(__FILE__) . 'features/shortcodes/feature.php';
$feature = new ContentOracleShortcodes();
$plugin->register_feature('shortcodes', $feature);

//register the setup wizard feature
require_once plugin_dir_path(__FILE__) . 'features/setup_wizard/feature.php';
$feature = new ContentOracleSetupWizard();
$plugin->register_feature('setup_wizard', $feature);



//init the plugin
$plugin->init();

//register the deactivation hook, which will call the uninstall method for each feature
register_deactivation_hook(__FILE__, array($plugin, 'uninstall'));

//register a transient to denote that the plugin was just activated
register_activation_hook(__FILE__, function() use ($prefix) {
    set_transient($prefix . "plugin_activated", true, 30);
});