# ApiServer

Pretty simple API-Server.

## How to setup

```php
require __DIR__ . '/vendor/autoload.php';

$mongo = new MongoClient;

$api = new crodas\ApiServer(
    $mongo, // MongoClient connection
    'databasename', // my database
    __DIR__ . '/src/services', // my apis
    __DIR__ . '/src/models' // my models
);

$api->main();
``
