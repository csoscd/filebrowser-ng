<?php

declare(strict_types=1);

namespace FilebrowserNg\Storage\Smb;

use FilebrowserNg\Storage\StorageProviderInterface;
use Icewind\SMB\BasicAuth;
use Icewind\SMB\IShare;
use Icewind\SMB\ServerFactory;
use Psr\Log\LoggerInterface;

class SmbProvider implements StorageProviderInterface
{
    private ?object $server = null;
    /** @var array<string, IShare> */
    private array $shareCache = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $domain = '',
    ) {}

    public function connect(string $host, string $username, string $password, array $options = []): bool
    {
        try {
            $domain = $options['domain'] ?? $this->domain;
            $auth   = new BasicAuth($username, $domain, $password);
            $factory = new ServerFactory();
            $server  = $factory->createServer($host, $auth);
            $server->listShares(); // verify credentials
            $this->server     = $server;
            $this->shareCache = [];
            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('SMB connect failed', [
                'host'  => $host,
                'user'  => $username,
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    public function listShares(): array
    {
        $this->assertConnected();
        $shares = [];
        foreach ($this->server->listShares() as $share) {
            $name = $share->getName();
            if (!str_ends_with($name, '$')) {
                $shares[] = ['name' => $name, 'type' => 'smb'];
            }
        }
        return $shares;
    }

    public function listDirectory(string $share, string $path = ''): array
    {
        $smbShare = $this->getShare($share);
        $smbPath  = '/' . ltrim($path, '/');

        $entries = [];
        foreach ($smbShare->dir($smbPath) as $info) {
            if (in_array($info->getName(), ['.', '..'], true)) {
                continue;
            }
            $entries[] = [
                'name'     => $info->getName(),
                'path'     => ltrim($path . '/' . $info->getName(), '/'),
                'type'     => $info->isDirectory() ? 'dir' : 'file',
                'size'     => $info->isDirectory() ? 0 : $info->getSize(),
                'modified' => $info->getMTime(),
            ];
        }

        usort($entries, static fn($a, $b) => $a['type'] === $b['type']
            ? strnatcasecmp($a['name'], $b['name'])
            : ($a['type'] === 'dir' ? -1 : 1)
        );

        return $entries;
    }

    public function getFile(string $share, string $path): mixed
    {
        $smbShare = $this->getShare($share);
        $tmpFile  = tempnam(sys_get_temp_dir(), 'fbng_');
        if ($tmpFile === false) {
            throw new \RuntimeException('Failed to create temp file');
        }
        try {
            $smbShare->get('/' . ltrim($path, '/'), $tmpFile);
        } catch (\Throwable $e) {
            unlink($tmpFile);
            throw $e;
        }
        $stream = fopen($tmpFile, 'rb');
        // clean up after the stream is consumed
        register_shutdown_function(static function () use ($tmpFile): void {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        });
        return $stream;
    }

    public function putFile(string $share, string $path, mixed $source): void
    {
        $smbShare   = $this->getShare($share);
        $remotePath = '/' . ltrim($path, '/');

        if (is_string($source) && file_exists($source)) {
            $smbShare->put($source, $remotePath);
            return;
        }

        // stream → write to tmp first
        $tmpFile = tempnam(sys_get_temp_dir(), 'fbng_up_');
        if ($tmpFile === false) {
            throw new \RuntimeException('Failed to create temp file');
        }
        try {
            $dest = fopen($tmpFile, 'wb');
            stream_copy_to_stream($source, $dest);
            fclose($dest);
            $smbShare->put($tmpFile, $remotePath);
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    public function rename(string $share, string $oldPath, string $newPath): void
    {
        $this->getShare($share)->rename(
            '/' . ltrim($oldPath, '/'),
            '/' . ltrim($newPath, '/')
        );
    }

    public function delete(string $share, string $path): void
    {
        $smbShare = $this->getShare($share);
        $smbPath  = '/' . ltrim($path, '/');
        try {
            $smbShare->del($smbPath);
        } catch (\Icewind\SMB\Exception\InvalidTypeException) {
            $this->deleteRecursive($smbShare, $smbPath);
        }
    }

    public function createDirectory(string $share, string $path): void
    {
        $this->getShare($share)->mkdir('/' . ltrim($path, '/'));
    }

    private function deleteRecursive(IShare $share, string $path): void
    {
        foreach ($share->dir($path) as $info) {
            if (in_array($info->getName(), ['.', '..'], true)) {
                continue;
            }
            $child = rtrim($path, '/') . '/' . $info->getName();
            if ($info->isDirectory()) {
                $this->deleteRecursive($share, $child);
            } else {
                $share->del($child);
            }
        }
        $share->rmdir($path);
    }

    private function getShare(string $name): IShare
    {
        $this->assertConnected();
        if (!isset($this->shareCache[$name])) {
            $this->shareCache[$name] = $this->server->getShare($name);
        }
        return $this->shareCache[$name];
    }

    private function assertConnected(): void
    {
        if ($this->server === null) {
            throw new \RuntimeException('Not connected. Call connect() first.');
        }
    }
}
