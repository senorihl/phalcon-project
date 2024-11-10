<?php

namespace App\Api\Controller;

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{

    /**
     * @Route("/")
     */
    public function indexAction()
    {
        return [
            'Hello' => 'World!'
        ];
    }

    /**
     * @return \Phalcon\Http\ResponseInterface
     * @Route("/status")
     */
    public function statusAction()
    {
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