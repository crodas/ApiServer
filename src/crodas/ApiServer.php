<?php

namespace crodas;

use FunctionDiscovery;
use ServiceProvider\Provider;
use ActiveMongo2;
use MongoClient;
use RuntimeException;
use Exception;

class ApiServer
{
    const WRONG_REQ_METHOD = -1;
    const INVALID_SESSION  = -2;

    protected $db;
    protected $apps;
    protected $sessionId;
    protected $sessionData;
    protected $sessionParser;

    public function __construct($db, $dir)
    {
        $loader   = new FunctionDiscovery($dir);
        $this->db = $db;
        $this->apps   = $loader->getFunctions('@api');
        $this->events = array(
            'preRoute' => $loader->getFunctions('preRoute'),
            'postRoute' => $loader->getFunctions('postRoute'),
            'preResponse' => $loader->getFunctions('preResponse'),
        );
    }

    public function setSessionParser(Callable $function)
    {
        $this->sessionParser = $function;
        return $this;
    }

    public function getDb()
    {
        return $this->db;
    }

    public function getSession()
    {
        if (empty($this->sessionData)) {
            throw new RuntimeException("There is no session");
        }
        return $this->sessionData;
    }

    public function setSession($session, $set = true)
    {
        $sessionParser     = $this->sessionParser;
        $this->sessionId   = $session;
        $this->sessionData = $sessionParser($session);
        if ($set) {
            header("X-Set-Session-Id: {$this->sessionId}");
        }
        return $this;
    }

    public function destroySession()
    {
        header("X-Destroy-Session-Id: 1");
        $this->sessionId = null;
        $this->sessionData = null;
        return $this;
    }

    protected function runEvent($name, $function, &$argument)
    {
        foreach ($this->events[$name] as $name => $annArgs) {
            if (!$function || (is_numeric($name) || $function->hasAnnotation($name))) {
                $response = $annArgs($argument, $this, $this->sessionData, $function ? $function->getAnnotation($name) : null);
                if ($response !== null) {
                    $argument = $response;
                }
            }
        }
    }

    public function processRequest(Array $request)
    {
        if (!empty($_SERVER["HTTP_X_SESSION_ID"])) {
            $this->setSession($_SERVER['HTTP_X_SESSION_ID'], false);
            if (empty($this->sessionData)) {
                return self::INVALID_SESSION;
            }
        }

        $responses = array();
        foreach ($request as $object) {
            try {
                $this->apiCall = $object[0];
                if (empty($this->apps[$this->apiCall])) {
                    throw new RuntimeException($object[0] . " is not a valid handler");
                }
                $function = $this->apps[$this->apiCall];
                $argument = $object[1];

                if ($function->hasAnnotation('auth') && !$this->sessionData) {
                    throw new RuntimeException("{$object[0]} requires a valid session");
                }

                $this->runEvent('preRoute', $function, $argument);
                $response = $function($argument, $this, $this->sessionData);
                $this->runEvent('postRoute', $function, $argument);

                $responses[] = $response;
            } catch (Exception $e) {
                $responses[] = array('error' => true, 'text' => $e->getMessage());
            }
        }

        $this->runEvent('preResponse', NULL, $responses);

        return $responses;
    }

    public function main()
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json");
        header('Access-Control-Allow-Credentials: false');
        header('Access-Control-Allow-Methods: POST');

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo self::WRONG_REQ_METHOD;
            exit;
        }

        $responses = $this->processRequest(json_decode(file_get_contents('php://input'), true));

        echo json_encode($responses);
    }
}
