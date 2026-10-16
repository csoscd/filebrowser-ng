<?php

declare(strict_types=1);

namespace FilebrowserNg\Repository;

use PDO;
use RuntimeException;

/**
 * Stores SMB passwords AES-256-GCM encrypted per user/host pair.
 * The encryption key is never stored here – it comes from the OIDC ID Token
 * (smb_key claim) and is held only in the PHP session.
 */
class CredentialRepository
{
    private const CIPHER  = 'aes-256-gcm';
    private const IV_LEN  = 12;
    private const TAG_LEN = 16;

    private PDO $db;

    public function __construct(private readonly string $dbPath)
    {
        $this->db = new PDO('sqlite:' . $this->dbPath, options: [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->migrate();
    }

    private function migrate(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS user_credentials (
                id                  INTEGER  PRIMARY KEY AUTOINCREMENT,
                username            TEXT     NOT NULL,
                host                TEXT     NOT NULL,
                encrypted_password  TEXT     NOT NULL,
                iv                  TEXT     NOT NULL,
                tag                 TEXT     NOT NULL,
                created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (username, host)
            )
        ");
    }

    /**
     * Saves (or replaces) the encrypted password for a user/host pair.
     * @throws RuntimeException when encryption fails
     */
    public function save(string $username, string $host, string $password, string $encryptionKey): void
    {
        $key = $this->deriveKey($encryptionKey);
        $iv  = random_bytes(self::IV_LEN);
        $tag = '';

        $encrypted = openssl_encrypt($password, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LEN);
        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO user_credentials (username, host, encrypted_password, iv, tag)
             VALUES (?, ?, ?, ?, ?)
             ON CONFLICT (username, host) DO UPDATE SET
               encrypted_password = excluded.encrypted_password,
               iv                 = excluded.iv,
               tag                = excluded.tag,
               updated_at         = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            $username,
            $host,
            base64_encode($encrypted),
            base64_encode($iv),
            base64_encode($tag),
        ]);
    }

    /**
     * Loads and decrypts the password for a user/host pair.
     * Returns null if no entry exists or decryption fails (e.g. key rotation).
     */
    public function load(string $username, string $host, string $encryptionKey): ?string
    {
        $stmt = $this->db->prepare(
            'SELECT encrypted_password, iv, tag FROM user_credentials WHERE username = ? AND host = ?'
        );
        $stmt->execute([$username, $host]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        $key = $this->deriveKey($encryptionKey);

        $decrypted = openssl_decrypt(
            base64_decode($row['encrypted_password']),
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            base64_decode($row['iv']),
            base64_decode($row['tag']),
        );

        return $decrypted !== false ? $decrypted : null;
    }

    public function exists(string $username, string $host): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM user_credentials WHERE username = ? AND host = ?'
        );
        $stmt->execute([$username, $host]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function delete(string $username, string $host): void
    {
        $stmt = $this->db->prepare(
            'DELETE FROM user_credentials WHERE username = ? AND host = ?'
        );
        $stmt->execute([$username, $host]);
    }

    public function deleteAllForUser(string $username): void
    {
        $stmt = $this->db->prepare('DELETE FROM user_credentials WHERE username = ?');
        $stmt->execute([$username]);
    }

    /** Stretches / normalises the key material to exactly 32 bytes (AES-256). */
    private function deriveKey(string $keyMaterial): string
    {
        return hash('sha256', $keyMaterial, true);
    }
}
