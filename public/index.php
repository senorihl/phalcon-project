<?php

require_once '../vendor/autoload.php';

define('BASE_PATH', dirname(__DIR__));
define('PHALCON_MODULE', $_SERVER['PHALCON_MODULE'] ?? 'web');

$container = build_dependency_injection(PHALCON_MODULE);
$application = build_application(PHALCON_MODULE, $container);
$response = $application->handle($_SERVER["REQUEST_URI"]);
$response->send();