<?php

namespace THCFrame\Router;

use THCFrame\Core\Base as Base;
use THCFrame\Router\Exception as Exception;

/**
 * Description of Route
 *
 * @author Tomy
 */
class Route extends Base
{

    /**
     * The Route path consisting of route elements
     * @var string
     * @readwrite
     */
    protected $_pattern;

    /**
     *
     * @var type 
     * @readwrite
     */
    protected $_module;

    /**
     * The name of the class that this route maps to
     * @var string
     * @readwrite
     */
    protected $_controller;

    /**
     * The name of the class method that this route maps to
     * @var string
     * @readwrite
     */
    protected $_action;

    /**
     * 
     * @param type $method
     * @return \THCFrame\Router\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

}
