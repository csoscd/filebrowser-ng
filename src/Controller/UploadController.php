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

    public function chunk(Request $request): void
    {
        $this->requireAuth();

        $share      = (string) $request->getBody('share', '');
        $path       = (string) $request->getBody('path', '');
        $filename   = (string) $request->getBody('filename', '');
        $chunkIndex = (int)    $request->getBody('chunk_index', 0);
        $chunkTotal = (int)    $request->getBody('chunk_total', 1);
        $chunkFile  = $request->getFile('chunk');

        if ($share === '' || $filename === '' || $chunkFile === null) {
            Response::json(['error' => 'missing parameters'], 400);
        }
        if ($chunkFile['error'] !== UPLOAD_ERR_OK) {
            Response::json(['error' => 'upload error ' . $chunkFile['error']], 400);
        }
        if ($chunkTotal < 1 || $chunkIndex < 0 || $chunkIndex >= $chunkTotal) {
            Response::json(['error' => 'invalid chunk parameters'], 400);
        }

        $this->connectSmb();

        $targetPath = ltrim($path . '/' . $filename, '/');

        try {
            // Stream chunk directly to the SMB share – no local temp file needed.
            // Chunk 0 → write (creates/overwrites), chunks 1..N → append.
            $out = $this->provider->openFileStream($share, $targetPath, $chunkIndex > 0);
            $in  = fopen($chunkFile['tmp_name'], 'rb');
            stream_copy_to_stream($in, $out);
            fclose($in);
            fclose($out);
        } catch (\Throwable $e) {
            $this->logger->error('Chunk stream failed', [
                'file'  => $filename,
                'chunk' => $chunkIndex,
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            // Try to remove the partial file so a retry can start clean.
            try {
                $this->provider->delete($share, $targetPath);
            } catch (\Throwable) {
                // ignore cleanup failure
            }
            Response::json(['error' => $e->getMessage()], 500);
        }

        if ($chunkIndex < $chunkTotal - 1) {
            Response::json(['ok' => true, 'chunk' => $chunkIndex]);
        }

        $this->logger->info('Chunked upload complete', [
            'user'   => $this->session->getUsername(),
            'share'  => $share,
            'path'   => $targetPath,
            'chunks' => $chunkTotal,
        ]);

        Response::json(['done' => true]);
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
