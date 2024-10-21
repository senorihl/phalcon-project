<?php

namespace App\Phalcon;

use App\Web\Controller\ErrorController;
use App\Web\Controller\IndexController;
use Phalcon\Mvc\Router\Annotations;

class Router extends Annotations
{
    public function __construct(bool $defaultRoutes = true)
    {
        parent::__construct($defaultRoutes);

        $this->addModuleResource('web', IndexController::class);
        $this->addModuleResource('web', ErrorController::class);
        $this->setControllerSuffix('Controller');
        $this->notFound(['controller' => 'Error', 'action' => 'notFound']);
    }

    /**
     * @param string $module
     * @param string $handler
     * @param string|null $prefix
     * @return self
     *
     * Fix that allow to use full class name
     */
    public function addModuleResource(string $module, string $handler, string $prefix = null): self
    {
        if (class_exists($handler) && str_ends_with($handler, $this->controllerSuffix)) {
            $handler = substr($handler, 0, strlen($handler) - strlen($this->controllerSuffix));
        }

        return parent::addModuleResource($module, $handler, $prefix);
    }

    public function beforeException()
    {
        dd(func_get_args());
    }
}