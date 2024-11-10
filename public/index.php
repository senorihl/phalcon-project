<?php


use App\Web\Module as WebModule;
use App\Api\Module as ApiModule;
use Phalcon\Mvc\Application;

require_once '../vendor/autoload.php';

define('BASE_PATH', dirname(__DIR__));
define('PHALCON_MODULE', $_SERVER['PHALCON_MODULE'] ?? 'web');

$container = build_dependency_injection(PHALCON_MODULE);
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
$application->setDefaultModule(PHALCON_MODULE);

$response = $application->handle($_SERVER["REQUEST_URI"]);
$response->send();