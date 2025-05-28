<?php

namespace App\Tests\Phalcon\Mvc\Model\MetaData\Strategy;

use App\Phalcon\Mvc\Model\MetaData\Strategy\Annotations;
use Phalcon\Annotations\Adapter\AdapterInterface;
use Phalcon\Annotations\Annotation;
use Phalcon\Annotations\Collection;
use Phalcon\Annotations\Reflection;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\Model\ManagerInterface;
use Phalcon\Mvc\Model\MetaData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(Annotations::class)]
class AnnotationsTest extends TestCase
{
    private MockObject $annotations;
    private MockObject $modelsManager;

    protected function setUp(): void
    {
        Di::getDefault()->set('annotations', $this->annotations = $this->createMock(AdapterInterface::class));
        Di::getDefault()->set('modelsManager', $this->modelsManager = $this->createMock(ManagerInterface::class));
    }

    public function testGetMetaData()
    {
        $strategy = $this->createPartialMock(Annotations::class, ['getRootStrategy']);

        $strategy
            ->expects($this->any())
            ->method('getRootStrategy')
            ->willReturn($oStrategy = $this->createMock(MetaData\Strategy\StrategyInterface::class));

        $oStrategy
            ->expects($this->once())
            ->method('getMetaData')
            ->willReturn([]);

        $this->annotations
            ->expects($this->any())
            ->method('get')
            ->willReturn($reflection = $this->createMock(Reflection::class));

        $reflection
            ->expects($this->any())
            ->method('getClassAnnotations')
            ->willReturn($classAnnotations = $this->createMock(Collection::class));

        $reflection
            ->expects($this->any())
            ->method('getPropertiesAnnotations')
            ->willReturn([
                'test' => ($annotations = $this->createMock(Collection::class)),
                'test_bis' => $annotations,
            ]);

        $classAnnotations
            ->method('has')
            ->willReturnMap([
                ['Index', true],
                ['HasMany', true],
                ['HasManyToMany', true],
                ['HasOne', true],
                ['BelongsTo', true],
                ['HasOneThrough', true],
            ]);

        $classAnnotations
            ->method('get')
            ->willReturnMap([
                ['Index', $index = $this->createMock(Annotation::class)],
                ['HasMany', $many = $this->createMock(Annotation::class)],
                ['HasManyToMany', $many2many = $this->createMock(Annotation::class)],
                ['HasOne', $one = $this->createMock(Annotation::class)],
                ['BelongsTo', $belongs = $this->createMock(Annotation::class)],
                ['HasOneThrough', $oneThrough = $this->createMock(Annotation::class)],
            ]);

        $index->method('getArguments')->willReturn(['name' => 'test_index', 'columns' => 'test_column', 'unique' => true]);
        $many->method('getArguments')->willReturn(['','','']);
        $many2many->method('getArguments')->willReturn(['','','','','','']);
        $one->method('getArguments')->willReturn(['','','']);
        $belongs->method('getArguments')->willReturn(['','','']);
        $oneThrough->method('getArguments')->willReturn(['','','','','','']);

        $model = $this->createMock(Model::class);

        $strategy->getMetaData(
            $model,
            $this->createMock(DiInterface::class),
        );

        $this->assertTrue(true);
    }

    public function testGetColumnMaps()
    {
        $strategy = new Annotations();

        $this->annotations
            ->expects($this->once())
            ->method('get')
            ->willReturn($reflection = $this->createMock(Reflection::class));

        $reflection
            ->expects($this->once())
            ->method('getPropertiesAnnotations')
            ->willReturn([
                'test' => ($annotations = $this->createMock(Collection::class)),
                'test_bis' => $annotations,
            ]);

        $annotations
            ->expects($this->exactly(2))
            ->method('has')
            ->with('Column')
            ->willReturn(true);

        $annotations
            ->expects($this->exactly(2))
            ->method('get')
            ->with('Column')
            ->willReturn($column = $this->createMock(Annotation::class));

        $column
            ->expects($this->exactly(2))
            ->method('getArguments')
            ->willReturnOnConsecutiveCalls([], ['column' => 'test_double']);

        $map = $strategy->getColumnMaps(
            $this->createMock(Model::class),
            $this->createMock(DiInterface::class),
        );

        $this->assertSame([
            MetaData::MODELS_COLUMN_MAP => ['test' => 'test', 'test_double' => 'test_bis'],
            MetaData::MODELS_REVERSE_COLUMN_MAP => ['test' => 'test', 'test_bis' => 'test_double'],
        ], $map);
    }
}
