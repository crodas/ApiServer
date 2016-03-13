<?php

namespace crodas\ApiServer;


interface SessionStorage
{
    public function __construct($id);

    public function set($name, $value);

    public function get($name);

    public function getSessionId();

}
