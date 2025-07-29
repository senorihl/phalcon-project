<?php

namespace App\Tests\Web\Controller;

use App\Tests\PhalconTestCase;
use App\Web\Controller\ErrorController;
use App\Web\Module;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ErrorController::class)]
#[UsesClass(Module::class)]
class ErrorControllerTest extends PhalconTestCase
{

    public function testNotFoundAction()
    {
        $this->handleAction([
            'controller' => 'error',
            'action' => 'notFound',
        ]);
        $this->assertEquals(404, $this->getResponse()->getStatusCode());
        $this->assertStringContainsString('<title>Page not found</title>', $this->getResponse()->getContent());
    }

    public function testExceptionAction()
    {
        $e = new \Exception('Unit Test Exception');
        $this->handleAction([
            'controller' => 'error',
            'action' => 'exception',
            'params' => [
                'exception' => $e
            ]
        ]);
        $this->assertEquals(500, $this->getResponse()->getStatusCode());
        $this->assertStringContainsString('<title>Something went wrong</title>', $this->getResponse()->getContent());
        $this->assertStringContainsString($e->getMessage(), $this->getResponse()->getContent());

    }

    public function getModule(): string
    {
        return 'web';
    }
}
