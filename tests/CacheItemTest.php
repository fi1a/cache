<?php

declare(strict_types=1);

namespace Fi1a\Unit\Cache;

use DateInterval;
use DateTime;
use Fi1a\Cache\CacheItem;
use Fi1a\Hydrator\Hydrator;
use PHPUnit\Framework\TestCase;

/**
 * Элемент кэша
 */
class CacheItemTest extends TestCase
{
    /**
     * Элемент кэша
     */
    public function testCacheItem(): void
    {
        $hydrator = new Hydrator();
        $expire = time() + 1000;
        /**
         * @var CacheItem $cacheItem
         */
        $cacheItem = $hydrator->hydrate([
            'key' => 'key1',
            'value' => 'value1',
            'isHit' => false,
            'expire' => $expire,
            'default_ttl' => 1000,
            'hash' => 'hash1',
        ], CacheItem::class);

        $this->assertEquals('key1', $cacheItem->getKey());
        $this->assertEquals('value1', $cacheItem->get());
        $this->assertFalse($cacheItem->isHit());
        $this->assertEquals($expire, $cacheItem->getExpire());
        $this->assertEquals('hash1', $cacheItem->getHash());
    }

    /**
     * Установить значение
     */
    public function testCacheItemSet(): void
    {
        $hydrator = new Hydrator();
        $expire = time() + 1000;
        /**
         * @var CacheItem $cacheItem
         */
        $cacheItem = $hydrator->hydrate([
            'key' => 'key1',
            'value' => 'value1',
            'isHit' => false,
            'expire' => $expire,
            'default_ttl' => 1000,
            'hash' => 'hash1',
        ], CacheItem::class);

        $this->assertEquals('value1', $cacheItem->get());
        $cacheItem->set('value2');
        $this->assertEquals('value2', $cacheItem->get());
    }

    /**
     * Установить значение
     */
    public function testCacheItemSetHash(): void
    {
        $hydrator = new Hydrator();
        $expire = time() + 1000;
        /**
         * @var CacheItem $cacheItem
         */
        $cacheItem = $hydrator->hydrate([
            'key' => 'key1',
            'value' => 'value1',
            'isHit' => false,
            'expire' => $expire,
            'default_ttl' => 1000,
            'hash' => 'hash1',
        ], CacheItem::class);
        $this->assertEquals('hash1', $cacheItem->getHash());
        $cacheItem->setHash('hash2');
        $this->assertEquals('hash2', $cacheItem->getHash());
    }

    /**
     * Срок жизни (null)
     */
    public function testExpiresAt()
    {
        $hydrator = new Hydrator();
        /**
         * @var CacheItem $cacheItem
         */
        $cacheItem = $hydrator->hydrate([
            'key' => 'key1',
            'value' => 'value1',
            'isHit' => false,
            'expire' => 0,
            'default_ttl' => 0,
            'hash' => 'hash1',
        ], CacheItem::class);
        $cacheItem->expiresAt(null);
        $this->assertEquals(0, $cacheItem->getExpire());
    }

    /**
     * Срок жизни (\DateTimeInterface)
     */
    public function testExpiresAtDateTimeInterface()
    {
        $hydrator = new Hydrator();
        /**
         * @var CacheItem $cacheItem
         */
        $cacheItem = $hydrator->hydrate([
            'key' => 'key1',
            'value' => 'value1',
            'isHit' => false,
            'expire' => 0,
            'default_ttl' => 1000,
            'hash' => 'hash1',
        ], CacheItem::class);
        $cacheItem->expiresAt(new DateTime());
        $this->assertTrue($cacheItem->getExpire() > 0);
    }

    /**
     * Срок жизни
     */
    public function testExpiresAfter()
    {
        $hydrator = new Hydrator();
        /**
         * @var CacheItem $cacheItem
         */
        $cacheItem = $hydrator->hydrate([
            'key' => 'key1',
            'value' => 'value1',
            'isHit' => false,
            'expire' => 0,
            'default_ttl' => 1000,
            'hash' => 'hash1',
        ], CacheItem::class);
        $cacheItem->expiresAfter(new DateInterval('PT3S'));
        $this->assertTrue($cacheItem->getExpire() > 0);
    }

    /**
     * Срок жизни (null)
     */
    public function testExpiresAfterNull()
    {
        $hydrator = new Hydrator();
        /**
         * @var CacheItem $cacheItem
         */
        $cacheItem = $hydrator->hydrate([
            'key' => 'key1',
            'value' => 'value1',
            'isHit' => false,
            'expire' => 0,
            'defaultTtl' => 1000,
            'hash' => 'hash1',
        ], CacheItem::class);
        $cacheItem->expiresAfter(null);
        $this->assertTrue($cacheItem->getExpire() > 0);
    }

    /**
     * Срок жизни (time)
     */
    public function testExpiresAfterTime()
    {
        $hydrator = new Hydrator();
        /**
         * @var CacheItem $cacheItem
         */
        $cacheItem = $hydrator->hydrate([
            'key' => 'key1',
            'value' => 'value1',
            'isHit' => false,
            'expire' => 0,
            'default_ttl' => 1000,
            'hash' => 'hash1',
        ], CacheItem::class);
        $cacheItem->expiresAfter(time());
        $this->assertTrue($cacheItem->getExpire() > 0);
    }
}
