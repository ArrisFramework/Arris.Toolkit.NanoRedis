<?php

declare(strict_types=1);

namespace Arris\Toolkit;

use Redis;
use RedisException;

/**
 * Class RedisClient.
 */
class RedisClient
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
     * RedisClient constructor.
     *
     * @throws RedisClientException|RedisException
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

        // try to connect
        $this->client = new Redis();
        if ($this->database) {
            $this->client->select($this->database);
        }
        $this->is_connected = true;
    }

    /**
     * @throws RedisClientException
     * @throws RedisException
     */
    public function tryConnect(): void
    {
        if (!$this->enabled) {
            return;
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
    }

    /**
     * @throws RedisException
     */
    public function setDatabase(int $database):Redis
    {
        return $this->client->select($database);
    }

    public function connect(string $host = 'localhost', int $port = 6379, ?int $database = 0, bool $enabled = true)
    {
        $this->host = $host;
        $this->port = $port;
        $this->database = $database;
        $this->enabled = $enabled;

        $this->tryConnect();
    }

    /**
     * get value from Redis
     * if $isJson=true, try to convert retrieved data to json.
     *
     * @return mixed|bool|string
     *
     * @throws RedisClientException|RedisException
     */
    public function get(string $key, bool $isJson = true): mixed
    {
        if (!$this->enabled) {
            return false;
        }

        $this->tryConnect();

        $r = $this->client->get($key);

        if (false !== $r && $isJson) {
            $r = json_decode($r, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                throw new RedisClientException('Json conversion failed: '.json_last_error_msg(), 3000);
            }
        }

        return $r;
    }

    /**
     * set value to Redis.
     * optional $timeout in seconds.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $timeout
     *
     * @return bool|Redis
     *
     * @throws RedisClientException
     * @throws RedisException
     */
    public function set(string $key, mixed $value, ?int $timeout = null)
    {
        if (!$this->enabled) {
            return false;
        }

        $this->tryConnect();

        if (is_array($value)) {
            $value = json_encode($value);
        }

        return  null === $timeout
                ? $this->client->set($key, $value)
                : $this->client->setex($key, $timeout, $value);

    }

    /**
     * get last Redis error msg.
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
     * delete Redis key.
     * @throws RedisException|RedisClientException
     */
    public function delete(string $key): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->tryConnect();

        $iterator = null;
        do {
            // SCAN возвращает часть ключей и новый курсор
            $keys = $this->client->scan($iterator, $key, 100);
            if (!empty($keys)) {
                $this->client->del($keys);
            }
        } while ($iterator !== 0);
        return;
    }

    /**
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
            if (!empty($keys)) {
                $found_keys = array_merge($found_keys, $keys);
            }
        } while ($iterator !== 0);

        return $found_keys;
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
}
