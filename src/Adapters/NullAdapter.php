<?php

declare(strict_types=1);

namespace Fi1a\Cache\Adapters;

use Fi1a\Cache\DTO\KeyDTO;

/**
 * Адаптер null
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
    public function have(KeyDTO $keyDTO): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function delete(array $keys): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(string $namespace): bool
    {
        return true;
    }
}
