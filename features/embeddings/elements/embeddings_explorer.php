<?php

if (!defined('ABSPATH')) {
    exit;
}

//this file shows an input, and uses it to display the raw embeddings values for a given post

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
    <form method="POST" action="">
        <label for="post_id">Post ID</label>
        <div style="display: flex; align-items: center;">
            <select name="post_id" id="post_id" required>
                <option value="" selected>Select a post...</option>
                <?php foreach ($posts as $post) { ?>
        
                    <option value="<?php echo esc_attr( $post->ID ); ?>"><?php echo esc_html( $post->post_title ); ?> (<?php echo esc_html( $post->post_type ) ?>)</option>
        
                <?php } ?>
            </select>
            <input type="submit" value="Get Embeddings">
        </div>
    </form>
    <br>
    <div>
        <table>
            <thead>
                <tr>
                    <th>Content</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td title="Lorem ipsum dolor sit amet, consectetur adipisicing elit. Alias veniam repellendus iste eaque. Aliquam minima voluptas perferendis sunt qui rerum! Magni, at aspernatur asperiores placeat adipisci accusantium commodi repellendus sit?">
                        I know ... delicious
                    </td>
                    <td>0.123</td>
                </tr>
                <tr>
                    <td title="Lorem ipsum dolor sit amet, consectetur adipisicing elit. Alias veniam repellendus iste eaque. Aliquam minima voluptas perferendis sunt qui rerum! Magni, at aspernatur asperiores placeat adipisci accusantium commodi repellendus sit?">
                        food ... Tell me more
                    </td>
                    <td>0.456</td>
                </tr>
                <tr>
                    <td title="Lorem ipsum dolor sit amet, consectetur adipisicing elit. Alias veniam repellendus iste eaque. Aliquam minima voluptas perferendis sunt qui rerum! Magni, at aspernatur asperiores placeat adipisci accusantium commodi repellendus sit?">
                        ! If you ... now!
                    </td>
                    <td>0.789</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>