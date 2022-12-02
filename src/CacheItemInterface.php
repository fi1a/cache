<?php

declare(strict_types=1);

namespace Fi1a\Cache;

use DateInterval;
use DateTimeInterface;

/**
 * Интерфейс элемента кэша
 */
interface CacheItemInterface
{
    /**
     * Возвращает ключ
     *
     * @return mixed
     */
    public function getKey();

    /**
     * Возвращает значение
     *
     * @return mixed
     */
    public function get();

    /**
     * Возвращает true, если значение извлечено
     */
    public function isHit(): bool;

    /**
     * Устанавливает значение
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function set($value);

    /**
     * Установить хеш значения
     *
     * @return $this
     */
    public function setHash(?string $hash = null);

    /**
     * Возвращает хеш значения
     */
    public function getHash(): ?string;

    /**
     * Истечет в переданное время
     *
     * @return $this
     */
    public function expiresAt(?DateTimeInterface $expiration);

    /**
     * @param int|DateInterval|null $time
     *
     * @return $this
     */
    public function expiresAfter($time);

    /**
     * Возвращает когда закончится срок жизни
     */
    public function getExpire(): int;
}
