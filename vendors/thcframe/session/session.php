<?php

namespace THCFrame\Session;

use THCFrame\Core\Base as Base;
use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Session\Exception as Exception;

/**
 * Factory class
 * 
 * @author Tomy
 */
class Session extends Base
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
     * @return \THCFrame\Session\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * 
     * @return \THCFrame\Session\Session\Driver\Server
     * @throws Exception\Argument
     */
    public function initialize()
    {
        Events::fire('framework.session.initialize.before', array($this->type, $this->options));

        if (!$this->type) {
            $configuration = Registry::get('config');

            if (!empty($configuration->session->default) && !empty($configuration->session->default->type)) {
                $this->type = $configuration->session->default->type;
                unset($configuration->session->default->type);
                $this->options = (array) $configuration->session->default;
            } else {
                throw new \Exception('Error in configuration file');
            }
        }

        if (!$this->type) {
            throw new Exception\Argument('Invalid type');
        }

        Events::fire('framework.session.initialize.after', array($this->type, $this->options));

        switch ($this->type) {
            case 'server': {
                    return new Driver\Server($this->options);
                    break;
                }
            default: {
                    throw new Exception\Argument('Invalid type');
                    break;
                }
        }
    }

}
