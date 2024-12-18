<?php

namespace App\Tests\Api\Controller;

use App\Api\Controller\IndexController;
use App\Api\Module;
use App\Api\Plugin\ResponseFormatter;
use App\Tests\PhalconTestCase;
use Phalcon\Db\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexController::class)]
#[CoversClass(ResponseFormatter::class)]
#[UsesClass(Module::class)]
class IndexControllerTest extends PhalconTestCase
{

    public function testStatusAction()
    {
        $db = $this->createMock(AdapterInterface::class);
        $db->expects($this->once())
            ->method('execute')
            ->with('SELECT 1')
            ->willReturn(true);
        $this->di->setShared('db', $db);

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

    public function testIndexAction()
    {
        $this->handleAction(['action' => 'index', 'controller' => 'index']);
        $this->assertJson($this->getResponse()->getContent());
        $this->assertSame(['Hello' => 'World!'], json_decode($this->getResponse()->getContent(), true));
    }

    public function getModule(): string
    {
        return 'api';
    }
}
