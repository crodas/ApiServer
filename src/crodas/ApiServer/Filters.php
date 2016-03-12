<?php

namespace crodas\ApiServer;

/**
 *  @preRoute
 */
function filter_auth(Array $args, Array $request, $server, $session)
{
    if (empty($session)) {
        throw new RuntimeException("{$server->api} requires a valid session");
    }
}
