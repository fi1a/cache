<?php

declare(strict_types=1);

namespace Fi1a\Unit\Cache\Adapters;

use ErrorException;
use Fi1a\Cache\Adapters\AdapterInterface;
use Fi1a\Cache\Adapters\FilesystemAdapter;
use Fi1a\Cache\DTO\KeyDTO;
use Fi1a\Hydrator\Hydrator;
use Fi1a\Unit\Cache\TestCase\FilesystemAdapterTestCase;
use InvalidArgumentException;

/**
 * Адаптер кэширования в файловой системе
 */
class FilesystemAdapterTest extends FilesystemAdapterTestCase
{
    /**
     * Возвращает адаптер
     */
    private function getAdapter(): AdapterInterface
    {
        return new FilesystemAdapter(self::$folderPath . '/cache');
    }

    /**
     * Инициализация кэша
     */
    public function testConstructorException()
    {
        $this->expectException(ErrorException::class);
        mkdir(self::$folderPath, 0775, true);
        chmod(self::$folderPath, 0000);
        try {
            new FilesystemAdapter(self::$folderPath . '/cache');
        } catch (ErrorException $exception) {
            chmod(self::$folderPath, 0775);
            self::deleteCacheFolder();

            throw $exception;
        }
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
     * Сохранение значений в кэше
     */
    public function testSaveError(): void
    {
        $adapter = $this->getAdapter();
        chmod(self::$folderPath, 0000);
        $this->assertFalse($adapter->save([
            'key1' => ['value1', 'hash1', time() + 1000, ''],
            'key2' => ['value2', 'hash2', time() + 1000, ''],
            'key3' => ['value3', null, time() + 1000, ''],
        ]));
        chmod(self::$folderPath, 0775);
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
        $this->assertFalse($adapter->have($keyDto1));
        $this->assertFalse($adapter->have($keyDto2));
    }

    /**
     * Очищает кэш
     *
     * @depends testSave
     */
    public function testClearFail(): void
    {
        $adapter = $this->getAdapter();
        chmod(self::$folderPath, 0000);
        $this->assertFalse($adapter->clear(''));
        chmod(self::$folderPath, 0775);
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

    /**
     * Использование namespace
     */
    public function testNamespaceException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $adapter = $this->getAdapter();
        $this->assertTrue($adapter->save([
            'key3' => ['value3', null, time() + 1000, '@'],
        ]));
    }
}
