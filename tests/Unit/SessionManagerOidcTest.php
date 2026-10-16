<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilebrowserNg\Session\SessionManager;
use Mockery;
use Noodlehaus\Config;
use PHPUnit\Framework\TestCase;

class SessionManagerOidcTest extends TestCase
{
    private SessionManager $session;

    protected function setUp(): void
    {
        $_SESSION = [];
        $config   = Mockery::mock(Config::class);
        $config->shouldReceive('get')->with('session.lifetime', 3600)->andReturn(3600);
        $config->shouldReceive('get')->with('session.name', 'filebrowser_sess')->andReturn('filebrowser_sess');
        $this->session = new SessionManager($config);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        Mockery::close();
    }

    public function testOidcStateRoundtrip(): void
    {
        $this->session->setOidcState('abc123');
        $this->assertSame('abc123', $this->session->getOidcState());
        $this->session->clearOidcState();
        $this->assertNull($this->session->getOidcState());
    }

    public function testOidcNonceRoundtrip(): void
    {
        $this->session->setOidcNonce('nonce42');
        $this->assertSame('nonce42', $this->session->getOidcNonce());
        $this->session->clearOidcNonce();
        $this->assertNull($this->session->getOidcNonce());
    }

    public function testOidcPendingUser(): void
    {
        $this->assertFalse($this->session->isOidcPending());
        $this->assertNull($this->session->getOidcPendingUser());

        $this->session->setOidcPendingUser('alice');
        $this->assertTrue($this->session->isOidcPending());
        $this->assertSame('alice', $this->session->getOidcPendingUser());

        $this->session->clearOidcPending();
        $this->assertFalse($this->session->isOidcPending());
        $this->assertNull($this->session->getOidcPendingUser());
    }

    public function testSmbKeyRoundtrip(): void
    {
        $this->assertFalse($this->session->hasSmbKey());
        $this->assertNull($this->session->getSmbKey());

        $this->session->setSmbKey('mysecretkey');
        $this->assertTrue($this->session->hasSmbKey());
        $this->assertSame('mysecretkey', $this->session->getSmbKey());
    }

    public function testSmbKeyNotSetWhenEmpty(): void
    {
        $_SESSION['smb_key'] = '';
        $this->assertFalse($this->session->hasSmbKey());
    }

    public function testOidcPendingFalseWhenEmpty(): void
    {
        $_SESSION['oidc_pending_user'] = '';
        $this->assertFalse($this->session->isOidcPending());
    }
}
