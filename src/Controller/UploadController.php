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

        $tmpDir = sys_get_temp_dir()
                . '/fbng_' . preg_replace('/[^a-zA-Z0-9]/', '', session_id())
                . '_' . md5($share . '/' . ltrim($path, '/') . '/' . $filename);

        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0700, true)) {
            Response::json(['error' => 'cannot create temp dir'], 500);
        }

        $chunkPath = $tmpDir . '/chunk_' . sprintf('%05d', $chunkIndex);
        if (!move_uploaded_file($chunkFile['tmp_name'], $chunkPath)) {
            Response::json(['error' => 'failed to store chunk'], 500);
        }

        // Not the last chunk yet
        if ($chunkIndex < $chunkTotal - 1) {
            Response::json(['ok' => true, 'chunk' => $chunkIndex]);
        }

        // Last chunk: assemble into chunk_00000 (append chunks 1..N to it), then upload
        $assembledPath = $tmpDir . '/chunk_00000';

        try {
            $out = fopen($assembledPath, 'ab');
            if ($out === false) {
                throw new \RuntimeException('Cannot open assembled file for writing');
            }
            for ($i = 1; $i < $chunkTotal; $i++) {
                $partPath = $tmpDir . '/chunk_' . sprintf('%05d', $i);
                $in = fopen($partPath, 'rb');
                if ($in === false) {
                    fclose($out);
                    throw new \RuntimeException("Chunk {$i} missing");
                }
                stream_copy_to_stream($in, $out);
                fclose($in);
                unlink($partPath);
            }
            fclose($out);

            $this->connectSmb();

            $targetPath = ltrim($path . '/' . $filename, '/');
            $this->provider->putFile($share, $targetPath, $assembledPath);

            $this->logger->info('Chunked upload complete', [
                'user'   => $this->session->getUsername(),
                'share'  => $share,
                'path'   => $targetPath,
                'chunks' => $chunkTotal,
            ]);

            Response::json(['done' => true]);
        } catch (\Throwable $e) {
            $this->logger->error('Chunked upload failed', [
                'file'  => $filename,
                'error' => $e->getMessage(),
            ]);
            Response::json(['error' => $e->getMessage()], 500);
        } finally {
            if (file_exists($assembledPath)) {
                unlink($assembledPath);
            }
            for ($i = 1; $i < $chunkTotal; $i++) {
                $p = $tmpDir . '/chunk_' . sprintf('%05d', $i);
                if (file_exists($p)) {
                    unlink($p);
                }
            }
            if (is_dir($tmpDir)) {
                @rmdir($tmpDir);
            }
        }
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
