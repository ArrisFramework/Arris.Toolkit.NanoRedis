<?php

declare(strict_types=1);

namespace Arris\Toolkit;

use Redis;
use RedisException;

/**
 * RedisClient tiny wrapper
 *
 * НЕ поддерживает мультирежим.
 */
class RedisClient implements RedisClientInterface
{
    /**
     * @var Redis
     */
    public Redis $client;

    /**
     * @var string
     */
    private string $host;

    /**
     * @var int
     */
    private int $port;

    /**
     * @var string|null
     */
    private ?string $auth;

    /**
     * @var int|null
     */
    private ?int $database;

    /**
     * @var bool
     */
    private bool $enabled = true;

    /**
     * @var bool
     */
    public bool $is_connected = false;

    /**
     * @var int
     */
    private int $jsonFlags = JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES;

    /**
     * Конструктор. Задает параметры.
     *
     * @throws RedisClientException
     */
    public function __construct(string $host = 'localhost', int $port = 6379, ?int $database = 0, ?string $auth = null, bool $enabled = true)
    {
        // test extension
        // @codeCoverageIgnoreStart
        if (!extension_loaded('redis')) {
            throw new RedisClientException('Redis extension missing', 1000);
        }
        // @codeCoverageIgnoreEnd

        $this->host = $host;
        $this->port = $port;
        $this->auth = $auth;
        $this->database = $database;
        $this->enabled = $enabled;

        $this->client = new Redis();
    }

    /**
     * Хелпер, устанавливает хост
     *
     * @param $host
     * @return $this
     */
    public function setHost($host = null):RedisClient
    {
        $this->host = $host !== null ? $host : $this->host;
        return $this;
    }

    /**
     * Хелпер, устанавливает порт
     *
     * @param $port
     * @return $this
     */
    public function setPort($port = null):RedisClient
    {
        $this->port = $port !== null ? $port : $this->port;
        return $this;
    }

    /**
     * Хелпер, устанавливает БД
     *
     * @param $database
     * @return $this
     */
    public function setDatabase($database = null):RedisClient
    {
        $this->database = $database !== null ? $database : $this->database;
        return $this;
    }

    /**
     * Хелпер, устанавливает флаг "enabled"
     *
     * @param $enabled
     * @return $this
     */
    public function enable($enabled = null):RedisClient
    {
        $this->enabled = $enabled !== null ? $enabled : $this->enabled;
        return $this;
    }

    /**
     * Устанавливает соединение с Redis
     *
     * @throws RedisClientException
     * @throws RedisException
     */
    public function connect($host = null, $port = null, $database = null, $enabled = null): bool
    {
        $this->host = $host !== null ? $host : $this->host;
        $this->port = $port !== null ? $port : $this->port;
        $this->database = $database !== null ? $database : $this->database;
        $this->enabled = $enabled !== null ? $enabled : $this->enabled;

        return $this->tryConnect();
    }

    /**
     * Пытается установить соединение с REDIS
     *
     * @throws RedisClientException
     * @throws RedisException
     */
    private function tryConnect(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if (!$this->client->isConnected()) {
            try {
                $this->client->connect($this->host, $this->port);
                $this->client->select($this->database);
                $this->is_connected = true;
            } catch (RedisException $e) {
                throw new RedisClientException($e->getMessage(), 2001);
            }

            if (null !== $this->auth && !$this->client->auth($this->auth)) {
                throw new RedisClientException('Connection auth failed', 2002);
            }
        }

        return true;
    }

    /**
     * Устанавливает базу данных
     *
     * @throws RedisException|RedisClientException
     */
    public function useDatabase(int $database):bool
    {
        if (!$this->enabled) {
            return false;
        }

        $this->tryConnect();

        return $this->client->select($database);
    }

    /**
     * @throws RedisClientException
     * @throws RedisException
     */
    public function getDatabase():int
    {
        if (!$this->enabled) {
            return -1;
        }

        $this->tryConnect();

        return $this->client->getDbNum();
    }

    /**
     * Очищает базу полностью
     *
     * @throws RedisClientException
     * @throws RedisException
     */
    public function flushDatabase($async = null): bool
    {
        if (!$this->enabled) {
            return false;
        }
        $this->tryConnect();

        return $this->client->flushDB($async);
    }

