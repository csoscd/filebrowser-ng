<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilebrowserNg\Controller\BrowserController;
use FilebrowserNg\Http\Response;
use FilebrowserNg\Http\ResponseSentException;
use FilebrowserNg\Repository\ServerRepository;
use FilebrowserNg\Repository\UserPreferenceRepository;
use FilebrowserNg\Session\SessionManager;
use FilebrowserNg\Storage\StorageProviderInterface;
use FilebrowserNg\View\TemplateEngine;
use Noodlehaus\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tests\Helpers\TestRequest;

class BrowserControllerSharesTest extends TestCase
{
    private string $configFile;
    private string $dbFile;
    private SessionManager $session;
    private ServerRepository $serverRepo;
    private UserPreferenceRepository $prefRepo;
    /** @var StorageProviderInterface&MockObject */
    private StorageProviderInterface $provider;
    private BrowserController $ctrl;

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

        $this->dbFile     = tempnam(sys_get_temp_dir(), 'fbng_db_') . '.sqlite';
        $config           = new Config($this->configFile);
        $this->session    = new SessionManager($config);
        $this->session->login('testuser', 'secret');
        $this->serverRepo = new ServerRepository($this->dbFile);
        $this->prefRepo   = new UserPreferenceRepository($this->dbFile);
        $this->provider   = $this->createMock(StorageProviderInterface::class);

        $view = $this->createStub(TemplateEngine::class);

        $this->ctrl = new BrowserController(
            $this->session,
            $this->provider,
            $this->serverRepo,
            $this->prefRepo,
            $view,
            $config,
            new NullLogger(),
        );
    }

    protected function tearDown(): void
    {
        Response::$testMode = false;
        $_SESSION = [];
        @unlink($this->configFile);
        @unlink($this->dbFile);
    }

    public function testSharesReturnsServersKey(): void
    {
        $this->provider->method('connect')->willReturn(true);
        $this->provider->method('listShares')->willReturn([['name' => 'Public', 'type' => 'smb']]);

        $ex = $this->callShares();
        $this->assertSame(200, $ex->statusCode);
        $this->assertArrayHasKey('servers', $ex->data);
    }

    public function testSharesDefaultServerIsFirstAndIsDefault(): void
    {
        $this->provider->method('connect')->willReturn(true);
        $this->provider->method('listShares')->willReturn([]);

        $ex      = $this->callShares();
        $servers = $ex->data['servers'];
        $this->assertNotEmpty($servers);
        $this->assertSame('default', $servers[0]['id']);
        $this->assertTrue($servers[0]['is_default']);
        $this->assertSame('nas1', $servers[0]['host']);
    }

    public function testSharesDefaultServerConnectionFailedSetsError(): void
    {
        $this->provider->method('connect')->willReturn(false);

        $ex      = $this->callShares();
        $servers = $ex->data['servers'];
        $this->assertSame('connection_failed', $servers[0]['error'] ?? null);
    }

    public function testSharesIncludesUserAddedServer(): void
    {
        $this->serverRepo->addServer('testuser', 'nas2', 'NAS 2', '');

        // Default server connects, nas2 also connects
        $this->provider->method('connect')->willReturn(true);
        $this->provider->method('listShares')->willReturn([]);

        $ex      = $this->callShares();
        $servers = $ex->data['servers'];
        $this->assertCount(2, $servers);
        $this->assertSame('nas2', $servers[1]['host']);
        $this->assertSame('NAS 2', $servers[1]['display_name']);
        $this->assertFalse($servers[1]['is_default']);
    }

    public function testSharesUserServerNeedsCredentialsWhenConnectionFails(): void
    {
        $this->serverRepo->addServer('testuser', 'nas2', 'NAS 2', '');

        // Default server connects, nas2 fails
        $this->provider->expects($this->exactly(2))
            ->method('connect')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->provider->method('listShares')->willReturn([]);

        $ex      = $this->callShares();
        $servers = $ex->data['servers'];
        $this->assertCount(2, $servers);
        $this->assertTrue($servers[1]['needs_credentials'] ?? false);
    }

    public function testSharesUserServerUsesExtraCredentialsFromSession(): void
    {
        $this->serverRepo->addServer('testuser', 'nas2', 'NAS 2', '');
        $this->session->setExtraCredentials('nas2', 'extrauser', 'extrapass');

        // Default connects, nas2 session-creds fail, nas2 extra-creds succeed
        $this->provider->expects($this->exactly(3))
            ->method('connect')
            ->willReturnOnConsecutiveCalls(true, false, true);
        $this->provider->method('listShares')->willReturn([]);

        $ex      = $this->callShares();
        $servers = $ex->data['servers'];
        $this->assertCount(2, $servers);
        $this->assertFalse($servers[1]['needs_credentials'] ?? false);
    }

    public function testSharesRequiresAuth(): void
    {
        $_SESSION = [];
        $ex = $this->callShares();
        $this->assertSame(401, $ex->statusCode);
    }

    private function callShares(): ResponseSentException
    {
        try {
            $this->ctrl->shares(new TestRequest());
            $this->fail('Expected ResponseSentException was not thrown');
        } catch (ResponseSentException $e) {
            return $e;
        }
    }
}
