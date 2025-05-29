<?php

namespace App\Tests\Phalcon;

use App\Phalcon\Router;
use App\Web\Controller as WebController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Router::class)]
class RouterTest extends TestCase
{

    public function testAddModuleResource()
    {
        $router = new Router();
        $router->addModuleResource('web', WebController\IndexController::class);

        $this->assertNotSame([
            [null, WebController\IndexController::class, 'web']
        ], $router->getResources());
    }
}
