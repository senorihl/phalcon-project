<?php

namespace App\Tests;

use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Http\ResponseInterface;
use Phalcon\Mvc\Application;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Mvc\ModuleDefinitionInterface;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\ViewInterface;
use PHPUnit\Framework\TestCase;

abstract class PhalconTestCase extends TestCase
{
    public DiInterface $di;
    public Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        Di::reset();
        $this->di = build_dependency_injection($this->getModule());
        $this->application = build_application($this->getModule(), $this->di);
        Di::setDefault($this->di);

    }

    public function getDispatcher(): Dispatcher
    {
        return $this->di->getShared('dispatcher');
    }

    public function getRouter(): Router
    {
        return $this->di->getShared('router');
    }

    public function getResponse(): ResponseInterface
    {
        return $this->di->getShared('response');
    }

    public function getView(): ViewInterface
    {
        return $this->di->getShared('view');
    }

    public function handleURL(string $url): void
    {
        $this->application->handle($url);
    }

    public function handleAction(array $params = []): ?ResponseInterface
    {
        $this->bindModule();

        !empty($params['namespace']) && $this->getDispatcher()->setNamespaceName($params['namespace']);
        $this->getDispatcher()->setControllerName($params['controller']);
        $this->getDispatcher()->setActionName($params['action']);
        $this->getDispatcher()->setParams($params['params'] ?? []);
        $handlerClass = $this->getDispatcher()->getHandlerClass();
        $this->di->set($this->getDispatcher()->getHandlerClass(), new $handlerClass());
        /** @var false|Dispatcher $handler */
        $handler = $this->getDispatcher()->dispatch();

        if ($handler === false) {
            return null;
        }

        $response = $this->getDispatcher()->getReturnedValue();

        if (false === $response) {
            return $this->getResponse();
        } elseif (is_string($response)) {
            return $this->getResponse()->setContent($response);
        } elseif ($response instanceof ResponseInterface) {
            return $response;
        } else {
            ob_clean();
            ob_start();
            $this->getView()->render($this->getDispatcher()->getControllerName(), $this->getDispatcher()->getActionName());
            return $this->getResponse()->setContent(ob_get_clean());
        }
    }

    /**
     * @return void
     */
    public function bindModule(): void
    {
        $this->assertArrayHasKey($this->getModule(), $this->application->getModules());
        $this->assertArrayHasKey('className', $this->application->getModules()[$this->getModule()]);
        $moduleClassName = $this->application->getModules()[$this->getModule()]['className'];
        /** @var ModuleDefinitionInterface $module */
        $module = new $moduleClassName();
        $module->registerAutoloaders($this->di);
        $module->registerServices($this->di);
    }

    public abstract function getModule(): string;
}