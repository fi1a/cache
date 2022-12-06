<?php

declare(strict_types=1);

namespace Fi1a\Unit\Cache\Adapters;

use Fi1a\Cache\Adapters\AdapterInterface;
use Fi1a\Cache\Adapters\NullAdapter;
use Fi1a\Cache\DTO\KeyDTO;
use Fi1a\Hydrator\Hydrator;
use PHPUnit\Framework\TestCase;

/**
 * Адаптер null
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
        $hydrator = new Hydrator();
        $adapter = $this->getAdapter();
        /**
         * @var KeyDTO $keyDto
         */
        $keyDto = $hydrator->hydrate([
            'key' => 'key1',
            'namespace' => '',
            'hash' => 'hash1',
        ], KeyDTO::class);
        $this->assertFalse($adapter->have($keyDto));
        /**
         * @var KeyDTO $keyDto
         */
        $keyDto = $hydrator->hydrate([
            'key' => 'unknown',
            'namespace' => '',
            'hash' => '',
        ], KeyDTO::class);
        $this->assertFalse($adapter->have($keyDto));
        /**
         * @var KeyDTO $keyDto
         */
        $keyDto = $hydrator->hydrate([
            'key' => 'key4',
            'namespace' => 'some-namespace',
            'hash' => 'hash4',
        ], KeyDTO::class);
        $this->assertFalse($adapter->have($keyDto));
        /**
         * @var KeyDTO $keyDto
         */
        $keyDto = $hydrator->hydrate([
            'key' => 'key5',
            'namespace' => 'some/namespace',
            'hash' => 'hash5',
        ], KeyDTO::class);
        $this->assertFalse($adapter->have($keyDto));
        /**
         * @var KeyDTO $keyDto
         */
        $keyDto = $hydrator->hydrate([
            'key' => 'key1',
            'namespace' => 'some-namespace',
            'hash' => 'hash1',
        ], KeyDTO::class);
        $this->assertFalse($adapter->have($keyDto));
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
        $hydrator = new Hydrator();
        $adapter = $this->getAdapter();
        /**
         * @var KeyDTO $keyDto1
         */
        $keyDto1 = $hydrator->hydrate([
            'key' => 'key1',
            'namespace' => '',
        ], KeyDTO::class);
        /**
         * @var KeyDTO $keyDto2
         */
        $keyDto2 = $hydrator->hydrate([
            'key' => 'key2',
            'namespace' => '',
        ], KeyDTO::class);
        $this->assertTrue($adapter->delete([$keyDto1, $keyDto2]));
        /**
         * @var KeyDTO $keyDto4
         */
        $keyDto4 = $hydrator->hydrate([
            'key' => 'key4',
            'namespace' => 'some-namespace',
        ], KeyDTO::class);
        $this->assertTrue($adapter->delete([$keyDto4]));
        $this->assertFalse($adapter->have($keyDto1));
        $this->assertFalse($adapter->have($keyDto2));
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
