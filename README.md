# RedisClient

Simple class easy to use that wraps Redis extension without dependencies.

Based on https://github.com/mp3000mp/RedisClient

Installation
------------

```sh
composer require karelwintersky/arris.toolkit.nanoredis
```


Usage
-----

```php
define('REDIS_HOST', 'localhost');
define('REDIS_PORT', 6379);
define('REDIS_DATABASE', 0);
define('REDIS_AUTH', null);

$client = new RedisClient(host: REDIS_HOST, port: REDIS_PORT, enabled: true);

$client->connect(); // не обязательно

// simple get set system
$client->set('key', 'value');
$val = $client->get('key');

// this value will be converted into json text into redis
$client->set('key_array', ['test' => 'test']);
// сохраняет '{"test":"test"}'

$client->get('key_array');
// возвращает ['test' => 'test']
$client->get('key_array', true);

// this key will live 120 seconds
$client->set('key', 'test', 120); 
$client->delete('key');

// delete keys by pattern
$client->set('key_delete', 'test');
$client->set('key_delete2', 'test');
$client->set('key_delete3', 'test');
$client->delete('key_del*');

// смена БД
$client->setDatabase(1);
$client->set('key_1', 1);

$сlient->setDatabase(1);
var_dump( $client->getDatabase()); // 1

$сlient->flushDatabase(); // flush database 


// close connection
$client->close();

```
