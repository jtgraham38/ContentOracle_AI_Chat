<?php
/**
 * Plugin Name:       ContentOracle AI Search
 * Plugin URI:        https://jacob-t-graham.com/contentoracle-ai-search-a-website-add-on-that-uses-ai-to-boost-the-power-of-your-web-content/
 * Description:       ContentOracle AI Search seamlessly blends the power of generative AI with your website’s search feature.
 * Version:           1.0.0
 * Requires at least: 5.2
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
require_once __DIR__ . '/vendor/autoload.php';

// Use statements for namespaced classes
use jtgraham38\jgwordpresskit\Plugin;
use jtgraham38\jgwordpresskit\PluginFeature;

//create a new plugin manager
$plugin = new Plugin("prefix_", plugin_dir_path( __FILE__ ), plugin_dir_url( __FILE__ ));

//register features with the plugin manager here...
//TODO: $feature = ... (import an instance of PluginFeature());
//TODO: $plugin->register_feature($feature);

//init the plugin
$plugin->init();
