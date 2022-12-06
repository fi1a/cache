<?php

declare(strict_types=1);

namespace Fi1a\Cache\Adapters;

use ErrorException;
use Fi1a\Cache\DTO\KeyDTO;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use UnexpectedValueException;

use const DIRECTORY_SEPARATOR;
use const LOCK_SH;
use const LOCK_UN;
use const PHP_EOL;

/**
 * Адаптер кэширования в файловой системе
 */
class FilesystemAdapter implements AdapterInterface
{
    /**
     * @var string
     */
    private $folderPath;

    /**
     * Конструктор
     */
    public function __construct(string $folderPath)
    {
        if (
            !is_dir($folderPath)
            && !@mkdir($folderPath, 0775, true)
        ) {
            throw new ErrorException(sprintf('Не удалось создать папку "%s"', $folderPath));
        }
        $this->folderPath = $folderPath;
    }

    /**
     * @inheritDoc
     */
    public function fetch(array $keys): array
    {
        $values = [];
        $now = time();
        foreach ($keys as $keyDTO) {
            $path = $this->getFile($keyDTO->key, $keyDTO->namespace);
            if (!is_file($path) || !($file = @fopen($path, 'rb'))) {
                continue;
            }
            flock($file, LOCK_SH);
            $expire = (int) fgets($file);
            $itemHash = trim(fgets($file));
            if (!$itemHash) {
                $itemHash = null;
            }
            if ($now >= $expire || $keyDTO->hash !== $itemHash) {
                flock($file, LOCK_UN);
                fclose($file);
                @unlink($path);

                continue;
            }
            /**
             * @var mixed $value
             */
            $value = unserialize(stream_get_contents($file));

            flock($file, LOCK_UN);
            fclose($file);
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
            $filePath = $this->getFile($key, $namespace);
            $folderPath = dirname($filePath);
            $value = $expire . PHP_EOL . $hash . PHP_EOL . serialize($value);
            if (
                (
                    !is_dir($folderPath)
                    && !@mkdir($folderPath, 0775, true)
                )
                || file_put_contents($filePath, $value) === false
            ) {
                $result = false;

                continue;
            }
            @touch($filePath, $expire);
        }

        return $result;
    }

    /**
    * @inheritDoc
    */
    public function have(KeyDTO $keyDTO): bool
    {
        $filePath = $this->getFile($keyDTO->key, $keyDTO->namespace);

        return is_file($filePath) && count($this->fetch([$keyDTO]));
    }

    /**
     * @inheritDoc
     */
    public function delete(array $keys): bool
    {
        $result = true;
        foreach ($keys as $keyDto) {
            $filePath = $this->getFile($keyDto->key, $keyDto->namespace);
            $result = (!is_file($filePath) || unlink($filePath) || !is_file($filePath)) && $result;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function clear(string $namespace): bool
    {
        $folderPath = $this->folderPath;
        if ($namespace) {
            $folderPath = $this->folderPath . DIRECTORY_SEPARATOR . $namespace;
        }
        if (!is_dir($folderPath)) {
            return true;
        }

        try {
            $directoryIterator = new RecursiveDirectoryIterator(
                $folderPath,
                RecursiveDirectoryIterator::SKIP_DOTS
            );
        } catch (UnexpectedValueException $exception) {
            return false;
        }

        $filesIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);

        /**
         * @var RecursiveDirectoryIterator $file
         */
        foreach ($filesIterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());

                continue;
            }

            unlink($file->getRealPath());
        }
        rmdir($folderPath);

        return !is_dir($folderPath);
    }

    /**
     * Возвращает файл
     */
    private function getFile(string $key, string $namespace): string
    {
        if (preg_match('/([\:\*\?\"\<\>\|\+\%\!\@]+)/mui', $namespace) > 0) {
            throw new InvalidArgumentException('Использованы запрещенные символы в $namespace');
        }

        return $this->folderPath
            . ($namespace ? DIRECTORY_SEPARATOR . $namespace : '')
            . DIRECTORY_SEPARATOR . $key[0]
            . DIRECTORY_SEPARATOR . $key[1]
            . DIRECTORY_SEPARATOR . $key[2]
            . DIRECTORY_SEPARATOR . $key;
    }
}
