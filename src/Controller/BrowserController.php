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
        $this->requireAuthApi();

        $username    = $this->session->getUsername() ?? '';
        $defaultHost = (string) $this->config->get('smb.server', '');

        $servers = [];

        // ── Default server ────────────────────────────────────────────────────
        $defaultEntry = [
            'id'           => 'default',
            'host'         => $defaultHost,
            'display_name' => $defaultHost,
            'domain'       => (string) $this->config->get('smb.domain', ''),
            'shares'       => [],
            'custom'       => [],
            'is_default'   => true,
        ];

        $connected = $this->provider->connect(
            $defaultHost,
            $this->session->getUsername() ?? '',
            $this->session->getPassword() ?? '',
            ['domain' => $defaultEntry['domain']]
        );

        if ($connected) {
            $autoShares = $this->provider->listShares();
            if (method_exists($this->provider, 'getDiskInfo')) {
                foreach ($autoShares as &$share) {
                    $share['disk'] = $this->provider->getDiskInfo($share['name']);
                }
                unset($share);
            }
            $defaultEntry['shares'] = $autoShares;
            // Custom shares with no server_id belong to the default server
            $defaultEntry['custom'] = array_values(array_filter(
                $this->serverRepo->findSharesByUser($username),
                static fn($s) => $s['server_id'] === null || $s['server_id'] === ''
            ));
        } else {
            $defaultEntry['error'] = 'connection_failed';
        }

        $servers[] = $defaultEntry;

        // ── User-added servers ────────────────────────────────────────────────
        foreach ($this->serverRepo->findServersByUser($username) as $userServer) {
            $host   = (string) $userServer['host'];
            $domain = (string) $userServer['domain'];
            $entry  = [
                'id'           => (int) $userServer['id'],
                'host'         => $host,
                'display_name' => $userServer['display_name'] !== '' ? $userServer['display_name'] : $host,
                'domain'       => $domain,
                'shares'       => [],
                'custom'       => [],
                'is_default'   => false,
            ];

            // Try session creds first, then stored extra creds
            $ok = $this->provider->connect(
                $host,
                $this->session->getUsername() ?? '',
                $this->session->getPassword() ?? '',
                ['domain' => $domain]
            );

            if (!$ok && $this->session->hasExtraCredentials($host)) {
                $extra = $this->session->getExtraCredentials($host);
                $ok    = $this->provider->connect($host, $extra['username'], $extra['password'], ['domain' => $domain]);
            }

            if ($ok) {
                $autoShares = $this->provider->listShares();
                if (method_exists($this->provider, 'getDiskInfo')) {
                    foreach ($autoShares as &$share) {
                        $share['disk'] = $this->provider->getDiskInfo($share['name']);
                    }
                    unset($share);
                }
                $entry['shares'] = $autoShares;
                $entry['custom'] = $this->serverRepo->findSharesByServer((int) $userServer['id'], $username);
            } else {
                $entry['needs_credentials'] = true;
            }

            $servers[] = $entry;
        }

        Response::json(['servers' => $servers]);
    }

    public function listFiles(Request $request): void
    {
        $this->requireAuthApi();
        $share  = (string) $request->getQuery('share', '');
        $path   = (string) $request->getQuery('path', '');
        $server = (string) $request->getQuery('server', '');

        if ($share === '') {
            Response::json(['error' => 'share required'], 400);
        }

        $this->connectSmbForHost($server);

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
            $this->logger->error('listFiles failed', [
                'share' => $share,
                'path'  => $path,
                'class' => get_class($e),
                'error' => $e->getMessage(),
            ]);
            Response::json(['error' => 'Failed to list directory'], 500);
        }
    }

    public function download(Request $request): void
    {
        $this->requireAuthApi();
        $share    = (string) $request->getQuery('share', '');
        $path     = (string) $request->getQuery('path', '');
        $server   = (string) $request->getQuery('server', '');
        $filename = basename($path);

        if ($share === '' || $path === '') {
            Response::error('share and path required', 400);
        }

        $this->connectSmbForHost($server);

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
        $this->requireAuthApi();
        $data   = $request->jsonBody();
        $share  = (string) ($data['share']  ?? '');
        $paths  = array_values(array_filter(array_map('strval', (array) ($data['paths'] ?? []))));
        $server = (string) ($data['server'] ?? '');

        if ($share === '' || empty($paths)) {
            Response::error('share and paths required', 400);
        }

        $this->connectSmbForHost($server);

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
        $this->requireAuthApi();
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

    private function requireAuthApi(): void
    {
        if (!$this->session->isAuthenticated()) {
            Response::json(['error' => 'unauthorized'], 401);
        }
    }

    public function connectSmbForHost(string $host): void
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
                Response::redirect('/');
            }
            return;
        }

        // User-added server: look up domain, try session creds then extra creds
        $userServer = $this->serverRepo->findServerByHost($this->session->getUsername() ?? '', $host);
        $domain     = $userServer !== null ? (string) $userServer['domain'] : '';

        $connected = $this->provider->connect(
            $host,
            $this->session->getUsername() ?? '',
            $this->session->getPassword() ?? '',
            ['domain' => $domain]
        );

        if (!$connected && $this->session->hasExtraCredentials($host)) {
            $extra     = $this->session->getExtraCredentials($host);
            $connected = $this->provider->connect($host, $extra['username'], $extra['password'], ['domain' => $domain]);
        }

        if (!$connected) {
            Response::json(['error' => 'credentials_required', 'host' => $host], 401);
        }
    }
}
