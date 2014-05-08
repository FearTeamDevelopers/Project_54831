<?php

namespace THCFrame\Registry;

/**
 * Description of Registry
 *
 * @author Tomy
 */
class Registry
{

    private static $_instances = array();

    private function __construct()
    {
        
    }

    private function __clone()
    {
        
    }

    /**
     * 
     * @param type $key
     * @param type $default
     * @return type
     */
    public static function get($key, $default = null)
    {
        if (isset(self::$_instances[$key])) {
            return self::$_instances[$key];
        }
        return $default;
    }

    /**
     * 
     * @param type $key
     * @param type $instance
     */
    public static function set($key, $instance = null)
    {
        self::$_instances[$key] = $instance;
    }

    /**
     * 
     * @param type $key
     */
    public static function erase($key)
    {
        unset(self::$_instances[$key]);
    }

}
