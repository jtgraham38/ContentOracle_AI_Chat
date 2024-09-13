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

//admin menu feature
require_once plugin_dir_path(__FILE__) . 'features/admin_menu/feature.php';
$feature = new ContentOracleMenu();
$plugin->register_feature($feature);

//settings feature
require_once plugin_dir_path(__FILE__) . 'features/settings/feature.php';
$feature = new ContentOracleSettings();
$plugin->register_feature($feature); 

//api feature
require_once plugin_dir_path(__FILE__) . 'features/wp_api/feature.php';
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

//register embeddings feature
require_once plugin_dir_path(__FILE__) . 'features/embeddings/feature.php';
$feature = new ContentOracleEmbeddings();
$plugin->register_feature($feature);

//register the analytics feature
require_once plugin_dir_path(__FILE__) . 'features/analytics/feature.php';
$feature = new ContentOracleAnalytics();
$plugin->register_feature($feature);

//init the plugin
$plugin->init();
