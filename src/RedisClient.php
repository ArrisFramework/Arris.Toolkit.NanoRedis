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
    private Redis $client;

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
     * RedisClient constructor.
     *
     * @throws RedisClientException
     */
    public function __construct(string $host = 'localhost', int $port = 6379, ?string $auth = null)
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

        // try to connect
        $this->client = new Redis();
    }

    /**
     * @throws RedisClientException
     * @throws RedisException
     */
    private function tryConnect(): void
    {
        if (!$this->client->isConnected()) {
            try {
                $this->client->connect($this->host, $this->port);
            } catch (RedisException $e) {
                throw new RedisClientException($e->getMessage(), 2001);
            }
            if (null !== $this->auth && !$this->client->auth($this->auth)) {
                throw new RedisClientException('Connection auth failed', 2002);
            }
        }
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
     * @throws RedisClientException
     * @throws RedisException
     */
    public function set(string $key, mixed $value, ?int $timeout = null): void
    {
        $this->tryConnect();

        if (is_array($value)) {
            $value = json_encode($value);
        }

        if (null === $timeout) {
            $this->client->set($key, $value);
        } else {
            $this->client->setex($key, $timeout, $value);
        }
    }

    /**
     * get last Redis error msg.
     * @throws RedisException|RedisClientException
     */
    public function getLastError(): ?string
    {
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
        $this->tryConnect();

        $iterator = null;
        do {
            // SCAN возвращает часть ключей и новый курсор
            $keys = $this->client->scan($iterator, $key, 100);
            if (!empty($keys)) {
                $this->client->del($keys);
            }
        } while ($iterator !== 0);
    }

    /**
     * @throws RedisClientException
     * @throws RedisException
     */
    public function keys(string $key):array
    {
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
}
