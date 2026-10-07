<?php


if (!defined('ABSPATH')) {
    exit;
}
global $wpdb;


//render artifacts
$render_artifacts = function($content){
    //get all coai artifacts, and get ready to replace them with the rendered html
    $content = preg_replace_callback('/<coai-artifact[^>]*>(.*?)<\/coai-artifact>/', function($matches){
        //get the artifact
        $artifact = $matches[0];

        //parse it uising simplexml
        $artifact = simplexml_load_string($artifact);


        //get the artifact type
        $artifact_type = $artifact->attributes()->artifact_type;

        //rendered artifact buffer
        $rendered_artifact = '';

        //switch based on the artifact type
        switch ($artifact_type){
            case 'featured_content':
                $content_id = strval($artifact->attributes()->content_id);
                $button_text = strval($artifact->attributes()->button_text);
                $inner_content = (string)$artifact;
                
                $rendered_artifact = '<div class="coai_chat-featured_content">';
                $rendered_artifact .= '<div class="coai_chat-featured_content_inner">';
                $rendered_artifact .= '<img src="' . get_the_post_thumbnail_url($content_id) . '" alt="' . get_the_title($content_id) . '">';
                $rendered_artifact .= '<p>' . $inner_content . '</p>';
                $rendered_artifact .= '<a href="' . get_permalink($content_id) . '" target="_blank">' . $button_text . '</a>';
                $rendered_artifact .= '</div>';
                $rendered_artifact .= '</div>';
                break;    

            case 'inline_citation':
                $content_id = strval($artifact->attributes()->content_id);
                $button_text = strval($artifact->attributes()->button_text);
                $inner_content = (string)$artifact;
                
                $rendered_artifact = '<div class="coai_chat-inline_citation">';
                $rendered_artifact .= '<a href="' . get_permalink($content_id) . '" target="_blank">' . $button_text . '</a>';
                $rendered_artifact .= '</div>';
                break;
            default:
                $inner_content = (string)$artifact;
                $rendered_artifact = $inner_content;
                break;
        }

        //return the rendered artifact
        return $rendered_artifact;
    }, $content);


    return $content;
};
//  \\  //  \\  //  \\  //  \\

//include autoloader
require_once plugin_dir_path(__FILE__) . '../../../vendor/autoload.php';
use League\CommonMark\CommonMarkConverter;

//define the table name
$table_name = $wpdb->prefix . $this->prefixed('chatlog');

//get the id of the chat log
$chat_log_id = isset($_GET['chat_log_id']) ? $_GET['chat_log_id'] : null;

//retrieve the chat log data from the custom table
$chat_log = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $chat_log_id));

//get the conversation from the chat log
$conversation = json_decode($chat_log->conversation, true);
$created_at = $chat_log->created_at;
$updated_at = $chat_log->updated_at;

//EDIT NOTHING ABOVE THIS LINE
?>

<script>
    const first_header = document.querySelector('h1');
    if (first_header){
        first_header.style.display = 'none';
    }
</script>

<div class="chat-log-content-display">
    <h2><?php _e('Chat Log Content', 'contentoracle-ai-chat'); ?></h2>
    
    <?php if (isset($chat_log_id) && isset($created_at)): ?>
        <div class="chat-log-meta">
            <div class="chat-log-header">
                <a href="<?php echo esc_url(admin_url('admin.php?page=contentoracle-ai-chat-analytics')); ?>" class="go-back-button">
                    ← <?php _e('Go Back', 'contentoracle-ai-chat'); ?>
                </a>
                <p><strong><?php _e('Chat ID:', 'contentoracle-ai-chat'); ?></strong> <?php echo esc_html($chat_log_id); ?></p>
            </div>
            <p><strong><?php _e('Created:', 'contentoracle-ai-chat'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($created_at))); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($conversation && is_array($conversation)): ?>
        <div class="<?php echo $this->prefixed('chat-conversation'); ?>">
            <?php foreach ($conversation as $index => $message): 
                //get the message from the array
                $role = $message['role'] ?? 'unknown';

                //switch based on the role
                switch ($role){
                    case 'user':
                        $content = $message['message'] ?? '';
                        $content_supplied = $message['content_supplied'] ?? [];
                        $role_class = $this->prefixed('user-message');
                        $timestamp = $message['timestamp'] ?? '';
                        break;
                    case 'assistant':
                        //strip all html tags from the message other than coai-artifact and br tags
                        $sanitized_content = wp_kses($message['message'], array(
                            'coai-artifact' => array(
                                'artifact_type' => array(),
                                'content_id' => array(),
                                'button_text' => array(),
                            ),
                            'br' => array(),
                        ));

                        $content = $sanitized_content ?? '';
                        $content_supplied = null;
                        $role_class = $this->prefixed('assistant-message');
                        $timestamp = $message['timestamp'] ?? '';
                        break;
                    default:
                        $content = $message['message'] ?? '';
                        $content_supplied = null;
                        $role_class = $this->prefixed('unknown-message');
                        $timestamp = $message['timestamp'] ?? '';
                        break;
                }
            ?>
                <div class="<?php echo $this->prefixed('chat-message'); ?> <?php echo esc_attr($role_class); ?>">
                    <div class="<?php echo $this->prefixed('message-header'); ?>">
                        <strong><?php echo esc_html(ucfirst($role)); ?></strong>
                        <span class="<?php echo $this->prefixed('message-number'); ?>">Message <?php echo ($index + 1); ?></span>
                        <?php if ($timestamp): ?>
                            <span class="<?php echo $this->prefixed('message-timestamp'); ?>"><?php echo esc_html(date('M j, Y g:i:s A', $timestamp)); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="<?php echo $this->prefixed('message-content'); ?>">
                        <?php 

                        //run the content through wp_kses to only allow br and coai-artifact tags
                        $content = wp_kses($content, array(
                            'br' => array(),
                            'coai-artifact' => array(
                                'artifact_type' => array(),
                                'content_id' => array(),
                                'button_text' => array(),
                            ),
                        ));


                        //replace multiple newlines with a single newline
                        $content = preg_replace('/\n+/', "\n", $content);

                        //convert the content to markdown
                        $converter = new CommonMarkConverter();
                        $content = $converter->convert($content);


                        //render the artifacts
                        $content = $render_artifacts($content);

                        //strip whitespace off front and end
                        $content = trim($content);

                        echo $content; 
                        ?>
                    </div>
                    <?php if ($content_supplied && is_array($content_supplied)): ?>
                        <div class="<?php echo $this->prefixed('content-supplied-label'); ?>">Content Sent to Agent:</div>
                        <ol class="<?php echo $this->prefixed('content-supplied'); ?>">
                            <?php foreach ($content_supplied as $content_item): ?>
                                <li>
                                    <span class="<?php echo $this->prefixed('content-item-title'); ?>"><?php echo esc_html($content_item['title']); ?></span>
                                    <span>
                                        <a 
                                            href="<?php echo esc_url($content_item['url']); ?>" 
                                            target="_blank"
                                            class="<?php echo $this->prefixed('content-item-link'); ?>"
                                        >
                                            →
                                        </a>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif ($post && $post->post_content): ?>
        <p><em>Invalid chat data</em></p>
    <?php else: ?>
        <p><em>No logs</em></p>
    <?php endif; ?>
</div>