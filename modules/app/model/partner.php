<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Partner
 *
 * @author Tomy
 */
class App_Model_Partner extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'pa';

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
     * 
     * @validate max(3)
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     */
    protected $_sectionId;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 100
     * 
     * @validate required, alphanumeric, max(60)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate alphanumeric, max(1024)
     * @label address
     * 
     */
    protected $_address;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 100
     * 
     * @validate required, url, max(100)
     * @label web
     */
    protected $_web;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 200
     * 
     * @validate max(200)
     * @label logo
     */
    protected $_logo;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 60
     * 
     * @validate email, max(60)
     * @label email
     */
    protected $_email;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 25
     * 
     * @validate numeric, max(25)
     * @label mobile
     */
    protected $_mobile;

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
    public function getUnlinkLogoPath($type = true)
    {
        if ($type) {
            if (file_exists('./' . $this->_logo)) {
                return './' . $this->_logo;
            } elseif (file_exists('.' . $this->_logo)) {
                return '.' . $this->_logo;
            } elseif (file_exists($this->_logo)) {
                return $this->_logo;
            }
        } else {
            return $this->_logo;
        }
    }

}
