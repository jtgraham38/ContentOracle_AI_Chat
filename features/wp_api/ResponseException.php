<?php

//exception class that remembers the response, to allow the coai api response to bubble up through the stack
class ContentOracle_ResponseException extends Exception{
    public $response;
    public $error_source;
    public function __construct($message = "", $response = null, $error_source = "wp"){
        parent::__construct($message);

        $this->response = json_decode($response['body'], true);
        $this->error_source = $error_source;
    }
}