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
    public function testRouting(Array $request, Array $response)
    {
        $server = new crodas\ApiServer(null, __DIR__ . '/apps');
        $this->assertEquals($server->processRequest($request), $response);
    }
}
