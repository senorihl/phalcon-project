<?php

use Phalcon\Di\DiInterface;
use Phalcon\Di\FactoryDefault;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Logger\Logger;

function build_config()
{
    return new \Phalcon\Config\Config([
        'base_path' => BASE_PATH
    ]);
}

function build_dependency_injection(): DiInterface
{
    $container = new FactoryDefault();

    $container->setShared('logger', function () use ($container) {
        $logger = new Logger('main', [new Stream('php://stdout')]);
        return $logger;
    });

    $container->setShared('router', function () use ($container) {
        $router = new \App\Phalcon\Router(false);
        $router->setDI($container);
        $router->setEventsManager($container->get('eventsManager'));
        return $router;
    });

    $container->setShared('config', build_config());

    return $container;
}