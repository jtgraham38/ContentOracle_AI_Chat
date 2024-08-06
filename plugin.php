<?php
/**
 * Plugin Name:       My WordPress Plugin
 * Plugin URI:        https://example.com/my-plugin
 * Description:       A brief description of the plugin.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Jacob Graham
 * Author URI:        https://jacob-t-graham.com
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       my-plugin
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
