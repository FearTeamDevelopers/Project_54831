<?php

use THCFrame\Model\Model as Model;

/**
 * Description of App_Model_Section
 *
 * @author Tomy
 */
class App_Model_Section extends Model
{

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
     */
    protected $_parentId;

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
     * @length 40
     * 
     * @validate required, alphanumeric, max(40)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 100
     * @unique
     * @index
     * 
     * @validate required, alphanumeric, max(100)
     * @label url key
     */
    protected $_urlKey;

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
     * @type boolean
     */
    protected $_supportVideo;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     */
    protected $_supportPhoto;
    
    /**
     * @column
     * @readwrite
     * @type boolean
     */
    protected $_supportCollection;

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

}
