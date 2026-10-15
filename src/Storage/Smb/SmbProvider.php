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

    private string $connectionHost     = '';
    private string $connectionUsername = '';
    private string $connectionPassword = '';
    private string $connectionDomain   = '';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $domain = '',
    ) {}

    public function connect(string $host, string $username, string $password, array $options = []): bool
    {
        try {
            $domain = $options['domain'] ?? $this->domain;
            // Pass null for empty domain – BasicAuth with '' generates "-W ''"
            // which causes smbclient to fail silently on some versions.
            $auth   = new BasicAuth($username, $domain !== '' ? $domain : null, $password);
            $factory = new ServerFactory();
            $server  = $factory->createServer($host, $auth);
            $server->listShares(); // verify credentials
            $this->server           = $server;
            $this->shareCache       = [];
            $this->connectionHost     = $host;
            $this->connectionUsername = $username;
            $this->connectionPassword = $password;
            $this->connectionDomain   = $options['domain'] ?? $this->domain;
            return true;
        } catch (\Throwable $e) {
            $this->logger->warning('SMB connect failed', [
                'host'  => $host,
                'user'  => $username,
                'error' => $e->getMessage(),
                'class' => get_class($e),
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

    public function getDiskInfo(string $shareName): ?array
    {
        if ($this->connectionHost === '') {
            return null;
        }

        // Native path: smbclient PHP extension (libsmbclient-php) – no exec() needed
        if (function_exists('smbclient_statvfs')) {
            return $this->getDiskInfoNative($shareName);
        }

        // Fallback: smbclient binary via exec() (dskattr removed in smbclient 4.22)
        $this->logger->warning('getDiskInfo: smbclient PHP extension not loaded, disk info unavailable');
        return null;
    }

    private function getDiskInfoNative(string $shareName): ?array
    {
        $state = smbclient_state_new();
        if ($state === false) {
            return null;
        }
        $domain = $this->connectionDomain !== '' ? $this->connectionDomain : null;
        if (!smbclient_state_init($state, $domain, $this->connectionUsername, $this->connectionPassword)) {
            smbclient_state_free($state);
            return null;
        }
        $uri  = 'smb://' . $this->connectionHost . '/' . $shareName;
        $info = @smbclient_statvfs($state, $uri);
        smbclient_state_free($state);

        if (!is_array($info) || empty($info['frsize'])) {
            $this->logger->warning('getDiskInfo: statvfs returned no data', ['share' => $shareName]);
            return null;
        }
        $this->logger->info('getDiskInfo statvfs raw', ['share' => $shareName, 'statvfs' => $info]);
        return [
            'total' => (int) $info['blocks'] * (int) $info['frsize'],
            'free'  => (int) $info['bavail'] * (int) $info['frsize'],
        ];
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

    public function openFileStream(string $share, string $path, bool $append = false): mixed
    {
        $smbShare   = $this->getShare($share);
        $remotePath = '/' . ltrim($path, '/');
        return $append
            ? $smbShare->append($remotePath)
            : $smbShare->write($remotePath);
    }

    public function rename(string $share, string $oldPath, string $newPath): void
    {
        $this->getShare($share)->rename(
            '/' . ltrim($oldPath, '/'),
            '/' . ltrim($newPath, '/')
        );
    }

    public function copy(string $share, string $sourcePath, string $destPath, string $destShare = ''): void
    {
        $srcSmbShare  = $this->getShare($share);
        $destSmbShare = $destShare !== '' ? $this->getShare($destShare) : $srcSmbShare;
        $this->copyRecursive($srcSmbShare, $sourcePath, $destSmbShare, $destPath);
    }

    private function copyRecursive(IShare $srcShare, string $src, IShare $destShare, string $dest): void
    {
        $srcSmb  = '/' . ltrim($src,  '/');
        $destSmb = '/' . ltrim($dest, '/');

        $info = $srcShare->stat($srcSmb);
        if ($info->isDirectory()) {
            $destShare->mkdir($destSmb);
            foreach ($srcShare->dir($srcSmb) as $item) {
                if (in_array($item->getName(), ['.', '..'], true)) {
                    continue;
                }
                $this->copyRecursive(
                    $srcShare,
                    ltrim($src,  '/') . '/' . $item->getName(),
                    $destShare,
                    ltrim($dest, '/') . '/' . $item->getName(),
                );
            }
        } else {
            $tmpFile = tempnam(sys_get_temp_dir(), 'fbng_copy_');
            if ($tmpFile === false) {
                throw new \RuntimeException('Failed to create temp file');
            }
            try {
                $srcShare->get($srcSmb, $tmpFile);
                $destShare->put($tmpFile, $destSmb);
            } finally {
                if (file_exists($tmpFile)) {
                    unlink($tmpFile);
                }
            }
        }
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
