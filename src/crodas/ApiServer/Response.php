<?php

namespace crodas\ApiServer;

use crodas\ApiServer;

class Response
{
    protected $server;
    protected $headers = array();
    protected $body;

    public function __construct(ApiServer $server, $body)
    {
        $this->server  = $server;
        $this->body    = $body; 
        $this->headers = array(
            'Access-Control-Allow-Origin: *',
            'Content-Type: application/json',
            'Access-Control-Allow-Credentials: false',
            'Access-Control-Allow-Methods: POST',
        );

        if ($server['session']->getAll() && $server['session']->getSessionId() !== $server['session_id']) {
            $this->headers[] = 'X-Session-Id: ' . $server['session']->getSessionId();
        }

        $keys = array();
        foreach (array_merge($this->headers, headers_list()) as $header) {
            list($key, ) = explode(":", $header);
            $keys[] = $key;
        }
        $this->headers[] = 'Access-Control-Allow-Headers: ' . implode(",", array_unique($keys));
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function send()
    {
        foreach ($this->headers as $header) {
            header($header);
        }
        echo json_encode($this->body);
    }
}
