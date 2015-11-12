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
    public function __construct(MongoClient $conn, $dbname, $applications, $models)
    {
        $loader = new FunctionDiscovery($applications, '@api');
        $conf   = new ActiveMongo2\Configuration;
        $conf->addModelPath($models);
        $this->db   = new ActiveMongo2\Connection($conf, $conn, $conn->selectDB($dbname));
        $this->apps = $loader->filter(function($function, $annotation) {
            $function->setName($annotation->getArg());
        });
    }

    public function setSession($session)
    {
        $this->session = $session;
        return $this;
    }

    public function main()
    {
        $json = json_decode(file_get_contents('php://stdin'), true);
        $responses = array();
        foreach ($json as $object) {
            try {
                if (empty($this->apps[$object['method']])) {
                    throw new RuntimeException($object['method'] . "doesn't exists");
                }
                $function = $this->apps[$object['method']];
                $responses[] = $function($object['args'], $this);
            } catch (Exception $e) {
                $responses[] = array('error' => true, 'text' => $e->GetMessage());
            }
        }

        echo json_encode($responses);
    }
}
