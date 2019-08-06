<?php


namespace App\Helpers;

// standard api response
class ApiResponse
{
    private $data;
    private $message;

    public function __construct($data, $message = null)
    {
       $this->data      = $data;
       $this->message   = $message;
    }

    public function get()
    {
        return [
            'data'      => $this->data ?? null,
            'message'   => $this->message ?? ''
        ];
    }

    public function getError()
    {
        return [
            'reason'    => $this->data,
            'message'   => $this->message
        ];
    }
}
