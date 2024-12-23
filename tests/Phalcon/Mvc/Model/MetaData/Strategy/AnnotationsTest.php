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
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function testGetRootStrategy()
    {
        $this->assertInstanceOf(\Phalcon\Mvc\Model\MetaData\Strategy\Annotations::class, (new Annotations)->getRootStrategy());
    }

    public function testGetMetaData()
    {
        $strategy = $this->createPartialMock(Annotations::class, ['getRootStrategy', 'getColumnMaps']);

        $strategy
            ->method('getColumnMaps')
            ->willReturn([
                [],
                [
                    'test_size' => 'test_size',
                    'test_index' => 'test_index',
                    'test_unique' => 'test_unique',
                    'test_anon_unique' => 'test_anon_unique',
                    'test_now' => 'test_now',
                    'test_belongs' => 'test_belongs',
                    'test_hasMany' => 'test_hasMany',
                    'test_hasOne' => 'test_hasOne',
                ]
            ]);

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
                'test_size' => $withLength = $this->createMock(Collection::class),
                'test_index' => $withIndex = $this->createMock(Collection::class),
                'test_unique' => $withUnique = $this->createMock(Collection::class),
                'test_anon_unique' => $withAnonUnique = $this->createMock(Collection::class),
                'test_now' => $withDefault = $this->createMock(Collection::class),
                'test_belongs' => $withBelongs = $this->createMock(Collection::class),
                'test_hasMany' => $withMany = $this->createMock(Collection::class),
                'test_hasOne' => $withOne = $this->createMock(Collection::class),
            ]);

        $classAnnotations
            ->method('getAnnotations')
            ->willReturn([
                $index = $this->createMock(Annotation::class),
                $index,
                $many = $this->createMock(Annotation::class),
                $many2many = $this->createMock(Annotation::class),
                $one = $this->createMock(Annotation::class),
                $belongs = $this->createMock(Annotation::class),
                $oneThrough = $this->createMock(Annotation::class)
            ]);

        $withLength->method('getAnnotations')->willReturn([$column = $this->createMock(Annotation::class)]);
        $withIndex->method('getAnnotations')->willReturn([$column]);
        $withUnique->method('getAnnotations')->willReturn([$column]);
        $withAnonUnique->method('getAnnotations')->willReturn([$column]);
        $withDefault->method('getAnnotations')->willReturn([$column]);
        $withBelongs->method('getAnnotations')->willReturn([$column_belongs = $this->createMock(Annotation::class)]);
        $withMany->method('getAnnotations')->willReturn([$column_many = $this->createMock(Annotation::class)]);
        $withOne->method('getAnnotations')->willReturn([$column_one = $this->createMock(Annotation::class)]);

        $column
            ->expects($this->atLeast(3))
            ->method('getName')
            ->willReturn('Column');

        $column
            ->expects($this->atLeast(3))
            ->method('getArguments')
            ->willReturnOnConsecutiveCalls(
                ['size' => 3],
                ['index' => 'columned_index'],
                ['unique' => 'columned_unique'],
                ['unique' => true],
                ['sql_default' => 'NOW()'],
            );

        $column_belongs
            ->expects($this->atLeast(1))
            ->method('getName')
            ->willReturn('BelongsTo');

        $column_belongs
            ->expects($this->atLeast(1))
            ->method('getArguments')
            ->willReturn([ get_class($this->createMock(\Phalcon\Mvc\Model::class)), 'id' ]);

        $column_many
            ->expects($this->atLeast(1))
            ->method('getName')
            ->willReturn('HasMany');

        $column_many
            ->expects($this->atLeast(1))
            ->method('getArguments')
            ->willReturn([ get_class($this->createMock(\Phalcon\Mvc\Model::class)), 'id' ]);

        $column_one
            ->expects($this->atLeast(1))
            ->method('getName')
            ->willReturn('HasOne');

        $column_one
            ->expects($this->atLeast(1))
            ->method('getArguments')
            ->willReturn([ get_class($this->createMock(\Phalcon\Mvc\Model::class)), 'id' ]);


        $index->method('getName')->willReturn('Index');
        $many->method('getName')->willReturn('HasMany');
        $many2many->method('getName')->willReturn('HasManyToMany');
        $one->method('getName')->willReturn('HasOne');
        $belongs->method('getName')->willReturn('BelongsTo');
        $oneThrough->method('getName')->willReturn('HasOneThrough');

        $index->method('getArguments')->willReturnOnConsecutiveCalls(
            ['name' => 'test_index', 'columns' => 'test_column', 'unique' => true],
            ['name' => 'test_index_bis', 'columns' => 'test_bis'],
        );
        $many->method('getArguments')->willReturn(['','','']);
        $many2many->method('getArguments')->willReturn(['','','','','','']);
        $one->method('getArguments')->willReturn(['','','']);
        $belongs->method('getArguments')->willReturn(['','','']);
        $oneThrough->method('getArguments')->willReturn(['','','','','','']);

        $model = $this->createMock(Model::class);

        $this->modelsManager->expects($this->exactly(2))->method('addHasMany')->withAnyParameters();
        $this->modelsManager->expects($this->once())->method('addHasManyToMany')->withAnyParameters();
        $this->modelsManager->expects($this->exactly(2))->method('addHasOne')->withAnyParameters();
        $this->modelsManager->expects($this->exactly(2))->method('addBelongsTo')->withAnyParameters();
        $this->modelsManager->expects($this->once())->method('addHasOneThrough')->withAnyParameters();

        $metaData = $strategy->getMetaData(
            $model,
            $this->createMock(DiInterface::class),
        );

        $this->assertSame(array (
                'unique' =>
                    array (
                        'test_index' =>
                            array (
                                0 => 'test_column',
                            ),
                        'columned_unique' =>
                            array (
                                0 => 'test_unique',
                            ),
                        'test_anon_unique_uniq' =>
                            array (
                                0 => 'test_anon_unique',
                            ),
                    ),
                'indexes' =>
                    array (
                        'test_index_bis' =>
                            array (
                                0 => 'test_bis',
                            ),
                        'columned_index' =>
                            array (
                                0 => 'test_index',
                            ),
                    ),
                'sizes' =>
                    array (
                        'test_size' => 3,
                    ),
                'sql_default' =>
                    array (
                        'test_now' => 'NOW()',
                    ),
            )
            , $metaData);
    }

    public static function getInvalidRelations(): array
    {
        return [
            ['test_belongs'],
            ['test_hasMany'],
            ['test_hasOne'],
        ];
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[DataProvider('getInvalidRelations')]
    public function testGetMetaDataInvalidRelatedModel(string $invalidRelationColumn)
    {
        $strategy = $this->createPartialMock(Annotations::class, ['getRootStrategy', 'getColumnMaps']);

        $strategy
            ->method('getColumnMaps')
            ->willReturn([
                [],
                [
                    'test_belongs' => 'test_belongs',
                    'test_hasMany' => 'test_hasMany',
                    'test_hasOne' => 'test_hasOne',
                ]
            ]);

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
                'test_belongs' => $withBelongs = $this->createMock(Collection::class),
                'test_hasMany' => $withMany = $this->createMock(Collection::class),
                'test_hasOne' => $withOne = $this->createMock(Collection::class),
            ]);

        $classAnnotations
            ->method('getAnnotations')
            ->willReturn([]);

        $withBelongs->method('getAnnotations')->willReturn([$column_belongs = $this->createMock(Annotation::class)]);
        $withMany->method('getAnnotations')->willReturn([$column_many = $this->createMock(Annotation::class)]);
        $withOne->method('getAnnotations')->willReturn([$column_one = $this->createMock(Annotation::class)]);

        $column_belongs
            ->method('getName')
            ->willReturn('BelongsTo');

        $column_belongs
            ->method('getArguments')
            ->willReturn([ $invalidRelationColumn === 'test_belongs' ? \stdClass::class : get_class($this->createMock(\Phalcon\Mvc\Model::class)), 'id' ]);

        $column_many
            ->method('getName')
            ->willReturn('HasMany');

        $column_many
            ->method('getArguments')
            ->willReturn([ $invalidRelationColumn === 'test_hasMany' ? \stdClass::class : get_class($this->createMock(\Phalcon\Mvc\Model::class)), 'id' ]);

        $column_one
            ->method('getName')
            ->willReturn('HasOne');

        $column_one
            ->method('getArguments')
            ->willReturn([ $invalidRelationColumn === 'test_hasOne' ? \stdClass::class : get_class($this->createMock(\Phalcon\Mvc\Model::class)), 'id' ]);


        $model = $this->createMock(Model::class);

        $this->modelsManager->expects($this->any())->method('addHasMany')->withAnyParameters();
        $this->modelsManager->expects($this->never())->method('addHasManyToMany')->withAnyParameters();
        $this->modelsManager->expects($this->any())->method('addHasOne')->withAnyParameters();
        $this->modelsManager->expects($this->any())->method('addBelongsTo')->withAnyParameters();
        $this->modelsManager->expects($this->never())->method('addHasOneThrough')->withAnyParameters();

        $this->expectExceptionMessageMatches('#^Unable to reference a non-Model object stdClass in MockObject_Model_#mi');

        $strategy->getMetaData(
            $model,
            $this->createMock(DiInterface::class),
        );


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
