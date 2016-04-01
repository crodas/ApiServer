<?php

/**
 *  @API session
 */
function session(array $args, $server)
{
    $ret = $server['session']->get('remember');
    $server['session']->set('remember', $args['remember']);

    return $ret;
}
