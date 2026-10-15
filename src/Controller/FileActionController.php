<?php

declare(strict_types=1);

namespace FilebrowserNg\Controller;

use FilebrowserNg\Http\Request;
use FilebrowserNg\Http\Response;
use FilebrowserNg\Session\SessionManager;
use FilebrowserNg\Storage\StorageProviderInterface;
use Noodlehaus\Config;
use Psr\Log\LoggerInterface;

class FileActionController
{
    public function __construct(
        private readonly SessionManager $session,
        private readonly StorageProviderInterface $provider,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function rename(Request $request): void
    {
        $this->requireAuth();
        $data    = $request->jsonBody();
        $share   = (string) ($data['share']    ?? '');
        $oldPath = (string) ($data['old_path'] ?? '');
        $newName = (string) ($data['new_name'] ?? '');
        $server  = (string) ($data['server']   ?? '');

        if ($share === '' || $oldPath === '' || $newName === '') {
            Response::json(['error' => 'share, old_path, new_name required'], 400);
        }

        $dir     = dirname($oldPath);
        $newPath = $dir === '.' ? $newName : $dir . '/' . $newName;

        $this->connectSmb($server);

        try {
            $this->provider->rename($share, $oldPath, $newPath);
            $this->logger->info('Renamed', [
                'user'  => $this->session->getUsername(),
                'share' => $share,
                'from'  => $oldPath,
                'to'    => $newPath,
            ]);
            Response::json(['success' => true, 'new_path' => $newPath]);
        } catch (\Throwable $e) {
            $this->logger->error('Rename failed', ['error' => $e->getMessage()]);
            Response::json(['error' => 'Rename failed'], 500);
        }
    }

    public function delete(Request $request): void
    {
        $this->requireAuth();
        $data   = $request->jsonBody();
        $share  = (string) ($data['share']  ?? '');
        $path   = (string) ($data['path']   ?? '');
        $server = (string) ($data['server'] ?? '');

        if ($share === '' || $path === '') {
            Response::json(['error' => 'share and path required'], 400);
        }

        $this->connectSmb($server);

        try {
            $this->provider->delete($share, $path);
            $this->logger->info('Deleted', [
                'user'  => $this->session->getUsername(),
                'share' => $share,
                'path'  => $path,
            ]);
            Response::json(['success' => true]);
        } catch (\Throwable $e) {
            $this->logger->error('Delete failed', ['error' => $e->getMessage()]);
            Response::json(['error' => 'Delete failed'], 500);
        }
    }

    public function mkdir(Request $request): void
    {
        $this->requireAuth();
        $data    = $request->jsonBody();
        $share   = (string) ($data['share']  ?? '');
        $path    = (string) ($data['path']   ?? '');
        $dirName = (string) ($data['name']   ?? '');
        $server  = (string) ($data['server'] ?? '');

        if ($share === '' || $dirName === '') {
            Response::json(['error' => 'share and name required'], 400);
        }

        $fullPath = $path === '' ? $dirName : rtrim($path, '/') . '/' . $dirName;

        $this->connectSmb($server);

        try {
            $this->provider->createDirectory($share, $fullPath);
            $this->logger->info('Directory created', [
                'user'  => $this->session->getUsername(),
                'share' => $share,
                'path'  => $fullPath,
            ]);
            Response::json(['success' => true, 'path' => $fullPath]);
        } catch (\Throwable $e) {
            $this->logger->error('Mkdir failed', ['error' => $e->getMessage()]);
            Response::json(['error' => 'Create directory failed'], 500);
        }
    }

    public function move(Request $request): void
    {
        $this->requireAuth();
        $data       = $request->jsonBody();
        $share      = (string) ($data['share']       ?? '');
        $oldPath    = urldecode((string) ($data['old_path']    ?? ''));
        $destDir    = urldecode((string) ($data['dest_dir']    ?? ''));
        $destShare  = (string) ($data['dest_share']  ?? $share);
        $server     = (string) ($data['server']      ?? '');
        $destServer = (string) ($data['dest_server'] ?? $server);

        if ($share === '' || $oldPath === '') {
            Response::json(['error' => 'share and old_path required'], 400);
        }

        $newPath     = ltrim(rtrim($destDir, '/') . '/' . basename($oldPath), '/');
        $isCross     = $this->isCrossServer($server, $destServer);
        $actualDest  = $destShare !== '' ? $destShare : $share;

        $this->connectSmb($server);

        try {
            if ($isCross) {
                $this->crossServerTransfer($share, $oldPath, $actualDest, $newPath, $server, $destServer);
                $this->connectSmb($server);
                $this->provider->delete($share, $oldPath);
            } elseif ($destShare !== '' && $destShare !== $share) {
                $this->provider->copy($share, $oldPath, $newPath, $destShare);
                $this->provider->delete($share, $oldPath);
            } else {
                $this->provider->rename($share, $oldPath, $newPath);
            }
            $this->logger->info('Moved', [
                'user'        => $this->session->getUsername(),
                'from_share'  => $share,
                'from'        => $oldPath,
                'to_share'    => $actualDest,
                'to'          => $newPath,
                'cross_server' => $isCross,
            ]);
            Response::json(['success' => true, 'new_path' => $newPath]);
        } catch (\Throwable $e) {
            $this->logger->error('Move failed', [
                'share'       => $share,
                'old_path'    => $oldPath,
                'new_path'    => $newPath,
                'dest_share'  => $actualDest,
                'server'      => $server,
                'dest_server' => $destServer,
                'error'       => $e->getMessage(),
            ]);
            Response::json(['error' => 'Move failed'], 500);
        }
    }

    public function copy(Request $request): void
    {
        $this->requireAuth();
        $data       = $request->jsonBody();
        $share      = (string) ($data['share']       ?? '');
        $srcPath    = urldecode((string) ($data['src_path']    ?? ''));
        $destDir    = urldecode((string) ($data['dest_dir']    ?? ''));
        $destShare  = (string) ($data['dest_share']  ?? $share);
        $server     = (string) ($data['server']      ?? '');
        $destServer = (string) ($data['dest_server'] ?? $server);

        if ($share === '' || $srcPath === '') {
            Response::json(['error' => 'share and src_path required'], 400);
        }

        $dstPath    = ltrim(rtrim($destDir, '/') . '/' . basename($srcPath), '/');
        $isCross    = $this->isCrossServer($server, $destServer);
        $actualDest = $destShare !== '' ? $destShare : $share;

        $this->connectSmb($server);

        try {
            if ($isCross) {
                $this->crossServerTransfer($share, $srcPath, $actualDest, $dstPath, $server, $destServer);
            } else {
                $this->provider->copy($share, $srcPath, $dstPath, $actualDest);
            }
            $this->logger->info('Copied', [
                'user'         => $this->session->getUsername(),
                'from_share'   => $share,
                'from'         => $srcPath,
                'to_share'     => $actualDest,
                'to'           => $dstPath,
                'cross_server' => $isCross,
            ]);
            Response::json(['success' => true, 'new_path' => $dstPath]);
        } catch (\Throwable $e) {
            $this->logger->error('Copy failed', [
                'share'        => $share,
                'src_path'     => $srcPath,
                'dst_path'     => $dstPath,
                'dest_share'   => $actualDest,
                'server'       => $server,
                'dest_server'  => $destServer,
                'error'        => $e->getMessage(),
            ]);
            Response::json(['error' => 'Copy failed'], 500);
        }
    }

    /**
     * Download from source server to a local temp file, then upload to destination server.
     * Uses stream_get_meta_data() to reuse the temp file path from getFile(),
     * avoiding a second temp copy.
     */
    private function crossServerTransfer(
        string $srcShare, string $srcPath,
        string $dstShare, string $dstPath,
        string $srcServer, string $dstServer,
    ): void {
        // Download from source
        $this->connectSmb($srcServer);
        $stream  = $this->provider->getFile($srcShare, $srcPath);
        $tmpPath = stream_get_meta_data($stream)['uri'];

        // Upload to destination (reuse the temp file path directly)
        $this->connectSmb($dstServer);
        $this->provider->putFile($dstShare, $dstPath, $tmpPath);

        fclose($stream);
    }

    /** Normalize empty host to default, then compare. */
    private function isCrossServer(string $a, string $b): bool
    {
        $default = (string) $this->config->get('smb.server', '');
        $a = $a === '' ? $default : $a;
        $b = $b === '' ? $default : $b;
        return $a !== $b;
    }

    private function requireAuth(): void
    {
        if (!$this->session->isAuthenticated()) {
            Response::json(['error' => 'unauthorized'], 401);
        }
    }

    private function connectSmb(string $host = ''): void
    {
        $defaultHost = (string) $this->config->get('smb.server', '');

        if ($host === '' || $host === $defaultHost) {
            $connected = $this->provider->connect(
                $defaultHost,
                $this->session->getUsername() ?? '',
                $this->session->getPassword() ?? '',
                ['domain' => (string) $this->config->get('smb.domain', '')]
            );
            if (!$connected) {
                $this->session->destroy();
                Response::json(['error' => 'session expired'], 401);
            }
            return;
        }

        $connected = $this->provider->connect(
            $host,
            $this->session->getUsername() ?? '',
            $this->session->getPassword() ?? '',
            ['domain' => '']
        );

        if (!$connected && $this->session->hasExtraCredentials($host)) {
            $extra     = $this->session->getExtraCredentials($host);
            $connected = $this->provider->connect($host, $extra['username'], $extra['password'], ['domain' => '']);
        }

        if (!$connected) {
            Response::json(['error' => 'credentials_required', 'host' => $host], 401);
        }
    }
}
