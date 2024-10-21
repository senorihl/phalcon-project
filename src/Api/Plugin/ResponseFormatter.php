<?php

namespace App\Api\Plugin;

use Phalcon\Di\Injectable;
use Phalcon\Http\Response;
use Phalcon\Mvc\Dispatcher;

class ResponseFormatter extends Injectable
{
    public function afterExecuteRoute($_, Dispatcher $dispatcher)
    {
        $possibleResponse = $dispatcher->getReturnedValue();

        if (is_object($possibleResponse) && $possibleResponse instanceof Response) {
            return true;
        }

        if ($dispatcher->getReturnedValue() === false) {
            return true;
        }

        $dispatcher->setReturnedValue($this->response->setJsonContent($possibleResponse));
    }
}