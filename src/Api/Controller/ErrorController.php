<?php

namespace App\Api\Controller;

use Phalcon\Mvc\Controller;

class ErrorController extends Controller
{
    public function notFoundAction()
    {
        $this->response->setJsonContent(['error' => 'This resource does not exists.']);
        $this->response->setStatusCode(404);
        return false;
    }
}