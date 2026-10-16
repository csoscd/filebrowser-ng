<?php

declare(strict_types=1);

namespace Tests\Integration;

use FilebrowserNg\Repository\CredentialRepository;
use PHPUnit\Framework\TestCase;

class CredentialRepositoryTest extends TestCase
{
    private string $dbPath;
    private CredentialRepository $repo;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/filebrowser_cred_test_' . uniqid() . '.sqlite';
        $this->repo   = new CredentialRepository($this->dbPath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function testSaveAndLoad(): void
    {
        $this->repo->save('alice', 'nas01', 'secret123', 'mykey');
        $loaded = $this->repo->load('alice', 'nas01', 'mykey');
        $this->assertSame('secret123', $loaded);
    }

    public function testLoadReturnsNullWhenNotExists(): void
    {
        $this->assertNull($this->repo->load('bob', 'nas01', 'anykey'));
    }

    public function testLoadReturnsNullOnWrongKey(): void
    {
        $this->repo->save('alice', 'nas01', 'secret123', 'correctkey');
        $result = $this->repo->load('alice', 'nas01', 'wrongkey');
        $this->assertNull($result);
    }

    public function testExists(): void
    {
        $this->assertFalse($this->repo->exists('alice', 'nas01'));
        $this->repo->save('alice', 'nas01', 'secret', 'key');
        $this->assertTrue($this->repo->exists('alice', 'nas01'));
    }

    public function testDelete(): void
    {
        $this->repo->save('alice', 'nas01', 'secret', 'key');
        $this->assertTrue($this->repo->exists('alice', 'nas01'));
        $this->repo->delete('alice', 'nas01');
        $this->assertFalse($this->repo->exists('alice', 'nas01'));
    }

    public function testSaveOverwritesExisting(): void
    {
        $this->repo->save('alice', 'nas01', 'old_password', 'key');
        $this->repo->save('alice', 'nas01', 'new_password', 'key');
        $loaded = $this->repo->load('alice', 'nas01', 'key');
        $this->assertSame('new_password', $loaded);
    }

    public function testSaveWithDifferentKey(): void
    {
        $this->repo->save('alice', 'nas01', 'secret', 'key1');
        // Overwrite with different encryption key
        $this->repo->save('alice', 'nas01', 'secret', 'key2');
        // Old key no longer works
        $this->assertNull($this->repo->load('alice', 'nas01', 'key1'));
        // New key works
        $this->assertSame('secret', $this->repo->load('alice', 'nas01', 'key2'));
    }

    public function testDeleteAllForUser(): void
    {
        $this->repo->save('alice', 'nas01', 'p1', 'key');
        $this->repo->save('alice', 'nas02', 'p2', 'key');
        $this->repo->save('bob',   'nas01', 'p3', 'key');

        $this->repo->deleteAllForUser('alice');

        $this->assertFalse($this->repo->exists('alice', 'nas01'));
        $this->assertFalse($this->repo->exists('alice', 'nas02'));
        $this->assertTrue($this->repo->exists('bob', 'nas01'));
    }

    public function testIsolationBetweenUsers(): void
    {
        $this->repo->save('alice', 'nas01', 'alice_secret', 'alicekey');
        $this->repo->save('bob',   'nas01', 'bob_secret',   'bobkey');

        $this->assertSame('alice_secret', $this->repo->load('alice', 'nas01', 'alicekey'));
        $this->assertSame('bob_secret',   $this->repo->load('bob',   'nas01', 'bobkey'));
        // Alice's key cannot decrypt Bob's entry
        $this->assertNull($this->repo->load('bob', 'nas01', 'alicekey'));
    }

    public function testIsolationBetweenHosts(): void
    {
        $this->repo->save('alice', 'nas01', 'pass1', 'key');
        $this->repo->save('alice', 'nas02', 'pass2', 'key');

        $this->assertSame('pass1', $this->repo->load('alice', 'nas01', 'key'));
        $this->assertSame('pass2', $this->repo->load('alice', 'nas02', 'key'));
    }

    public function testSpecialCharactersInPassword(): void
    {
        $password = 'p@$$w0rd!#%^&*()_+-=[]{}|;"\'<>?,./`~';
        $this->repo->save('alice', 'nas01', $password, 'key');
        $this->assertSame($password, $this->repo->load('alice', 'nas01', 'key'));
    }

    public function testUnicodePassword(): void
    {
        $password = 'Pässwört_äüöß_日本語_🔑';
        $this->repo->save('alice', 'nas01', $password, 'key');
        $this->assertSame($password, $this->repo->load('alice', 'nas01', 'key'));
    }
}
