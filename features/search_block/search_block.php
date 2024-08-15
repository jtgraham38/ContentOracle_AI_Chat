<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleSearchBlock extends PluginFeature{
    public function add_filters(){
        //todo: add filters here
    }

    public function add_actions(){
        add_action('init', array($this, 'register_search_blocks'));
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //register the custom search block
    public function register_search_blocks(){
        //include seach block utils
        require_once $this->get_base_dir() . 'features/search_block/util.php';

        //register search block
        register_block_type($this->get_base_dir() . '/features/search_block/contentoracle-ai-searchbar/build');
    }

}