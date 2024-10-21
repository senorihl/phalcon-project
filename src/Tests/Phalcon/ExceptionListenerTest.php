<?php

namespace App\Tests\Phalcon;

use App\Phalcon\ExceptionListener;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ExceptionListener::class)]
class ExceptionListenerTest extends TestCase
{
    public function testBeforeException()
    {
        $listener = new ExceptionListener();

        $event = $this->createMock(\Phalcon\Events\Event::class);
        $dispatcher = $this->createMock(\Phalcon\Mvc\Dispatcher::class);
        $ex = new \Exception('Test exception');

        $dispatcher->expects($this->once())
            ->method('forward')
            ->with([
                'controller' => 'error',
                'action' => 'exception',
                'params' => ['exception' => $ex],
            ]);

        $event->expects($this->once())
            ->method('isCancelable')
            ->willReturn(true);

        $event->expects($this->once())
            ->method('isStopped')
            ->willReturn(false);

        $event->expects($this->once())
            ->method('stop');

        $listener->beforeException($event, $dispatcher, $ex);
    }

}
