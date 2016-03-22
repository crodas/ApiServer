# ApiServer

ApiServer is a micro-framework written in PHP. It's designed to help making APIs server a childish game.

ApiServer aims to be:

1. _Simple_: 
    - It was designed to work with a Javascript client.
    - There is a single end-point which exposes the entire API functions
    - JSON-in, JSON-out.
2. _Extensible_: it has an extension system based around the [Pimple service-container](http://pimple.sensiolabs.org/) that makes it easy to tie in third party libraries.
3. _Efficient_: The client can concatenate many API calls in a single request.


## How to use it

The bootstrap code (index.php) should look like this:

```php
require __DIR__ . '/vendor/autoload.php';

/** @API sum */
function sum(Array $args)
{
    return array_sum($args);
}

$api = new crodas\ApiServer(__DIR__);
$api->main();
```

The `client` is included in `client/dist` (You can build the source with `bower install; gulp dist`).

```js
Server.setUrl("http://api.foobar.com");
Server.exec("sum", [1,2,3]).then(function(result) {
    console.error(result); // 6
});
Server.exec("sum", [2,3]).then(function(result) {
    console.error(result); // 5
});
```
