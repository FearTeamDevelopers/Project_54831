<?php

namespace THCFrame\Core;

use THCFrame\Core\Inspector as Inspector;
use THCFrame\Core\ArrayMethods as ArrayMethods;
use THCFrame\Core\StringMethods as StringMethods;
use THCFrame\Core\Exception as Exception;

/**
 * Description of Base
 *
 * @author Tomy
 */
class Base
{

    private $_inspector;
    protected $_dataStore = array();

    /**
     * 
     * @param type $property
     * @return \THCFrame\Core\Exception\ReadOnly
     */
    protected function _getReadonlyException($property)
    {
        return new Exception\ReadOnly(sprintf('%s is read-only', $property));
    }

    /**
     * 
     * @param type $property
     * @return \THCFrame\Core\Exception\WriteOnly
     */
    protected function _getWriteonlyException($property)
    {
        return new Exception\WriteOnly(sprintf('%s is write-only', $property));
    }

    /**
     * 
     * @return \THCFrame\Core\Exception\Property
     */
    protected function _getPropertyException()
    {
        return new Exception\Property('Invalid property');
    }

    /**
     * 
     * @param type $method
     * @return \THCFrame\Core\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        $this->_inspector = new Inspector($this);

        if (is_array($options) || is_object($options)) {
            foreach ($options as $key => $value) {
                $key = ucfirst($key);
                $method = "set{$key}";
                $this->$method($value);
            }
        }
    }

    /**
     * 
     * @param type $name
     * @param type $arguments
     * @return null|\THCFrame\Core\Base
     * @throws Exception
     * @throws type
     */
    public function __call($name, $arguments)
    {
        if (empty($this->_inspector)) {
            throw new Exception('Call parent::__construct!');
        }

        $getMatches = StringMethods::match($name, '#^get([a-zA-Z0-9_]+)$#');
        if (count($getMatches) > 0) {
            $normalized = lcfirst($getMatches[0]);
            $property = "_{$normalized}";

            if (property_exists($this, $property)) {
                $meta = $this->_inspector->getPropertyMeta($property);

                if (empty($meta['@readwrite']) && empty($meta['@read'])) {
                    throw $this->_getWriteonlyException($normalized);
                }

                if (isset($this->$property)) {
                    return $this->$property;
                }else{
                    return null;
                }
            } elseif (array_key_exists($normalized, $this->_dataStore)) {
                return $this->_dataStore[$normalized];
            } else {
                return null;
            }
        }

        $setMatches = StringMethods::match($name, '#^set([a-zA-Z0-9_]+)$#');
        if (count($setMatches) > 0) {
            $normalized = lcfirst($setMatches[0]);
            $property = "_{$normalized}";

            if (property_exists($this, $property)) {
                $meta = $this->_inspector->getPropertyMeta($property);

                if (empty($meta['@readwrite']) && empty($meta['@write'])) {
                    throw $this->_getReadonlyException($normalized);
                }

                $this->$property = $arguments[0];
                return $this;
            } else {
                $this->_dataStore[$normalized] = $arguments[0];
                return $this;
            }
        }

        $unsetMatches = StringMethods::match($name, '#^uns([a-zA-Z0-9_]+)$#');
        if (count($unsetMatches) > 0) {
            $normalized = lcfirst($setMatches[0]);
            $property = "_{$normalized}";

            if (property_exists($this, $property)) {
                $meta = $this->_inspector->getPropertyMeta($property);

                if (empty($meta['@readwrite']) && empty($meta['@write'])) {
                    throw $this->_getReadonlyException($normalized);
                }

                unset($this->$property);
                return $this;
            } else {
                unset($this->_dataStore[$normalized]);
                return $this;
            }
        }

        throw $this->_getImplementationException($name);
    }

    /**
     * 
     * @param type $name
     * @return type
     */
    public function __get($name)
    {

        $function = 'get' . ucfirst($name);
        return $this->$function();
    }

    /**
     * 
     * @param type $name
     * @param type $value
     * @return type
     */
    public function __set($name, $value)
    {
        $function = 'set' . ucfirst($name);
        return $this->$function($value);
    }

    /**
     * 
     * @param type $name
     * @return type
     */
    public function __unset($name)
    {
        $function = 'uns' . ucfirst($name);
        return $this->$function();
    }

}
