<?php 

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// // Require Composer's autoload file
// require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Use statements for namespaced classes
//custom heap class, used in candidate generation
class ContentOracleMinHeap extends SplMinHeap{
    protected function compare($a, $b): int{
        return $a['hamming_distance'] <=> $b['hamming_distance'];
    }
}

//class that manages the vector table
class ContentOracle_VectorTable{
    private $table_name;
    private $db_version;
    private $plugin_prefix;
    private $vector_length;

    public function __construct($plugin_prefix, int $vector_length=1024){
        global $wpdb;

        $this->plugin_prefix = $plugin_prefix;
        $this->table_name = $wpdb->prefix . $plugin_prefix . 'embeddings';
        $this->db_version = '1.0';
        $this->vector_length = $vector_length;

        //call initialize function
        $this->init();
    }

    //initialize the table
    public function init(): void{
        //if the table does not exist, create it
        if ($this->table_exists() == false){
            $this->create_table();
        }

    }

    //  \\  //  \\  //  \\ TABLE CRUD //  \\  //  \\  //  \\

    //find the n most similar vectors to a given vector
    public function search($vector, int $n=5): array{
        global $wpdb;

        //convert from array to string
        if (is_array($vector)){
            $vector = json_encode($vector);
        }

        //get the binary code
        $binary_code = $this->get_binary_code($vector);

        //NOTE
        //NOTE
        //NOTE
        //NOTE: the below method for computing hamming distance in db dosen't work
        //NOTE: because different databases (like mysql and mariadb handle binary operations differently)
        //TODO: I need to find a sql query that will work in both mysql and mariadb bc wordpress supports both
        //find candidates by computing the hamming distance, and taking the n closest
        // $candidates_query = "
        // SELECT 
        // id,
        // BIT_COUNT(BINARY binary_code ^ UNHEX(BINARY %s)) AS hamming_distance
        // FROM $this->table_name
        // ORDER BY hamming_distance ASC
        // LIMIT $n
        // ";
        // $candidates = $wpdb->get_results($wpdb->prepare($candidates_query, $binary_code));
        // $candidate_ids = [];
        // foreach ($candidates as $candidate){
        //     $candidate_ids[] = $candidate->id;
        // }
        // $candidates_str = implode(',', $candidate_ids);
        // $normalized_vector = json_encode($this->normalize(json_decode($vector, true)));

        // echo "<pre>";
        // var_dump($candidates);
        // echo "</pre>";

        //get all vectors from the database
        $candidates_query = "select id, binary_code from $this->table_name";
        $embeddings = $wpdb->get_results($candidates_query);

        //get the n vectors with the smallest hamming distance
        $closest_candidates = new ContentOracleMinHeap();
        //add each vector to my minheap
        foreach ($embeddings as $embedding){
            //compute the hamming distance between the embedding and the user query vector
            $hamming_distance = substr_count( hex2bin($binary_code) ^ $embedding->binary_code, "1");

            //get id and binary code
            $closest_candidates->insert([
                'id' => $embedding->id,
                'hamming_distance' => $hamming_distance
            ]);
        }
        
        //get the n closest candidates out of the heap
        $candidates = [];
        for ($i = 0; $i < $n; $i++){
            if ($closest_candidates->count() < 1) break;
            $candidates[] = $closest_candidates->extract();
        }

        //get the ids of the candidates
        $candidate_ids = array_map(function($candidate){ return $candidate['id']; }, $candidates);
        $candidates_str = implode(',', $candidate_ids);

        //normalize the user query vector
        $normalized_vector = json_encode($this->normalize(json_decode($vector, true)));

        //using only the candidates found, rerank the candidates in the database
        //NOTE: currently,this query computes the cosine similarity of each candidate with the user query vector
        $rerank_query = 
        "SELECT v.id, (SUM(q_json.element * db_json.element) / (v.magnitude * %f)) AS cosine_similarity
            FROM $this->table_name v
            JOIN JSON_TABLE(v.normalized_vector, '$[*]' COLUMNS (idx FOR ORDINALITY, element DOUBLE PATH '$')) db_json 
                ON 1 = 1  
            JOIN JSON_TABLE(%s, '$[*]' COLUMNS (idx FOR ORDINALITY, element DOUBLE PATH '$')) q_json 
                ON q_json.idx = db_json.idx 
            WHERE v.id IN ($candidates_str)
            GROUP BY v.id
            ORDER BY cosine_similarity DESC
            LIMIT $n
        ";
        $reranked_candidates = $wpdb->get_results($wpdb->prepare($rerank_query,
            $this->magnitude(json_decode($vector, true)),   //enter magnitude of user query vector
            $normalized_vector ,                                         //enter user query vector
        ));

        if (empty($reranked_candidates)){
            return [];
        }
        $reranked_ids = array_map(function($candidate){ return $candidate->id; }, $reranked_candidates);

        return $reranked_ids;
    }

