<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilebrowserNg\Security\OidcService;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use stdClass;

class OidcServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function makeService(array $overrides = []): OidcService
    {
        return new OidcService(
            clientId:      $overrides['clientId']      ?? 'test-client',
            clientSecret:  $overrides['clientSecret']  ?? 'test-secret',
            discoveryUrl:  $overrides['discoveryUrl']  ?? 'https://auth.example.com/.well-known/openid-configuration',
            redirectUri:   $overrides['redirectUri']   ?? 'https://app.example.com/auth/oidc/callback',
            smbKeyClaim:   $overrides['smbKeyClaim']   ?? 'smb_key',
            usernameClaim: $overrides['usernameClaim'] ?? '',
            logger:        new NullLogger(),
        );
    }

    public function testIsConfiguredReturnsTrueWhenClientIdAndDiscoverySet(): void
    {
        $service = $this->makeService();
        $this->assertTrue($service->isConfigured());
    }

    public function testIsConfiguredReturnsFalseWhenClientIdEmpty(): void
    {
        $service = $this->makeService(['clientId' => '']);
        $this->assertFalse($service->isConfigured());
    }

    public function testIsConfiguredReturnsFalseWhenDiscoveryEmpty(): void
    {
        $service = $this->makeService(['discoveryUrl' => '']);
        $this->assertFalse($service->isConfigured());
    }

    public function testGenerateRandomStringReturnsUniqueValues(): void
    {
        $a = OidcService::generateRandomString();
        $b = OidcService::generateRandomString();
        $this->assertNotSame($a, $b);
        $this->assertSame(64, strlen($a)); // 32 bytes → 64 hex chars
    }

    public function testExtractUsernameFromPreferredUsername(): void
    {
        $service = $this->makeService();
        $claims  = new stdClass();
        $claims->preferred_username = 'alice';
        $claims->sub                = 'sub-123';
        $this->assertSame('alice', $service->extractUsername($claims));
    }

    public function testExtractUsernameFallsBackToSub(): void
    {
        $service = $this->makeService();
        $claims  = new stdClass();
        $claims->sub = 'sub-456';
        $this->assertSame('sub-456', $service->extractUsername($claims));
    }

    public function testExtractUsernameUsesConfiguredClaim(): void
    {
        $service = $this->makeService(['usernameClaim' => 'email']);
        $claims  = new stdClass();
        $claims->email              = 'alice@example.com';
        $claims->preferred_username = 'alice';
        $this->assertSame('alice@example.com', $service->extractUsername($claims));
    }

    public function testExtractSmbKeyReturnsValueWhenPresent(): void
    {
        $service = $this->makeService(['smbKeyClaim' => 'smb_key']);
        $claims  = new stdClass();
        $claims->smb_key = 'abc123secretkey';
        $this->assertSame('abc123secretkey', $service->extractSmbKey($claims));
    }

    public function testExtractSmbKeyReturnsNullWhenMissing(): void
    {
        $service = $this->makeService(['smbKeyClaim' => 'smb_key']);
        $claims  = new stdClass();
        $this->assertNull($service->extractSmbKey($claims));
    }

    public function testExtractSmbKeyReturnsNullWhenClaimNameEmpty(): void
    {
        $service = $this->makeService(['smbKeyClaim' => '']);
        $claims  = new stdClass();
        $claims->smb_key = 'somekey';
        $this->assertNull($service->extractSmbKey($claims));
    }

    public function testExtractSmbKeyUsesConfiguredClaimName(): void
    {
        $service = $this->makeService(['smbKeyClaim' => 'file_key']);
        $claims  = new stdClass();
        $claims->file_key = 'customkeyvalue';
        $this->assertSame('customkeyvalue', $service->extractSmbKey($claims));
    }
}
