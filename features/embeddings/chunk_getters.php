<?php

//exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../../vendor/autoload.php';

use NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer;


//function to get the section of the post body that an embedding is for
//0-indexed
function token256_get_chunk($content, $embedding_number){
    $chunk_size = 256;
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