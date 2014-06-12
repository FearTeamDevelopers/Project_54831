<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Photo
 *
 * @author Tomy
 */
class App_Model_Photo extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'ph';

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 60
     * 
     * @validate alphanumeric, max(60)
     * @label photo name
     */
    protected $_photoName;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate required, max(250)
     * @label thum path
     */
    protected $_thumbPath;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate required, max(250)
     * @label photo path
     */
    protected $_path;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate alphanumeric, max(250)
     * @label description
     */
    protected $_description;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 60
     * 
     * @validate alphanumeric, max(60)
     * @label category
     */
    protected $_category;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 32
     * 
     * @validate required, max(32)
     * @label mime type
     */
    protected $_mime;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     * @label size
     */
    protected $_size;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     * @label width
     */
    protected $_width;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     * @label height
     */
    protected $_height;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     * @label thumb size
     */
    protected $_thumbSize;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     * @label thumb width
     */
    protected $_thumbWidth;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     * @label thumb height
     */
    protected $_thumbHeight;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * 
     * @validate numeric, max(2)
     * @label priority
     */
    protected $_priority;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_modified;

    /**
     * @readwrite
     */
    protected $_inSections;

    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setActive(true);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     * @return type
     */
    public function getFormatedSize($unit = 'kb')
    {
        $bytes = floatval($this->_size);

        $units = array(
            'b' => 1,
            'kb' => 1024,
            'mb' => pow(1024, 2),
            'gb' => pow(1024, 3)
        );

        $result = $bytes / $units[strtolower($unit)];
        $result = strval(round($result, 2)) . ' ' . strtoupper($unit);

        return $result;
    }

    /**
     * 
     * @return type
     */
    public function getUnlinkPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_path)) {
                return APP_PATH . $this->_path;
            } elseif (file_exists('.' . $this->_path)) {
                return '.' . $this->_path;
            } elseif (file_exists('./' . $this->_path)) {
                return './' . $this->_path;
            }
        } else {
            return $this->_path;
        }
    }

    /**
     * 
     * @return type
     */
    public function getUnlinkThumbPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_thumbPath)) {
                return APP_PATH . $this->_thumbPath;
            } elseif (file_exists('.' . $this->_thumbPath)) {
                return '.' . $this->_thumbPath;
            } elseif (file_exists('./' . $this->_thumbPath)) {
                return './' . $this->_thumbPath;
            }
        } else {
            return $this->_thumbPath;
        }
    }

}
