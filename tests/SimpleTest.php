<?php

class SessionStorage implements crodas\ApiServer\SessionStorage
{
    public static $xdata = [];

    public function __construct($id)
    {
        $this->id = $id ?: uniqid(true);
        $this->data = [];
        if (!empty(self::$xdata[$this->id])) {
            $this->data = self::$xdata[$this->id];
        }
    }

    public function __destruct()
    {
        self::$xdata[$this->id] = $this->data;
    }

    public function get($name)
    {
        if (!array_key_exists($name, $this->data)) {
            return;
        }

        return $this->data[$name];
    }

    public function set($name, $value)
    {
        $this->data[$name] = $value;

        return $this;
    }

    public function getAll()
    {
        return $this->data;
    }

    public function destroy()
    {
        $this->data = [];
    }

    public function getSessionId()
    {
        self::$xdata[$this->id] = $this->data;

        return $this->id;
    }
}

class SimpleTest extends \phpunit_framework_testcase
{
    public static function requests()
    {
        $args = [];
        foreach (glob(__DIR__.'/features/*.json') as $file) {
            $arg = json_decode(file_get_contents($file), true);
            $args[] = [$arg['request'], $arg['response']];
        }

        return $args;
    }

    /**
     *  @dataProvider requests
     */
    public function testRouting(array $request, array $response)
    {
        $server = new crodas\ApiServer(__DIR__.'/apps');
        $server['session_storage'] = 'SessionStorage';
        $this->assertEquals($response, $server->processRequest($request));
    }

    /**
     *  @dataProvider requests
     */
    public function testPreresponse(array $request, array $response)
    {
        $server = new crodas\ApiServer(__DIR__.'/apps');
        $server['session_storage'] = 'SessionStorage';
        $GLOBALS['encrypt'] = true;
        do_encrypt($response);
        $this->assertEquals($response, $server->processRequest($request));
    }

    public function testSession()
    {
        $server = new crodas\ApiServer(__DIR__.'/apps');
        $GLOBALS['encrypt'] = false;
        $server['session_storage'] = 'SessionStorage';
        $this->assertEquals([null], $server->processRequest([['session', ['remember' => 1]]]));
        $_SERVER['HTTP_X_SESSION_ID'] = $server['session']->getSessionId();

        $server = new crodas\ApiServer(__DIR__.'/apps');
        $GLOBALS['encrypt'] = false;
        $server['session_storage'] = 'SessionStorage';
        $this->assertEquals([1], $server->processRequest([['session', ['remember' => 2]]]));

        $server = new crodas\ApiServer(__DIR__.'/apps');
        $GLOBALS['encrypt'] = false;
        $server['session_storage'] = 'SessionStorage';
        $this->assertEquals([1], $server->processRequest([['session', ['remember' => 3]]]));
    }
}
