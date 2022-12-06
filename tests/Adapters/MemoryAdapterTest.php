<?php

declare(strict_types=1);

namespace Fi1a\Unit\Cache\Adapters;

use Fi1a\Cache\Adapters\AdapterInterface;
use Fi1a\Cache\Adapters\MemoryAdapter;
use Fi1a\Cache\DTO\KeyDTO;
use Fi1a\Hydrator\Hydrator;
use Fi1a\Unit\Cache\TestCase\FilesystemAdapterTestCase;

/**
 * Адаптер кэширования в памяти
 */
class MemoryAdapterTest extends FilesystemAdapterTestCase
{
    /**
     * Возвращает адаптер
     */
    private function getAdapter(): AdapterInterface
    {
        return new MemoryAdapter();
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
        $this->assertTrue($adapter->have($keyDto));
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
        $this->assertTrue($adapter->have($keyDto));
        /**
         * @var KeyDTO $keyDto
         */
        $keyDto = $hydrator->hydrate([
            'key' => 'key5',
            'namespace' => 'some/namespace',
            'hash' => 'hash5',
        ], KeyDTO::class);
        $this->assertTrue($adapter->have($keyDto));
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
        $hydrator = new Hydrator();
        $adapter = $this->getAdapter();
        $this->assertCount(2, $adapter->fetch([
            $hydrator->hydrate([
                'key' => 'key1',
                'namespace' => '',
                'hash' => 'hash1',
            ], KeyDTO::class),
            $hydrator->hydrate([
                'key' => 'key2',
                'namespace' => '',
                'hash' => 'new-hash',
            ], KeyDTO::class),
            $hydrator->hydrate([
                'key' => 'key3',
                'namespace' => '',
                'hash' => null,
            ], KeyDTO::class),
            $hydrator->hydrate([
                'key' => 'unknown',
                'namespace' => '',
                'hash' => null,
            ], KeyDTO::class),
            $hydrator->hydrate([
                'key' => 'key4',
                'namespace' => 'some-namespace',
                'hash' => 'hash1',
            ], KeyDTO::class),
            $hydrator->hydrate([
                'key' => 'key5',
                'namespace' => 'some/namespace',
                'hash' => 'hash1',
            ], KeyDTO::class),
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
        /**
         * @var KeyDTO $keyDto5
         */
        $keyDto5 = $hydrator->hydrate([
            'key' => 'key5',
            'namespace' => 'unknown',
        ], KeyDTO::class);
        $this->assertTrue($adapter->delete([$keyDto5]));
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
