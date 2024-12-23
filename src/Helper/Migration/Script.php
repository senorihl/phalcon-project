<?php

namespace App\Helper\Migration;

use Phalcon\Di\Injectable;

abstract class Script extends Injectable
{
    abstract protected function up();
    abstract protected function down();
    abstract public function getVersion(): int;

    /**
     * @param 'up'|'down' $direction
     * @return void
     * @throws \Exception
     */
    public function run(string $direction)
    {
        if (!in_array($direction, ['up', 'down'])) {
            throw new \Exception(sprintf('Invalid method %s::%s', get_called_class(), $direction));
        }

        $this->$direction();
    }

    public function __get(string $propertyName): mixed
    {
        $return = parent::__get($propertyName);

        if ($return instanceof \Phalcon\Db\Adapter\AbstractAdapter) {
            $return->setEventsManager($this->eventsManager);
        }

        return $return;
    }


}