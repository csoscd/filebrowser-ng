<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilebrowserNg\Session\SessionManager;
use Mockery;
use Noodlehaus\Config;
use PHPUnit\Framework\TestCase;

class SessionManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function makeConfig(array $values = []): Config
    {
        $defaults = [
            'session.lifetime' => 3600,
            'session.name'     => 'test_sess',
        ];
        $config = Mockery::mock(Config::class);
        foreach (array_merge($defaults, $values) as $key => $value) {
            $config->allows('get')->with($key, Mockery::any())->andReturn($value);
        }
        return $config;
    }

    public function testIsNotAuthenticatedByDefault(): void
    {
        $_SESSION = [];
        $manager  = new SessionManager($this->makeConfig());
        $this->assertFalse($manager->isAuthenticated());
    }

    public function testLoginSetsSessionData(): void
    {
        $_SESSION = [];
        $manager  = new SessionManager($this->makeConfig());
        $manager->login('testuser', 'secret');

        $this->assertSame('testuser', $manager->getUsername());
        $this->assertSame('secret',   $manager->getPassword());
        $this->assertTrue($manager->isAuthenticated());
    }

    public function testGetAndSet(): void
    {
        $_SESSION = [];
        $manager  = new SessionManager($this->makeConfig());
        $manager->set('foo', 'bar');

        $this->assertSame('bar', $manager->get('foo'));
        $this->assertNull($manager->get('nonexistent'));
        $this->assertSame('default', $manager->get('nonexistent', 'default'));
    }
}
