<?php

namespace Arris\Toolkit;

use Redis;

interface RedisClientInterface
{
    public function __construct(string $host = 'localhost', int $port = 6379, ?int $database = 0, ?string $auth = null, bool $enabled = true);
    public function setHost($host = null):RedisClient;
    public function setPort($port = null):RedisClient;
    public function setDatabase($database = null):RedisClient;
    public function enable($enabled = null):RedisClient;

    public function connect(string $host = 'localhost', int $port = 6379, ?int $database = 0, bool $enabled = true): bool;

    public function useDatabase(int $database):bool;
    public function getDatabase():int;
    public function flushDatabase($async = null): bool;

    public function set(string $key, mixed $value, ?int $timeout = null): bool;
    public function expire(string $key, int $ttl = 0):bool;

    public function exists(string $key):bool;
    public function get(string $key, bool $decodeJSON = true): mixed;
    public function getMany(string $key, bool $decodeJSON = true, bool $sortKeys = false): mixed;

    public function delete(string $key): array;

    public function keys(string $key, $sort_keys = true):array;

    public function incrBy($key, $value = 1): bool|int;
    public function decrBy($key, $value = 1): bool|int;

    public function getClient(bool $try_connect = false): Redis;
    public function getLastError(): ?string;

    public function close(): void;

    public function setJSONEncodeFlags(int $flags = JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES): void;
}