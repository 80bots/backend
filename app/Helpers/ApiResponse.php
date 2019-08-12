<?php


namespace App\Helpers;

// standard api response
class ApiResponse
{
    private $data;
    private $message;

    public function __construct($data = null, $message = '')
    {
       $this->data      = $data;
       $this->message   = $message;
    }

    public function get()
    {
        $response = [];

        if (! empty($this->message)) {
            $response['message'] = $this->message;
        }

        if (! empty($this->data)) {
            foreach ($this->data as $key => $data) {
                $response[$key] = $data;
            }
        }

        return $response;
    }

    public function getError()
    {
        return [
            'reason'    => $this->data ?? null,
            'message'   => $this->message ?? ''
        ];
    }
}
