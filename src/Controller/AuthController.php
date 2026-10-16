<?php

declare(strict_types=1);

namespace FilebrowserNg\Controller;

use FilebrowserNg\Http\Request;
use FilebrowserNg\Http\Response;
use FilebrowserNg\Repository\CredentialRepository;
use FilebrowserNg\Security\OidcService;
use FilebrowserNg\Session\SessionManager;
use FilebrowserNg\Storage\StorageProviderInterface;
use FilebrowserNg\View\TemplateEngine;
use Noodlehaus\Config;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class AuthController
{
    public function __construct(
        private readonly SessionManager         $session,
        private readonly StorageProviderInterface $provider,
        private readonly TemplateEngine         $view,
        private readonly Config                 $config,
        private readonly LoggerInterface        $logger,
        private readonly ?OidcService           $oidc = null,
        private readonly ?CredentialRepository  $credentials = null,
    ) {}

    // ── Classic login (used when OIDC is not configured) ─────────────────────

    public function showLogin(Request $request): void
    {
        if ($this->session->isAuthenticated()) {
            Response::redirect('/browser');
        }

        $error = $this->session->get('login_error');
        $this->session->set('login_error', null);

        echo $this->view->render('login', [
            'error'        => $error,
            'oidc_enabled' => $this->oidc?->isConfigured() ?? false,
        ], 'layout');
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
        $user    = $this->session->getUsername();
        $isOidc  = $this->oidc?->isConfigured() ?? false;
        $this->session->destroy();
        $this->logger->info('Logout', ['user' => $user]);
        Response::redirect('/');
    }

    // ── OIDC login flow ───────────────────────────────────────────────────────

    /** Step 1: redirect to the OIDC provider. */
    public function oidcRedirect(Request $request): void
    {
        if (!($this->oidc?->isConfigured())) {
            Response::redirect('/');
        }

        $state = OidcService::generateRandomString();
        $nonce = OidcService::generateRandomString();

        $this->session->setOidcState($state);
        $this->session->setOidcNonce($nonce);

        try {
            $url = $this->oidc->buildAuthorizationUrl($state, $nonce);
        } catch (RuntimeException $e) {
            $this->logger->error('OIDC redirect failed', ['error' => $e->getMessage()]);
            $this->session->set('login_error', 'oidc_unavailable');
            Response::redirect('/');
        }

        Response::redirect($url);
    }

    /** Step 2: callback from the OIDC provider. */
    public function oidcCallback(Request $request): void
    {
        if (!($this->oidc?->isConfigured())) {
            Response::redirect('/');
        }

        $code  = (string) ($request->getQuery('code')  ?? '');
        $state = (string) ($request->getQuery('state') ?? '');
        $error = (string) ($request->getQuery('error') ?? '');

        if ($error !== '') {
            $this->logger->warning('OIDC callback error', ['error' => $error]);
            $this->session->set('login_error', 'oidc_denied');
            Response::redirect('/');
        }

        $expectedState = $this->session->getOidcState();
        $expectedNonce = $this->session->getOidcNonce();
        $this->session->clearOidcState();
        $this->session->clearOidcNonce();

        if ($expectedState === null || !hash_equals($expectedState, $state)) {
            $this->logger->warning('OIDC state mismatch');
            $this->session->set('login_error', 'oidc_invalid');
            Response::redirect('/');
        }

        try {
            $tokens   = $this->oidc->exchangeCode($code);
            $claims   = $this->oidc->validateIdToken($tokens['id_token'], $expectedNonce);
            $username = $this->oidc->extractUsername($claims);
            $smbKey   = $this->oidc->extractSmbKey($claims);
        } catch (Throwable $e) {
            $this->logger->error('OIDC token validation failed', ['error' => $e->getMessage()]);
            $this->session->set('login_error', 'oidc_invalid');
            Response::redirect('/');
        }

        if ($username === '') {
            $this->logger->error('OIDC: empty username in token');
            $this->session->set('login_error', 'oidc_invalid');
            Response::redirect('/');
        }

        // Store smb_key in session (only in memory, never persisted to DB)
        if ($smbKey !== null) {
            $this->session->setSmbKey($smbKey);
        }

        // Try to load stored SMB credentials (requires smb_key)
        if ($smbKey !== null && $this->credentials !== null) {
            $defaultHost = (string) $this->config->get('smb.server', '');
            $password    = $this->credentials->load($username, $defaultHost, $smbKey);

            if ($password !== null) {
                $domain    = (string) $this->config->get('smb.domain', '');
                $connected = $this->provider->connect($defaultHost, $username, $password, ['domain' => $domain]);

                if ($connected) {
                    $this->session->login($username, $password);
                    $this->logger->info('OIDC login (stored credentials)', ['user' => $username]);
                    Response::redirect('/browser');
                }

                // Stored credentials invalid (e.g. SMB password changed) – ask again
                $this->logger->info('OIDC stored credentials rejected, requesting new ones', ['user' => $username]);
                $this->credentials->delete($username, $defaultHost);
            }
        }

        // SMB credentials not stored or decryption failed – show credential form
        $this->session->setOidcPendingUser($username);
        Response::redirect('/auth/smb');
    }

    /** Step 3a: show SMB credential form after OIDC login. */
    public function showSmbCredentials(Request $request): void
    {
        if ($this->session->isAuthenticated()) {
            Response::redirect('/browser');
        }

        $username = $this->session->getOidcPendingUser();
        if ($username === null) {
            Response::redirect('/');
        }

        $error  = $this->session->get('smb_error');
        $this->session->set('smb_error', null);

        echo $this->view->render('smb_credentials', [
            'username'    => $username,
            'error'       => $error,
            'can_save'    => $this->session->hasSmbKey() && $this->credentials !== null,
        ], 'layout');
    }

    /** Step 3b: process the SMB credential form. */
    public function smbCredentials(Request $request): void
    {
        $username = $this->session->getOidcPendingUser();
        if ($username === null) {
            Response::redirect('/');
        }

        $password = (string) $request->getBody('smb_password', '');
        $save     = (string) $request->getBody('save_credentials', '') === '1';

        if ($password === '') {
            $this->session->set('smb_error', 'password_required');
            Response::redirect('/auth/smb');
        }

        $defaultHost = (string) $this->config->get('smb.server', '');
        $domain      = (string) $this->config->get('smb.domain', '');
        $connected   = $this->provider->connect($defaultHost, $username, $password, ['domain' => $domain]);

        if (!$connected) {
            $this->logger->info('SMB credential check failed', ['user' => $username, 'server' => $defaultHost]);
            $this->session->set('smb_error', 'invalid_credentials');
            Response::redirect('/auth/smb');
        }

        // Optionally encrypt and store the password
        if ($save && $this->session->hasSmbKey() && $this->credentials !== null) {
            try {
                $this->credentials->save($username, $defaultHost, $password, $this->session->getSmbKey());
                $this->logger->info('SMB credentials saved (encrypted)', ['user' => $username]);
            } catch (Throwable $e) {
                $this->logger->error('Failed to save SMB credentials', ['error' => $e->getMessage()]);
            }
        }

        $this->session->clearOidcPending();
        $this->session->login($username, $password);
        $this->logger->info('OIDC login (manual SMB credentials)', ['user' => $username]);
        Response::redirect('/browser');
    }
}
