<?php

namespace App\Phalcon;

use Phalcon\Mvc\Router\Annotations;

class Router extends Annotations
{
    public function __construct(bool $defaultRoutes = true)
    {
        parent::__construct($defaultRoutes);
        $this->setControllerSuffix('Controller');
        $this->notFound(['controller' => 'error', 'action' => 'notFound']);
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
}