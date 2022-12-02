<?php

declare(strict_types=1);

namespace Fi1a\Cache\Adapters;

/**
 * Интерфейс адаптера кэша
 */
interface AdapterInterface
{
    /**
     * Возвращает значение из кэша
     *
     * @param string[][]|null[][] $keys
     *
     * @return mixed[][]
     */
    public function fetch(array $keys): array;

    /**
     * Сохранение значений в кэше
     *
     * @param mixed[][] $values
     */
    public function save(array $values): bool;

    /**
     * Проверяет наличие значения в кэше
     */
    public function have(string $key, string $namespace, ?string $hash = null): bool;

    /**
     * Удаляет значение из кэша
     *
     * @param string[] $keys
     */
    public function delete(array $keys, string $namespace): bool;

    /**
     * Очищает кэш
     */
    public function clear(): bool;
}
