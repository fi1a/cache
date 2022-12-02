<?php

declare(strict_types=1);

namespace Fi1a\Cache\Adapters;

/**
 * Адаптер кэширования (Null объект)
 */
class NullAdapter implements AdapterInterface
{
    /**
     * @inheritDoc
     */
    public function fetch(array $keys): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function save(array $values): bool
    {
        return true;
    }

    /**
    * @inheritDoc
    */
    public function have(string $key, string $namespace, ?string $hash = null): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function delete(array $keys, string $namespace): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(?string $namespace = null): bool
    {
        return true;
    }
}
