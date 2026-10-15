<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilebrowserNg\Session\SessionManager;
use Noodlehaus\Config;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    private string $configFile;

    protected function setUp(): void
    {
        $this->configFile = tempnam(sys_get_temp_dir(), 'fbng_cfg_') . '.json';
        file_put_contents($this->configFile, json_encode([
            'session' => ['lifetime' => 3600, 'name' => 'test_sess'],
        ]));
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        if (file_exists($this->configFile)) {
            unlink($this->configFile);
        }
        $_SESSION = [];
    }

    private function makeManager(): SessionManager
    {
        return new SessionManager(new Config($this->configFile));
    }

    public function testIsNotAuthenticatedByDefault(): void
    {
        $this->assertFalse($this->makeManager()->isAuthenticated());
    }

    public function testLoginSetsSessionData(): void
    {
        $manager = $this->makeManager();
        $manager->login('testuser', 'secret');

        $this->assertSame('testuser', $manager->getUsername());
        $this->assertSame('secret',   $manager->getPassword());
        $this->assertTrue($manager->isAuthenticated());
    }

    public function testGetAndSet(): void
    {
        $manager = $this->makeManager();
        $manager->set('foo', 'bar');

        $this->assertSame('bar',     $manager->get('foo'));
        $this->assertNull($manager->get('nonexistent'));
        $this->assertSame('default', $manager->get('nonexistent', 'default'));
    }

    public function testExtraCredentialsRoundtrip(): void
    {
        $manager = $this->makeManager();

        $this->assertFalse($manager->hasExtraCredentials('nas2'));
        $this->assertNull($manager->getExtraCredentials('nas2'));

        $manager->setExtraCredentials('nas2', 'bob', 's3cr3t');

        $this->assertTrue($manager->hasExtraCredentials('nas2'));
        $creds = $manager->getExtraCredentials('nas2');
        $this->assertNotNull($creds);
        $this->assertSame('bob',    $creds['username']);
        $this->assertSame('s3cr3t', $creds['password']);
    }

    public function testExtraCredentialsClear(): void
    {
        $manager = $this->makeManager();
        $manager->setExtraCredentials('nas2', 'bob', 's3cr3t');
        $manager->clearExtraCredentials('nas2');

        $this->assertFalse($manager->hasExtraCredentials('nas2'));
    }

    public function testExtraCredentialsIsolatedPerHost(): void
    {
        $manager = $this->makeManager();
        $manager->setExtraCredentials('nas1', 'alice', 'pw1');
        $manager->setExtraCredentials('nas2', 'bob',   'pw2');

        $this->assertSame('alice', $manager->getExtraCredentials('nas1')['username']);
        $this->assertSame('bob',   $manager->getExtraCredentials('nas2')['username']);

        $manager->clearExtraCredentials('nas1');
        $this->assertFalse($manager->hasExtraCredentials('nas1'));
        $this->assertTrue($manager->hasExtraCredentials('nas2'));
    }
}
