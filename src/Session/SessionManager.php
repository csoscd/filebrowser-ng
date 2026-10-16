<?php

declare(strict_types=1);

namespace FilebrowserNg\Session;

use Noodlehaus\Config;

class SessionManager
{
    public function __construct(private readonly Config $config) {}

    public function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $lifetime = (int) $this->config->get('session.lifetime', 3600);
        $name     = (string) $this->config->get('session.name', 'filebrowser_sess');

        session_name($name);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $lifetime) {
            $this->destroy();
            return;
        }
        $_SESSION['last_activity'] = time();
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['username']) && $_SESSION['username'] !== '';
    }

    public function login(string $username, string $password): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['username']      = $username;
        $_SESSION['password']      = $password;
        $_SESSION['last_activity'] = time();
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function getUsername(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    public function getPassword(): ?string
    {
        return $_SESSION['password'] ?? null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function setExtraCredentials(string $host, string $username, string $password): void
    {
        if (!isset($_SESSION['extra_credentials'])) {
            $_SESSION['extra_credentials'] = [];
        }
        $_SESSION['extra_credentials'][$host] = ['username' => $username, 'password' => $password];
    }

    /** @return array{username: string, password: string}|null */
    public function getExtraCredentials(string $host): ?array
    {
        return $_SESSION['extra_credentials'][$host] ?? null;
    }

    public function hasExtraCredentials(string $host): bool
    {
        return isset($_SESSION['extra_credentials'][$host]);
    }

    public function clearExtraCredentials(string $host): void
    {
        unset($_SESSION['extra_credentials'][$host]);
    }

    // ── OIDC state / nonce (used during login flow, cleared after callback) ────

    public function setOidcState(string $state): void
    {
        $_SESSION['oidc_state'] = $state;
    }

    public function getOidcState(): ?string
    {
        return $_SESSION['oidc_state'] ?? null;
    }

    public function clearOidcState(): void
    {
        unset($_SESSION['oidc_state']);
    }

    public function setOidcNonce(string $nonce): void
    {
        $_SESSION['oidc_nonce'] = $nonce;
    }

    public function getOidcNonce(): ?string
    {
        return $_SESSION['oidc_nonce'] ?? null;
    }

    public function clearOidcNonce(): void
    {
        unset($_SESSION['oidc_nonce']);
    }

    // ── OIDC pending: authenticated via OIDC, SMB password not yet supplied ───

    public function setOidcPendingUser(string $username): void
    {
        $_SESSION['oidc_pending_user'] = $username;
    }

    public function getOidcPendingUser(): ?string
    {
        return $_SESSION['oidc_pending_user'] ?? null;
    }

    public function clearOidcPending(): void
    {
        unset($_SESSION['oidc_pending_user']);
    }

    public function isOidcPending(): bool
    {
        return isset($_SESSION['oidc_pending_user']) && $_SESSION['oidc_pending_user'] !== '';
    }

    // ── SMB encryption key (from OIDC smb_key claim, held in session memory) ──

    public function setSmbKey(string $key): void
    {
        $_SESSION['smb_key'] = $key;
    }

    public function getSmbKey(): ?string
    {
        return $_SESSION['smb_key'] ?? null;
    }

    public function hasSmbKey(): bool
    {
        return isset($_SESSION['smb_key']) && $_SESSION['smb_key'] !== '';
    }
}
