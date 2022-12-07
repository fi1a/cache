<?php

declare(strict_types=1);

namespace Fi1a\Cache\DTO;

/**
 * Ключ кэша
 *
 * @psalm-suppress MissingConstructor
 */
class KeyDTO
{
    /**
     * @var string
     */
    public $key;

    /**
     * @var string|null
     */
    public $hash;

    /**
     * @var string
     */
    public $namespace;
}
