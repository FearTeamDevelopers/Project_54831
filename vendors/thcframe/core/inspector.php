<?php

namespace THCFrame\Core;

use THCFrame\Core\ArrayMethods as ArrayMethods;
use THCFrame\Core\StringMethods as StringMethods;

/**
 * Description of Inspector
 *
 * @author Tomy
 */
class Inspector
{

    protected $_class;
    protected $_meta = array(
        'class' => array(),
        'properties' => array(),
        'methods' => array()
    );
    protected $_properties = array();
    protected $_methods = array();

    /**
     * 
     * @param type $class
     */
    public function __construct($class)
    {
        $this->_class = $class;
    }

    /**
     * 
     * @return type
     */
    protected function _getClassComment()
    {
        $reflection = new \ReflectionClass($this->_class);
        return $reflection->getDocComment();
    }

    /**
     * 
     * @return type
     */
    protected function _getClassProperties()
    {
        $reflection = new \ReflectionClass($this->_class);
        return $reflection->getProperties();
    }

    /**
     * 
     * @return type
     */
    protected function _getClassMethods()
    {
        $reflection = new \ReflectionClass($this->_class);
        return $reflection->getMethods();
    }

    /**
     * 
     * @param type $property
     * @return type
     */
    protected function _getPropertyComment($property)
    {
        $reflection = new \ReflectionProperty($this->_class, $property);
        return $reflection->getDocComment();
    }

    /**
     * 
     * @param type $method
     * @return type
     */
    protected function _getMethodComment($method)
    {
        $reflection = new \ReflectionMethod($this->_class, $method);
        return $reflection->getDocComment();
    }

    /**
     * 
     * @param type $comment
     * @return type
     */
    protected function _parse($comment)
    {
        $meta = array();
        $pattern = '(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_]*)';
        $matches = StringMethods::match($comment, $pattern);

        if ($matches != null) {
            foreach ($matches as $match) {
                $parts = ArrayMethods::clean(
                                ArrayMethods::trim(
                                        StringMethods::split($match, '[\s]', 2)
                                )
                );

                $meta[$parts[0]] = true;

                if (count($parts) > 1) {
                    $meta[$parts[0]] = ArrayMethods::clean(
                                    ArrayMethods::trim(
                                            StringMethods::split($parts[1], ',')
                                    )
                    );
                }
            }
        }

        return $meta;
    }

    /**
     * 
     * @return null
     */
    public function getClassMeta()
    {
        if (!isset($_meta['class'])) {
            $comment = $this->_getClassComment();

            if (!empty($comment)) {
                $_meta['class'] = $this->_parse($comment);
            } else {
                $_meta['class'] = null;
            }
        }

        return $_meta['class'];
    }

    /**
     * 
     * @return type
     */
    public function getClassProperties()
    {
        if (!isset($_properties)) {
            $properties = $this->_getClassProperties();

            foreach ($properties as $property) {
                $_properties[] = $property->getName();
            }
        }

        return $_properties;
    }

    /**
     * 
     * @return type
     */
    public function getClassMethods()
    {
        if (!isset($_methods)) {
            $methods = $this->_getClassMethods();

            foreach ($methods as $method) {
                $_methods[] = $method->getName();
            }
        }

        return $_methods;
    }

    /**
     * 
     * @param type $property
     * @return null
     */
    public function getPropertyMeta($property)
    {
        if (!isset($_meta['properties'][$property])) {
            $comment = $this->_getPropertyComment($property);

            if (!empty($comment)) {
                $_meta['properties'][$property] = $this->_parse($comment);
            } else {
                $_meta['properties'][$property] = null;
            }
        }

        return $_meta['properties'][$property];
    }

    /**
     * 
     * @param type $method
     * @return null
     */
    public function getMethodMeta($method)
    {
        if (!isset($_meta['methods'][$method])) {
            $comment = $this->_getMethodComment($method);

            if (!empty($comment)) {
                $_meta['methods'][$method] = $this->_parse($comment);
            } else {
                $_meta['methods'][$method] = null;
            }
        }

        return $_meta['methods'][$method];
    }

}
