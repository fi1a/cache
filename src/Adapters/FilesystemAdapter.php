<?php

declare(strict_types=1);

namespace Fi1a\Cache\Adapters;

use ErrorException;
use Fi1a\Cache\DTO\KeyDTO;
use Fi1a\Filesystem\FileInterface;
use Fi1a\Filesystem\FolderInterface;
use InvalidArgumentException;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

/**
 * Адаптер кэширования в файловой системе
 */
class FilesystemAdapter implements AdapterInterface
{
    /**
     * @var FolderInterface
     */
    private $folder;

    /**
     * Конструктор
     */
    public function __construct(FolderInterface $folder)
    {
        if (!$folder->isExist() && !$folder->make()) {
            throw new ErrorException(
                sprintf('Не удалось создать папку "%s"', $folder->getPath())
            );
        }
        $this->folder = $folder;
    }

    /**
     * @inheritDoc
     */
    public function fetch(array $keys): array
    {
        $values = [];
        $now = time();
        foreach ($keys as $keyDTO) {
            $file = $this->getFile($keyDTO->key, $keyDTO->namespace);
            if (!$file->isExist()) {
                continue;
            }
            [$expire, $itemHash, $value] = explode(PHP_EOL, (string) $file->read());
            if (!$itemHash) {
                $itemHash = null;
            }
            if ($now >= $expire || $keyDTO->hash !== $itemHash) {
                $file->delete();

                continue;
            }
            /**
             * @var mixed $value
             */
            $value = unserialize($value);

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
        $result = true;
        foreach ($values as $key => $item) {
            $key = (string) $key;
            [$value, $hash, $expire, $namespace] = $item;
            if (!is_null($expire)) {
                $expire = (int) $expire;
            }
            $hash = (string) $hash;
            $namespace = (string) $namespace;
            $expire = $expire ?: strtotime('+1 year', $now);
            $file = $this->getFile($key, $namespace);
            /**
             * @var FolderInterface $folder
             */
            $folder = $file->getParent();
            $value = $expire . PHP_EOL . $hash . PHP_EOL . serialize($value);
            if (
                (

                    !$folder->isExist()
                    && !$folder->make()
                )
                || $file->write($value) === false
            ) {
                $result = false;
            }
        }

        return $result;
    }

    /**
    * @inheritDoc
    */
    public function have(KeyDTO $keyDTO): bool
    {
        $file = $this->getFile($keyDTO->key, $keyDTO->namespace);

        return $file->isExist() && count($this->fetch([$keyDTO]));
    }

    /**
     * @inheritDoc
     */
    public function delete(array $keys): bool
    {
        $result = true;
        foreach ($keys as $keyDto) {
            $file = $this->getFile($keyDto->key, $keyDto->namespace);
            $result = (!$file->isExist() || $file->delete() || !$file->isExist()) && $result;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function clear(string $namespace): bool
    {
        $folder = $this->folder;
        if ($namespace) {
            $folder = $folder->getFolder($namespace);
        }
        if (!$folder->isExist()) {
            return true;
        }

        return $folder->delete();
    }

    /**
     * Возвращает файл
     */
    private function getFile(string $key, string $namespace): FileInterface
    {
        if (preg_match('/([\:\*\?\"\<\>\|\+\%\!\@]+)/mui', $namespace) > 0) {
            throw new InvalidArgumentException('Использованы запрещенные символы в $namespace');
        }

        $folder = $this->folder;
        if ($namespace) {
            $folder = $folder->getFolder($namespace);
        }

        return $folder->getFile(
            $key[0] . DIRECTORY_SEPARATOR . $key[1]
            . DIRECTORY_SEPARATOR . $key[2] . DIRECTORY_SEPARATOR . $key
        );
    }
}
