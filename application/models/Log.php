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
class Messerve_Model_Log extends Messerve_Model_ModelAbstract
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
    protected $_Ipaddress;

    /**
     * Database var type datetime
     *
     * @var string
     */
    protected $_Datetime;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_UserName;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_UserId;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_ClientId;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_EmployeeId;

    /**
     * Database var type enum('attendance','rate','calendar','client','employee','new client','new employee')
     *
     * @var string
     */
    protected $_ChangeType;

    /**
     * Database var type text
     *
     * @var string
     */
    protected $_Description;

    /**
     * Database var type text
     *
     * @var string
     */
    protected $_Data;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'ipaddress'=>'Ipaddress',
            'datetime'=>'Datetime',
            'user_name'=>'UserName',
            'user_id'=>'UserId',
            'client_id'=>'ClientId',
            'employee_id'=>'EmployeeId',
            'change_type'=>'ChangeType',
            'description'=>'Description',
            'data'=>'Data',
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
     * @return Messerve_Model_Log
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
     * Sets column ipaddress
     *
     * @param string $data
     * @return Messerve_Model_Log
     */
    public function setIpaddress($data)
    {
        $this->_Ipaddress = $data;
        return $this;
    }

    /**
     * Gets column ipaddress
     *
     * @return string
     */
    public function getIpaddress()
    {
        return $this->_Ipaddress;
    }

    /**
     * Sets column datetime. Stored in ISO 8601 format.
     *
     * @param string|Zend_Date $date
     * @return Messerve_Model_Log
     */
    public function setDatetime($data)
    {
        if (! empty($data)) {
            if (! $data instanceof Zend_Date) {
                $zdate = new Zend_Date();
            }

            $data = $zdate->toString($data,'YYYY-MM-ddTHH:mm:ss.S');
        }

        $this->_Datetime = $data;
        return $this;
    }

    /**
     * Gets column datetime
     *
     * @param boolean $returnZendDate
     * @return Zend_Date|null|string Zend_Date representation of this datetime if enabled, or ISO 8601 string if not
     */
    public function getDatetime($returnZendDate = false)
    {
        if ($returnZendDate) {
            if ($this->_Datetime === null) {
                return null;
            }

            return new Zend_Date($this->_Datetime, Zend_Date::ISO_8601);
        }

        return $this->_Datetime;
    }

    /**
     * Sets column user_name
     *
     * @param string $data
     * @return Messerve_Model_Log
     */
    public function setUserName($data)
    {
        $this->_UserName = $data;
        return $this;
    }

    /**
     * Gets column user_name
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->_UserName;
    }

    /**
     * Sets column user_id
     *
     * @param int $data
     * @return Messerve_Model_Log
     */
    public function setUserId($data)
    {
        $this->_UserId = $data;
        return $this;
    }

    /**
     * Gets column user_id
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->_UserId;
    }

    /**
     * Sets column client_id
     *
     * @param int $data
     * @return Messerve_Model_Log
     */
    public function setClientId($data)
    {
        $this->_ClientId = $data;
        return $this;
    }

    /**
     * Gets column client_id
     *
     * @return int
     */
    public function getClientId()
    {
        return $this->_ClientId;
    }

    /**
     * Sets column employee_id
     *
     * @param int $data
     * @return Messerve_Model_Log
     */
    public function setEmployeeId($data)
    {
        $this->_EmployeeId = $data;
        return $this;
    }

    /**
     * Gets column employee_id
     *
     * @return int
     */
    public function getEmployeeId()
    {
        return $this->_EmployeeId;
    }

    /**
     * Sets column change_type
     *
     * @param string $data
     * @return Messerve_Model_Log
     */
    public function setChangeType($data)
    {
        $this->_ChangeType = $data;
        return $this;
    }

    /**
     * Gets column change_type
     *
     * @return string
     */
    public function getChangeType()
    {
        return $this->_ChangeType;
    }

    /**
     * Sets column description
     *
     * @param string $data
     * @return Messerve_Model_Log
     */
    public function setDescription($data)
    {
        $this->_Description = $data;
        return $this;
    }

    /**
     * Gets column description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->_Description;
    }

    /**
     * Sets column data
     *
     * @param string $data
     * @return Messerve_Model_Log
     */
    public function setData($data)
    {
        $this->_Data = $data;
        return $this;
    }

    /**
     * Gets column data
     *
     * @return string
     */
    public function getData()
    {
        return $this->_Data;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_Log
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_Log());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_Log::delete
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
