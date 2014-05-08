<?php

namespace THCFrame\Cache\Driver;

use THCFrame\Cache as Cache;
use THCFrame\Cache\Exception as Exception;

/**
 * Description of Memcached
 *
 * @author Tomy
 */
class Memcached extends Cache\Driver
{

    protected $_service;

    /**
     * @readwrite
     */
    protected $_host = '127.0.0.1';

    /**
     * @readwrite
     */
    protected $_port = '11211';

    /**
     * @readwrite
     */
    protected $_isConnected = false;

    /**
     * @readwrite
     */
    protected $_duration;

    /**
     * 
     * @return boolean
     */
    protected function _isValidService()
    {
        $isEmpty = empty($this->_service);
        $isInstance = $this->_service instanceof \Memcache;

        if ($this->isConnected && $isInstance && !$isEmpty) {
            return true;
        }

        return false;
    }

    /**
     * 
     * @return \THCFrame\Cache\Driver\Memcached
     * @throws Exception\Service
     */
    public function connect()
    {
        try {
            $this->_service = new \Memcache();
            $this->_service->connect(
                    $this->host, $this->port
            );

            $this->isConnected = true;
        } catch (\Exception $e) {
            throw new Exception\Service('Unable to connect to service');
        }

        return $this;
    }

    /**
     * 
     * @return \THCFrame\Cache\Driver\Memcached
     */
    public function disconnect()
    {
        if ($this->_isValidService()) {
            $this->_service->close();
            $this->isConnected = false;
        }

        return $this;
    }

    /**
     * 
     * @param type $key
     * @param type $default
     * @return type
     * @throws Exception\Service
     */
    public function get($key, $default = null)
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid service');
        }

        $value = $this->_service->get($key, MEMCACHE_COMPRESSED);

        if ($value) {
            return $value;
        }

        return $default;
    }

    /**
     * 
     * @param type $key
     * @param type $value
     * @param type $duration
     * @return \THCFrame\Cache\Driver\Memcached
     * @throws Exception\Service
     */
    public function set($key, $value)
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid service');
        }

        $this->_service->set($key, $value, MEMCACHE_COMPRESSED, $this->duration);
        return $this;
    }

    /**
     * 
     * @param type $key
     * @return \THCFrame\Cache\Driver\Memcached
     * @throws Exception\Service
     */
    public function erase($key)
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid service');
        }

        $this->_service->delete($key);
        return $this;
    }

    /**
     * 
     * @return \THCFrame\Cache\Driver\Memcached
     * @throws Exception\Service
     */
    public function clearCache()
    {
        if (!$this->_isValidService()) {
            throw new Exception\Service('Not connected to a valid service');
        }

        $this->_service->flush();
        return $this;
    }

}