    //get a vector by id
    public function id(int $id): object | null{
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE id = %d",
            $id
        ));
    }

    //get multiple vectors by id
    public function ids(array $ids): array{
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
    public function get(int $post_id, int $sequence_no): object | null{
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE post_id = %d AND sequence_no = %d",
            $post_id,
            $sequence_no
        ));
    }

    //get most recently generated vector for a post
    public function get_latest_updated(int $post_id): object | null{
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $this->table_name WHERE post_id = %d ORDER BY updated_at DESC LIMIT 1",
            $post_id
        ));
    }

    //get all vectors
    public function get_all(): array{
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

        //get the binary code
        $binary_code = $this->get_binary_code($vector);

        //get the normalized vector
        $normalized_vector = json_encode($this->normalize(json_decode($vector, true)));

        //if the vector exists, update it with a sql statement (to use the UNHEX function)
        if ($vector_exists > 0){
            $wpdb->query($wpdb->prepare(
                "UPDATE $this->table_name SET vector = %s, normalized_vector = %s, vector_type = %s, binary_code = UNHEX(BINARY %s ) WHERE post_id = %d AND sequence_no = %d",
                $vector,
                $normalized_vector,
                $vector_type,
                $binary_code,
                $post_id,
                $sequence_no
            ));

            $ret_id = $vector_exists->id;
        }
        //if the vector does not exist, insert it
        else{
            //insert with a sql statement (to use the UNHEX function)
             $wpdb->query($wpdb->prepare(
                "INSERT INTO $this->table_name (post_id, sequence_no, vector, normalized_vector, vector_type, binary_code, magnitude) VALUES (%d, %d, %s, %s, %s, UNHEX(BINARY %s ), %f)",
                $post_id,
                $sequence_no,
                $vector,
                $normalized_vector,
                $vector_type,
                $binary_code,
                $this->magnitude(json_decode($vector, true))
            ));

            //return the id of the inserted vector
            $ret_id = $wpdb->insert_id;
        }

        //return the id of the inserted/updated vector
        return $ret_id;
    }

    //insert or update all vectors for a particular post
    //NOTE: the vectors array should be ordered from sequence no 0 to n
    public function insert_all(int $post_id, array $vectors): array{
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
    public function delete(int $id): void{
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
    public function create_table(): void{
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        //NOTE: sequence_no is the index of the vector in the document
        $sql = sprintf("CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            sequence_no mediumint(9) NOT NULL,
            vector JSON NOT NULL,
            normalized_vector JSON NOT NULL,
            vector_type varchar(255) NOT NULL,
            binary_code BINARY(%d) NOT NULL,
            magnitude float NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;", $this->vector_length/8);
        //^ binary code is the binary representation of the vector, length is vector_length/8 for 8 bits per byte
        //it is divided by 8 because 1 byte = 8 bits,
        //and each character in the binary code is a hexadecimal character representing 4 bits
        //each hexadecimal character represents the signs of 4 values in the vector
        // 4 bits/char * 2 chars/byte = 8 bits/byte
        //so divide the length of the binary code in bits by 8 to get the length in bytes

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option($this->prefix . 'db_version', $this->db_version);
    }

    //drop the table
    public function drop_table(): void{
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS $this->table_name");
    }

    //check if the table exists
    public function table_exists(): bool{
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") == $this->table_name;
    }

    //  \\  //  \\  //  \\  BINARY CODES //  \\  //  \\  //  \\
    //get the binary representation of a vector
    public function get_binary_code( $vector ): string{    //hexadecimal string
        //convert the vecor to an array, if it is not one
        if (!is_array($vector)){
            $vector = json_decode($vector, true);
        }

        return $this->vector_to_binary($vector);
    }

    //convert a vector to binary
    //each hexadecimal character represents the signs of 4 values in the vector
    public function vector_to_binary(array $vector_arr): string{    //hexadecimal string
        $binary_code = '';

        //1 if value is greater than 0, 0 otherwise
        foreach ($vector_arr as $value){
            $binary_code .= $value > 0 ? '1' : '0';
        }

        //convert binary to bytes
        $binhexCode = "";
        foreach (str_split($binary_code, 4) as $halfByte){
            $binhexCode .= strtoupper(dechex(bindec($halfByte)));
        }

        return $binhexCode;
    }

    //  \\  //  \\  //  \\ UTILS //  \\  //  \\  //  \\
    //normalize a vector
    public function normalize($vector): array{
        $mag = $this->magnitude($vector);
        $magnitude = $mag == 0 ? 1e-10 : $mag;
        return array_map(function($value) use ($magnitude){
            return $value / $magnitude;
        }, $vector);
    }


    //get the table name
    public function get_table_name(): string{
        return $this->table_name;
    }

    //get the db version
    public function get_db_version(): string{
        return $this->db_version;
    }

    //get the prefix
    public function get_prefix(): string{
        return $this->prefix;
    }

    //get vector magnitude
    public function magnitude($vector): float{
        $magnitude = 0;
        foreach ($vector as $value){
            $magnitude += $value * $value;
        }
        return sqrt($magnitude);
    }
}