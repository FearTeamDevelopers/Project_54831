<?php

use THCFrame\Security\Model\BasicUser;

/**
 *
 */
class App_Model_User extends BasicUser
{

    /**
     * @column
     * @readwrite
     * @type text
     * @length 30
     *
     * @validate required, alpha, min(3), max(30)
     * @label first name
     */
    protected $_firstname;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 30
     *
     * @validate required, alpha, min(3), max(30)
     * @label last name
     */
    protected $_lastname;

    /**
     * 
     * @return type
     */
    public function getWholeName()
    {
        return $this->_firstname . ' ' . $this->_lastname;
    }

    /**
     * 
     * @return type
     */
    public function __toString()
    {
        $str = "Id: {$this->_id} <br/>Email: {$this->_email} <br/> Name: {$this->_firstname} {$this->_lastname}";
        return $str;
    }

}
