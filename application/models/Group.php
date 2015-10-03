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
class Messerve_Model_Group extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Id;

    /**
     * Database var type varchar(128)
     *
     * @var string
     */
    protected $_Name;

    /**
     * Database var type varchar(128)
     *
     * @var string
     */
    protected $_BillingName;

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
    protected $_RateId;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_RateClientId;

    /**
     * Database var type text
     *
     * @var string
     */
    protected $_Calendars;

    /**
     * Client name and group name
     * Database var type varchar(128)
     *
     * @var string
     */
    protected $_Search;

    /**
     * Database var type varchar(32)
     *
     * @var string
     */
    protected $_Code;

    /**
     * Database var type enum('yes','no')
     *
     * @var string
     */
    protected $_RoundOff10;

    /**
     * Database var type varchar(16)
     *
     * @var string
     */
    protected $_Tin;

    /**
     * Database var type text
     *
     * @var string
     */
    protected $_Address;

    /**
     * Database var type decimal(5,2)
     *
     * @var float
     */
    protected $_Fuelperhour;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'name'=>'Name',
            'billing_name'=>'BillingName',
            'client_id'=>'ClientId',
            'rate_id'=>'RateId',
            'rate_client_id'=>'RateClientId',
            'calendars'=>'Calendars',
            'search'=>'Search',
            'code'=>'Code',
            'round_off_10'=>'RoundOff10',
            'tin'=>'Tin',
            'address'=>'Address',
            'fuelperhour'=>'Fuelperhour',
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
     * @return Messerve_Model_Group
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
     * Sets column name
     *
     * @param string $data
     * @return Messerve_Model_Group
     */
    public function setName($data)
    {
        $this->_Name = $data;
        return $this;
    }

    /**
     * Gets column name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_Name;
    }

    /**
     * Sets column billing_name
     *
     * @param string $data
     * @return Messerve_Model_Group
     */
    public function setBillingName($data)
    {
        $this->_BillingName = $data;
        return $this;
    }

    /**
     * Gets column billing_name
     *
     * @return string
     */
    public function getBillingName()
    {
        return $this->_BillingName;
    }

    /**
     * Sets column client_id
     *
     * @param int $data
     * @return Messerve_Model_Group
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
     * Sets column rate_id
     *
     * @param int $data
     * @return Messerve_Model_Group
     */
    public function setRateId($data)
    {
        $this->_RateId = $data;
        return $this;
    }

    /**
     * Gets column rate_id
     *
     * @return int
     */
    public function getRateId()
    {
        return $this->_RateId;
    }

    /**
     * Sets column rate_client_id
     *
     * @param int $data
     * @return Messerve_Model_Group
     */
    public function setRateClientId($data)
    {
        $this->_RateClientId = $data;
        return $this;
    }

    /**
     * Gets column rate_client_id
     *
     * @return int
     */
    public function getRateClientId()
    {
        return $this->_RateClientId;
    }

    /**
     * Sets column calendars
     *
     * @param string $data
     * @return Messerve_Model_Group
     */
    public function setCalendars($data)
    {
        $this->_Calendars = $data;
        return $this;
    }

    /**
     * Gets column calendars
     *
     * @return string
     */
    public function getCalendars()
    {
        return $this->_Calendars;
    }

    /**
     * Sets column search
     *
     * @param string $data
     * @return Messerve_Model_Group
     */
    public function setSearch($data)
    {
        $this->_Search = $data;
        return $this;
    }

    /**
     * Gets column search
     *
     * @return string
     */
    public function getSearch()
    {
        return $this->_Search;
    }

    /**
     * Sets column code
     *
     * @param string $data
     * @return Messerve_Model_Group
     */
    public function setCode($data)
    {
        $this->_Code = $data;
        return $this;
    }

    /**
     * Gets column code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_Code;
    }

    /**
     * Sets column round_off_10
     *
     * @param string $data
     * @return Messerve_Model_Group
     */
    public function setRoundOff10($data)
    {
        $this->_RoundOff10 = $data;
        return $this;
    }

    /**
     * Gets column round_off_10
     *
     * @return string
     */
    public function getRoundOff10()
    {
        return $this->_RoundOff10;
    }

    /**
     * Sets column tin
     *
     * @param string $data
     * @return Messerve_Model_Group
     */
    public function setTin($data)
    {
        $this->_Tin = $data;
        return $this;
    }

    /**
     * Gets column tin
     *
     * @return string
     */
    public function getTin()
    {
        return $this->_Tin;
    }

    /**
     * Sets column address
     *
     * @param string $data
     * @return Messerve_Model_Group
     */
    public function setAddress($data)
    {
        $this->_Address = $data;
        return $this;
    }

    /**
     * Gets column address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->_Address;
    }

    /**
     * Sets column fuelperhour
     *
     * @param float $data
     * @return Messerve_Model_Group
     */
    public function setFuelperhour($data)
    {
        $this->_Fuelperhour = $data;
        return $this;
    }

    /**
     * Gets column fuelperhour
     *
     * @return float
     */
    public function getFuelperhour()
    {
        return $this->_Fuelperhour;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_Group
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_Group());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_Group::delete
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
