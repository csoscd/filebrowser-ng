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

    public function testAddAndFindByUser(): void
    {
        $id = $this->repo->add('alice', '192.168.1.1', 'Documents', 'Docs');

        $this->assertGreaterThan(0, $id);
        $rows = $this->repo->findByUser('alice');
        $this->assertCount(1, $rows);
        $this->assertSame('Documents', $rows[0]['share']);
        $this->assertSame('Docs',      $rows[0]['label']);
    }

    public function testFindByUserReturnsOnlyOwnShares(): void
    {
        $this->repo->add('alice', '192.168.1.1', 'ShareA', 'A');
        $this->repo->add('bob',   '192.168.1.2', 'ShareB', 'B');

        $this->assertCount(1, $this->repo->findByUser('alice'));
        $this->assertCount(1, $this->repo->findByUser('bob'));
    }

    public function testDeleteShare(): void
    {
        $id = $this->repo->add('alice', '192.168.1.1', 'Temp', '');
        $this->assertTrue($this->repo->delete($id, 'alice'));
        $this->assertCount(0, $this->repo->findByUser('alice'));
    }

    public function testDeleteFailsForWrongUser(): void
    {
        $id = $this->repo->add('alice', '192.168.1.1', 'Private', '');
        $this->assertFalse($this->repo->delete($id, 'bob'));
    }

    public function testFindById(): void
    {
        $id  = $this->repo->add('alice', '192.168.1.1', 'Data', 'label');
        $row = $this->repo->find($id, 'alice');

        $this->assertNotNull($row);
        $this->assertSame('Data', $row['share']);
    }
}
