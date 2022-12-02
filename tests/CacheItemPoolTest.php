<?php

declare(strict_types=1);

namespace Fi1a\Unit\Cache;

use DateTime;
use Exception;
use Fi1a\Cache\Adapters\AdapterInterface;
use Fi1a\Cache\Adapters\FilesystemAdapter;
use Fi1a\Cache\CacheItemInterface;
use Fi1a\Cache\CacheItemPool;
use Fi1a\Cache\CacheItemPoolInterface;
use Fi1a\Unit\Cache\TestCase\FilesystemAdapterTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Кэш
 */
class CacheItemPoolTest extends FilesystemAdapterTestCase
{
    /**
     * Возвращает адаптер для кеша
     */
    private function getAdapter(): AdapterInterface
    {
        return new FilesystemAdapter(self::$folderPath . '/cache');
    }

    /**
     * Кэш
     */
    private function getCache(): CacheItemPoolInterface
    {
        return new CacheItemPool($this->getAdapter(), 'unit-test');
    }

    /**
     * Получение не существующего значения
     */
    public function testGetItemNew(): void
    {
        $cache = $this->getCache();
        $item = $cache->getItem('key1');
        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertFalse($item->isHit());
        $this->assertNull($item->get());
        $this->assertNull($item->getHash());
    }

    /**
     * Получение не существующих значений
     */
    public function testGetItemsNew(): void
    {
        $cache = $this->getCache();
        $items = $cache->getItems([['key1'], ['key2', 'hash_value']]);
        $this->assertCount(2, $items);
    }

    /**
     * Сохранение значения
     *
     * @depends testGetItemNew
     */
    public function testSave(): void
    {
        $cache = $this->getCache();
        $item = $cache->getItem('key1')
            ->set(1);
        $this->assertTrue($cache->save($item));
        $item = $cache->getItem('key2')
            ->set('string');
        $this->assertTrue($cache->save($item));
        $item = $cache->getItem('key4')
            ->set(null);
        $this->assertTrue($cache->save($item));
    }

    /**
     * Отложенное сохранение
     *
     * @depends testSave
     */
    public function testSaveDeferred(): void
    {
        $cache = $this->getCache();
        $item = $cache->getItem('key1');
        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $item->set(2);
        $this->assertTrue($cache->saveDeferred($item));
        $cache->__destruct();
    }

    /**
     * Проверка значения
     *
     * @depends testSaveDeferred
     */
    public function testHasItem(): void
    {
        $cache = $this->getCache();
        $this->assertTrue($cache->hasItem('key1'));
        $this->assertFalse($cache->hasItem('key5'));
    }

    /**
     * Получение значения
     *
     * @depends testSaveDeferred
     */
    public function testGetItem(): void
    {
        $cache = $this->getCache();
        $item = $cache->getItem('key1');
        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertTrue($item->isHit());
        $this->assertEquals(2, $item->get());
        $this->assertNull($item->getHash());
    }

    /**
     * Удаление элемента кеша
     *
     * @depends testGetItem
     */
    public function testDeleteItem(): void
    {
        $cache = $this->getCache();
        $this->assertTrue($cache->deleteItem('key1'));
        $this->assertFalse($cache->hasItem('key1'));
    }

    /**
     * Удаление элементов кеша
     *
     * @depends testGetItem
     */
    public function testDeleteItems(): void
    {
        $cache = $this->getCache();
        $this->assertTrue($cache->deleteItems(['key1', 'key2', 'key3',]));
        $this->assertFalse($cache->hasItem('key2'));
    }

