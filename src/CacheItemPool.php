<?php

declare(strict_types=1);

namespace Fi1a\Cache;

use Fi1a\Cache\Adapters\AdapterInterface;
use Fi1a\Cache\DTO\KeyDTO;
use Fi1a\Hydrator\Extractor;
use Fi1a\Hydrator\ExtractorInterface;
use Fi1a\Hydrator\Hydrator;
use Fi1a\Hydrator\HydratorInterface;

/**
 * Кэш
 */
class CacheItemPool implements CacheItemPoolInterface
{
    /**
     * @var CacheItemInterface[]
     */
    private $deferred = [];

    /**
     * @var int
     */
    private $defaultTtl;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var AdapterInterface
     */
    private $adapter;

    /**
     * @var HydratorInterface
     */
    private $hydrator;

    /**
     * @var ExtractorInterface
     */
    private $extractor;

    /**
     * @inheritDoc
     */
    public function __construct(AdapterInterface $adapter, string $namespace = '', int $defaultTtl = 0)
    {
        $this->namespace = $namespace;
        $this->defaultTtl = $defaultTtl;
        $this->adapter = $adapter;
        $this->hydrator = new Hydrator();
        $this->extractor = new Extractor();
    }

    /**
     * @inheritDoc
     */
    public function getItem($key, ?string $hash = null): CacheItemInterface
    {
        $this->checkDeferred();
        $isHit = false;
        $value = null;
        $expire = null;
        try {
            /**
             * @var KeyDTO $keyDto
             */
            $keyDto = $this->hydrator->hydrate(
                [
                    'key' => $this->getStoredKey($key),
                    'hash' => $hash,
                    'namespace' => $this->namespace,
                ],
                KeyDTO::class
            );
            $items = $this->adapter->fetch([$keyDto]);
            if (count($items)) {
                $item = array_shift($items);
                [$value, $hash, $expire] = $item;
                if (!is_null($expire)) {
                    $expire = (int) $expire;
                }
                if (!is_null($hash)) {
                    $hash = (string) $hash;
                }
                $isHit = true;
            }
        } catch (\Throwable $exception) {
        }

        return $this->factoryCacheItem($key, $value, $hash, $isHit, $expire);
    }

    /**
     * @inheritDoc
     */
    public function getItems(array $keys): array
    {
        $this->checkDeferred();
        $items = [];
        foreach ($keys as $item) {
            if (count($item) === 1) {
                $item[] = null;
            }
            [$key, $hash] = $item;
            if (!is_null($hash)) {
                $hash = (string) $hash;
            }
            $items[(string) $key] = $this->getItem($key, $hash);
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    public function hasItem($key, ?string $hash = null): bool
    {
        $this->checkDeferred();
        $result = false;
        try {
            /**
             * @var KeyDTO $keyDto
             */
            $keyDto = $this->hydrator->hydrate(
                [
                    'key' => $this->getStoredKey($key),
                    'hash' => $hash,
                    'namespace' => $this->namespace,
                ],
                KeyDTO::class
            );
            $result = $this->adapter->have($keyDto);
        } catch (\Throwable $exception) {
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function deleteItem($key): bool
    {
        $this->checkDeferred();
        $result = false;
        try {
            /**
             * @var KeyDTO $keyDto
             */
            $keyDto = $this->hydrator->hydrate(
                [
                    'key' => $this->getStoredKey($key),
                    'namespace' => $this->namespace,
                ],
                KeyDTO::class
            );
            $result = $this->adapter->delete([$keyDto]);
        } catch (\Throwable $exception) {
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function deleteItems(array $keys): bool
    {
        $this->checkDeferred();
        /**
         * @var KeyDTO[] $keysDTO
         */
        $keysDTO = [];
        /**
         * @var mixed $key
         */
        foreach ($keys as $key) {
            /**
             * @var KeyDTO $keyDto
             */
            $keyDto = $this->hydrator->hydrate(
                [
                    'key' => $this->getStoredKey($key),
                    'namespace' => $this->namespace,
                ],
                KeyDTO::class
            );
            $keysDTO[] = $keyDto;
        }
        $result = false;
        try {
            $result = $this->adapter->delete($keysDTO);
        } catch (\Throwable $exception) {
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $this->checkDeferred();
        $result = false;
        try {
            $result = $this->adapter->clear($this->namespace);
        } catch (\Throwable $exception) {
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function save(CacheItemInterface $item): bool
    {
        $this->checkDeferred();
        $this->deferred[$this->getStoredKey($item->getKey())] = $item;

        return $this->commit();
    }

    /**
     * @inheritDoc
     */
    public function saveDeferred(CacheItemInterface $item): bool
    {
        $deferred = $this->getDeferred();
        $deferred[$this->getStoredKey($item->getKey())] = $item;
        $this->setDeferred($deferred);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function commit(): bool
    {
        $expand = $this->expandValues();
        $this->setDeferred([]);
        $result = false;
        try {
            $result = $this->adapter->save($expand);
        } catch (\Throwable $exception) {
        }

        return $result;
    }

    /**
     * Возвращает отложенные значения
     *
     * @return CacheItemInterface[]
     */
    private function getDeferred(): array
    {
        return $this->deferred;
    }

    /**
     * Проверяет наличие отложенных значений, если они есть - сохраняет
     */
    private function checkDeferred(): void
    {
        if (count($this->getDeferred())) {
            $this->commit();
        }
    }

    /**
     * Фабричный метод для элемента кэша
     *
     * @param mixed $key
     * @param mixed $value
     */
    private function factoryCacheItem($key, $value, ?string $hash, bool $isHit, ?int $expire): CacheItemInterface
    {
        if (!$expire) {
            $expire = $this->defaultTtl ? $this->defaultTtl + time() : 0;
        }

        /**
         * @var CacheItemInterface $cacheItem
         */
        $cacheItem = $this->hydrator->hydrate([
            'key' => $key,
            'value' => $value,
            'hash' => $hash,
            'isHit' => $isHit,
            'defaultTtl' => $this->defaultTtl,
            'expire' => $expire,
        ], CacheItem::class);

        return $cacheItem;
    }

    /**
     * Возврашает значения
     *
     * @return mixed[][]
     */
    private function expandValues(): array
    {
        $expand = [];
        foreach ($this->getDeferred() as $key => $item) {
            $expand[$key] = array_values(
                array_merge(
                    $this->extractor->extract($item, ['value', 'hash', 'expire']),
                    [$this->namespace]
                )
            );
        }

        return $expand;
    }

    /**
     * Возвращает ключ для хранения значения
     *
     * @param mixed $key
     */
    private function getStoredKey($key): string
    {
        if (is_object($key) || is_array($key)) {
            $key = serialize($key);
        }

        return sha1((string) $key);
    }

    /**
     * Устанавливает отложенные значения
     *
     * @param CacheItemInterface[] $deferred
     */
    private function setDeferred(array $deferred): void
    {
        $this->deferred = $deferred;
    }

    public function __destruct()
    {
        $this->checkDeferred();
    }
}
