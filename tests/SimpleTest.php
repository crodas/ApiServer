<?php

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
    public function testRouting(Array $request, $response)
    {
        $server = new crodas\ApiServer(__DIR__ . '/apps');
        $server['session_storage'] = 'SessionStorage';
        $this->assertEquals($response, $server->handle($request)->getBody());
    }

    /**
     *  @dataProvider requests
     */
    public function testPreresponse(Array $request, $response)
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
        $response = $server->handle([['xxx', []]]);
        $this->assertEquals([['foo' => 'bar']], $response->getBody());
        $this->assertFalse($this->hasSessionHeader($response->getHeaders()));


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
    }
}
