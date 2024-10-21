<?php

namespace App\Tests\Web;

use App\Web\Controller\IndexController;
use App\Web\Module;
use Phalcon\Di\DiInterface;
use Phalcon\Events\Manager;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\View;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Module::class)]
class ModuleTest extends TestCase
{

    public function testRegisterAutoloaders()
    {
        $module = new Module();

        $di = $this
            ->getMockBuilder(DiInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->expectNotToPerformAssertions();

        $module->registerAutoloaders($di);
    }

    public function testRegisterServices()
    {
        $module = new Module();

        $di = $this
            ->getMockBuilder(DiInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->createMock(Manager::class);

        $em->expects($this->once())
            ->method('attach')
            ->withAnyParameters();

        $di->expects($this->any())
            ->method('get')
            ->with('eventsManager')
            ->willReturn($em);

        $di->expects($this->exactly(3))
            ->method('setShared')
            ->with($this->callback(function ($name, ...$args) {
                switch ($name) {
                    case 'dispatcher':
                        $this->assertTrue(is_callable($args[0]), 'Creator for dispatcher is not a function');
                        $service = $args[0]();
                        $this->assertInstanceOf(Dispatcher::class, $service);
                        return true;
                    case 'volt':
                        $this->assertTrue(is_callable($args[0]), 'Creator for volt is not a function');
                        $service = $args[0]($this->createStub(View::class));
                        $this->assertInstanceOf(View\Engine\Volt::class, $service);
                        return true;
                    case 'view':
                        $this->assertTrue(is_callable($args[0]), 'Creator for view is not a function');
                        $service = $args[0]();
                        $this->assertInstanceOf(View::class, $service);
                        return true;
                }

                return false;
            }))
            ;

        $module->registerServices($di);
    }
}
