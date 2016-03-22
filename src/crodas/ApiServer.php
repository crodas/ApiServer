<?php

namespace crodas;

use FunctionDiscovery;
use RuntimeException;
use Exception;
use Pimple;
use FunctionDiscovery\TFunction;

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
            return new $service['session_storage'](!empty($_SERVER['HTTP_X_SESSION_ID']) ? $_SERVER['HTTP_X_SESSION_ID'] :  null);
        });
    }

    /**
     *  Run a given event
     *  
     *  @param string       $event      Event name to run
     *  @param mixed        &$argument  Arguments  
     *  @param TFunction    $function   Function wrapper (FunctionDiscovery\TFunction)
     *
     *  @return null
     */
    protected function runEvent($event, &$argument, TFunction $function = null)
    {
        foreach ($this->events[$event] as $name => $aArguments) {
            if ($event === 'initRequest' && is_string($name) ) {
                $arguments = array();
                foreach ($argument as $id => $arg) {
                    if ($arg[0] === $name) {
                        $arguments[] = &$argument[$id][1];
                    }
                }
                if (empty($arguments)) {
                    continue;
                }
                $aArguments($arguments, $this, $function ? $function->getAnnotation($name) : null);
                continue;
            }
            if (!$function || (is_numeric($name) || $function->hasAnnotation($name))) {
                $aArguments->call(array(&$argument, $this, $function ? $function->getAnnotation($name) : null));
            }
        }
    }

    /**
     *  processRequest
     *  
     *  @param  Array $requests     Array with all the requests
     *
     *  @return Array return all the responses
     */
    public function processRequest(Array $requests)
    {
        $this->runEvent('initRequest', $requests);

        $responses = array();
        foreach ($requests as $request) {
            try {
                $this['request'] = ['function' => $request[0], 'arguments' => &$request[1]];

                if (empty($this->apps[$request[0]])) {
                    throw new RuntimeException($request[0] . " is not a valid handler");
                }
                $function = $this->apps[$request[0]];

                $this->runEvent('preRoute', $request[1], $function);
                $response = $function->call(array(&$request[1], $this));
                $this->runEvent('postRoute', $request[1], $function);

                $responses[] = $response;
            } catch (Exception $e) {
                $responses[] = array('error' => true, 'text' => $e->getMessage());
            }
        }

        $this->runEvent('preResponse', $responses);

        return $responses;
    }

    /**
     *  Send all the HTTP response headers
     */
    protected function sendHeaders()
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json");
        header('Access-Control-Allow-Credentials: false');
        header('Access-Control-Allow-Methods: POST');
        if ($this['session'] && $this['session']->getSessionId() !== $_SERVER['HTTP_X_SESSION_ID']) {
            header("X-Session-Id: {$this['session']->getSessionId()}");
        }

        $keys = array('X-Session-Id', 'X-Destroy-Session-Id');
        foreach (headers_list() as $header) {
            list($key, ) = explode(":", $header);
            $keys[] = $key;
        }
        header('Access-Control-Allow-Headers: ' . implode(",", array_unique($keys)));
    }

    public function main()
    {
        $this->run();
    }

    public function run()
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
