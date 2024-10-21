<?php

namespace App\Web\Controller;

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{
    /**
     * @return void
     * @Route("/")
     */
    public function indexAction()
    {

    }
    /**
     * @return \Phalcon\Http\ResponseInterface
     * @Route("/status")
     */
    public function statusAction()
    {
        $this->view->disable();

        try {
            $this->db->execute('SELECT 1');
            $db_status = 'Ok';
        } catch (\Throwable $e) {
            $db_status = 'Nok ('.$e->getMessage().')';

        }

        return $this->response->setContent(<<<TEXT
Liveliness: Ok
Database: $db_status
TEXT);
    }
}