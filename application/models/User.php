<?php

/**
 * Application Models
 *
 * @package Messerve_Model
 * @subpackage Model
 * @author Slide Gurtiza
 * @copyright Slide Gurtiza
 * @license All rights reserved
 */


/**
 * 
 *
 * @package Messerve_Model
 * @subpackage Model
 * @author Slide Gurtiza
 */
class Messerve_Model_User extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Id;

    /**
     * Database var type varchar(32)
     *
     * @var string
     */
    protected $_Username;

    /**
     * Database var type varchar(32)
     *
     * @var string
     */
    protected $_Password;

    /**
     * Database var type varchar(32)
     *
     * @var string
     */
    protected $_PasswordSalt;

    /**
     * Database var type varchar(128)
     *
     * @var string
     */
    protected $_RealName;

    /**
     * Database var type enum('supervisor','accounting','manager','admin','encoder')
     *
     * @var string
     */
    protected $_Type;

    /**
     * Database var type enum('active','inactive')
     *
     * @var string
     */
    protected $_Status;

    /**
     * Database var type text
     *
     * @var string
     */
    protected $_Permissions;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'username'=>'Username',
            'password'=>'Password',
            'password_salt'=>'PasswordSalt',
            'real_name'=>'RealName',
            'type'=>'Type',
            'status'=>'Status',
            'permissions'=>'Permissions',
        ));

        $this->setParentList(array(
        ));

        $this->setDependentList(array(
        ));
    }

    /**
     * Sets column id
     *
     * @param int $data
     * @return Messerve_Model_User
     */
    public function setId($data)
    {
        $this->_Id = $data;
        return $this;
    }

    /**
     * Gets column id
     *
     * @return int
     */
    public function getId()
    {
        return $this->_Id;
    }

    /**
     * Sets column username
     *
     * @param string $data
     * @return Messerve_Model_User
     */
    public function setUsername($data)
    {
        $this->_Username = $data;
        return $this;
    }

    /**
     * Gets column username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->_Username;
    }

    /**
     * Sets column password
     *
     * @param string $data
     * @return Messerve_Model_User
     */
    public function setPassword($data)
    {
        $this->_Password = $data;
        return $this;
    }

    /**
     * Gets column password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->_Password;
    }

    /**
     * Sets column password_salt
     *
     * @param string $data
     * @return Messerve_Model_User
     */
    public function setPasswordSalt($data)
    {
        $this->_PasswordSalt = $data;
        return $this;
    }

    /**
     * Gets column password_salt
     *
     * @return string
     */
    public function getPasswordSalt()
    {
        return $this->_PasswordSalt;
    }

    /**
     * Sets column real_name
     *
     * @param string $data
     * @return Messerve_Model_User
     */
    public function setRealName($data)
    {
        $this->_RealName = $data;
        return $this;
    }

    /**
     * Gets column real_name
     *
     * @return string
     */
    public function getRealName()
    {
        return $this->_RealName;
    }

    /**
     * Sets column type
     *
     * @param string $data
     * @return Messerve_Model_User
     */
    public function setType($data)
    {
        $this->_Type = $data;
        return $this;
    }

    /**
     * Gets column type
     *
     * @return string
     */
    public function getType()
    {
        return $this->_Type;
    }

    /**
     * Sets column status
     *
     * @param string $data
     * @return Messerve_Model_User
     */
    public function setStatus($data)
    {
        $this->_Status = $data;
        return $this;
    }

    /**
     * Gets column status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->_Status;
    }

    /**
     * Sets column permissions
     *
     * @param string $data
     * @return Messerve_Model_User
     */
    public function setPermissions($data)
    {
        $this->_Permissions = $data;
        return $this;
    }

    /**
     * Gets column permissions
     *
     * @return string
     */
    public function getPermissions()
    {
        return $this->_Permissions;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_User
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_User());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_User::delete
     * @return int|boolean Number of rows deleted or boolean if doing soft delete
     */
    public function deleteRowByPrimaryKey()
    {
        if ($this->getId() === null) {
            throw new Exception('Primary Key does not contain a value');
        }

        return $this->getMapper()
                    ->getDbTable()
                    ->delete('id = ' .
                             $this->getMapper()
                                  ->getDbTable()
                                  ->getAdapter()
                                  ->quote($this->getId()));
    }
}
