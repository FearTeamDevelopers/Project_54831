<?php

use THCFrame\Model\Model as Model;

/**
 * Description of App_Model_Announcement
 *
 * @author Tomy
 */
class App_Model_Announcement extends Model {

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
     * @length 100
     * 
     * @validate required, alphanumeric, max(100)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, alphanumeric, max(2048)
     * @label text
     */
    protected $_body;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 80
     * 
     * @validate required, max(80)
     * @label signature
     */
    protected $_signature;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate date, max(22)
     * @label start date
     */
    protected $_dateStart;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate date, max(22)
     * @label end date
     */
    protected $_dateEnd;
    
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
    public function preSave() {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setActive(true);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

}