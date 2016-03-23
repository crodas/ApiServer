<?php

namespace crodas;

use FunctionDiscovery;
use RuntimeException;
use Exception;
use Pimple;
use FunctionDiscovery\TFunction;
use crodas\ApiServer\Response;

class ApiServer extends Pimple
{
    const WRONG_REQ_METHOD  = -1;
    const INVALID_SESSION   = -2;
    const INTERNAL_ERROR    = -3;

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
        $this['session_id'] = $this->share(function() {
            return !empty($_SERVER['HTTP_X_SESSION_ID']) ? $_SERVER['HTTP_X_SESSION_ID'] :  null;
        });
        $this['session'] = $this->share(function($service) {
            return new $service['session_storage']($service['session_id']);
        });
    }

    /**
     *  Run a given event
     *  
     *  @param string       $event      Event name to run
     *  @param mixed        &$argument  Arguments  
     *  @param TFunction    $function   Function wrapper (FunctionDiscovery\TFunction)
     *
     *  @return {crodas\ApiServer\Response}     Response object
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
     *  handle
     *  
     *  @param  Array $requests     Array with all the requests
     *
     *  @return Array return all the responses
     */
    public function handle(Array $requests)
    {
        $requests = $requests ?: json_decode(file_get_contents('php://input'), true);

        if (empty($requests)) {
            return new Response($this, self::WRONG_REQ_METHOD);
        }

        try {
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
        } catch (Exception  $e) {
            $responses = self::INTERNAL_ERROR;
        }

        return new Response($this, $responses);
    }

    public function run(Array $requests = array())
    {
        return $this->handle($requests)->send();
    }
}
