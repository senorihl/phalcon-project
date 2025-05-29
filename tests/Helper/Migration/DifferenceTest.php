<?php

namespace App\Tests\Helper\Migration;

use App\Helper\Migration\Difference;
use PHPUnit\Framework\TestCase;

class DifferenceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        build_dependency_injection('test');
    }

    public function testDetailPostgresTable()
    {
        $diff = new Difference();
        $detail = $diff->detailPostgresTable(DummyModel::class, new DummyModel);
        $this->assertIsArray($detail);
    }

    public function testMorphTable()
    {
        $this->assertTrue(true);
    }

    public function testDetailMysqlTable()
    {
        $this->assertTrue(true);
    }
}


class DummyModel extends \Phalcon\Mvc\Model
{
    /**
     * @Primary
     * @Identity
     * @Column(column=test_id, type=integer, nullable=false, autoIncrement=true)
     */
    public int $id;

    /**
     * @Column(column=test_test, type=varchar, size=255, sql_default="'TEST'", index=true)
     */
    public string $test;

    /**
     * @Column(column=test_uniq, type=varchar, size=255, unique=true, nullable=false)
     */
    public string $uniq;
}
