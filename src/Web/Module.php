<?php

namespace App\Web;

use App\Phalcon\ExceptionListener;
use App\Phalcon\Volt\AssetExtension;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\ModuleDefinitionInterface;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt;

class Module implements ModuleDefinitionInterface
{

    public function registerAutoloaders(DiInterface $container = null) {}

    public function registerServices(DiInterface $container)
    {
        $container->setShared('dispatcher', function () use ($container) {
            $dispatcher = new Dispatcher();
            $dispatcher->setEventsManager($container->get('eventsManager'));
            $dispatcher->setControllerSuffix('Controller');
            $dispatcher->setDefaultNamespace('\\App\\Web\\Controller\\');
            $dispatcher->setDefaultController('Index');
            return $dispatcher;
        });

        $container->setShared('volt', function ($view) use ($container) {
            $volt = new Volt($view, $container);

            $volt->setOptions([
                'path' => BASE_PATH . '/var/cache/volt/',
            ]);

            $volt->getCompiler()->addExtension(new AssetExtension());

            return $volt;
        });

        $container->setShared('view', function () use ($container) {
            $view = new View();
            $view->setEventsManager($container->get('eventsManager'));
            $view->setViewsDir(BASE_PATH . '/views/');
            $view->setLayoutsDir(BASE_PATH . '/layouts/');
            $view->registerEngines(['.volt' => 'volt']);
            return $view;
        });

        $container->get('eventsManager')->attach('dispatch', new ExceptionListener());
    }
}