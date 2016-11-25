<?php

namespace HNG\Http\Exception;

class InvalidRequest extends \Exception {

    protected $response;

    public function __construct($response, $message = null, $code = 0, Exception $previous = null)
    {
        $this->response = $response;

        return parent::__construct($message, $code, $previous);
    }

    public function getRequestResponse()
    {
        return $this->response;
    }
}