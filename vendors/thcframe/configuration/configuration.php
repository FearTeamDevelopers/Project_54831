<?php

namespace THCFrame\Configuration;

use THCFrame\Core\Base as Base;
use THCFrame\Events\Events as Events;
//use THCFrame\Configuration as Configuration;
use THCFrame\Configuration\Exception as Exception;

/**
 * Factory class
 * 
 * @author Tomy
 */
class Configuration extends Base
{

    /**
     * @readwrite
     */
    protected $_type;

    /**
     * @readwrite
     */
    protected $_options;

    /**
     * 
     * @param type $method
     * @return \THCFrame\Configuration\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * 
     * @return \THCFrame\Configuration\Configuration\Driver\Ini
     * @throws Exception\Argument
     */
    public function initialize()
    {
        Events::fire('framework.configuration.initialize.before', array($this->type, $this->options));

        if (!$this->type) {
            throw new Exception\Argument('Invalid type');
        }

        Events::fire('framework.configuration.initialize.after', array($this->type, $this->options));

        switch ($this->type) {
            case 'ini': {
                    return new Driver\Ini($this->options);
                    break;
                }
            default: {
                    throw new Exception\Argument('Invalid type');
                    break;
                }
        }
    }

}
