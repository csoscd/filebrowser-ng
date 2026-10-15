<?php

declare(strict_types=1);

namespace FilebrowserNg\Repository;

use PDO;

class UserPreferenceRepository
{
    private PDO $db;

    public function __construct(string $dbPath)
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
            CREATE TABLE IF NOT EXISTS user_preferences (
                username TEXT NOT NULL,
                key      TEXT NOT NULL,
                value    TEXT NOT NULL,
                PRIMARY KEY (username, key)
            )
        ");
    }

    public function get(string $username, string $key, string $default = ''): string
    {
        $stmt = $this->db->prepare('SELECT value FROM user_preferences WHERE username = ? AND key = ?');
        $stmt->execute([$username, $key]);
        $row = $stmt->fetch();
        return $row !== false ? (string) $row['value'] : $default;
    }

    public function set(string $username, string $key, string $value): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO user_preferences (username, key, value) VALUES (?, ?, ?)
             ON CONFLICT(username, key) DO UPDATE SET value = excluded.value'
        );
        $stmt->execute([$username, $key, $value]);
    }
}
