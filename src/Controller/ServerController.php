<?php

declare(strict_types=1);

namespace FilebrowserNg\Controller;

use FilebrowserNg\Http\Request;
use FilebrowserNg\Http\Response;
use FilebrowserNg\Repository\ServerRepository;
use FilebrowserNg\Session\SessionManager;
use FilebrowserNg\Storage\StorageProviderInterface;
use Noodlehaus\Config;
use Psr\Log\LoggerInterface;

class ServerController
{
    public function __construct(
        private readonly SessionManager $session,
        private readonly ServerRepository $serverRepo,
        private readonly StorageProviderInterface $provider,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function list(Request $request): void
    {
        $this->requireAuth();
        $servers = $this->serverRepo->findServersByUser($this->session->getUsername() ?? '');
        Response::json(['servers' => $servers]);
    }

    public function add(Request $request): void
    {
        $this->requireAuth();
        $data        = $request->jsonBody();
        $host        = trim((string) ($data['host']         ?? ''));
        $displayName = trim((string) ($data['display_name'] ?? ''));
        $domain      = trim((string) ($data['domain']       ?? ''));
        $username    = trim((string) ($data['username']     ?? ''));
        $password    = (string) ($data['password'] ?? '');

        if ($host === '') {
            Response::json(['error' => 'host required'], 400);
        }

        $user = $this->session->getUsername() ?? '';

        if ($this->serverRepo->findServerByHost($user, $host) !== null) {
            Response::json(['error' => 'server_already_added'], 409);
        }

        // Try session credentials first, then provided credentials
        $credUsername = $this->session->getUsername() ?? '';
        $credPassword = $this->session->getPassword() ?? '';
        $usedExtra    = false;

        $connected = $this->provider->connect($host, $credUsername, $credPassword, ['domain' => $domain]);

        if (!$connected && $username !== '') {
            $connected  = $this->provider->connect($host, $username, $password, ['domain' => $domain]);
            $usedExtra  = true;
        }

        if (!$connected) {
            Response::json(['error' => 'connection_failed', 'needs_credentials' => true], 401);
        }

        if ($usedExtra) {
            $this->session->setExtraCredentials($host, $username, $password);
        }

        $id = $this->serverRepo->addServer($user, $host, $displayName !== '' ? $displayName : $host, $domain);

        $this->logger->info('Server added', ['user' => $user, 'host' => $host]);

        Response::json(['ok' => true, 'id' => $id]);
    }

    public function remove(Request $request, array $params): void
    {
        $this->requireAuth();
        $id   = (int) ($params['id'] ?? 0);
        $user = $this->session->getUsername() ?? '';

        $server = $this->serverRepo->findServer($id, $user);
        if ($server === null) {
            Response::json(['error' => 'not_found'], 404);
        }

        $this->serverRepo->deleteServer($id, $user);
        $this->session->clearExtraCredentials((string) $server['host']);

        $this->logger->info('Server removed', ['user' => $user, 'host' => $server['host']]);

        Response::json(['ok' => true]);
    }

    public function storeCredentials(Request $request, array $params): void
    {
        $this->requireAuth();
        $id   = (int) ($params['id'] ?? 0);
        $user = $this->session->getUsername() ?? '';

        $server = $this->serverRepo->findServer($id, $user);
        if ($server === null) {
            Response::json(['error' => 'not_found'], 404);
        }

        $data     = $request->jsonBody();
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '') {
            Response::json(['error' => 'username required'], 400);
        }

        $host   = (string) $server['host'];
        $domain = (string) $server['domain'];

        $connected = $this->provider->connect($host, $username, $password, ['domain' => $domain]);
        if (!$connected) {
            Response::json(['error' => 'connection_failed', 'needs_credentials' => true], 401);
        }

        $this->session->setExtraCredentials($host, $username, $password);

        Response::json(['ok' => true]);
    }

    private function requireAuth(): void
    {
        if (!$this->session->isAuthenticated()) {
            Response::json(['error' => 'unauthorized'], 401);
        }
    }
}
