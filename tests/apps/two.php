<?php

/**
 *  @preResponse
 */
function do_encrypt($response)
{
    if (!empty($GLOBALS['encrypt'])) {
        return base64_encode(json_encode($response));
    }
}

/**
 *  @preRoute yyy
 *  @xxx
 */
function do_it()
{
}

/**
 *  @preRoute
 */
function all_request(Array $args)
{
    $args['added'] = 1;
    return $args;
}
