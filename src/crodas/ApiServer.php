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

    public function __construct(MongoClient $conn, $dbname, $models, $applications)
    {
        $loader = new FunctionDiscovery($applications, '@api');
        $conf   = new ActiveMongo2\Configuration;
        $conf->addModelPath($models);
        $this->db   = new ActiveMongo2\Connection($conf, $conn, $conn->selectDB($dbname));
        $this->apps = $loader->filter(function($function, $annotation) {
            $function->setName($annotation->getArg());
        });
    }

    public function getDb()
    {
        return $this->db;
    }

    public function setSession($session)
    {
        $this->session = $session;
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

        $json = json_decode(file_get_contents('php://input'), true);
        $responses = array();
        foreach ($json as $object) {
            try {
                if (empty($this->apps[$object[0]])) {
                    throw new RuntimeException($object[0] . "doesn't exists");
                }
                $function = $this->apps[$object[0]];
                $responses[] = $function($object[1], $this);
            } catch (Exception $e) {
                $responses[] = array('error' => true, 'text' => $e->GetMessage());
            }
        }

        echo json_encode($responses);
    }
}
