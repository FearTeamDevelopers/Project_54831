<?php

namespace THCFrame\Cache\Driver;

use THCFrame\Cache as Cache;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Cache\Exception as Exception;

/**
 * Description of Filecache
 *
 * @author Tomy
 */
class Filecache extends Cache\Driver
{

    /**
     * @readwrite
     */
    protected $_duration;
    private $_cacheFilePath;
    private $_fileSuffix;

    /**
     * 
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $configuration = Registry::get('config');

        if (!empty($configuration->cache->filecache)) {
            $this->_cacheFilePath = APP_PATH . '/' . $configuration->cache->filecache->path . '/';
            $this->_fileSuffix = '.' . $configuration->cache->filecache->suffix;
        } else {
            throw new \Exception('Error in configuration file');
        }
    }

    /**
     * 
     * @param type $key
     * @return boolean
     */
    public function isFresh($key)
    {
        if (ENV == 'dev') {
            return false;
        }

        if (file_exists($this->_cacheFilePath . $key . $this->_fileSuffix)) {
            if (time() - filemtime($this->_cacheFilePath . $key . $this->_fileSuffix) <= $this->duration) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $key
     * @param type $default
     */
    public function get($key, $default = null)
    {
        if ($this->isFresh($key)) {
            $data = unserialize(file_get_contents($this->_cacheFilePath . $key . $this->_fileSuffix));
            return $data;
        } else {
            return $default;
        }
    }

    /**
     * 
     * @param type $key
     * @param type $value
     * @return type
     * @throws Exception\Service
     */
    public function set($key, $value)
    {
        $file = $this->_cacheFilePath . $key . $this->_fileSuffix;
        $tmpFile = tempnam($this->_cacheFilePath, basename($key . $this->_fileSuffix));

        if (false !== @file_put_contents($tmpFile, serialize($value)) && @rename($tmpFile, $file)) {
            @chmod($file, 0666 & ~umask());

            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }

            return;
        }

        throw new Exception\Service(sprintf('Failed to write cache file %s', $file));
    }

    /**
     * 
     * @param type $key
     */
    public function erase($key)
    {
        if (file_exists($this->_cacheFilePath . $key . $this->_fileSuffix)) {
            unlink($this->_cacheFilePath . $key . $this->_fileSuffix);
        }
    }

    /**
     * Removes all files and folders from cache folder
     */
    public function clearCache()
    {
        $dh = opendir($this->_cacheFilePath);
        if ($dh) {
            while ($file = readdir($dh)) {
                if (!in_array($file, array('.', '..'))) {
                    if (is_file($this->_cacheFilePath . $file)) {
                        unlink($this->_cacheFilePath . $file);
                    } else if (is_dir($this->_cacheFilePath . $file)) {
                        rmdir_files($this->_cacheFilePath . $file);
                    }
                }
            }
        }
    }

}
