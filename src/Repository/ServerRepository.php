<?php

declare(strict_types=1);

namespace FilebrowserNg\Repository;

use PDO;

class ServerRepository
{
    private PDO $db;

    public function __construct(private readonly string $dbPath)
    {
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->db = new PDO('sqlite:' . $dbPath, options: [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->migrate();
    }

    private function migrate(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_servers (
                id           INTEGER  PRIMARY KEY AUTOINCREMENT,
                username     TEXT     NOT NULL,
                host         TEXT     NOT NULL,
                display_name TEXT     NOT NULL DEFAULT '',
                domain       TEXT     NOT NULL DEFAULT '',
                created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (username, host)
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_shares (
                id         INTEGER  PRIMARY KEY AUTOINCREMENT,
                username   TEXT     NOT NULL,
                server     TEXT     NOT NULL,
                server_id  INTEGER  REFERENCES user_servers(id) ON DELETE CASCADE,
                share      TEXT     NOT NULL,
                label      TEXT     NOT NULL DEFAULT '',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // v0.3 migration: add server_id to user_shares if missing (existing databases).
        $cols = array_column(
            $this->db->query('PRAGMA table_info(user_shares)')->fetchAll(),
            'name'
        );
        if (!in_array('server_id', $cols, true)) {
            $this->db->exec(
                'ALTER TABLE user_shares ADD COLUMN server_id INTEGER REFERENCES user_servers(id) ON DELETE CASCADE'
            );
        }
    }

    // ── User Servers ──────────────────────────────────────────────────────────

    /** @return array<array<string, mixed>> */
    public function findServersByUser(string $username): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM user_servers WHERE username = ? ORDER BY display_name, host'
        );
        $stmt->execute([$username]);
        return $stmt->fetchAll();
    }

    public function findServer(int $id, string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_servers WHERE id = ? AND username = ?');
        $stmt->execute([$id, $username]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public function findServerByHost(string $username, string $host): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_servers WHERE username = ? AND host = ?');
        $stmt->execute([$username, $host]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    public function addServer(string $username, string $host, string $displayName = '', string $domain = ''): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_servers (username, host, display_name, domain) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$username, $host, $displayName, $domain]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteServer(int $id, string $username): bool
    {
        // user_shares with server_id FK cascade-delete automatically
        $stmt = $this->db->prepare('DELETE FROM user_servers WHERE id = ? AND username = ?');
        $stmt->execute([$id, $username]);
        return $stmt->rowCount() > 0;
    }

    // ── Custom Shares ─────────────────────────────────────────────────────────

    /** @return array<array<string, mixed>> */
    public function findSharesByUser(string $username): array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_shares WHERE username = ? ORDER BY label, share');
        $stmt->execute([$username]);
        return $stmt->fetchAll();
    }

    /** @return array<array<string, mixed>> */
    public function findSharesByServer(int $serverId, string $username): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM user_shares WHERE server_id = ? AND username = ? ORDER BY label, share'
        );
        $stmt->execute([$serverId, $username]);
        return $stmt->fetchAll();
    }

    public function addShare(string $username, string $server, string $share, string $label = '', ?int $serverId = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_shares (username, server, server_id, share, label) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$username, $server, $serverId, $share, $label]);
        return (int) $this->db->lastInsertId();
    }

    public function deleteShare(int $id, string $username): bool
    {
        $stmt = $this->db->prepare('DELETE FROM user_shares WHERE id = ? AND username = ?');
        $stmt->execute([$id, $username]);
        return $stmt->rowCount() > 0;
    }

    public function findShare(int $id, string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_shares WHERE id = ? AND username = ?');
        $stmt->execute([$id, $username]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    // ── Legacy aliases (used by BrowserController before v0.3) ───────────────

    /** @return array<array<string, mixed>> */
    public function findByUser(string $username): array
    {
        return $this->findSharesByUser($username);
    }

    public function add(string $username, string $server, string $share, string $label = ''): int
    {
        return $this->addShare($username, $server, $share, $label);
    }

    public function delete(int $id, string $username): bool
    {
        return $this->deleteShare($id, $username);
    }

    public function find(int $id, string $username): ?array
    {
        return $this->findShare($id, $username);
    }
}
