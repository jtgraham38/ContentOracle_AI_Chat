<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../../vendor/autoload.php';

use \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;

//this file shows an input, and uses it to display the raw embeddings values for a given post

//set default values
$post_id = null;
$embeddings = [];

//get the post id of the selected post
if (isset($_REQUEST['post_id'])) {
    $post_id = $_REQUEST['post_id'];
    $selected_post = get_post($post_id);

    //handle an embedding generation request
    if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'generate_embeddings') {

        //un comment to allow manual embedding editing
        // //get the embeddings from the request
        // $embeddings = $_REQUEST['embeddings'];

        // //update the embeddings for the post
        // update_post_meta($post_id, $this->get_prefix() . 'embeddings', $embeddings);

        //trigger new embeddings generation, by loading the post, and saving it with a flag
        update_post_meta($post_id, $this->get_prefix() . 'should_generate_embeddings', true);
        
        // wp_update_post(array(
        //     'ID' => $post_id,
        //     'post_content' => $selected_post->post_content . $this->get_update_tag()
        // ));
    }


    //get the embeddings of the post
    $embeddings = get_post_meta($post_id, $this->get_prefix() . 'embeddings', true) ?? [];
}

//ensure the embeddings are an array
if (!is_array($embeddings)) {
    $embeddings = [];
}

//get the types of posts that are indexed by the AI
$post_types = get_option('contentoracle_post_types');

//get all posts of the indexed types
$posts = get_posts(array(
    'post_type' => $post_types,
    'numberposts' => -1,
    'orderby' => 'post_type',
    'order' => 'ASC'
));

//get chunk size for embedddings
$chunk_size = $this->get_chunk_size();

//function to get the section of the post body that an embedding is for
//0-indexed
$getSectionForEmbedding = function($content, $embedding_number) use ($chunk_size){
    //strip tags and tokenize content
    $tokenizer = new WhitespaceAndPunctuationTokenizer();
    $tokens = $tokenizer->tokenize(strip_tags($content));

    //get start and end indices of section
    $start = $embedding_number * $chunk_size;
    $end = ($embedding_number + 1) * $chunk_size;

    //get the section from the post content
    $section = array_slice($tokens, $start, $end - $start);
    $section = implode(' ', $section);

    //return the section
    return $section;
};


?>
<div id="<?php echo esc_attr( $this->get_prefix() ) ?>embeddings_explorer">
        <label for="<?php echo esc_attr($this->get_prefix()) ?>post_embedding_selector">Post</label>
        <select name="post_id" required id="<?php echo esc_attr($this->get_prefix()) ?>post_embedding_selector">
            <option value="" selected>Select a post...</option>
            <?php foreach ($posts as $post) { ?>
                <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $post_id, $post->ID ); ?>><?php echo esc_html( $post->post_title ); ?> (<?php echo esc_attr( $post->post_type ) ?>)</option>
            <?php } ?>
        </select>

        <script>
            document.addEventListener('DOMContentLoaded', function(){
                let selector = document.getElementById('<?php echo esc_attr($this->get_prefix()) ?>post_embedding_selector');
                console.log(selector, "selector");
                selector.addEventListener('change', function(){
                    window.location.href = '<?php echo esc_url($_SERVER['PHP_SELF']); ?>?page=contentoracle-embeddings&post_id=' + selector.value;
                });
            });
        </script>
    <br>
    <div>
        <form method="POST" action="<?php echo esc_url($_SERVER['PHP_SELF']); ?>?page=contentoracle-embeddings&post_id=<?php echo esc_url($post_id) ?>">
            <table>
                <thead>
                    <tr>
                        <th>Content</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <?php if (isset($embeddings) && count($embeddings) > 0) { ?>
                        <?php foreach ($embeddings as $i => $embedding) { ?>
                            <tr>

                                <?php 
                                    $section = $getSectionForEmbedding($selected_post->post_content, $i);
                                ?>

                                <td title="<?php echo esc_attr($section); ?>">
                                    <?php 
                                        $tokens = explode(' ', $section);
                                        $display_section = implode(' ', array_slice($tokens, 0, 3)) . ' ... ' . implode(' ', array_slice($tokens, -3));
                                        echo esc_html($display_section);
                                    ?>
                                </td>
                                <td contenteditableinput name="embeddings[]"><?php echo esc_html($embedding); ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="2">No embeddings found.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ) ?>">
            <input type="hidden" name="action" value="generate_embeddings">
            <input type="submit" value="<?php echo count($embeddings) > 0 ? "Re-Generate Embeddings" : "Generate Embeddings"  ?>" class="button-primary">
        </form>
    </div>

    <script>
        class ContentEditableInput{
            //this class binds the innertext of a contenteditable element to the value of an input
            constructor(el){
                this.init(el);
            }

            //initialize the class, creating the hidden input and binding the events
            init(el){
                //link this instance to the element
                this.el = el;
                this.el.setAttribute('contenteditable', 'true');

                //create the hidden input
                this.input = document.createElement('input');
                this.input.type = 'hidden';
                this.input.name = this.el.getAttribute('name');
                this.el.appendChild(this.input);

                //add event listener to this.el to update the input
                this.el.addEventListener('input', ()=>{this.bind(this)});   //pass in the context, due to event listener
                this.bind();
            }

            //update the input value based on the element's innerText
            bind(context){
                //bind the input to the element
                if (!context) context = this;
                this.input.value = context.el.innerText;
            }
        }

        //attach a form element to elements with the contenteditableinput attribute
        document.addEventListener('DOMContentLoaded', function(){
            let elements = document.querySelectorAll('[contenteditableinput]');
            elements.forEach(el => {
                let cei = new ContentEditableInput(el);
            });
        });
    </script>

</div>