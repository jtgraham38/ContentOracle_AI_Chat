<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../../vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . '../VectorTable.php';

use \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;
//access to vector table
$VT = new ContentOracle_VectorTable($this->get_prefix());
//$result = $VT->search(json_encode( $vector ));
//this file shows an input, and uses it to display the raw embeddings values for a given post

//set default values
$post_id = null;
$embeddings = [];

//get the selected post, if one is selected
//get the post id of the selected post
if (isset($_REQUEST['post_id'])) {
    $post_id = $_REQUEST['post_id'];
    $selected_post = get_post($post_id);

    //get the embeddings of the post
    $embedding_ids = get_post_meta($post_id, $this->get_prefix() . 'embeddings', true) ?? [];
    if (!is_array($embedding_ids)) {    //ensure the embeddings are an array
        $embedding_ids = [];
    }
    $embeddings = $VT->ids($embedding_ids);
}

//handle an embedding generation request
//handle the action from the admin page
if (isset($_REQUEST['action'])) {

    switch ($_REQUEST['action']){
        case 'generate_embeddings':
            //get the post id of the selected post
            if (isset($_REQUEST['post_id'])) {
                //un comment to allow manual embedding editing
                // //get the embeddings from the request
                // $embeddings = $_REQUEST['embeddings'];

                // //update the embeddings for the post
                // update_post_meta($post_id, $this->get_prefix() . 'embeddings', $embeddings);

                //ensure the post is of a type that the ai indexes
                $post_types = get_option('contentoracle_post_types');
                if (!isset($selected_post) || !in_array($selected_post->post_type, $post_types)) {
                    return;
                }

                //trigger new embeddings generation, by loading the post, and saving it with a flag
                update_post_meta($post_id, $this->get_prefix() . 'should_generate_embeddings', true);
                
                //this triggers the save_post hook, which will generate the embeddings
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $selected_post->post_content . $this->get_update_tag()
                ));

                //get the updated embeddings of the post
                $embedding_ids = get_post_meta($post_id, $this->get_prefix() . 'embeddings', true) ?? [];
                if (!is_array($embedding_ids)) {    //ensure the embeddings are an array
                    $embedding_ids = [];
                }
                $embeddings = $VT->ids($embedding_ids);
            }
            break;
        case 'bulk_generate_embeddings':

            //ensure embeddings are enabled
            $chunking_method = get_option($this->get_prefix() . 'chunking_method');
            if ($chunking_method == 'none' || $chunking_method == '') {
                break;
            }

            //get the option from the bulk selector
            if (!isset($_REQUEST['bulk_generate_embeddings_option']) || $_REQUEST['bulk_generate_embeddings_option'] == '') {
                break;
            }
            $bulk_option = $_REQUEST['bulk_generate_embeddings_option'];

            //get all the posts, based on the selected bulk option and post types to index
            $post_types = get_option('contentoracle_post_types') ?? [];
            $posts = [];
            switch ($bulk_option) {
                case 'unembedded':
                    $posts = get_posts(array(
                        'post_type' => $post_types,
                        'numberposts' => -1,
                        'orderby' => 'post_type',
                        'order' => 'ASC',
                        'meta_query' => array(
                            'relation' => 'OR',
                            array(
                                'key' => $this->get_prefix() . 'embeddings',
                                'compare' => 'NOT EXISTS'
                            ),
                            array(
                                'key' => $this->get_prefix() . 'embeddings',
                                'value' => 'false',
                                'compare' => '='
                            )
                        )
                    ));
                    break;
                case 'all':
                    $posts = get_posts(array(
                        'post_type' => $post_types,
                        'numberposts' => -1,
                        'orderby' => 'post_type',
                        'order' => 'ASC'
                    ));
                    break;
                default:
                    return;
            }
            
            //call $this->generate_embeddings for each post
            //TODO: I should make a mass batch submission to my api eventually
            $this->generate_embeddings($posts);
    }
    //end switch action
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
<strong>Note: Embeddings will only be generated for posts of the types set in the "Prompt" settings.  They will also only be generated if a chunking method is set.</strong>
<br>
<br>
<details>
    <summary>Tips</summary>
    <ul>
        <li>
            Select a post from the dropdown to view its embeddings.
        </li>
        <li>
            Click "Generate Embeddings" to generate embeddings for the selected post.
        </li>
        <li>
            Click "Re-Generate Embeddings" to re-generate embeddings for the selected post.
        </li>
        <li>
            Click "Bulk Generate Embeddings" to generate embeddings for many posts at once.
        </li>
        <li>
            Note that embeddings will only be generated and shown for posts of the types selected in the prompt settings.
        </li>
    </ul>
</details>
<div id="<?php echo esc_attr( $this->get_prefix() ) ?>embeddings_explorer" style="display: flex;">
    <div>
        <h3>Embeddings Explorer</h3>
        <p>Use the form below to explore the embeddings for a given post.</p>

        <label for="<?php echo esc_attr($this->get_prefix()) ?>post_embedding_selector">Post</label>
        <select 
            name="post_id" 
            required 
            id="<?php echo esc_attr($this->get_prefix()) ?>post_embedding_selector"
            title="The embeddings shown in the table below are for the selected post.  Embeddings will be generated for this post if the button is pressed."
        >
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
        <br>

            <?php
                if ($post_id && intval($post_id) > 0) {
                    //get the last time the embeddings were generated
                    $most_recent_embedding = $VT->get_latest_updated($post_id);
        
                    if (isset($most_recent_embedding->updated_at)) {
                        ?>
                            Embeddings last generated at: <?php echo esc_html($most_recent_embedding->updated_at); ?>
                        <?php
                    }
                }
            ?>

        <div
        >
            <form 
                method="POST" 
                action="<?php echo esc_url($_SERVER['PHP_SELF']); ?>?page=contentoracle-embeddings&post_id=<?php echo esc_url($post_id) ?>"
            >
                <div
                    style="max-width: 48rem; overflow-x: auto;"
                >
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
                                        <td contenteditableinput name="embeddings[]"><?php echo esc_html($embedding->vector); ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="2">No embeddings found.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
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
    <div style="width: 2rem;"></div>
    <div>
        <h3>Bulk Generate Embeddings</h3>
        <p>Generate embeddings for many posts at once!</p>

        <form 
            method="POST" 
            action="<?php echo esc_url($_SERVER['PHP_SELF']); ?>?page=contentoracle-embeddings" 
        >
        <label for="bulk_generate_embeddings_select">Bulk Options</label>
            <div style="display: flex;">
                <select 
                    name="bulk_generate_embeddings_option" 
                    id="bulk_generate_embeddings_select" 
                    required
                    title="Select an option to generate embeddings for many posts at once.  This will only generate embeddings for posts of the types selected in the prompt settings, and only if a chunking method is set."    
                >
                    <option value="" selected>Select an option...</option>
                    <option value="unembedded">All Posts Without Embeddings</option>
                    <option value="all">All Posts</option>
                </select>
                <input type="submit" value="Generate Embeddings" class="button-primary">
            </div>
            <input type="hidden" name="action" value="bulk_generate_embeddings">
        </form>
    </div>
</div>
<br>
<br>
<small>Note: Generating embeddings may result in charges to your account.</small>