<?php

namespace App\Api;

use App\Api\Plugin\ResponseFormatter;
use Phalcon\Di\DiInterface;
use Phalcon\Events\Event;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\ModuleDefinitionInterface;

class Module implements ModuleDefinitionInterface {

    public function registerAutoloaders(DiInterface $container = null)
    {

    }

    public function registerServices(DiInterface $container)
    {
        $container->get('eventsManager')->attach('application', $this);

        $container->get('eventsManager')->attach('dispatch', new ResponseFormatter());

        $container->setShared('dispatcher', function () use ($container) {
            $dispatcher = new Dispatcher();
            $dispatcher->setEventsManager($container->get('eventsManager'));
            $dispatcher->setControllerSuffix('Controller');
            $dispatcher->setDefaultNamespace('\\App\\Api\\Controller\\');
            return $dispatcher;
        });
    }

    public function afterStartModule($_, Application $application)
    {
        $application->useImplicitView(false);
    }
}