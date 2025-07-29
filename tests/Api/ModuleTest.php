<?php

namespace App\Tests\Api;

use App\Api\Module;
use Phalcon\Di\DiInterface;
use Phalcon\Events\Manager;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher;
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

        $em->expects($this->exactly(2))
            ->method('attach')
            ->withAnyParameters();

        $di->expects($this->any())
            ->method('get')
            ->with('eventsManager')
            ->willReturn($em);

        $di->expects($this->once())
            ->method('setShared')
            ->with('dispatcher', $this->callback(function ($name, ...$args) {
                $this->assertTrue(is_callable($args[0]), 'Creator for dispatcher is not a function');
                $service = $args[0]();
                $this->assertInstanceOf(Dispatcher::class, $service);
                return true;
            }))
        ;

        $module->registerServices($di);
    }

    public function testAfterStartModule()
    {
        $app = $this->createMock(Application::class);

        $app->expects($this->once())
            ->method('useImplicitView')
            ->with(false)
            ->willReturnSelf();

        $module = new Module();
        $module->afterStartModule(null, $app);
    }
}
