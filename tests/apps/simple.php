<?php

/**
 *  @API xxx
 */
function apps(array $args, $session)
{
    if (empty($args['added'])) {
        throw new RuntimeException();
    }

    return ['foo' => 'bar'];
}
