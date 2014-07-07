<?php

use THCFrame\Model\Model;
use THCFrame\Security\UserInterface;

/**
 * Description of App_Model_User
 *
 * @author Tomy
 */
class App_Model_User extends Model implements UserInterface {

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
     * @type text
     * @length 60
     * @index
     * @unique
     *
     * @validate required, email, max(60)
     * @label email address
     */
    protected $_email;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * @index
     *
     * @validate required, min(5), max(250)
     * @label password
     */
    protected $_password;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 50
     * @unique
     *
     * @validate min(45), max(50)
     */
    protected $_salt;
    
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
     * @length 25
     * 
     * @validate required, alpha, max(25)
     * @label user role
     */
    protected $_role;

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

    /**
     * 
     * @param type $value
     * @throws \THCFrame\Security\Exception\Role
     */
    public function setRole($value) {
        $role = strtolower(substr($value, 0, 5));
        if ($role != 'role_') {
            throw new \THCFrame\Security\Exception\Role(sprintf('Role %s is not valid', $value));
        } else {
            $this->_role = $value;
        }
    }

    /**
     * 
     */
    public function isActive() {
        return (boolean) $this->_active;
    }

    /**
     * 
     * @return type
     */
    public function getWholeName() {
        return $this->_firstname . ' ' . $this->_lastname;
    }

    /**
     * 
     * @return type
     */
    public function __toString() {
        $str = "Id: {$this->_id} <br/>Email: {$this->_email} <br/> Name: {$this->_firstname} {$this->_lastname}";
        return $str;
    }

}