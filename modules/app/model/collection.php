<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Collection
 *
 * @author Tomy
 */
class App_Model_Collection extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'cl';

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
     * @type integer
     * @index
     * 
     * @validate required, numeric, max(8)
     * @label menu
     */
    protected $_menuId;

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
     * @type text
     * @length 150
     * 
     * @validate required, alphanumeric, max(150)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 15
     * 
     * @validate date, max(15)
     * @label date
     */
    protected $_date;

    /**
     * @column
     * @readwrite
     * @type integer
     * @length 5
     * 
     * @validate numeric, max(4)
     * @label year
     */
    protected $_year;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 60
     * 
     * @validate alphanumeric, max(60)
     * @label season
     */
    protected $_season;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html, max(5000)
     * @label description
     */
    protected $_description;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * 
     * @validate numeric, max(2)
     * @label rank
     */
    protected $_rank;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 100
     * 
     * @validate alphanumeric, max(100)
     * @label photographer
     */
    protected $_photographer;

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
    protected $_photos;
    
    /**
     * @readwrite
     */
    protected $_videos;

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

}
