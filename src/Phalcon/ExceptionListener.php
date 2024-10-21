<?php

namespace App\Phalcon;

use Phalcon\Di\Injectable;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;

class ExceptionListener extends Injectable
{
    public function beforeException(
        Event $event,
        Dispatcher $dispatcher,
        \Exception $ex
    ) {
        $event->isCancelable() && !$event->isStopped() && $event->stop();

        $dispatcher->forward([
            'controller' => 'error',
            'action' => 'exception',
            'params' => ['exception' => $ex],
        ]);
    }
}