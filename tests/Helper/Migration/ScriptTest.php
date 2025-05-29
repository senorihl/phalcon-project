<?php

namespace App\Tests\Helper\Migration;

use App\Helper\Migration\Script;
use Phalcon\Db\Adapter\AbstractAdapter;
use Phalcon\Di\Di;
use Phalcon\Events\ManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Script::class)]
class ScriptTest extends TestCase
{

    public static function getExpectedRun()
    {
        return [
            ['up', 'up'],
            ['down', 'down'],
            ['invalid', null, ['Invalid method', '::invalid']],
        ];
    }

    #[DataProvider('getExpectedRun')]
    public function testRun(string $direction, ?string $expectedOutput, array $expectedExceptionMessages = [])
    {
        $script = new class extends Script
        {
            protected function up()
            {
                echo 'up';
            }

            protected function down()
            {
                echo 'down';
            }

            public function getVersion(): int
            {
                return 0;
            }
        };

        if ($expectedOutput !== null) {
            $this->expectOutputString($expectedOutput);
        }

        foreach ($expectedExceptionMessages  as $expectedExceptionMessage) {
            $this->expectExceptionMessage($expectedExceptionMessage);
        }

        $script->run($direction);
    }

    public function test__get()
    {
        Di::getDefault()->set('dummy', $dummy = $this->createMock(AbstractAdapter::class));
        Di::getDefault()->set('eventsManager', $em = $this->createMock(ManagerInterface::class));

        $script = new class extends Script
        {
            protected function up() {
                $this->dummy->getType();
            }

            protected function down() {}

            public function getVersion(): int { return 0; }
        };

        $dummy->expects($this->once())
            ->method('setEventsManager')
            ->with($em);

        $script->run('up');

    }
}
