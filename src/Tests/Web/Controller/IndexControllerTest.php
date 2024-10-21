<?php

namespace App\Tests\Web\Controller;

use App\Web\Controller\ErrorController;
use App\Phalcon\ExceptionListener;
use App\Tests\PhalconTestCase;
use App\Web\Controller\IndexController;
use App\Web\Module;
use Phalcon\Db\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(IndexController::class)]
#[UsesClass(Module::class)]
#[UsesClass(ExceptionListener::class)]
#[UsesClass(ErrorController::class)]
class IndexControllerTest extends PhalconTestCase
{

    public function testPhpinfoAction()
    {
        $this->handleAction([
            'controller' => 'index',
            'action' => 'phpinfo',
        ]);
        $this->assertEquals(200, $this->getResponse()->getStatusCode() ?? 200);
        $this->assertStringContainsString('Linux', $this->getResponse()->getContent());
    }

    public function testStatusAction()
    {
        $this->handleAction([
            'controller' => 'index',
            'action' => 'status',
        ]);
        $this->assertEquals(200, $this->getResponse()->getStatusCode() ?? 200);
        $this->assertStringContainsString('Liveliness: Ok', $this->getResponse()->getContent());
        $this->assertStringContainsString('Database: Ok', $this->getResponse()->getContent());
    }

    public function testStatusExceptionAction()
    {
        $db = $this->createMock(AdapterInterface::class);
        $db->expects($this->once())
            ->method('execute')
            ->with('SELECT 1')
            ->willThrowException(new \Exception());

        $this->di->setShared('db', $db);
        $this->handleAction([
            'controller' => 'index',
            'action' => 'status',
        ]);
        $this->assertEquals(200, $this->getResponse()->getStatusCode() ?? 200);
        $this->assertStringContainsString('Liveliness: Ok', $this->getResponse()->getContent());
        $this->assertStringContainsString('Database: Nok', $this->getResponse()->getContent());
    }

    public function testExceptionAction()
    {
        $this->handleAction([
            'controller' => 'index',
            'action' => 'exception',
        ]);
        $this->assertEquals(500, $this->getResponse()->getStatusCode());
        $this->assertStringContainsString('<title>Something went wrong</title>', $this->getResponse()->getContent());
        $this->assertStringContainsString('Example exception', $this->getResponse()->getContent());
    }

    public function testIndexAction()
    {
        $this->handleAction([
            'controller' => 'index',
            'action' => 'index',
        ]);
        $this->assertEquals(200, $this->getResponse()->getStatusCode() ?? 200);
        $this->assertStringContainsString('<title>You\'re all set up</title>', $this->getResponse()->getContent());
    }

    public function getModule(): string
    {
        return 'web';
    }
}
