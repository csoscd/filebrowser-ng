<?php

declare(strict_types=1);

namespace FilebrowserNg\Controller;

use FilebrowserNg\Http\Request;
use FilebrowserNg\Http\Response;
use FilebrowserNg\Repository\ServerRepository;
use FilebrowserNg\Repository\UserPreferenceRepository;
use FilebrowserNg\Session\SessionManager;
use FilebrowserNg\Storage\StorageProviderInterface;
use FilebrowserNg\View\TemplateEngine;
use Noodlehaus\Config;
use Psr\Log\LoggerInterface;

class BrowserController
{
    public function __construct(
        private readonly SessionManager $session,
        private readonly StorageProviderInterface $provider,
        private readonly ServerRepository $serverRepo,
        private readonly UserPreferenceRepository $prefRepo,
        private readonly TemplateEngine $view,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(Request $request): void
    {
        $this->requireAuth();
        echo $this->view->render('browser', [
            'username'      => $this->session->getUsername(),
            'defaultServer' => $this->config->get('smb.server', ''),
            'chunkSize'     => (int) $this->config->get('upload.chunk_size', 10485760),
        ], 'layout');
    }

    public function shares(Request $request): void
    {
        $this->requireAuth();
        $this->connectSmb();

        $auto   = $this->provider->listShares();
        $custom = $this->serverRepo->findByUser($this->session->getUsername() ?? '');

        Response::json(['auto' => $auto, 'custom' => $custom]);
    }

    public function listFiles(Request $request): void
    {
        $this->requireAuth();
        $share = (string) $request->getQuery('share', '');
        $path  = (string) $request->getQuery('path', '');

        if ($share === '') {
            Response::json(['error' => 'share required'], 400);
        }

        $this->connectSmb();

        try {
            $files = $this->provider->listDirectory($share, $path);

            $username = $this->session->getUsername() ?? '';
            $sortKey  = 'sort:' . $share . ':' . $path;
            $sortRaw  = $this->prefRepo->get($username, $sortKey, '');
            $sort     = $sortRaw !== '' ? json_decode($sortRaw, true) : null;

            Response::json([
                'files' => $files,
                'path'  => $path,
                'share' => $share,
                'sort'  => $sort,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('listFiles failed', ['error' => $e->getMessage()]);
            Response::json(['error' => 'Failed to list directory'], 500);
        }
    }

    public function download(Request $request): void
    {
        $this->requireAuth();
        $share    = (string) $request->getQuery('share', '');
        $path     = (string) $request->getQuery('path', '');
        $filename = basename($path);

        if ($share === '' || $path === '') {
            Response::error('share and path required', 400);
        }

        $this->connectSmb();

        try {
            $stream = $this->provider->getFile($share, $path);
            Response::streamDownload($stream, $filename);
        } catch (\Throwable $e) {
            $this->logger->error('Download failed', ['error' => $e->getMessage()]);
            Response::error('Download failed', 500);
        }
    }

    public function downloadZip(Request $request): void
    {
        $this->requireAuth();
        $data  = $request->jsonBody();
        $share = (string) ($data['share'] ?? '');
        $paths = array_values(array_filter(array_map('strval', (array) ($data['paths'] ?? []))));

        if ($share === '' || empty($paths)) {
            Response::error('share and paths required', 400);
        }

        $this->connectSmb();

        $tmpZip = tempnam(sys_get_temp_dir(), 'fbng_zip_');
        $zip    = new \ZipArchive();
        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpZip);
            Response::error('Failed to create ZIP archive', 500);
        }

        $added = 0;
        foreach ($paths as $path) {
            try {
                $stream   = $this->provider->getFile($share, $path);
                $contents = stream_get_contents($stream);
                fclose($stream);
                $zip->addFromString(basename($path), (string) $contents);
                $added++;
            } catch (\Throwable $e) {
                $this->logger->warning('ZIP: skipped file', ['path' => $path, 'error' => $e->getMessage()]);
            }
        }

        $zip->close();

        if ($added === 0) {
            @unlink($tmpZip);
            Response::error('No files could be added to ZIP', 500);
        }

        $size = (int) filesize($tmpZip);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="download.zip"');
        header('Content-Length: ' . $size);
        header('Cache-Control: no-cache');
        readfile($tmpZip);
        @unlink($tmpZip);
        exit;
    }

    public function saveSortPref(Request $request): void
    {
        $this->requireAuth();
        $data   = $request->jsonBody();
        $share  = (string) ($data['share']  ?? '');
        $path   = (string) ($data['path']   ?? '');
        $column = (string) ($data['column'] ?? 'name');
        $dir    = (string) ($data['dir']    ?? 'asc');

        if ($share === '' || !in_array($column, ['name', 'size', 'modified'], true)
                          || !in_array($dir, ['asc', 'desc'], true)) {
            Response::json(['error' => 'invalid input'], 400);
        }

        $username = $this->session->getUsername() ?? '';
        $this->prefRepo->set($username, 'sort:' . $share . ':' . $path,
            json_encode(['column' => $column, 'dir' => $dir]));

        Response::json(['success' => true]);
    }

    private function requireAuth(): void
    {
        if (!$this->session->isAuthenticated()) {
            Response::redirect('/');
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
            Response::redirect('/');
        }
    }
}
