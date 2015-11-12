<?php

require __DIR__ . '/../vendor/autoload.php';

$mongo = new MongoClient;

$api = new crodas\ApiServer(
    $mongo,
    'databasename',
    __DIR__ . '/../src/services',
    __DIR__ . '/../src/models'
);

$api->main();
