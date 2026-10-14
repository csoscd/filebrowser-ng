<?php

declare(strict_types=1);

namespace FilebrowserNg\Controller;

use FilebrowserNg\Http\Request;
use FilebrowserNg\Http\Response;
use FilebrowserNg\Session\SessionManager;
use FilebrowserNg\Storage\StorageProviderInterface;
use Noodlehaus\Config;
use Psr\Log\LoggerInterface;

class UploadController
{
    public function __construct(
        private readonly SessionManager $session,
        private readonly StorageProviderInterface $provider,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function upload(Request $request): void
    {
        $this->requireAuth();

        $share = (string) $request->getBody('share', '');
        $path  = (string) $request->getBody('path', '');
        $files = $request->getFiles();

        if ($share === '') {
            Response::json(['error' => 'share required'], 400);
        }

        if (empty($files['files'])) {
            Response::json(['error' => 'no files uploaded'], 400);
        }

        $this->connectSmb();

        $uploaded = [];
        $failed   = [];

        foreach ($this->normalizeFiles($files['files']) as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $failed[] = $file['name'];
                continue;
            }
            $targetPath = ltrim($path . '/' . $file['name'], '/');
            try {
                $this->provider->putFile($share, $targetPath, $file['tmp_name']);
                $uploaded[] = $file['name'];
                $this->logger->info('File uploaded', [
                    'user'  => $this->session->getUsername(),
                    'share' => $share,
                    'path'  => $targetPath,
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Upload failed', ['file' => $file['name'], 'error' => $e->getMessage()]);
                $failed[] = $file['name'];
            }
        }

        Response::json(['uploaded' => $uploaded, 'failed' => $failed]);
    }

    /** @return array<array<string, mixed>> */
    private function normalizeFiles(array $files): array
    {
        if (!is_array($files['name'])) {
            return [$files];
        }
        $result = [];
        foreach ($files['name'] as $i => $name) {
            $result[] = [
                'name'     => $name,
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }
        return $result;
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
