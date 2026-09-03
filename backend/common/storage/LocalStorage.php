<?php

declare(strict_types=1);

namespace common\storage;

use RuntimeException;
use yii\web\UploadedFile;

final class LocalStorage implements StorageInterface
{
    /**
     * Absolute path to the physical file storage directory.
     *
     * This is intentionally public because Yii's DI container
     * configures this property after creating the object.
     */
    public string $basePath = '';

    public function init(): void
    {
        if ($this->basePath === '') {
            throw new RuntimeException(
                'LocalStorage basePath is not configured.'
            );
        }

        if (!is_dir($this->basePath)) {
            if (
                !mkdir($this->basePath, 0775, true)
                && !is_dir($this->basePath)
            ) {
                throw new RuntimeException(
                    'Unable to create storage directory: '
                    . $this->basePath
                );
            }
        }
    }

    /**
     * Store an uploaded file.
     */
    public function put(
        UploadedFile $file,
        string $storageKey
    ): string {
        $targetPath = $this->resolvePath($storageKey);

        $directory = dirname($targetPath);

        if (!is_dir($directory)) {
            if (
                !mkdir($directory, 0775, true)
                && !is_dir($directory)
            ) {
                throw new RuntimeException(
                    'Unable to create storage directory: '
                    . $directory
                );
            }
        }

        if (!$file->saveAs($targetPath, false)) {
            throw new RuntimeException(
                'Unable to store uploaded file.'
            );
        }

        return $storageKey;
    }

    /**
     * Delete a stored file.
     */
    public function delete(string $storageKey): bool
    {
        $path = $this->resolvePath($storageKey);

        if (!is_file($path)) {
            return true;
        }

        return unlink($path);
    }

    /**
     * Check whether a stored file exists.
     */
    public function exists(string $storageKey): bool
    {
        return is_file(
            $this->resolvePath($storageKey)
        );
    }

    /**
     * Return the absolute path of a stored file.
     */
    public function path(string $storageKey): string
    {
        return $this->resolvePath($storageKey);
    }

    /**
     * Open a readable stream.
     */
    public function readStream(string $storageKey)
    {
        $path = $this->resolvePath($storageKey);

        if (!is_file($path)) {
            throw new RuntimeException(
                'Stored file does not exist.'
            );
        }

        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new RuntimeException(
                'Unable to open stored file.'
            );
        }

        return $stream;
    }

    /**
     * Return stored file size.
     */
    public function size(string $storageKey): int
    {
        $path = $this->resolvePath($storageKey);

        if (!is_file($path)) {
            throw new RuntimeException(
                'Stored file does not exist.'
            );
        }

        $size = filesize($path);

        if ($size === false) {
            throw new RuntimeException(
                'Unable to determine stored file size.'
            );
        }

        return $size;
    }

    /**
     * Resolve a storage key into an absolute filesystem path.
     */
    private function resolvePath(string $storageKey): string
    {
        if ($this->basePath === '') {
            throw new RuntimeException(
                'LocalStorage basePath is not configured.'
            );
        }

        $storageKey = ltrim($storageKey, '/');

        /*
         * Prevent path traversal.
         */
        if (
            str_contains($storageKey, '..')
            || str_contains($storageKey, "\0")
        ) {
            throw new RuntimeException(
                'Invalid storage key.'
            );
        }

        return rtrim(
            $this->basePath,
            DIRECTORY_SEPARATOR
        )
            . DIRECTORY_SEPARATOR
            . $storageKey;
    }
}