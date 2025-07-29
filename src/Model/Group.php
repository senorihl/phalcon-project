<?php

namespace App\Model;

class Group extends \Phalcon\Mvc\Model
{
    /**
     * @Primary
     * @Identity
     * @Column(type='integer', nullable=false, autoIncrement=true)
     */
    public $id;

    /**
     * @Column(type='string', size=255, nullable=false, unique=true)
     */
    public $name;
}