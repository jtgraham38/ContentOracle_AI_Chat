<?php 

if (!defined('ABSPATH')) {
    exit;
}

trait ContentOracle_ChatLoggerTrait{
    abstract public function get_client_ip();

    //log a chat from the user
    public function logUserChat(WP_REST_Request $request, $content_supplied = []){
        $chat_log_id = null;

        //if the chat has no chat log id, create a new one
        if ($request->get_param('chat_log_id') === null){

            //get either the username or the email or the ip address
            $user_info = wp_get_current_user();
            $user_info = $user_info->user_login ?? $user_info->user_email ?? $this->get_client_ip();

            //create a new chat log
            $chat_log_id = wp_insert_post(array(
                'post_type' => $this->prefixed('chatlog'),
                'post_title' => 'Chat Log from: ' . $user_info,
                'post_content' => json_encode([
                    "conversation" => [
                        [
                            "role" => "user",
                            'message' => sanitize_text_field($request->get_param('message')),
                            'content_supplied' => $content_supplied,
                            'timestamp' => time(),
                        ]
                    ]
                ]),
                'post_status' => 'publish',
            ));


        }
        //otherwise, update the existing chat log
        else{

            //get the existing chat log
            $chat_log = get_post($request->get_param('chat_log_id'));

            //get the existing conversation
            $conversation = json_decode($chat_log->post_content, true);

            //add the user's message to the conversation
            $conversation['conversation'][] = [
                'role' => 'user',
                'message' => $request->get_param('message'),
                'content_supplied' => $content_supplied,
                'timestamp' => time()
            ];

            //update the existing chat log
            $chat_log_id = $request->get_param('chat_log_id');
            wp_update_post(array(
                'ID' => $chat_log_id,
                'post_content' => json_encode($conversation),
            ));
        }

        //return the chat log id
        return $chat_log_id;
    }

    //log a chat from the ai
    public function logAiChat(string $message, $chat_log_id=null){
        if ($chat_log_id === null){
            $chat_log_id = $request->get_param('chat_log_id');
        }

        //if the chat log id is null, something has gone wrong.  Return from the function
        if ($chat_log_id === null){
            return null;
        }

        //get the existing chat log
        $chat_log = get_post($chat_log_id);

        //get the existing conversation
        $conversation = json_decode($chat_log->post_content, true) ?: ['conversation' => []];

        //sanitize the message
        $sanitized_message = htmlspecialchars($message, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $sanitized_message = nl2br($sanitized_message);

        //add the ai's message to the conversation
        $conversation['conversation'][] = [
            'role' => 'assistant',
            'message' => $sanitized_message,
            'timestamp' => time()
        ];

        //json encode the conversation
        $encoded_conversation = json_encode($conversation, JSON_UNESCAPED_UNICODE /*| JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT*/ );

        // Check if JSON encoding was successful
        if ($encoded_conversation === false) {
            // Log error for debugging (optional)
            error_log('Failed to encode JSON in logAiChat: ' . json_last_error_msg());
            return null;
        }
            
        //update the existing chat log
        wp_update_post(array(
            'ID' => $chat_log_id,
            'post_content' => $encoded_conversation,
        ));

        //return the chat log id
        return $chat_log_id;
    }


    /*
    * 
    * 
    * TODO: I need a more robust system that does not overwrite the chat log with each message, but instead appends to it.
    * Ideally, we woul add the user's message to the chat log at the beginning of the request, and the completed ai response to the chat log at the end of the request.
    * Then, we would add error handling to the chat log.  Then, the chat log feature would just need auto-deleting, and it'd be done!
    * 
    * 
    * 
    * 
    * 
    */
}