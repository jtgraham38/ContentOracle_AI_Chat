<?php 

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// // Require Composer's autoload file
// require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Use statements for namespaced classes


//class that manages the vector table
class ContentOracle_VectorTable{
    private $table_name;
    private $db_version;
    private $plugin_prefix;

    public function __construct($plugin_prefix){
        global $wpdb;

        $this->plugin_prefix = $plugin_prefix;
        $this->table_name = $wpdb->prefix . $plugin_prefix . 'embeddings';
        $this->db_version = '1.0';

        //call initialize function
        $this->init();
    }

    //initialize the table
    public function init(){
        //if the table does not exist, create it
        if ($this->table_exists() == false){
            $this->create_table();
        }

    }

    //  \\  //  \\  //  \\ TABLE CRUD //  \\  //  \\  //  \\

    //get a vector by id
    public function id(int $id){
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE id = %d",
            $id
        ));
    }

    //get multiple vectors by id
    public function ids(array $ids){
        global $wpdb;

        $ids_str = implode(',', $ids);

        if (empty($ids_str)){
            return [];
        }

        return $wpdb->get_results(
            "SELECT * FROM $this->table_name WHERE id IN ($ids_str)"
        );

    }

    //get a vector by post id and sequence no
    public function get(int $post_id, int $sequence_no){
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE post_id = %d AND sequence_no = %d",
            $post_id,
            $sequence_no
        ));
    }

    //get most recently generated vector for a post
    public function get_latest_updated(int $post_id){
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE post_id = %d ORDER BY updated_at DESC LIMIT 1",
            $post_id
        ));
    }

    //get all vectors
    public function get_all(){
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM $this->table_name"
        );
    }

    //insert or update a vector
    public function upsert(int $post_id, int $sequence_no, string $vector, string $vector_type){
        global $wpdb;

        //check if the vector exists
        $vector_exists = $this->get($post_id, $sequence_no);

        //if the vector exists, update it
        if ($vector_exists > 0){
            $wpdb->update(
                $this->table_name,
                array(
                    'vector' => $vector,
                    'vector_type' => $vector_type
                ),
                array(
                    'post_id' => $post_id,
                    'sequence_no' => $sequence_no
                ),
                array(
                    '%s',
                    '%s'
                ),
                array(
                    '%d',
                    '%d'
                )
            );
        }
        //if the vector does not exist, insert it
        else{
            $wpdb->insert(
                $this->table_name,
                array(
                    'post_id' => $post_id,
                    'sequence_no' => $sequence_no,
                    'vector' => $vector,
                    'vector_type' => $vector_type
                ),
                array(
                    '%d',
                    '%d',
                    '%s',
                    '%s'
                )
            );
        }

        //return the id of the inserted/updated vector
        return $wpdb->insert_id;
    }

    //insert or update all vectors for a particular post
    //NOTE: the vectors array should be ordered from sequence no 0 to n
    public function insert_all(int $post_id, array $vectors){
        global $wpdb;

        //delete all existing vectors for the post
        $wpdb->delete(
            $this->table_name,
            array(
                'post_id' => $post_id
            ),
            array(
                '%d'
            )
        );

        //track inserted ids
        $inserted_ids = [];

        //insert the new vectors
        foreach ($vectors as $sequence_no => $vector){
            $inserted_ids[] = $this->upsert($post_id, $sequence_no, $vector['vector'], $vector['vector_type']);
        }

        return $inserted_ids;
    }

    //delete a vector by id
    public function delete(int $id){
        global $wpdb;

        $wpdb->delete(
            $this->table_name,
            array(
                'id' => $id
            ),
            array(
                '%d'
            )
        );
    }


    //  \\  //  \\  //  \\ MANAGE SQL TABLES/FUNCS //  \\  //  \\  //  \\
    //create the table
    public function create_table(){
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        //NOTE: sequence_no is the index of the vector in the document
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            sequence_no mediumint(9) NOT NULL,
            vector JSON NOT NULL,
            vector_type varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option($this->prefix . 'db_version', $this->db_version);
    }

    //drop the table
    public function drop_table(){
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS $this->table_name");
    }

    //check if the table exists
    public function table_exists(){
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") == $this->table_name;
    }

    //  \\  //  \\  //  \\ UTILS //  \\  //  \\  //  \\
    //get the table name
    public function get_table_name(){
        return $this->table_name;
    }

    //get the db version
    public function get_db_version(){
        return $this->db_version;
    }

    //get the prefix
    public function get_prefix(){
        return $this->prefix;
    }
}