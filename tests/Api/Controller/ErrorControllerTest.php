<?php

namespace App\Tests\Api\Controller;

use App\Api\Controller\ErrorController;
use App\Api\Module;
use App\Api\Plugin\ResponseFormatter;
use App\Tests\PhalconTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ErrorController::class)]
#[CoversClass(ResponseFormatter::class)]
#[UsesClass(Module::class)]
class ErrorControllerTest extends PhalconTestCase
{

    public function testNotFoundAction()
    {
        $this->handleAction(['action' => 'notFound', 'controller' => 'error']);
        $this->assertJson($this->getResponse()->getContent());
        $this->assertSame(['error' => 'This resource does not exists.'], json_decode($this->getResponse()->getContent(), true));

    }

    public function getModule(): string
    {
        return 'api';
    }
}
