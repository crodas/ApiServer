<?php

namespace crodas;

use FunctionDiscovery;
use RuntimeException;
use Exception;
use Pimple;

class ApiServer extends Pimple
{
    const WRONG_REQ_METHOD = -1;
    const INVALID_SESSION  = -2;
    const INTERNAL_ERROR = -3;

    protected $apps;

    public function __construct($dirs)
    {
        $dirs   = (Array)$dirs;
        $dirs[] = __DIR__;
        $loader = new FunctionDiscovery($dirs);
        $this->apps   = $loader->getFunctions('api');
        $this->events = array(
            'initRequest'   => $loader->getFunctions('initRequest'),
            'preRoute'      => $loader->getFunctions('preRoute'),
            'postRoute'     => $loader->getFunctions('postRoute'),
            'preResponse'   => $loader->getFunctions('preResponse'),
        );
        $this['session_storage'] = __NAMESPACE__ . '\ApiServer\SessionNative';
        $this['session'] = $this->share(function($service) {
            return new $service['session_storage'](!empty($_GET['sessionId']) ? $_GET['sessionId'] :  null);
        });
    }

    protected function runEvent($event, $function, &$argument)
    {
        $ref = array();
        foreach ($argument as $id => $arg) {
            $ref[$id] = &$argument[$id];
        }

        foreach ($this->events[$event] as $name => $annArgs) {
            if ($event === 'initRequest' && is_string($name) ) {
                $args = array();
                foreach ($argument as $id => $arg) {
                    if ($arg[0] === $name) {
                        $args[] = &$argument[$id][1];
                    }
                }
                if (empty($args)) {
                    continue;
                }
                $annArgs($args, $this, $function ? $function->getAnnotation($name) : null);
                continue;
            }
            if (!$function || (is_numeric($name) || $function->hasAnnotation($name))) {
                $annArgs->call(array(&$argument, $this, $function ? $function->getAnnotation($name) : null));
            }
        }
    }

    public function processRequest(Array $request)
    {
        $this->runEvent('initRequest', NULL, $request);

        $responses = array();
        foreach ($request as $object) {
            try {
                $this->apiCall = $object[0];
                if (empty($this->apps[$this->apiCall])) {
                    throw new RuntimeException($object[0] . " is not a valid handler");
                }
                $function = $this->apps[$this->apiCall];
                $argument = $object[1];

                $this->runEvent('preRoute', $function, $argument);
                $response = $function($argument, $this);
                $this->runEvent('postRoute', $function, $argument);

                $responses[] = $response;
            } catch (Exception $e) {
                $responses[] = array('error' => true, 'text' => $e->getMessage());
            }
        }

        $this->runEvent('preResponse', NULL, $responses);

        return $responses;
    }

    protected function sendHeaders()
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json");
        header('Access-Control-Allow-Credentials: false');
        header('Access-Control-Allow-Methods: POST');
        header("X-Session-Id: {$this['session']->getSessionId()}");

        $keys = array();
        foreach (headers_list() as $header) {
            list($key, ) = explode(":", $header);
            $keys[] = $key;
        }
        header('Access-Control-Allow-Headers: ' . implode(",", $keys));
    }

    public function main()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->sendHeaders();
            echo self::WRONG_REQ_METHOD;
            exit;
        }

        $request   = json_decode(file_get_contents('php://input'), true);
        $responses = $this->processRequest($request);

        $this->sendHeaders();
        echo json_encode($responses);
    }
}
