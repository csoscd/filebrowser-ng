<?php

declare(strict_types=1);

namespace Tests\Integration;

use FilebrowserNg\Repository\ServerRepository;
use PHPUnit\Framework\TestCase;

class ServerRepositoryTest extends TestCase
{
    private string $dbFile;
    private ServerRepository $repo;

    protected function setUp(): void
    {
        $this->dbFile = tempnam(sys_get_temp_dir(), 'fbng_test_') . '.sqlite';
        $this->repo   = new ServerRepository($this->dbFile);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbFile)) {
            unlink($this->dbFile);
        }
    }

    // ── Server CRUD ────────────────────────────────────────────────────────────

    public function testAddServerAndFindByUser(): void
    {
        $id = $this->repo->addServer('alice', 'nas1', 'NAS 1', 'WORKGROUP');

        $this->assertGreaterThan(0, $id);
        $rows = $this->repo->findServersByUser('alice');
        $this->assertCount(1, $rows);
        $this->assertSame('nas1',      $rows[0]['host']);
        $this->assertSame('NAS 1',     $rows[0]['display_name']);
        $this->assertSame('WORKGROUP', $rows[0]['domain']);
    }

    public function testFindServersByUserReturnsOnlyOwnServers(): void
    {
        $this->repo->addServer('alice', 'nas1');
        $this->repo->addServer('bob',   'nas2');

        $this->assertCount(1, $this->repo->findServersByUser('alice'));
        $this->assertCount(1, $this->repo->findServersByUser('bob'));
    }

    public function testFindServerById(): void
    {
        $id  = $this->repo->addServer('alice', 'nas1', 'NAS 1');
        $row = $this->repo->findServer($id, 'alice');

        $this->assertNotNull($row);
        $this->assertSame('nas1', $row['host']);
    }

    public function testFindServerByIdFailsForWrongUser(): void
    {
        $id = $this->repo->addServer('alice', 'nas1');
        $this->assertNull($this->repo->findServer($id, 'bob'));
    }

    public function testFindServerByHost(): void
    {
        $this->repo->addServer('alice', 'nas1', 'NAS 1');
        $row = $this->repo->findServerByHost('alice', 'nas1');

        $this->assertNotNull($row);
        $this->assertSame('NAS 1', $row['display_name']);
    }

    public function testFindServerByHostReturnsNullForUnknown(): void
    {
        $this->assertNull($this->repo->findServerByHost('alice', 'unknown'));
    }

    public function testDeleteServer(): void
    {
        $id = $this->repo->addServer('alice', 'nas1');
        $this->assertTrue($this->repo->deleteServer($id, 'alice'));
        $this->assertCount(0, $this->repo->findServersByUser('alice'));
    }

    public function testDeleteServerFailsForWrongUser(): void
    {
        $id = $this->repo->addServer('alice', 'nas1');
        $this->assertFalse($this->repo->deleteServer($id, 'bob'));
    }

    public function testAddServerUniqueConstraint(): void
    {
        $this->repo->addServer('alice', 'nas1');
        $this->expectException(\PDOException::class);
        $this->repo->addServer('alice', 'nas1');
    }

    // ── Custom Shares ──────────────────────────────────────────────────────────

    public function testAddShareAndFindByUser(): void
    {
        $id = $this->repo->addShare('alice', 'nas1', 'Documents', 'Docs');

        $this->assertGreaterThan(0, $id);
        $rows = $this->repo->findSharesByUser('alice');
        $this->assertCount(1, $rows);
        $this->assertSame('Documents', $rows[0]['share']);
        $this->assertSame('Docs',      $rows[0]['label']);
    }

    public function testFindSharesByUserReturnsOnlyOwn(): void
    {
        $this->repo->addShare('alice', 'nas1', 'ShareA', 'A');
        $this->repo->addShare('bob',   'nas2', 'ShareB', 'B');

        $this->assertCount(1, $this->repo->findSharesByUser('alice'));
        $this->assertCount(1, $this->repo->findSharesByUser('bob'));
    }

    public function testDeleteShare(): void
    {
        $id = $this->repo->addShare('alice', 'nas1', 'Temp', '');
        $this->assertTrue($this->repo->deleteShare($id, 'alice'));
        $this->assertCount(0, $this->repo->findSharesByUser('alice'));
    }

    public function testDeleteShareFailsForWrongUser(): void
    {
        $id = $this->repo->addShare('alice', 'nas1', 'Private', '');
        $this->assertFalse($this->repo->deleteShare($id, 'bob'));
    }

    public function testFindShareById(): void
    {
        $id  = $this->repo->addShare('alice', 'nas1', 'Data', 'label');
        $row = $this->repo->findShare($id, 'alice');

        $this->assertNotNull($row);
        $this->assertSame('Data', $row['share']);
    }

    // ── Cascade Delete ─────────────────────────────────────────────────────────

    public function testDeleteServerCascadesShares(): void
    {
        $serverId = $this->repo->addServer('alice', 'nas1');
        $this->repo->addShare('alice', 'nas1', 'Docs', '', $serverId);
        $this->repo->addShare('alice', 'nas1', 'Music', '', $serverId);

        $this->assertCount(2, $this->repo->findSharesByServer($serverId, 'alice'));

        $this->repo->deleteServer($serverId, 'alice');

        $this->assertCount(0, $this->repo->findSharesByServer($serverId, 'alice'));
    }

    public function testFindSharesByServer(): void
    {
        $serverId = $this->repo->addServer('alice', 'nas1');
        $this->repo->addShare('alice', 'nas1', 'Docs',  'D', $serverId);
        $this->repo->addShare('alice', 'nas1', 'Music', 'M', $serverId);

        $shares = $this->repo->findSharesByServer($serverId, 'alice');
        $this->assertCount(2, $shares);
        $names = array_column($shares, 'share');
        $this->assertContains('Docs', $names);
        $this->assertContains('Music', $names);
    }

    // ── Legacy Aliases ─────────────────────────────────────────────────────────

    public function testLegacyAddAndFindByUser(): void
    {
        $id = $this->repo->add('alice', '192.168.1.1', 'Documents', 'Docs');

        $this->assertGreaterThan(0, $id);
        $rows = $this->repo->findByUser('alice');
        $this->assertCount(1, $rows);
        $this->assertSame('Documents', $rows[0]['share']);
    }

    public function testLegacyDeleteShare(): void
    {
        $id = $this->repo->add('alice', '192.168.1.1', 'Temp', '');
        $this->assertTrue($this->repo->delete($id, 'alice'));
        $this->assertCount(0, $this->repo->findByUser('alice'));
    }

    public function testLegacyFindShare(): void
    {
        $id  = $this->repo->add('alice', '192.168.1.1', 'Data', 'label');
        $row = $this->repo->find($id, 'alice');

        $this->assertNotNull($row);
        $this->assertSame('Data', $row['share']);
    }
}
