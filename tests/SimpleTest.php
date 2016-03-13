<?php

class SessionStorage implements crodas\ApiServer\SessionStorage
{
    public function __construct($id)
    {
        $this->id = $id ?: uniqid(true);
        $this->data = array();
        $this->file = __DIR__ . '/tmp/' . $this->id . '.txt';
        if (is_file($this->file)) {
            $this->data = unserialize(file_get_contents($this->file));
        }
    }

    public function __destruct()
    {
        file_put_contents($this->file, serialize($this->data));
    }
    
    public function get($name)
    {
        if (!array_key_exists($name, $this->data)) {
            return null;
        }
        return $this->data[$name];
    }

    public function set($name, $value)
    {
        $this->data[$name] = $value;
        return $this;
    }

    public function getSessionId()
    {
        file_put_contents($this->file, serialize($this->data));
        return $this->id;
    }
}

class SimpleTest extends \phpunit_framework_testcase
{
    public static function requests()
    {
        $args = array();
        foreach (glob(__DIR__ . '/features/*.json') as $file) {
            $arg = json_decode(file_get_contents($file), true);
            $args[] = [$arg['request'], $arg['response']];
        }
        return $args;
    }

    /**
     *  @dataProvider requests
     */
    public function testRouting(Array $request, Array $response)
    {
        $server = new crodas\ApiServer(__DIR__ . '/apps');
        $server['session_storage'] = 'SessionStorage';
        $this->assertEquals($response, $server->processRequest($request));
    }

    /**
     *  @dataProvider requests
     */
    public function testPreresponse(Array $request, Array $response)
    {
        $server = new crodas\ApiServer(__DIR__ . '/apps');
        $server['session_storage'] = 'SessionStorage';
        $GLOBALS['encrypt'] = true;
        $this->assertEquals(do_encrypt($response), $server->processRequest($request));
    }

    public function testSession()
    {
        $server = new crodas\ApiServer(__DIR__ . '/apps');
        $GLOBALS['encrypt'] = false;
        $server['session_storage'] = 'SessionStorage';
        $this->assertEquals([null], $server->processRequest([['session', ['remember' => 1]]]));
        $_GET['sessionId'] = $server['session']->getSessionId();

        $server = new crodas\ApiServer(__DIR__ . '/apps');
        $GLOBALS['encrypt'] = false;
        $server['session_storage'] = 'SessionStorage';
        $this->assertEquals([1], $server->processRequest([['session', ['remember' => 2]]]));

        $server = new crodas\ApiServer(__DIR__ . '/apps');
        $GLOBALS['encrypt'] = false;
        $server['session_storage'] = 'SessionStorage';
        $this->assertEquals([1], $server->processRequest([['session', ['remember' => 3]]]));
    }
}
