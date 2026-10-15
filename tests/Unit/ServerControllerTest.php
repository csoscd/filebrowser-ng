<?php

declare(strict_types=1);

namespace Tests\Unit;

use FilebrowserNg\Controller\ServerController;
use FilebrowserNg\Http\Response;
use FilebrowserNg\Http\ResponseSentException;
use FilebrowserNg\Repository\ServerRepository;
use FilebrowserNg\Session\SessionManager;
use FilebrowserNg\Storage\StorageProviderInterface;
use Noodlehaus\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tests\Helpers\TestRequest;

class ServerControllerTest extends TestCase
{
    private string $configFile;
    private string $dbFile;
    private SessionManager $session;
    private ServerRepository $repo;
    /** @var StorageProviderInterface&MockObject */
    private StorageProviderInterface $provider;
    private ServerController $ctrl;

    protected function setUp(): void
    {
        Response::$testMode = true;
        $_SESSION = [];

        $this->configFile = tempnam(sys_get_temp_dir(), 'fbng_cfg_') . '.json';
        file_put_contents($this->configFile, json_encode([
            'session' => ['lifetime' => 3600, 'name' => 'test_sess'],
            'smb'     => ['server' => 'nas1', 'domain' => ''],
        ]));

        $this->dbFile  = tempnam(sys_get_temp_dir(), 'fbng_db_') . '.sqlite';
        $this->session = new SessionManager(new Config($this->configFile));
        $this->session->login('testuser', 'secret');

        $this->repo     = new ServerRepository($this->dbFile);
        $this->provider = $this->createMock(StorageProviderInterface::class);

        $this->ctrl = new ServerController(
            $this->session,
            $this->repo,
            $this->provider,
            new Config($this->configFile),
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

    // ── list() ───────────────────────────────────────────────────────────────

    public function testListReturnsEmptyServersInitially(): void
    {
        $ex = $this->dispatchJson($this->ctrl, 'list', new TestRequest());
        $this->assertSame(200, $ex->statusCode);
        $this->assertSame([], $ex->data['servers']);
    }

    public function testListReturnsAddedServers(): void
    {
        $this->repo->addServer('testuser', 'nas2', 'NAS 2', '');

        $ex = $this->dispatchJson($this->ctrl, 'list', new TestRequest());
        $this->assertSame(200, $ex->statusCode);
        $this->assertCount(1, $ex->data['servers']);
        $this->assertSame('nas2', $ex->data['servers'][0]['host']);
    }

    public function testListRequiresAuth(): void
    {
        $_SESSION = []; // unauthenticated
        $ex = $this->dispatchJson($this->ctrl, 'list', new TestRequest());
        $this->assertSame(401, $ex->statusCode);
    }

    // ── add() ────────────────────────────────────────────────────────────────

    public function testAddMissingHostReturns400(): void
    {
        $req = new TestRequest('POST', '/api/servers', jsonBody: ['host' => '']);
        $ex  = $this->dispatchJson($this->ctrl, 'add', $req);
        $this->assertSame(400, $ex->statusCode);
    }

    public function testAddDuplicateServerReturns409(): void
    {
        $this->repo->addServer('testuser', 'nas2', 'NAS 2', '');
        $this->provider->method('connect')->willReturn(true);

        $req = new TestRequest('POST', '/api/servers', jsonBody: ['host' => 'nas2']);
        $ex  = $this->dispatchJson($this->ctrl, 'add', $req);
        $this->assertSame(409, $ex->statusCode);
        $this->assertSame('server_already_added', $ex->data['error']);
    }

    public function testAddConnectionFailedReturns401(): void
    {
        $this->provider->method('connect')->willReturn(false);

        $req = new TestRequest('POST', '/api/servers', jsonBody: ['host' => 'nas2']);
        $ex  = $this->dispatchJson($this->ctrl, 'add', $req);
        $this->assertSame(401, $ex->statusCode);
        $this->assertTrue($ex->data['needs_credentials']);
    }

    public function testAddWithSessionCredentialsSucceeds(): void
    {
        $this->provider->method('connect')->willReturn(true);

        $req = new TestRequest('POST', '/api/servers', jsonBody: ['host' => 'nas2', 'display_name' => 'NAS 2']);
        $ex  = $this->dispatchJson($this->ctrl, 'add', $req);
        $this->assertSame(200, $ex->statusCode);
        $this->assertTrue($ex->data['ok']);
        $this->assertNotNull($this->repo->findServerByHost('testuser', 'nas2'));
    }

    public function testAddWithExtraCredentialsStoresThemInSession(): void
    {
        // First connect (session creds) fails, second (extra creds) succeeds
        $this->provider->expects($this->exactly(2))
            ->method('connect')
            ->willReturnOnConsecutiveCalls(false, true);

        $req = new TestRequest('POST', '/api/servers', jsonBody: [
            'host'     => 'nas2',
            'username' => 'extrauser',
            'password' => 'extrapass',
        ]);
        $ex = $this->dispatchJson($this->ctrl, 'add', $req);
        $this->assertSame(200, $ex->statusCode);
        $this->assertTrue($this->session->hasExtraCredentials('nas2'));
        $creds = $this->session->getExtraCredentials('nas2');
        $this->assertSame('extrauser', $creds['username']);
    }

    // ── remove() ─────────────────────────────────────────────────────────────

    public function testRemoveNonExistentServerReturns404(): void
    {
        $ex = $this->dispatchJson($this->ctrl, 'remove', new TestRequest(), ['id' => '999']);
        $this->assertSame(404, $ex->statusCode);
    }

    public function testRemoveDeletesServerAndClearsSession(): void
    {
        $id = $this->repo->addServer('testuser', 'nas2', 'NAS 2', '');
        $this->session->setExtraCredentials('nas2', 'u', 'p');

        $ex = $this->dispatchJson($this->ctrl, 'remove', new TestRequest(), ['id' => (string) $id]);
        $this->assertSame(200, $ex->statusCode);
        $this->assertNull($this->repo->findServer($id, 'testuser'));
        $this->assertFalse($this->session->hasExtraCredentials('nas2'));
    }

    // ── storeCredentials() ───────────────────────────────────────────────────

    public function testStoreCredentialsMissingUsernameReturns400(): void
    {
        $id  = $this->repo->addServer('testuser', 'nas2', '', '');
        $req = new TestRequest('POST', jsonBody: ['username' => '', 'password' => 'x']);
        $ex  = $this->dispatchJson($this->ctrl, 'storeCredentials', $req, ['id' => (string) $id]);
        $this->assertSame(400, $ex->statusCode);
    }

    public function testStoreCredentialsConnectionFailedReturns401(): void
    {
        $id  = $this->repo->addServer('testuser', 'nas2', '', '');
        $this->provider->method('connect')->willReturn(false);
        $req = new TestRequest('POST', jsonBody: ['username' => 'u', 'password' => 'p']);
        $ex  = $this->dispatchJson($this->ctrl, 'storeCredentials', $req, ['id' => (string) $id]);
        $this->assertSame(401, $ex->statusCode);
        $this->assertTrue($ex->data['needs_credentials']);
    }

    public function testStoreCredentialsSuccessSavesInSession(): void
    {
        $id  = $this->repo->addServer('testuser', 'nas2', '', '');
        $this->provider->method('connect')->willReturn(true);
        $req = new TestRequest('POST', jsonBody: ['username' => 'u', 'password' => 'p']);
        $ex  = $this->dispatchJson($this->ctrl, 'storeCredentials', $req, ['id' => (string) $id]);
        $this->assertSame(200, $ex->statusCode);
        $this->assertTrue($this->session->hasExtraCredentials('nas2'));
    }

    // ── Helper ───────────────────────────────────────────────────────────────

    private function dispatchJson(
        ServerController $ctrl,
        string $method,
        TestRequest $req,
        array $params = [],
    ): ResponseSentException {
        try {
            match ($method) {
                'list'             => $ctrl->list($req),
                'add'              => $ctrl->add($req),
                'remove'           => $ctrl->remove($req, $params),
                'storeCredentials' => $ctrl->storeCredentials($req, $params),
            };
            $this->fail('Expected ResponseSentException was not thrown');
        } catch (ResponseSentException $e) {
            return $e;
        }
    }
}
