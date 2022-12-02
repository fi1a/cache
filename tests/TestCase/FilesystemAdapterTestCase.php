<?php

declare(strict_types=1);

namespace Fi1a\Unit\Cache\TestCase;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FilesystemAdapterTestCase extends TestCase
{
    /**
     * @var string
     */
    protected static $folderPath = __DIR__ . '/../runtime';

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        self::deleteCacheFolder();
    }

    /**
     * Удаляет директорию с кэшем
     */
    protected static function deleteCacheFolder(): void
    {
        if (!is_dir(self::$folderPath)) {
            return;
        }

        $directoryIterator = new RecursiveDirectoryIterator(self::$folderPath, RecursiveDirectoryIterator::SKIP_DOTS);
        $filesIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($filesIterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir(self::$folderPath);
    }
}
