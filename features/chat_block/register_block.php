<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use jtgraham38\jgwordpresskit\PluginFeature;

class ContentOracleAiBlock extends PluginFeature{
    public function add_filters(){
        //todo: add filters here
    }

    public function add_actions(){
        add_action('init', array($this, 'register_chat_blocks'));
    }

    //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\  //  \\

    //register the custom search block
    public function register_chat_blocks(){
        //include chat block utils
        //include seach block utils
        require_once $this->get_base_dir() . 'features/chat_block/util.php';

        //register chat block
        register_block_type($this->get_base_dir() . '/features/chat_block/block/build');
    }

}