    /**
     * set value to Redis.
     * optional $timeout in seconds.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $timeout
     *
     * @return bool
     *
     * @throws RedisClientException
     * @throws RedisException
     */
    public function set(string $key, mixed $value, ?int $timeout = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $this->tryConnect();

        if (is_array($value)) {
            $value = json_encode($value, flags: $this->jsonFlags);
        }

        return  null === $timeout
            ? $this->client->set($key, $value)
            : $this->client->setex($key, $timeout, $value);
    }

    /**
     * Устанавливает таймаут ключа
     *
     * @throws RedisException
     * @throws RedisClientException
     */
    public function expire(string $key, int $ttl = 0):bool
    {
        if (!$this->enabled) {
            return false;
        }
        $this->tryConnect();

        return $this->client->expire($key, $ttl);
    }

    /**
     * get value from Redis
     * if $isJson=true, try to convert retrieved data to json.
     *
     * @return mixed|bool|string
     *
     * @throws RedisClientException|RedisException
     */
    public function get(string $key, bool $decodeJSON = true): mixed
    {
        if (!$this->enabled) {
            return false;
        }

        $this->tryConnect();

        $r = $this->client->get($key);

        if (false !== $r && $decodeJSON) {
            $r = json_decode($r, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new RedisClientException('JSON decode failed: '.json_last_error_msg(), 3000);
            }
        }

        return $r;
    }

    /**
     * Проверяет существование ключа
     *
     * @throws RedisClientException
     * @throws RedisException
     */
    public function exists(string $key):bool
    {
        if (!$this->enabled) {
            return false;
        }
        $this->tryConnect();

        return (bool)$this->client->exists($key);
    }

    /**
     * Delete redis key(s) by mask.
     * Удаляет ключи по маске.
     *
     * @param string $key
     * @return array - список удаленных ключей
     * @throws RedisClientException
     * @throws RedisException
     */
    public function delete(string $key): array
    {
        if (!$this->enabled) {
            return [];
        }
        $deletedKeys = [];

        $this->tryConnect();

        $iterator = null;
        do {
            // SCAN возвращает часть ключей и новый курсор
            $keys = $this->client->scan($iterator, $key, 100);

            if (!empty($keys)) {
                $deleted = $this->client->del($keys);

                if (is_int($deleted)) {
                    if ($deleted > 0) {
                        $deletedKeys = array_merge($deletedKeys, $keys);
                    }
                }
            }

        } while ($iterator !== 0);

        return $deletedKeys;
    }

    /**
     * Return redis key(s) by mask.
     * Возвращает ключи по маске.
     *
     * @throws RedisClientException
     * @throws RedisException
     */
    public function keys(string $key):array
    {
        if (!$this->enabled) {
            return [];
        }

        $this->tryConnect();
        $found_keys = [];

        $iterator = null;
        do {
            $keys = $this->client->scan($iterator, $key, 100);
            if (!empty($keys) && is_array($keys)) {
                $found_keys = array_merge($found_keys, $keys);
            }
        } while ($iterator !== 0);

        return $found_keys;
    }

    /**
     * Увеличивает ключ на N
     *
     * @throws RedisException
     * @throws RedisClientException
     */
    public function incrBy($key, $value = 1): bool|int
    {
        if (!$this->enabled) {
            return false;
        }
        $this->tryConnect();

        return $this->client->incrBy($key, $value);
    }

    /**
     * Уменьшает ключ на N
     *
     * @throws RedisException
     * @throws RedisClientException
     */
    public function decrBy($key, $value = 1): bool|int
    {
        if (!$this->enabled) {
            return false;
        }
        $this->tryConnect();

        return $this->client->decrBy($key, $value);
    }

    /**
     * return Redis client instance
     *
     * @param bool $try_connect
     * @return Redis
     * @throws RedisClientException
     * @throws RedisException
     */
    public function getClient(bool $try_connect = false): Redis
    {
        if (!$this->enabled) {
            return $this->client;
        }

        if ($try_connect) {
            $this->tryConnect();
        }

        return $this->client;
    }

    /**
     * get last Redis error msg.
     *
     * @throws RedisException|RedisClientException
     */
    public function getLastError(): ?string
    {
        if (!$this->enabled) {
            return 'Not connected';
        }

        $this->tryConnect();

        return $this->client->getLastError();
    }

    /**
     * close Redis connection.
     * @throws RedisException
     */
    public function close(): void
    {
        if ($this->client->isConnected()) {
            $this->client->close();
        }
    }

    /**
     * Устанавливает флаги JSON-кодирования по-умолчанию
     *
     * @param int $flags
     * @return void
     */
    public function setJSONEncodeFlags(int $flags = JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES): void
    {
        $this->jsonFlags = $flags;
    }

}
