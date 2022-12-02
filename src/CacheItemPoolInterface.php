<?php

declare(strict_types=1);

namespace Fi1a\Cache;

use Fi1a\Cache\Adapters\AdapterInterface;

/**
 * Интерфейс кэша
 */
interface CacheItemPoolInterface
{
    /**
     * Конструктор
     */
    public function __construct(AdapterInterface $adapter, string $namespace = '', int $defaultTtl = 0);

    /**
     * Возвращает значение
     *
     * @param mixed $key
     */
    public function getItem($key, ?string $hash = null): CacheItemInterface;

    /**
     * Возвращает значения
     *
     * @param mixed[][] $keys
     *
     * @return CacheItemInterface[]
     */
    public function getItems(array $keys): array;

    /**
     * Проверяет наличие значения
     *
     * @param mixed $key
     */
    public function hasItem($key, ?string $hash = null): bool;

    /**
     * Удаляет значение
     *
     * @param mixed $key
     */
    public function deleteItem($key): bool;

    /**
     * Удаляет значения
     *
     * @param mixed[] $keys
     */
    public function deleteItems(array $keys): bool;

    /**
     * Очищает
     */
    public function clear(): bool;

    /**
     * Сохраняет значение
     */
    public function save(CacheItemInterface $item): bool;

    /**
     * Отложенное сохранения значения
     */
    public function saveDeferred(CacheItemInterface $item): bool;

    /**
     * Выполняет отложенное сохранение
     */
    public function commit(): bool;
}
