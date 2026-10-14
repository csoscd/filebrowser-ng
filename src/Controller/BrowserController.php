<?php

declare(strict_types=1);

namespace FilebrowserNg\Controller;

use FilebrowserNg\Http\Request;
use FilebrowserNg\Http\Response;
use FilebrowserNg\Repository\ServerRepository;
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
            Response::json(['files' => $files, 'path' => $path, 'share' => $share]);
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
