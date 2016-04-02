<?php

namespace crodas\ApiServer\Session;


interface Storage
{
    public function __construct($id);

    public function set($name, $value);

    public function get($name);

    public function destroy();

    public function getAll();

    public function getSessionId();

}
