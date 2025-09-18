<?php 

if (!defined('ABSPATH')) {
    exit;
}

trait ContentOracle_ChatLoggerTrait{
    abstract public function get_client_ip();

    //create the chat log table, if it does not exist
    public function create_chat_log_table(){
        global $wpdb;
        

        $table_name = $wpdb->prefix . $this->prefixed('chatlog');

        //check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name) {
            return;
        }

        $wpdb->query("CREATE TABLE IF NOT EXISTS {$table_name} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
    }

    

    //log a chat from the user
    public function logUserChat(WP_REST_Request $request, $content_supplied = []){
        global $wpdb;
        $chat_log_id = null;

        //check if chat logging is enabled
        $logging_enabled = get_option($this->prefixed('enable_chat_logging'));
        if ( !$logging_enabled ){
            return;
        }

        //ensure the chat log table exists
        $this->create_chat_log_table();

        //define the table name
        $table_name = $wpdb->prefix . $this->prefixed('chatlog');

        //if the chat has no chat log id, create a new one
        if ($request->get_param('chat_log_id') === null){

            //get either the username or the email or the ip address
            $user_info = wp_get_current_user();
            $user_info = $user_info->user_login ?? $user_info->user_email ?? $this->get_client_ip();

            //create a new chat log
            $conversation = [
                [
                    "role" => "user",
                    "message" => sanitize_text_field($request->get_param('message')),
                    "content_supplied" => $content_supplied,
                    "timestamp" => time(),
                ]
            ];

            //insert it into the database
            $result = $wpdb->insert(
                $table_name, 
                array(
                    'conversation' => json_encode($conversation),
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s')
            );

            //check if insert was successful
            if ($result === false) {
                error_log('Failed to insert chat log: ' . $wpdb->last_error);
                return null;
            }

            //get the id of the inserted chat log
            $chat_log_id = $wpdb->insert_id;


        }
        //otherwise, update the existing chat log
        else{

            //set the chat log id
            $chat_log_id = $request->get_param('chat_log_id');

            //get the existing chat log
            $chat_log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $chat_log_id));

            if (!$chat_log) {
                error_log('Chat log not found: ' . $chat_log_id);
                return null;
            }

            //get the existing conversation
            $conversation = json_decode($chat_log->conversation, true) ?: [];
            
            // Log if JSON decode failed (for debugging)
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Failed to decode existing chat log JSON in logUserChat: ' . json_last_error_msg());
                $conversation = [];
            }

            //add the user's message to the conversation
            $conversation[] = [
                "role" => "user",
                "message" => sanitize_text_field($request->get_param('message')),
                "content_supplied" => $content_supplied,
                "timestamp" => time(),
            ];

            //update the existing chat log
            $result = $wpdb->update(
                $table_name, 
                array(
                    'conversation' => json_encode($conversation),
                    'updated_at' => current_time('mysql')
                ), 
                array('id' => $chat_log_id),
                array('%s', '%s'),
                array('%d')
            );

            if ($result === false) {
                error_log('Failed to update chat log: ' . $wpdb->last_error);
                return null;
            }

        }
        
        //send changes to db
        $wpdb->flush();

        //return the chat log id
        return $chat_log_id;
    }

    //log a chat from the ai
    public function logAiChat(string $message, $chat_log_id=null){
        global $wpdb;

        //check if chat logging is enabled
        $logging_enabled = get_option($this->prefixed('enable_chat_logging'));
        if ( !$logging_enabled ){
            return;
        }

        //ensure the chat log table exists
        $this->create_chat_log_table();

        //define the table name
        $table_name = $wpdb->prefix . $this->prefixed('chatlog');

        //if the chat log id is null, something has gone wrong.  Return from the function
        if ($chat_log_id === null){
            return null;
        }

        //get the existing chat log
        $chat_log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $chat_log_id));

        if (!$chat_log) {
            error_log('Chat log not found: ' . $chat_log_id);
            return null;
        }

        //get the existing conversation
        $conversation = json_decode($chat_log->conversation, true) ?: [];

        // Log if JSON decode failed (for debugging)
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Failed to decode existing chat log JSON in logAiChat: ' . json_last_error_msg());
            $conversation = [];
        }

        // First, strip all tags except coai-artifact tags
        $sanitized_message = wp_kses($message, array(
            'coai-artifact' => array(
                'artifact_type' => array(),
                'content_id' => array(),
                'button_text' => array(),
            ),
        ));

        //add the ai's message to the conversation
        $conversation[] = [
            'role' => 'assistant',
            'message' => $sanitized_message,
            'timestamp' => time()
        ];

        //json encode the conversation with proper escaping
        $encoded_conversation = json_encode($conversation, JSON_UNESCAPED_UNICODE);

        // Check if JSON encoding was successful
        if ($encoded_conversation === false) {
            // Log error for debugging (optional)
            error_log('Failed to encode JSON in logAiChat: ' . json_last_error_msg());
            return null;
        }
            
        //update the existing chat log
        $result = $wpdb->update(
            $table_name,
            array(
                'conversation' => $encoded_conversation,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $chat_log_id),
            array('%s', '%s'),
            array('%d')
        );

        if ($result === false) {
            error_log('Failed to update chat log with AI message: ' . $wpdb->last_error);
            return null;
        }

        //return the chat log id
        return $chat_log_id;
    }



}