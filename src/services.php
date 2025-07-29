<?php

use App\Api\Controller as ApiController;
use App\Api\Module as ApiModule;
use App\Phalcon\Mvc\Model\MetaData\Strategy\Annotations;
use App\Phalcon\Router;
use App\Web\Controller as WebController;
use App\Web\Module as WebModule;
use Phalcon\Config\Config;
use Phalcon\Db\Adapter\PdoFactory;
use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Di\FactoryDefault;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Logger\Logger;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Model\MetaData\Memory;
use Symfony\Component\Console\Command\Command;

function build_config()
{
    $config = new Config([
        'base_path' => BASE_PATH,
    ]);

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BASE_PATH . "/configuration"));
    $files = new RegexIterator($files, '/\.ya?ml$/');

    foreach ($files as $file) {
        $config->merge(yaml_parse_file($file->getRealPath()));
    }

    return $config;
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

        return (new PdoFactory)
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

    $container->set('modelsMetadata', function () {
        $metadata = new Memory();
        $metadata->setStrategy(new Annotations());
        return $metadata;
    });

    $container->set('router', function () use ($container, $defaultModule) {
        $router = new Router(false);
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

    Di::setDefault($container);

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

/**
 * @return string[]
 */
function get_application_classes(): array
{
    $classes = [];
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(BASE_PATH . "/src"));
    $files = new RegexIterator($files, '/\.php$/');

    foreach ($files as $file) {
        if (str_starts_with($file->getRealPath(), BASE_PATH . '/src/Tests')) {
            continue;
        }
        $content = file_get_contents($file->getRealPath());
        $tokens = token_get_all($content);
        $namespace = '';
        for ($index = 0; isset($tokens[$index]); $index++) {
            if (!isset($tokens[$index][0])) {
                continue;
            }
            if (
                T_NAMESPACE === $tokens[$index][0]
                && T_WHITESPACE === $tokens[$index + 1][0]
                && (T_STRING === $tokens[$index + 2][0] || T_NAME_QUALIFIED === $tokens[$index + 2][0])
            ) {
                $namespace = $tokens[$index + 2][1];
                // Skip "namespace" keyword, whitespaces, and actual namespace
                $index += 2;
            }
            if (
                T_CLASS === $tokens[$index][0]
                && T_WHITESPACE === $tokens[$index + 1][0]
                && T_STRING === $tokens[$index + 2][0]
            ) {
                $classes[] = $namespace.'\\'.$tokens[$index + 2][1];
                // Skip "class" keyword, whitespaces, and actual classname
                $index += 2;

                # break if you have one class per file (psr-4 compliant)
                # otherwise you'll need to handle class constants (Foo::class)
                break;
            }
        }
    }

    return $classes;
}

/**
 * @return Command[]
 */
function get_commands(): array
{
    $commands = [];

    /** @var Config $config */
    $config   = Di::getDefault()->get('config');

    foreach (get_application_classes() as $class) {
        if (!in_array(Command::class, class_parents($class))) {
            continue;
        }

        if (str_starts_with($class, 'App\\Command\\Migration') && false === $config->path('migrations', false)) {
            continue;
        }

        $commands[] = new $class;
    }

    return $commands;
}