<?php

declare(strict_types=1);

namespace Fi1a\Unit\Cache\Adapters;

use Fi1a\Cache\Adapters\AdapterInterface;
use Fi1a\Cache\Adapters\NullAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Адаптер кэширования (Null объект)
 */
class NullAdapterTest extends TestCase
{
    /**
     * Возвращает адаптер
     */
    private function getAdapter(): AdapterInterface
    {
        return new NullAdapter();
    }

    /**
     * Конструктор
     */
    public function testConstruct(): void
    {
        $this->assertInstanceOf(AdapterInterface::class, $this->getAdapter());
    }

    /**
     * Сохранение значений в кэше
     */
    public function testSave(): void
    {
        $adapter = $this->getAdapter();
        $this->assertTrue($adapter->save([
            'key1' => ['value1', 'hash1', time() + 1000, ''],
            'key2' => ['value2', 'hash2', time() + 1000, ''],
            'key3' => ['value3', null, time() + 1000, ''],
            'key4' => ['value1', 'hash4', time() + 1000, 'some-namespace'],
            'key5' => ['value2', 'hash5', time() + 1000, 'some/namespace'],
        ]));
    }

    /**
     * Проверяет наличие значения в кэше
     *
     * @depends testSave
     */
    public function testHave(): void
    {
        $adapter = $this->getAdapter();
        $this->assertFalse($adapter->have('key1', '', 'hash1'));
        $this->assertFalse($adapter->have('unknown', ''));
        $this->assertFalse($adapter->have('key4', 'some-namespace', 'hash4'));
        $this->assertFalse($adapter->have('key5', 'some/namespace', 'hash5'));
        $this->assertFalse($adapter->have('key1', 'some-namespace', 'hash1'));
    }

    /**
     * Возвращает значение из кэша
     *
     * @depends testSave
     */
    public function testFetch(): void
    {
        $adapter = $this->getAdapter();
        $this->assertCount(0, $adapter->fetch([
            ['key1', 'hash1', ''],
            ['key2', 'new-hash', ''],
            ['key3', null, ''],
            ['unknown', null, ''],
            ['key4', 'hash1', 'some-namespace'],
            ['key5', 'hash1', 'some/namespace'],
        ]));
    }

    /**
     * Удаляет значение из кэша
     *
     * @depends testSave
     */
    public function testDelete(): void
    {
        $adapter = $this->getAdapter();
        $this->assertTrue($adapter->delete(['key1', 'key2'], ''));
        $this->assertTrue($adapter->delete(['key4',], 'some-namespace'));
        $this->assertFalse($adapter->have('key1', ''));
        $this->assertFalse($adapter->have('key2', ''));
    }

    /**
     * Очищает кэш
     *
     * @depends testSave
     */
    public function testClear(): void
    {
        $adapter = $this->getAdapter();
        $this->assertTrue($adapter->clear('some-namespace'));
        $this->assertTrue($adapter->clear(''));
        $this->assertTrue($adapter->clear(''));
    }
}
