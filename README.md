# PHP библиотека реализующая кэш (cache)

[![Latest Version][badge-release]][packagist]
[![Software License][badge-license]][license]
[![PHP Version][badge-php]][php]
![Coverage Status][badge-coverage]
[![Total Downloads][badge-downloads]][downloads]
[![Support mail][badge-mail]][mail]

PHP библиотека предоставляет классы и методы для реализации кэширования.
Имеются адаптеры для различных механизмов кэширования.

Доступные адаптеры:

- `Fi1a\Cache\Adapters\FilesystemAdapter` - адаптер кэширования в файловой системе;
- `Fi1a\Cache\Adapters\MemoryAdapter` - адаптер кэширования в памяти;
- `Fi1a\Cache\Adapters\NullAdapter` - адаптер null.

## Установка

Установить этот пакет можно как зависимость, используя Composer.

``` bash
composer require fi1a/cache
```

## Доступ к значениям и сохранение кэша

Для доступа к значениям кэша используется класс, реализующий интерфейс `Fi1a\Cache\CacheItemPoolInterface`.
Он представляет собой логический репозиторий для всех значений кэша.
Все кэшируемые элементы извлекаются как объекты `Fi1a\Cache\CacheItemInterface`.

```php
use Fi1a\Cache\Adapters\FilesystemAdapter;
use Fi1a\Cache\CacheItemPool;
use Fi1a\Filesystem\Adapters\LocalAdapter;
use Fi1a\Filesystem\Filesystem;

$cachePath = __DIR__ . '/runtime/cache';

if (!is_dir($cachePath)) {
    mkdir($cachePath, 0775, true);
}

$filesystem = new Filesystem(new LocalAdapter($cachePath));
$adapter = new FilesystemAdapter($filesystem->factoryFolder($cachePath));

$cache = new CacheItemPool($adapter, 'your/namespace');

$item = $cache->getItem('cache-key'); // Fi1a\Cache\CacheItemInterface
$item->get(); // null
$item->set('some-value');
$item->expiresAfter(10); // время жизни 10 секунд
$cache->save($item);

$item = $cache->getItem('cache-key'); // Fi1a\Cache\CacheItemInterface
$item->get(); // 'some-value'
```

Аргументы конструктора `Fi1a\Cache\CacheItemPoolInterface`:

| Метод                     | Описание                                   |
|---------------------------|--------------------------------------------|
| AdapterInterface $adapter | Объект адаптер для хранения кэша           |
| string $namespace = ''    | Пространство имен для ключей кэша          |
| int $defaultTtl = 0       | Время жизни по умолчанию для элемента кэша |

Методы `Fi1a\Cache\CacheItemPoolInterface` реализующего пулл элементов кэша: 

| Метод                                                   | Описание                                                                |
|---------------------------------------------------------|-------------------------------------------------------------------------|
| getItem($key, ?string $hash = null): CacheItemInterface | Возвращает значение                                                     |
| getItems(array $keys): array                            | Возвращает значения ($keys = [['key1', 'hash1',], ['key1', 'hash1',],]) |
| hasItem($key, ?string $hash = null): bool               | Проверяет наличие значения                                              |
| deleteItem($key): bool                                  | Удаляет значение                                                        |
| deleteItems(array $keys): bool                          | Удаляет значения ($keys = ['key1', 'key2', 'key3',])                    |
| clear(): bool                                           | Очищает                                                                 |
| save(CacheItemInterface $item): bool                    | Сохраняет значение                                                      |
| saveDeferred(CacheItemInterface $item): bool            | Отложенное сохранения значения                                          |
| commit(): bool                                          | Выполняет отложенное сохранение                                         |

## Значения кэша

`Fi1a\Cache\CacheItemInterface` определяет элемент, используемый в системе кэширования.
Объект реализующий интерфейс `Fi1a\Cache\CacheItemInterface` создается классом `Fi1a\Cache\CacheItemPoolInterface`,
который отвечает за все необходимые настройки, а также связывает объект с уникальным ключом.

Получить один элемент кэша:

Если нет элемента кэша с ключом 'cache-key', будет возвращен новый элемент кэша.

```php
use Fi1a\Cache\Adapters\FilesystemAdapter;
use Fi1a\Cache\CacheItemPool;
use Fi1a\Filesystem\Adapters\LocalAdapter;
use Fi1a\Filesystem\Filesystem;

$cachePath = __DIR__ . '/runtime/cache';

if (!is_dir($cachePath)) {
    mkdir($cachePath, 0775, true);
}

$filesystem = new Filesystem(new LocalAdapter($cachePath));
$adapter = new FilesystemAdapter($filesystem->factoryFolder($cachePath));

$cache = new CacheItemPool($adapter, 'your/namespace');

$item = $cache->getItem('cache-key'); // Fi1a\Cache\CacheItemInterface
```

