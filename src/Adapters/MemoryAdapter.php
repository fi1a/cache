<?php

declare(strict_types=1);

namespace Fi1a\Cache\Adapters;

use Fi1a\Cache\DTO\KeyDTO;

/**
 * Адаптер кэширования в памяти
 */
class MemoryAdapter implements AdapterInterface
{
    /**
     * @var mixed[][][]
     */
    private static $cache = [];

    /**
     * @inheritDoc
     */
    public function fetch(array $keys): array
    {
        $values = [];
        $now = time();
        foreach ($keys as $keyDTO) {
            if (
                !array_key_exists($keyDTO->namespace, self::$cache)
                || !array_key_exists($keyDTO->key, self::$cache[$keyDTO->namespace])
            ) {
                continue;
            }
            [$value, $itemHash, $expire] = self::$cache[$keyDTO->namespace][$keyDTO->key];
            if ($now >= $expire || $keyDTO->hash !== $itemHash) {
                unset(self::$cache[$keyDTO->namespace][$keyDTO->key]);

                continue;
            }
            $values[$keyDTO->key] = [$value, $itemHash, $expire];
        }

        return $values;
    }

    /**
     * @inheritDoc
     */
    public function save(array $values): bool
    {
        $now = time();
        foreach ($values as $key => $item) {
            $key = (string) $key;
            $item[2] = isset($item[2]) && $item[2] ? (int) $item[2] : strtotime('+1 year', $now);
            $namespace = (string) $item[3];
            self::$cache[$namespace][$key] = $item;
        }

        return true;
    }

    /**
    * @inheritDoc
    */
    public function have(KeyDTO $keyDTO): bool
    {
        return array_key_exists($keyDTO->namespace, self::$cache)
            && array_key_exists($keyDTO->key, self::$cache[$keyDTO->namespace])
            && count($this->fetch([$keyDTO]));
    }

    /**
     * @inheritDoc
     */
    public function delete(array $keys): bool
    {
        foreach ($keys as $keyDto) {
            if (!array_key_exists($keyDto->namespace, self::$cache)) {
                continue;
            }
            if (!array_key_exists($keyDto->key, self::$cache[$keyDto->namespace])) {
                continue;
            }
            unset(self::$cache[$keyDto->namespace][$keyDto->key]);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function clear(string $namespace): bool
    {
        self::$cache[$namespace] = [];

        return true;
    }
}
