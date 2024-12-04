<?php 

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Require Composer's autoload file
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Use statements for namespaced classes


//class that manages the vector table
class ContentOracle_EmbeddingsTable{
    private $table_name;
    private $cosim_function_name;
    private $db_version;
    private $prefix;

    //a function to calculate the cosine similarity of two vectors, with work offloaded to db
    const SQL_COSIM_FUNCTION = "
CREATE FUNCTION COSIM(v1 JSON, v2 JSON) RETURNS FLOAT DETERMINISTIC BEGIN DECLARE sim FLOAT DEFAULT 0; DECLARE i INT DEFAULT 0; DECLARE len INT DEFAULT JSON_LENGTH(v1); IF JSON_LENGTH(v1) != JSON_LENGTH(v2) THEN RETURN NULL; END IF; WHILE i < len DO SET sim = sim + (JSON_EXTRACT(v1, CONCAT('$[', i, ']')) * JSON_EXTRACT(v2, CONCAT('$[', i, ']'))); SET i = i + 1; END WHILE; RETURN sim; END";


    public function __construct($prefix){
        $this->prefix = $prefix;
        $this->table_name = $prefix . 'contentoracle_embeddings';
        $this->cosim_function_name = $prefix . 'CONTENTORACLE_COSIM';
        $this->db_version = '1.0';
    }

    //initialize the table
    public function init(){
        //if the table does not exist, create it
        if ($this->table_exists() == false){
            $this->create_table();
        }

        //if the cosine similarity function does not exist, create it
        if ($this->cosim_exists() == false){
            $this->create_cosim();
        }

    }

    //create the table
    public function create_table(){
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        //NOTE: sequence_no is the index of the vector in the document
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            sequence_no mediumint(9) NOT NULL,
            vector text NOT NULL,
            vector_type varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option($this->prefix . 'db_version', $this->db_version);
    }

    //create the cosine similarity function in mysql
    public function create_cosim(){
        global $wpdb;
        $wpdb->query(self::SQL_COSIM_FUNCTION);
    }

    //drop the table
    public function drop_table(){
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS $this->table_name");
    }

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

    //check if the table exists
    public function table_exists(){
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") == $this->table_name;
    }

    //check if the cosine similarity function exists
    public function cosim_exists(){
        global $wpdb;
        return $wpdb->get_var("SHOW FUNCTION STATUS WHERE db = DATABASE() AND name = 'CONTENTORACLE_COSIM'") == 'CONTENTORACLE_COSIM';
    }
}