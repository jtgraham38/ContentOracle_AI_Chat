<?php

if (!defined('ABSPATH')) {
    exit;
}

//this file shows an input, and uses it to display the raw embeddings values for a given post

//set the post_id to the default value
$post_id = null;

//handle GET
if (isset( $_GET['post_id'] )){
    //set the post_id to the value from the GET request
    $post_id = $_GET['post_id'];
    //handle a request for getting the embeddings
    if (isset($_GET['get_embeddings'])) {
        $embeddings = get_post_meta($post_id, $this->get_prefix().'embeddings', true);
        echo '<pre>';
        print_r($embeddings);
        echo '</pre>';
    }
}
//handle POST
else if (isset($_POST['post_id'])) {
    //set the post_id to the value from the POST request
    $post_id = $_POST['post_id'];

    //handler for updating embeddings for a particular post
    if (isset($_POST['update_embeddings'])){
        $embeddings = $_POST['embeddings'];

        //update the embeddings for the post
        update_post_meta($post_id, $this->get_prefix().'embeddings', $embeddings);
    
    }
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
?>
<div id="<?php echo esc_attr( $this->get_prefix() ) ?>embeddings_explorer">
    <form method="GET" action="<?php echo esc_url($_SERVER['PHP_SELF']); ?>">
        <label for="post_id">Post ID</label>
        <div style="display: flex; align-items: center;">
            <select name="post_id" id="post_id" required>
                <option value="" selected>Select a post...</option>
                <?php foreach ($posts as $post) { ?>
                    <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $post_id, $post->ID ); ?>><?php echo esc_html( $post->post_title ); ?> (<?php echo esc_html( $post->post_type ) ?>)</option>
                <?php } ?>
            </select>
            <input type="hidden" name="get_embeddings" value="1">
            <input type="submit" value="Get Embeddings" >
        </div>
        <input type="hidden" name="page" value="contentoracle-embeddings">
    </form>
    <br>
    <div>
        <form method="POST" action="<?php echo esc_url($_SERVER['PHP_SELF'] . '?page=contentoracle-embeddings'); ?>">
            <table>
                <thead>
                    <tr>
                        <th>Content</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <?php if (isset($embeddings) && is_array($embeddings)) { ?>
                        <?php foreach ($embeddings as $key => $value) { ?>
                            <tr>
                                <td title="<?php echo esc_attr($key); ?>">
                                    <?php echo esc_html($key); ?>
                                </td>
                                <td contenteditableinput name="embeddings[]"><?php echo esc_html($value); ?></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="2">No embeddings available.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ) ?>">
            <input type="hidden" name="update_embeddings" value="1">
            <input type="hidden" name="page">
            <input type="submit" value="Update Embeddings" class="button-primary">
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