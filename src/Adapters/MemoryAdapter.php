<?php

declare(strict_types=1);

namespace Fi1a\Cache\Adapters;

/**
 * Адаптер кеширования в памяти
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
        foreach ($keys as $item) {
            [$key, $hash, $namespace] = $item;
            $key = (string) $key;
            $namespace = (string) $namespace;
            if (
                !array_key_exists($namespace, self::$cache)
                || !array_key_exists($key, self::$cache[$namespace])
            ) {
                continue;
            }
            [$value, $itemHash, $expire] = self::$cache[$namespace][$key];
            if ($now >= $expire || $hash !== $itemHash) {
                unset(self::$cache[$namespace][$key]);

                continue;
            }
            $values[$key] = [$value, $itemHash, $expire];
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
    public function have(string $key, string $namespace, ?string $hash = null): bool
    {
        return array_key_exists($namespace, self::$cache)
            && array_key_exists($key, self::$cache[$namespace])
            && count($this->fetch([[$key, $hash, $namespace,]]));
    }

    /**
     * @inheritDoc
     */
    public function delete(array $keys, string $namespace): bool
    {
        if (!array_key_exists($namespace, self::$cache)) {
            return true;
        }

        foreach ($keys as $key) {
            if (!array_key_exists($key, self::$cache[$namespace])) {
                continue;
            }
            unset(self::$cache[$namespace][$key]);
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
