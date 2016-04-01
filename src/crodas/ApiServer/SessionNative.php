<?php

namespace crodas\ApiServer;

class SessionNative implements SessionStorage
{
    public function __construct($id)
    {
        ini_set('session.use_cookies', 0);
        ini_set('session.use_only_cookies', 0);
        ini_set('session.cache_limiter', '');

        if ($id) {
            session_id($id);
        }
        session_start();
        $this->sessionId = session_id();
    }

    public function set($name, $value)
    {
        $_SESSION[$name] = $value;

        return $this;
    }

    public function destroy()
    {
        session_destroy();
        $this->sessionId = null;
    }

    public function getAll()
    {
        return $_SESSION;
    }

    public function get($name)
    {
        if (!array_key_exists($name, $_SESSION)) {
            return;
        }

        return $_SESSION[$name];
    }

    public function getSessionId()
    {
        return $this->sessionId;
    }
}
