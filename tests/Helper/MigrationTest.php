<?php

namespace App\Tests\Helper;

use App\Helper\Migration;
use Phalcon\Events\Manager;
use PHPUnit\Framework\TestCase;

class MigrationTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $config;
    private \PHPUnit\Framework\MockObject\MockObject $eventsManager;
    private \PHPUnit\Framework\MockObject\MockObject $db;

    protected function setUp(): void
    {
        \Phalcon\Di\Di::getDefault()->set('config', $this->config = $this->createMock(\Phalcon\Config\Config::class));
        \Phalcon\Di\Di::getDefault()->set('eventsManager', $this->eventsManager = $this->createMock(\Phalcon\Events\Manager::class));
        \Phalcon\Di\Di::getDefault()->set('db', $this->db = $this->createMock(\Phalcon\Db\Adapter\Pdo\AbstractPdo::class));
    }


    public function testGenerate()
    {
        $migration = new Migration();
        $filesize = $migration->generate('', '', $filename);
        $this->assertGreaterThan(0, $filesize);
        $this->assertFileExists($filename);
        $content = preg_replace('#\s+#mi',' ',file_get_contents($filename));
        $this->assertStringContainsString(' protected function down() { }', $content);
        $this->assertStringContainsString(' protected function up() { }', $content);
        unlink($filename);
    }

    public function testGetLastMigration()
    {
        $migration = new Migration();

        $this->config
            ->expects($this->exactly(2))
            ->method('path')
            ->willReturnOnConsecutiveCalls('migration_test', 'db');

        $this->db
            ->expects($this->once())
            ->method('query')
            ->with('SELECT id FROM migration_test ORDER BY id DESC LIMIT 1')
            ->willReturn(new class () {
                public function fetchArray()
                {
                    return ['id' => 'test'];
                }
            });

        $this->assertEquals('test', $migration->getLastMigration());
    }

    public function testGetMigrations()
    {
        $migration = new Migration();
        $migrations = $migration->getMigrations();
        $this->assertContainsNotOnlyInstancesOf(\App\Helper\Migration\Script::class, $migrations);
    }

    public function testGetModels()
    {
        $migration = new Migration();
        $models = $migration->getModels();
        $this->assertContainsNotOnlyInstancesOf(\Phalcon\Mvc\Model::class, $models);
    }

    public function testEventsManager()
    {
        $migration = new Migration();
        $migration->setEventsManager($mock = $this->createMock(Manager::class));
        $manager = $migration->getEventsManager();
        $this->assertEquals($mock, $manager);
    }

    public function testEnsureMigrationTableUndefinedService()
    {
        $migration = new Migration();

        $this->config
            ->expects($this->exactly(1))
            ->method('path')
            ->willReturnOnConsecutiveCalls(null);

        $this->expectExceptionMessage('You must define migrations.table.service in your configuration.');

        $migration->ensureMigrationTable();
    }

    public function testEnsureMigrationTableNotPdoService()
    {
        \Phalcon\Di\Di::getDefault()->set('db', $this->db = $this->createMock(\Phalcon\Db\Adapter\AbstractAdapter::class));
        $migration = new Migration();

        $this->config
            ->expects($this->exactly(1))
            ->method('path')
            ->willReturnOnConsecutiveCalls('db');

        $this->expectExceptionMessage('The service (migrations.table.service) is not an instance of Phalcon\Db\Adapter\Pdo\AbstractPdo, and cannot be used.');

        $migration->ensureMigrationTable();
    }

    public function testEnsureMigrationTableNotDbAdapter()
    {
        \Phalcon\Di\Di::getDefault()->set('db', $this->db = $this->createMock(\stdClass::class));
        $migration = new Migration();

        $this->config
            ->expects($this->exactly(1))
            ->method('path')
            ->willReturnOnConsecutiveCalls('db');

        $this->expectExceptionMessage('The service (migrations.table.service) is not an instance of Phalcon\Db\Adapter\AbstractAdapter.');

        $migration->ensureMigrationTable();
    }

    public function testEnsureMigrationTableAlreadyExists()
    {
        $migration = new Migration();

        $this->config
            ->expects($this->exactly(3))
            ->method('path')
            ->willReturnOnConsecutiveCalls('db', 'migration_test', 'db');

        $this->db
            ->expects($this->once())
            ->method('tableExists')
            ->with('migration_test')
            ->willReturn(true);

        $this->assertTrue($migration->ensureMigrationTable());
    }

    public function testEnsureMigrationTableNotExists()
    {
        $migration = new Migration();

        $this->config
            ->expects($this->exactly(3))
            ->method('path')
            ->willReturnOnConsecutiveCalls('db', 'migration_test', 'db');

        $this->db
            ->expects($this->once())
            ->method('tableExists')
            ->with('migration_test')
            ->willReturn(false);

        $this->db
            ->expects($this->once())
            ->method('createTable')
            ->with('migration_test')
            ->willReturn(true);

        $this->assertTrue($migration->ensureMigrationTable());
    }
}
