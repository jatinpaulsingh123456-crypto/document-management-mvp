<?php

declare(strict_types=1);

namespace common\storage;

use yii\web\UploadedFile;

interface StorageInterface
{
    /**
     * Store an uploaded file and return its storage key.
     */
    public function put(
        UploadedFile $file,
        string $storageKey
    ): string;

    /**
     * Delete a stored file.
     */
    public function delete(string $storageKey): bool;

    /**
     * Check whether a stored file exists.
     */
    public function exists(string $storageKey): bool;

    /**
     * Return the absolute/local path for a stored file.
     *
     * This is primarily useful for local storage.
     */
    public function path(string $storageKey): string;

    /**
     * Return a readable stream for a stored file.
     */
    public function readStream(string $storageKey);

    /**
     * Return the file size in bytes.
     */
    public function size(string $storageKey): int;
}