Получить несколько элементов кэша:

В случае отсутствия какого либо элемента кэша с определенным ключом, будет возвращен новый элемент кэша.

```php
use Fi1a\Cache\Adapters\FilesystemAdapter;
use Fi1a\Cache\CacheItemPool;
use Fi1a\Filesystem\Adapters\LocalAdapter;
use Fi1a\Filesystem\Filesystem;

$cachePath = __DIR__ . '/runtime/cache';

if (!is_dir($cachePath)) {
    mkdir($cachePath, 0775, true);
}

$filesystem = new Filesystem(new LocalAdapter($cachePath));
$adapter = new FilesystemAdapter($filesystem->factoryFolder($cachePath));
$cache = new CacheItemPool($adapter, 'your/namespace');
$items = $cache->getItems([['cache-key-1'], ['cache-key-2', 'hash2']]); // Fi1a\Cache\CacheItemInterface[]
```

Методы интерфейса `Fi1a\Cache\CacheItemInterface` реализующего элемент кэша:

| Метод                                     | Описание                                 |
|-------------------------------------------|------------------------------------------|
| getKey()                                  | Возвращает ключ                          |
| get()                                     | Возвращает значение                      |
| isHit(): bool                             | Возвращает true, если значение извлечено |
| set($value)                               | Устанавливает значение                   |
| setHash(?string $hash = null)             | Установить хеш значения                  |
| getHash(): ?string                        | Возвращает хеш значения                  |
| expiresAt(?DateTimeInterface $expiration) | Истечет в переданное время               |
| expiresAfter($time)                       | Истекает через переданное время          |
| getExpire()                               | Возвращает когда закончится срок жизни   |

## Адаптер кэширования в файловой системе

Для использования кэширования в файловой системе, следует передать объект `Fi1a\Cache\Adapters\FilesystemAdapter`
в конструктор класса `Fi1a\Cache\CacheItemPool`:

```php
use Fi1a\Cache\Adapters\FilesystemAdapter;
use Fi1a\Cache\CacheItemPool;

$adapter = new FilesystemAdapter(__DIR__ . '/runtime/cache');
$cache = new CacheItemPool($adapter, 'your/namespace');
```

Класс `Fi1a\Cache\Adapters\FilesystemAdapter` в качесве аргумента, конструктор принимает путь до папки, где будут
расположены файлы кэша.

## Адаптер кэширования в памяти

Иногда нужно хранить кэш только на момент выполнения, а по завершению его удалять. Для этого можно использовать
адаптер кэширования в памяти `Fi1a\Cache\Adapters\MemoryAdapter`.

```php
use Fi1a\Cache\Adapters\MemoryAdapter;
use Fi1a\Cache\CacheItemPool;

$adapter = new MemoryAdapter();
$cache = new CacheItemPool($adapter, 'your/namespace');
$items = $cache->getItems([['cache-key-1'], ['cache-key-2', 'hash2']]); // Fi1a\Cache\CacheItemInterface[]
```

## Адаптер null

Адаптер null представляет собой нейтральное, «бездейственное» поведение. Можно использовать в логике,
когда кэширование поддерживается, но оно на данный момент не нужно.

```php
use Fi1a\Cache\Adapters\NullAdapter;
use Fi1a\Cache\CacheItemPool;

$adapter = new NullAdapter();
$cache = new CacheItemPool($adapter, 'your/namespace');
$items = $cache->getItems([['cache-key-1'], ['cache-key-2', 'hash2']]); // Fi1a\Cache\CacheItemInterface[]
```

[badge-release]: https://img.shields.io/packagist/v/fi1a/cache?label=release
[badge-license]: https://img.shields.io/github/license/fi1a/cache?style=flat-square
[badge-php]: https://img.shields.io/packagist/php-v/fi1a/cache?style=flat-square
[badge-coverage]: https://img.shields.io/badge/coverage-100%25-green
[badge-downloads]: https://img.shields.io/packagist/dt/fi1a/cache.svg?style=flat-square&colorB=mediumvioletred
[badge-mail]: https://img.shields.io/badge/mail-support%40fi1a.ru-brightgreen

[packagist]: https://packagist.org/packages/fi1a/cache
[license]: https://github.com/fi1a/cache/blob/master/LICENSE
[php]: https://php.net
[downloads]: https://packagist.org/packages/fi1a/cache
[mail]: mailto:support@fi1a.ru