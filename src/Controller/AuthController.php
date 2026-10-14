<?php

declare(strict_types=1);

namespace FilebrowserNg\Controller;

use FilebrowserNg\Http\Request;
use FilebrowserNg\Http\Response;
use FilebrowserNg\Session\SessionManager;
use FilebrowserNg\Storage\StorageProviderInterface;
use FilebrowserNg\View\TemplateEngine;
use Noodlehaus\Config;
use Psr\Log\LoggerInterface;

class AuthController
{
    public function __construct(
        private readonly SessionManager $session,
        private readonly StorageProviderInterface $provider,
        private readonly TemplateEngine $view,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function showLogin(Request $request): void
    {
        if ($this->session->isAuthenticated()) {
            Response::redirect('/browser');
        }
        $error = $this->session->get('login_error');
        $this->session->set('login_error', null);
        echo $this->view->render('login', ['error' => $error], 'layout');
    }

    public function login(Request $request): void
    {
        $username = trim((string) $request->getBody('username', ''));
        $password = (string) $request->getBody('password', '');

        if ($username === '' || $password === '') {
            $this->session->set('login_error', 'credentials_required');
            Response::redirect('/');
        }

        $server = (string) $this->config->get('smb.server', '');
        $domain = (string) $this->config->get('smb.domain', '');

        $connected = $this->provider->connect($server, $username, $password, ['domain' => $domain]);

        if (!$connected) {
            $this->logger->info('Login failed', ['user' => $username, 'server' => $server]);
            $this->session->set('login_error', 'invalid_credentials');
            Response::redirect('/');
        }

        $this->session->login($username, $password);
        $this->logger->info('Login successful', ['user' => $username]);
        Response::redirect('/browser');
    }

    public function logout(Request $request): void
    {
        $user = $this->session->getUsername();
        $this->session->destroy();
        $this->logger->info('Logout', ['user' => $user]);
        Response::redirect('/');
    }
}
