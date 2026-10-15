<?php

declare(strict_types=1);

namespace FilebrowserNg\Storage;

interface StorageProviderInterface
{
    public function connect(string $host, string $username, string $password, array $options = []): bool;

    /** @return array<array{name: string, type: string}> */
    public function listShares(): array;

    /** @return array<array{name: string, path: string, type: string, size: int, modified: int}> */
    public function listDirectory(string $share, string $path = ''): array;

    /** @return resource */
    public function getFile(string $share, string $path): mixed;

    public function putFile(string $share, string $path, mixed $source): void;

    /**
     * Open a writable stream directly on the share (no local temp file).
     * Pass $append=true for all chunks after the first.
     *
     * @return resource
     */
    public function openFileStream(string $share, string $path, bool $append = false): mixed;

    public function rename(string $share, string $oldPath, string $newPath): void;

    public function copy(string $share, string $sourcePath, string $destPath, string $destShare = ''): void;

    public function delete(string $share, string $path): void;

    public function createDirectory(string $share, string $path): void;
}
