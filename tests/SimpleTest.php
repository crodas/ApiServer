<?php

class SessionStorage implements crodas\ApiServer\SessionStorage
{
    static $xdata = array();
    public function __construct($id)
    {
        $this->id = $id ?: uniqid(true);
        $this->data = array();
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
            return null;
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
        $this->assertEquals($response, $server->handle($request)->getBody());
    }

    /**
     *  @dataProvider requests
     */
    public function testPreresponse(Array $request, Array $response)
    {
        $server = new crodas\ApiServer(__DIR__ . '/apps');
        $server['session_storage'] = 'SessionStorage';
        $GLOBALS['encrypt'] = true;
        do_encrypt($response);
        $this->assertEquals($response, $server->handle($request)->getBody());
    }

    protected function hasSessionHeader(Array $headers)
    {
        foreach ($headers as $header) {
            if (preg_match("/X-Session-Id/", $header)) {
                return true;
            }
        }

        return false;
    }

    public function testSession()
    {
        $GLOBALS['encrypt'] = false;

        $server = new crodas\ApiServer(__DIR__ . '/apps');
        $server['session_storage'] = 'SessionStorage';
        $response = $server->handle([['session', ['remember' => 1]]]);
        $this->assertEquals([null], $response->getBody());
        $this->assertTrue($this->hasSessionHeader($response->getHeaders()));
        $_SERVER['HTTP_X_SESSION_ID'] = $server['session']->getSessionId();

        $server = new crodas\ApiServer(__DIR__ . '/apps');
        $server['session_storage'] = 'SessionStorage';
        $response = $server->handle([['session', ['remember' => 2]]]);
        $this->assertEquals([1], $response->getBody());
        $this->assertFalse($this->hasSessionHeader($response->getHeaders()));

        $server = new crodas\ApiServer(__DIR__ . '/apps');
        $server['session_storage'] = 'SessionStorage';
        $response = $server->handle([['session', ['remember' => 3]]]);
        $this->assertEquals([2], $response->getBody());
        $this->assertFalse($this->hasSessionHeader($response->getHeaders()));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRunDifferentMethod()
    {
        $server = new crodas\ApiServer(__DIR__ . '/apps');
        ob_start();
        $server->run([]);
        $this->assertEquals(crodas\ApiServer::WRONG_REQ_METHOD, ob_get_clean());
        foreach (headers_list() as $header) {
            var_dump($header);exit;
        }
    }
}
