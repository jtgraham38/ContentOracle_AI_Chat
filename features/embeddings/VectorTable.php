<?php 

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// // Require Composer's autoload file
// require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

// Use statements for namespaced classes
//custom heap class, used in candidate generation
class ContentOracleCandidateMinHeap extends SplMinHeap{
    protected function compare($a, $b): int{
        return $b['hamming_distance'] <=> $a['hamming_distance'];
    }
}

// another custom heap class, for getting most similar vectors
class ContentOracleRerankMaxHeap extends SplMinHeap{
    protected function compare($a, $b): int{
        return $a['cosine_similarity'] <=> $b['cosine_similarity'];
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
        $binary_code = $this->hex_to_binary( $this->get_binary_code($vector) );

        //  \\  //  \\  CANDIDATE GENERATION //  \\  //  \\  //
        //get all vectors from the database
        $candidates_query = "select id, binary_code from $this->table_name";
        $embeddings = $wpdb->get_results($candidates_query);

        //get the n vectors with the smallest hamming distance
        $closest_candidates = new ContentOracleCandidateMinHeap();
        //add each vector to my minheap
        foreach ($embeddings as $embedding){
            //compute the hamming distance between the embedding and the user query vector
            $embedding_binary_code = $this->hex_to_binary( $embedding->binary_code);
            $hamming_distance = 0;
            for ($i = 0; $i < $this->vector_length; $i++){
                if ($binary_code[$i] != $embedding_binary_code[$i]){
                    $hamming_distance++;
                }
            }

            //get id and binary code
            $closest_candidates->insert([
                'id' => $embedding->id,
                'hamming_distance' => floatval($hamming_distance)
            ]);
        }
        
        //get the 4n closest candidates out of the heap
        $candidates = [];
        for ($i = 0; $i < 4*$n; $i++){
            if ($closest_candidates->count() < 1) break;
            $candidates[] = $closest_candidates->extract();
        }

        //get the ids of the candidates
        $candidate_ids = array_map(function($candidate){ return $candidate['id']; }, $candidates);
        $candidates_str = implode(',', $candidate_ids);

        //  \\  //  \\  RERANKING //  \\  //  \\  //
        //find the candidates with the lowest cosine distance to the query vector using php
        $reranked_candidates = new ContentOracleRerankMaxHeap();
        
        //get all the candidates
        $sql = "SELECT id, magnitude, vector FROM $this->table_name WHERE id IN ($candidates_str)";
        $candidates = $wpdb->get_results($sql);

        //parse the vector
        $vector = json_decode($vector, true);

        //compute the cosine similarity of each candidate with the user query vector
        foreach ($candidates as $candidate){
            //decode the vector
            $candidate_vector = json_decode($candidate->vector, true);

            //calculate the cosine similarity
            $cosine_similarity = 0;
            for ($i = 0; $i < count( $candidate_vector ); $i++){
                $cosine_similarity += floatval( $vector[$i] ) * floatval( $candidate_vector[$i] );
            }
            $cosine_similarity /= ($candidate->magnitude * $this->magnitude($vector)) + 0.000000000001;

            //put the candidate in the reranked max heap
            $reranked_candidates->insert([
                'id' => $candidate->id,
                'cosine_similarity' => $cosine_similarity
            ]);

        }

        //get the n most similar candidates
        $reranked_candidates_arr = [];
        for ($i = 0; $i < $n; $i++){
            if ($reranked_candidates->count() < 1) break;
            $reranked_candidates_arr[] = $reranked_candidates->extract();
        }

        //get post titles with candidates
        foreach ($reranked_candidates_arr as &$candidate){
            $candidate['post_title'] = get_the_title($candidate['id']);
        }
        echo json_encode($reranked_candidates_arr);
        

        //return the ids of the reranked candidates
        $reranked_ids = array_map(function($candidate){ return $candidate['id']; }, $reranked_candidates_arr);

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
                "UPDATE $this->table_name SET vector = %s, normalized_vector = %s, vector_type = %s, binary_code = %s WHERE post_id = %d AND sequence_no = %d",
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
                "INSERT INTO $this->table_name (post_id, sequence_no, vector, normalized_vector, vector_type, binary_code, magnitude) VALUES (%d, %d, %s, %s, %s, %s , %f)",
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
            binary_code BLOB NOT NULL,
            magnitude float NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;"/*, $this->vector_length/8 * 2*/);
        //^ binary code is the binary representation of the vector, length is vector_length/8 for 8 bits per byte
        //it is divided by 8 because 1 byte = 8 bits,
        //and each character in the binary code is a hexadecimal character representing 4 bits
        //each hexadecimal character represents the signs of 4 values in the vector
        // 4 bits/char * 2 chars/byte = 8 bits/byte
        //so divide the length of the binary code in bits by 8, and multiply by 2 to get the length in bytes

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

        return $this->vector_to_hex($vector);
    }

    //convert a vector to binary
    //each hexadecimal character represents the signs of 4 values in the vector
    public function vector_to_hex(array $vector_arr): string{    //hexadecimal string
        $binary_code = '';

        //1 if value is greater than 0, 0 otherwise
        foreach ($vector_arr as $value){
            $binary_code .= $value > 0 ? '1' : '0';
        }

        //convert binary to bytes
        return $this->binary_to_hex($binary_code);
    }

    public function hex_to_binary(string $hex): string{
        $binary_code = '';
        foreach (str_split($hex) as $char){
            $binary_code .= str_pad(decbin(hexdec($char)), 4, '0', STR_PAD_LEFT);
        }
        return $binary_code;
    }

    public function binary_to_hex(string $binary): string{
        $hex_code = '';
        foreach (str_split($binary, 4) as $halfByte){
            $hex_code .= strtoupper(dechex(bindec($halfByte)));
        }
        return $hex_code;
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