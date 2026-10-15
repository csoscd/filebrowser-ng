<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilebrowserNg\Controller\FileActionController;
use FilebrowserNg\Controller\UploadController;
use FilebrowserNg\Http\Response;
use FilebrowserNg\Http\ResponseSentException;
use FilebrowserNg\Session\SessionManager;
use FilebrowserNg\Storage\StorageProviderInterface;
use Noodlehaus\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tests\Helpers\TestRequest;

/**
 * Verifies that the optional 'server' parameter is forwarded to connectSmb()
 * in UploadController and FileActionController.
 */
class ServerParameterTest extends TestCase
{
    private string $configFile;
    private string $tmpFile;
    private SessionManager $session;
    /** @var StorageProviderInterface&MockObject */
    private StorageProviderInterface $provider;
    private Config $config;

    protected function setUp(): void
    {
        Response::$testMode = true;
        $_SESSION = [];

        $this->configFile = tempnam(sys_get_temp_dir(), 'fbng_cfg_') . '.json';
        file_put_contents($this->configFile, json_encode([
            'session' => ['lifetime' => 3600, 'name' => 'test_sess'],
            'smb'     => ['server' => 'nas1', 'domain' => ''],
            'upload'  => ['chunk_size' => 10485760],
        ]));

        $this->tmpFile = tempnam(sys_get_temp_dir(), 'fbng_up_');
        file_put_contents($this->tmpFile, 'test');

        $this->config  = new Config($this->configFile);
        $this->session = new SessionManager($this->config);
        $this->session->login('testuser', 'secret');
        $this->provider = $this->createMock(StorageProviderInterface::class);
    }

    protected function tearDown(): void
    {
        Response::$testMode = false;
        $_SESSION = [];
        @unlink($this->configFile);
        @unlink($this->tmpFile);
    }

    // ── UploadController ─────────────────────────────────────────────────────

    public function testUploadDefaultServerUsesSessionCreds(): void
    {
        // connect() with default host + session credentials → succeeds → upload proceeds
        $this->provider->expects($this->once())
            ->method('connect')
            ->with('nas1', 'testuser', 'secret', $this->anything())
            ->willReturn(true);
        $this->provider->method('putFile'); // void – no return value needed

        $ctrl = $this->makeUploadController();
        $req  = new TestRequest(
            'POST',
            body: ['share' => 'Public', 'server' => ''],
            files: ['files' => ['name' => 'f.txt', 'tmp_name' => $this->tmpFile, 'error' => UPLOAD_ERR_OK, 'size' => 4]],
        );

        $ex = $this->catch(fn() => $ctrl->upload($req));
        $this->assertSame(200, $ex->statusCode);
        $this->assertContains('f.txt', $ex->data['uploaded']);
    }

    public function testUploadExtraServerWithoutCredentialsReturns401(): void
    {
        // Default session creds fail on nas2 → no stored extra creds → 401 credentials_required
        $this->provider->method('connect')->willReturn(false);

        $ctrl = $this->makeUploadController();
        $req  = new TestRequest(
            'POST',
            body: ['share' => 'Public', 'server' => 'nas2'],
            files: ['files' => ['name' => 'f.txt', 'tmp_name' => $this->tmpFile, 'error' => UPLOAD_ERR_OK, 'size' => 4]],
        );

        $ex = $this->catch(fn() => $ctrl->upload($req));
        $this->assertSame(401, $ex->statusCode);
        $this->assertSame('credentials_required', $ex->data['error']);
    }

    public function testUploadExtraServerWithStoredCredentialsConnects(): void
    {
        $this->session->setExtraCredentials('nas2', 'extrauser', 'extrapass');

        // Session creds fail on nas2, then extra creds succeed
        $this->provider->expects($this->exactly(2))
            ->method('connect')
            ->willReturnOnConsecutiveCalls(false, true);
        $this->provider->method('putFile'); // void – no return value needed

        $ctrl = $this->makeUploadController();
        $req  = new TestRequest(
            'POST',
            body: ['share' => 'Public', 'server' => 'nas2'],
            files: ['files' => ['name' => 'f.txt', 'tmp_name' => $this->tmpFile, 'error' => UPLOAD_ERR_OK, 'size' => 4]],
        );

        $ex = $this->catch(fn() => $ctrl->upload($req));
        // Connect succeeded → upload attempted → 200 (not 401)
        $this->assertSame(200, $ex->statusCode);
    }

    // ── FileActionController ─────────────────────────────────────────────────

    public function testFileDeleteDefaultServerUsesSessionCreds(): void
    {
        // Verify connect is called with the default host + session creds.
        // connect() fails → connectSmb() calls Response::json(401) BEFORE the try block,
        // so there is no wrapping catch (\Throwable) that would intercept our test exception.
        $this->provider->expects($this->once())
            ->method('connect')
            ->with('nas1', 'testuser', 'secret', $this->anything())
            ->willReturn(false);

        $ctrl = $this->makeFileActionController();
        $req  = new TestRequest('POST', jsonBody: ['share' => 'Public', 'path' => 'foo.txt', 'server' => '']);

        $ex = $this->catch(fn() => $ctrl->delete($req));
        $this->assertSame(401, $ex->statusCode);
        $this->assertSame('session expired', $ex->data['error']);
    }

    public function testFileDeleteExtraServerWithoutCredentialsReturns401(): void
    {
        $this->provider->method('connect')->willReturn(false);

        $ctrl = $this->makeFileActionController();
        $req  = new TestRequest('POST', jsonBody: ['share' => 'Public', 'path' => 'foo.txt', 'server' => 'nas2']);

        $ex = $this->catch(fn() => $ctrl->delete($req));
        $this->assertSame(401, $ex->statusCode);
        $this->assertSame('credentials_required', $ex->data['error']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeUploadController(): UploadController
    {
        return new UploadController($this->session, $this->provider, $this->config, new NullLogger());
    }

    private function makeFileActionController(): FileActionController
    {
        return new FileActionController($this->session, $this->provider, $this->config, new NullLogger());
    }

    private function catch(callable $fn): ResponseSentException
    {
        try {
            $fn();
            $this->fail('Expected ResponseSentException was not thrown');
        } catch (ResponseSentException $e) {
            return $e;
        }
    }
}
