<?php

declare(strict_types=1);

// namespace Arris\Toolkit\RedisClient\Tests;

use Arris\Toolkit\RedisClient;
use Arris\Toolkit\RedisClientException;
use PHPUnit\Framework\TestCase;

/**
 * Class RedisClient.
 */
class RedisClientTest extends TestCase
{
    /**
     * @var RedisClient
     */
    private $client;

    public function setUp(): void
    {
        if (
            !defined('REDIS_HOST') ||
            !defined('REDIS_PORT') ||
            !defined('REDIS_AUTH') ||
            !defined('REDIS_DATABASE')
        ) {
            echo 'Missing constants REDIS_HOST, REDIS_PORT, REDIS_DATABASE or REDIS_AUTH';
            exit();
        }
        $this->client = new RedisClient(REDIS_HOST, REDIS_PORT, REDIS_DATABASE, REDIS_AUTH);
    }

    public function tearDown(): void
    {
        $this->client->close();
    }

    public function testConnectionFail(): void
    {
        $this->expectException(RedisClientException::class);

        $this->client = new RedisClient('unknown');
        $this->client->set('no_server', 'test');
    }

    public function testGetSetSuccess(): void
    {
        $this->client->set('key', 'test');

        self::assertSame('test', $this->client->get('key', false));
    }

    public function testGetSetArray(): void
    {
        $this->client->set('key_array', ['test' => 'test']);

        self::assertSame('{"test":"test"}', $this->client->get('key_array', false));
        self::assertSame(['test' => 'test'], $this->client->get('key_array', true));
    }

    public function testGetSetArrayFail(): void
    {
        $this->client->set('key_array_fail', 'test');

        $this->expectErrorMessage('Json conversion failed: Syntax error');

        $this->client->get('key_array_fail', true);
    }

    public function testGetSetTimeout(): void
    {
        $this->client->set('key_timeout', 'test', 2);

        self::assertSame('test', $this->client->get('key_timeout', false));

        sleep(3);
        self::assertFalse($this->client->get('key_timeout', false));
    }

    public function testDelete(): void
    {
        $this->client->set('key_delete', 'test');
        $this->client->delete('key_delete');

        self::assertFalse($this->client->get('key_delete', false));
    }

    public function testDeletePattern(): void
    {
        $this->client->set('key_delete', 'test');
        $this->client->set('key_delete2', 'test');
        $this->client->set('key_delete3', 'test');
        $this->client->delete('key_del*');

        self::assertFalse($this->client->get('key_delete', false));
    }

    public function testGetLastError(): void
    {
        self::assertNull($this->client->getLastError());
    }
}
