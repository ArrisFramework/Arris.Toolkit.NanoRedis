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
// This will try to connect and throw a RedisClientException if connection failed
$client = new RedisClient($host, $port, $auth);

// simple get set system
$client->set('key', 'value');
$val = $client->get('key');

// this value will be converted into json text into redis
$client->set('key_array', ['test' => 'test']);
// returns '{"test":"test"}'

$client->get('key_array');
// returns ['test' => 'test']
$client->get('key_array', true);

// this key will live 120 seconds
$client->set('key', 'test', 120); 
$client->delete('key');

// delete keys by pattern
$client->set('key_delete', 'test');
$client->set('key_delete2', 'test');
$client->set('key_delete3', 'test');
$client->delete('key_del*');

// close connection
$client->close();

```
