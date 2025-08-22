<?php 

if (!defined('ABSPATH')) {
    exit;
}

trait ContentOracle_ChatLoggerTrait{
    public function handleChatLog(WP_REST_Request $request){
        $chat_log_id = null;

        //if the chat has no chat log id, create a new one
        if ($request->get_param('chat_log_id') === null){
            //create a new chat log
            $chat_log_id = wp_insert_post(array(
                'post_type' => $this->prefixed('chatlog'),
                'post_title' => 'Chat Log',
                'post_content' => json_encode($request->get_param('conversation')),
                'post_status' => 'publish',
            ));


        }
        //otherwise, update the existing chat log
        else{
            //update the existing chat log
            $chat_log_id = $request->get_param('chat_log_id');
            wp_update_post(array(
                'ID' => $chat_log_id,
                'post_content' => json_encode($request->get_param('conversation')),
            ));
        }

        //return the chat log id
        return $chat_log_id;
    }
}