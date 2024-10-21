<?php

namespace App\Web\Controller;

use Phalcon\Mvc\Controller;

class ErrorController extends Controller
{
    public function notFoundAction()
    {
        $this->response->setStatusCode(404);
    }

    public function exceptionAction()
    {
        $this->view->setVar('exception', $this->dispatcher->getParam('exception'));
        $this->response->setStatusCode(500);
    }

}