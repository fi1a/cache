<?php

declare(strict_types=1);

namespace Fi1a\Cache\Adapters;

use Fi1a\Cache\DTO\KeyDTO;

/**
 * Интерфейс адаптера кэша
 */
interface AdapterInterface
{
    /**
     * Возвращает значение из кэша
     *
     * @param KeyDTO[] $keys
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
    public function have(KeyDTO $keyDTO): bool;

    /**
     * Удаляет значение из кэша
     *
     * @param KeyDTO[] $keys
     */
    public function delete(array $keys): bool;

    /**
     * Очищает кэш
     */
    public function clear(string $namespace): bool;
}
