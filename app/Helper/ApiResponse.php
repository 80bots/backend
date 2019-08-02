<?php


namespace App\Helper;

// standard api response
class ApiResponse
{
    private $data;
    private $message;

    public function __construct($data, $message = null)
    {
       $this->data = $data;
       $this->message = $message;
    }

    public function get()
    {
        $response = [];
        if($this->data) $response['data'] = $this->data;
        if($this->message) $response['message'] = $this->message;
        return $response;
    }

    public function getError()
    {
        return [
            'reason' => $this->data,
            'message' => $this->message
        ];
    }
}
