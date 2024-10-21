<?php


use App\Web\Module as WebModule;
use Phalcon\Mvc\Application;

require_once '../vendor/autoload.php';

define('BASE_PATH', dirname(__DIR__));

$container = build_dependency_injection();
$application = new Application($container);
$application->setDefaultModule($_SERVER['PHALCON_MODULE']);
$application->registerModules([
    "web" => [
      "className" => WebModule::class,
      "path"      => BASE_PATH . "/src/Web/Module.php",
    ]
]);
$application->setEventsManager($container->get('eventsManager'));
$response = $application->handle($_SERVER["REQUEST_URI"]);
$response->send();