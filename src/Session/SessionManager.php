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
        session_regenerate_id(true);
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
        session_destroy();
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
}
