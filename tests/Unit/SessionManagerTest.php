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
}
