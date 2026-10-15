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

        if ($share === '' || $oldPath === '' || $newName === '') {
            Response::json(['error' => 'share, old_path, new_name required'], 400);
        }

        $dir     = dirname($oldPath);
        $newPath = $dir === '.' ? $newName : $dir . '/' . $newName;

        $this->connectSmb();

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
        $data  = $request->jsonBody();
        $share = (string) ($data['share'] ?? '');
        $path  = (string) ($data['path']  ?? '');

        if ($share === '' || $path === '') {
            Response::json(['error' => 'share and path required'], 400);
        }

        $this->connectSmb();

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
        $share   = (string) ($data['share'] ?? '');
        $path    = (string) ($data['path']  ?? '');
        $dirName = (string) ($data['name']  ?? '');

        if ($share === '' || $dirName === '') {
            Response::json(['error' => 'share and name required'], 400);
        }

        $fullPath = $path === '' ? $dirName : rtrim($path, '/') . '/' . $dirName;

        $this->connectSmb();

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
        $data      = $request->jsonBody();
        $share     = (string) ($data['share']      ?? '');
        $oldPath   = (string) ($data['old_path']   ?? '');
        $destDir   = (string) ($data['dest_dir']   ?? '');
        $destShare = (string) ($data['dest_share'] ?? $share);

        if ($share === '' || $oldPath === '') {
            Response::json(['error' => 'share and old_path required'], 400);
        }

        $newPath = ltrim(rtrim($destDir, '/') . '/' . basename($oldPath), '/');

        $this->connectSmb();

        try {
            if ($destShare !== $share) {
                // Cross-share move = copy + delete
                $this->provider->copy($share, $oldPath, $newPath, $destShare);
                $this->provider->delete($share, $oldPath);
            } else {
                $this->provider->rename($share, $oldPath, $newPath);
            }
            $this->logger->info('Moved', [
                'user'       => $this->session->getUsername(),
                'from_share' => $share,
                'from'       => $oldPath,
                'to_share'   => $destShare,
                'to'         => $newPath,
            ]);
            Response::json(['success' => true, 'new_path' => $newPath]);
        } catch (\Throwable $e) {
            $this->logger->error('Move failed', ['error' => $e->getMessage()]);
            Response::json(['error' => 'Move failed'], 500);
        }
    }

    public function copy(Request $request): void
    {
        $this->requireAuth();
        $data      = $request->jsonBody();
        $share     = (string) ($data['share']      ?? '');
        $srcPath   = (string) ($data['src_path']   ?? '');
        $destDir   = (string) ($data['dest_dir']   ?? '');
        $destShare = (string) ($data['dest_share'] ?? $share);

        if ($share === '' || $srcPath === '') {
            Response::json(['error' => 'share and src_path required'], 400);
        }

        $dstPath = ltrim(rtrim($destDir, '/') . '/' . basename($srcPath), '/');

        $this->connectSmb();

        try {
            $this->provider->copy($share, $srcPath, $dstPath, $destShare);
            $this->logger->info('Copied', [
                'user'       => $this->session->getUsername(),
                'from_share' => $share,
                'from'       => $srcPath,
                'to_share'   => $destShare,
                'to'         => $dstPath,
            ]);
            Response::json(['success' => true, 'new_path' => $dstPath]);
        } catch (\Throwable $e) {
            $this->logger->error('Copy failed', ['error' => $e->getMessage()]);
            Response::json(['error' => 'Copy failed'], 500);
        }
    }

    private function requireAuth(): void
    {
        if (!$this->session->isAuthenticated()) {
            Response::json(['error' => 'unauthorized'], 401);
        }
    }

    private function connectSmb(): void
    {
        $connected = $this->provider->connect(
            (string) $this->config->get('smb.server', ''),
            $this->session->getUsername() ?? '',
            $this->session->getPassword() ?? '',
            ['domain' => (string) $this->config->get('smb.domain', '')]
        );
        if (!$connected) {
            $this->session->destroy();
            Response::json(['error' => 'session expired'], 401);
        }
    }
}
