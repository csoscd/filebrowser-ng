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
        $this->migrate();
    }

    private function migrate(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_shares (
                id         INTEGER  PRIMARY KEY AUTOINCREMENT,
                username   TEXT     NOT NULL,
                server     TEXT     NOT NULL,
                share      TEXT     NOT NULL,
                label      TEXT     NOT NULL DEFAULT '',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /** @return array<array<string, mixed>> */
    public function findByUser(string $username): array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_shares WHERE username = ? ORDER BY label, share');
        $stmt->execute([$username]);
        return $stmt->fetchAll();
    }

    public function add(string $username, string $server, string $share, string $label = ''): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_shares (username, server, share, label) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$username, $server, $share, $label]);
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id, string $username): bool
    {
        $stmt = $this->db->prepare('DELETE FROM user_shares WHERE id = ? AND username = ?');
        $stmt->execute([$id, $username]);
        return $stmt->rowCount() > 0;
    }

    public function find(int $id, string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM user_shares WHERE id = ? AND username = ?');
        $stmt->execute([$id, $username]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }
}
