<?php

namespace THCFrame\Database;

use THCFrame\Core\Base as Base;
use THCFrame\Database\Exception as Exception;

/**
 * Description of Connector
 *
 * @author Tomy
 */
abstract class Connector extends Base
{

    /**
     * 
     * @return \THCFrame\Database\Connector
     */
    public function initialize()
    {
        return $this;
    }

    /**
     * 
     * @param type $method
     * @return \THCFrame\Database\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    abstract function connect();

    abstract function disconnect();

    abstract function query();

    abstract function execute($sql);

    abstract function escape($value);

    abstract function getLastInsertId();

    abstract function getAffectedRows();

    abstract function getLastError();

    abstract function sync(\THCFrame\Model\Model $model);
}
