<?php

declare(strict_types=1);

namespace Fi1a\Cache;

use DateInterval;
use DateTime;
use DateTimeInterface;

/**
 * Элемент кэша
 *
 * @psalm-suppress MissingConstructor
 */
class CacheItem implements CacheItemInterface
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var bool
     */
    private $isHit = false;

    /**
     * @var int
     */
    private $expire = 0;

    /**
     * @var int
     */
    private $defaultTtl = 0;

    /**
     * @var string|null
     */
    private $hash;

    /**
     * @inheritDoc
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * @inheritDoc
     */
    public function set($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setHash(?string $hash = null)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHash(): ?string
    {
        return $this->hash;
    }

    /**
     * @inheritDoc
     */
    public function expiresAt(?DateTimeInterface $expiration)
    {
        if (is_null($expiration)) {
            $this->expire = $this->defaultTtl > 0 ? time() + $this->defaultTtl : 0;

            return $this;
        }
        $this->expire = (int) $expiration->format('U');

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function expiresAfter($time)
    {
        if (is_null($time)) {
            $this->expire = $this->defaultTtl > 0 ? time() + $this->defaultTtl : 0;

            return $this;
        }
        if ($time instanceof DateInterval) {
            $this->expire = (int) (new DateTime())->add($time)->format('U');

            return $this;
        }
        $this->expire = $time + time();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getExpire(): int
    {
        return $this->expire;
    }
}
