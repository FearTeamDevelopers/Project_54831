<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_News
 *
 * @author Tomy
 */
class App_Model_News extends Model
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
     * @length 200
     * 
     * @validate required, alphanumeric, max(200)
     * @label url key
     */
    protected $_urlKey;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 85
     * 
     * @validate alphanumeric, max(85)
     * @label author
     */
    protected $_author;

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
     * @length 256
     * 
     * @validate required, html, max(5000)
     * @label short text
     */
    protected $_shortBody;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate alphanumeric, max(5000)
     * @label Rss feed text
     */
    protected $_rssFeedBody;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html, max(20000)
     * @label text
     */
    protected $_body;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate date, max(22)
     * @label expiration date
     */
    protected $_expirationDate;

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
    protected $_fbLikeUrl;
    
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
