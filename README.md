# ApiServer

Deadly simple API µServer. 

It was designed for Javascript clients:

1. Speaks JSON out of the box
2. API calls are buffered for 50ms waiting for other requests to join
    1. The less we talk to the server the better.
3. The server is implemented from scratch keeping easy of use in mind:
    1. API handlers are PHP functions or methods which are discovered with annotations
    2. Everything is compiled for speed.

## How to use it

The bootstrap code (index.php) should look like this:

```php
require __DIR__ . '/vendor/autoload.php';

$api = new crodas\ApiServer(
    __DIR__ . '/src/services' // my apis
);

$api->main();
```

```php
/** @API array_sum */
function do_array_sum($args, $server) {
    return array('array_sum' => array_sum($args));
}
```

A `client` is included in `client/dist` (You can build the source with `bower install; gulp dist`).

```js
Server.setUrl("http://api.foobar.com");
Server.exec("array_sum", [1,2,3]).then(function(result) {
    console.error(result);
});
Server.exec("array_sum", [2,3]).then(function(result) {
    console.error(result);
});
```

