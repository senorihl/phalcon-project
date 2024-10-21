<?php

use App\Web\Module as WebModule;
use App\Api\Module as ApiModule;
use Phalcon\Mvc\Application;
use App\Web\Controller as WebController;
use App\Api\Controller as ApiController;
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

function build_dependency_injection(string $defaultModule): DiInterface
{
    $container = new FactoryDefault();

    $container->set('logger', function () use ($container) {
        $logger = new Logger('main', [new Stream('php://stdout')]);
        return $logger;
    });

    $container->set('db', function () {
        $parts = parse_url(getenv('DATABASE_URL'));

        return (new \Phalcon\Db\Adapter\PdoFactory)
            ->newInstance(
                $parts['scheme'],
                [
                    'host' => $parts['host'],
                    'username' => $parts['user'],
                    'password' => $parts['pass'],
                    'dbname' => explode('/', trim($parts['path'], '/'))[0],
                ]
            );
    });

    $container->set('router', function () use ($container, $defaultModule) {
        $router = new \App\Phalcon\Router(false);
        $router->setDI($container);
        $router->setDefaultModule($defaultModule);
        $router->setEventsManager($container->get('eventsManager'));

        switch ($defaultModule) {
            case 'web':
                $router->addModuleResource('web', WebController\IndexController::class);
                $router->addModuleResource('web', WebController\ErrorController::class);
                break;
            case 'api':
                $router->addModuleResource('api', ApiController\IndexController::class);
                $router->addModuleResource('api', ApiController\ErrorController::class);
                break;
        }

        return $router;
    });

    $container->set('config', build_config());

    return $container;
}

function build_application(string $defaultModule, DiInterface $container): Application
{
    $application = new Application($container);
    $application->registerModules([
        "web" => [
            "className" => WebModule::class,
            "path"      => BASE_PATH . "/src/Web/Module.php",
        ],
        "api" => [
            "className" => ApiModule::class,
            "path"      => BASE_PATH . "/src/Api/Module.php",
        ]
    ]);
    $application->setEventsManager($container->get('eventsManager'));
    $application->setDefaultModule($defaultModule);
    return $application;
}