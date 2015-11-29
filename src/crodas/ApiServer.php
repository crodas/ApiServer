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

    public function __construct($db, $applications)
    {
        $loader = new FunctionDiscovery($applications, '@api');
        $this->db   = $db;
        $this->apps = $loader->filter(function($function, $annotation) {
            $function->setName($annotation->getArg());
        });
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

    public function setSession($session)
    {
        $sessionParser   = $this->sessionParser;
        $this->sessionId = $session;
        $this->sessionData = $sessionParser($session);
        header("X-Session-Id: {$this->sessionId}");
        return $this;
    }

    public function destroySession()
    {
        header("X-Destroy-Session-Id: 1");
        $this->sessionId = null;
        $this->sessionData = null;
        return $this;
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

        if (!empty($_SERVER["HTTP_X_SESSION_ID"])) {
            $this->setSession($_SERVER['HTTP_X_SESSION_ID'], false);
            if (empty($this->sessionData)) {
                echo self::INVALID_SESSION;
                exit;
            }
        }

        $json = json_decode(file_get_contents('php://input'), true);
        $responses = array();
        foreach ($json as $object) {
            try {
                if (empty($this->apps[$object[0]])) {
                    throw new RuntimeException($object[0] . " doesn't exists");
                }
                $function = $this->apps[$object[0]];
                if ($function->hasAnnotation('auth') && !$this->sessionData) {
                    throw new RuntimeException("{$object[0]} requires a valid session");
                }
                $responses[] = $function($object[1], $this, $this->sessionData);
            } catch (Exception $e) {
                $responses[] = array('error' => true, 'text' => $e->GetMessage());
            }
        }

        echo json_encode($responses);
    }
}