    /**
     * Очищаем кеш
     *
     * @depends testHasItem
     */
    public function testClear(): void
    {
        $cache = $this->getCache();
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->hasItem('key4'));
    }

    /**
     * Тестирование отличного от строки ключа
     *
     * @depends testClear
     */
    public function testGetItemMixedKey(): void
    {
        $key = ['key1', 'key2'];
        $cache = $this->getCache();
        $item = $cache->getItem($key);
        $this->assertEquals($key, $item->getKey());
        $item->set(1);
        $this->assertTrue($cache->save($item));
        $item = $cache->getItem($key);
        $this->assertEquals($key, $item->getKey());
        $this->assertEquals(1, $item->get());
        $this->assertTrue($cache->deleteItem($key));
    }

    /**
     * Срок жизни (null)
     */
    public function testExpiresAfterNull(): void
    {
        $cache = $this->getCache();
        $item = $cache->getItem('key1');
        $item->expiresAfter(null);
        $this->assertEquals(0, $item->getExpire());
    }

    /**
     * Срок жизни (int)
     */
    public function testExpiresAfterInt(): void
    {
        $cache = $this->getCache();
        $item = $cache->getItem('key1');
        $item->expiresAfter(1);
        $this->assertTrue($item->getExpire() > 0);
    }

    /**
     * Срок жизни (null)
     */
    public function testExpiresAt(): void
    {
        $cache = $this->getCache();
        $item = $cache->getItem('key1');
        $item->expiresAt(null);
        $this->assertEquals(0, $item->getExpire());
    }

    /**
     * Срок жизни (\DateTimeInterface)
     */
    public function testExpiresAtDateTimeInterface(): void
    {
        $cache = $this->getCache();
        $item = $cache->getItem('key1');
        $item->expiresAt(new DateTime());
        $this->assertTrue($item->getExpire() > 0);
    }

    /**
     * Тестирование хеша
     */
    public function testHash(): void
    {
        $cache = $this->getCache();
        $item = $cache->getItem('key1');
        $this->assertFalse($item->isHit());
        $item->setHash('hash');
        $item->set(2);
        $this->assertTrue($cache->save($item));
        $this->assertTrue($cache->hasItem('key1', 'hash'));
        $item = $cache->getItem('key1', 'hash');
        $this->assertTrue($item->isHit());
        $this->assertEquals(2, $item->get());
        $this->assertFalse($cache->hasItem('key1'));
        $item = $cache->getItem('key1');
        $this->assertFalse($item->isHit());
    }

    /**
     * Исключение при получении значения
     */
    public function testGetItemException(): void
    {
        /**
         * @var MockObject|FilesystemAdapter $adapter
         */
        $adapter = $this->getMockBuilder(FilesystemAdapter::class)
            ->setConstructorArgs([self::$folderPath . '/cache'])
            ->getMock();
        $adapter->method('fetch')->willThrowException(new Exception());

        $cache = new CacheItemPool($adapter, 'unit-test');
        $cache->getItem('key1');

        $this->assertTrue(true);
    }

    /**
     * Исключение при коммите
     */
    public function testCommitException(): void
    {
        /**
         * @var MockObject|FilesystemAdapter $adapter
         */
        $adapter = $this->getMockBuilder(FilesystemAdapter::class)
            ->setConstructorArgs([self::$folderPath . '/cache'])
            ->getMock();
        $adapter->method('save')->willThrowException(new Exception());

        $cache = new CacheItemPool($adapter, 'unit-test');
        $cache->commit();

        $this->assertTrue(true);
    }

    /**
     * Исключение при проверке
     */
    public function testHasItemException(): void
    {
        /**
         * @var MockObject|FilesystemAdapter $adapter
         */
        $adapter = $this->getMockBuilder(FilesystemAdapter::class)
            ->setConstructorArgs([self::$folderPath . '/cache'])
            ->getMock();
        $adapter->method('have')->willThrowException(new Exception());

        $cache = new CacheItemPool($adapter, 'unit-test');
        $cache->hasItem('key1');

        $this->assertTrue(true);
    }

    /**
     * Исключение при удалении
     */
    public function testDeleteItemException(): void
    {
        /**
         * @var MockObject|FilesystemAdapter $adapter
         */
        $adapter = $this->getMockBuilder(FilesystemAdapter::class)
            ->setConstructorArgs([self::$folderPath . '/cache'])
            ->getMock();
        $adapter->method('delete')->willThrowException(new \Exception());

        $cache = new CacheItemPool($adapter, 'unit-test');
        $cache->deleteItem('key1');

        $this->assertTrue(true);
    }

    /**
     * Исключение при удалении
     */
    public function testDeleteItemsException(): void
    {
        /**
         * @var MockObject|FilesystemAdapter $adapter
         */
        $adapter = $this->getMockBuilder(FilesystemAdapter::class)
            ->setConstructorArgs([self::$folderPath . '/cache'])
            ->getMock();
        $adapter->method('delete')->willThrowException(new \Exception());

        $cache = new CacheItemPool($adapter, 'unit-test');
        $cache->deleteItems(['key1', 'key2',]);

        $this->assertTrue(true);
    }

    /**
     * Исключение при очистке
     */
    public function testClearException(): void
    {
        /**
         * @var MockObject|FilesystemAdapter $adapter
         */
        $adapter = $this->getMockBuilder(FilesystemAdapter::class)
            ->setConstructorArgs([self::$folderPath . '/cache'])
            ->getMock();
        $adapter->method('clear')->willThrowException(new \Exception());

        $cache = new CacheItemPool($adapter, 'unit-test');
        $cache->clear();

        $this->assertTrue(true);
    }
}
