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
class Messerve_Model_Vehicle extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Id;

    /**
     * Database var type year(4)
     *
     * @var year
     */
    protected $_Year;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Makemodel;

    /**
     * Database var type varchar(16)
     *
     * @var string
     */
    protected $_PlateNo;

    /**
     * Database var type varchar(16)
     *
     * @var string
     */
    protected $_EngineNo;

    /**
     * Database var type date
     *
     * @var string
     */
    protected $_DatePurchased;

    /**
     * Database var type date
     *
     * @var string
     */
    protected $_DateDecommissioned;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_EmployeeId;

    /**
     * cached data
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Employee;

    /**
     * cached data
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Group;

    /**
     * cached data
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Client;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'year'=>'Year',
            'makemodel'=>'Makemodel',
            'plate_no'=>'PlateNo',
            'engine_no'=>'EngineNo',
            'date_purchased'=>'DatePurchased',
            'date_decommissioned'=>'DateDecommissioned',
            'employee_id'=>'EmployeeId',
            'employee'=>'Employee',
            'group'=>'Group',
            'client'=>'Client',
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
     * @return Messerve_Model_Vehicle
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
     * Sets column year
     *
     * @param year $data
     * @return Messerve_Model_Vehicle
     */
    public function setYear($data)
    {
        $this->_Year = $data;
        return $this;
    }

    /**
     * Gets column year
     *
     * @return year
     */
    public function getYear()
    {
        return $this->_Year;
    }

    /**
     * Sets column makemodel
     *
     * @param string $data
     * @return Messerve_Model_Vehicle
     */
    public function setMakemodel($data)
    {
        $this->_Makemodel = $data;
        return $this;
    }

    /**
     * Gets column makemodel
     *
     * @return string
     */
    public function getMakemodel()
    {
        return $this->_Makemodel;
    }

    /**
     * Sets column plate_no
     *
     * @param string $data
     * @return Messerve_Model_Vehicle
     */
    public function setPlateNo($data)
    {
        $this->_PlateNo = $data;
        return $this;
    }

    /**
     * Gets column plate_no
     *
     * @return string
     */
    public function getPlateNo()
    {
        return $this->_PlateNo;
    }

    /**
     * Sets column engine_no
     *
     * @param string $data
     * @return Messerve_Model_Vehicle
     */
    public function setEngineNo($data)
    {
        $this->_EngineNo = $data;
        return $this;
    }

    /**
     * Gets column engine_no
     *
     * @return string
     */
    public function getEngineNo()
    {
        return $this->_EngineNo;
    }

    /**
     * Sets column date_purchased
     *
     * @param string $data
     * @return Messerve_Model_Vehicle
     */
    public function setDatePurchased($data)
    {
        $this->_DatePurchased = $data;
        return $this;
    }

    /**
     * Gets column date_purchased
     *
     * @return string
     */
    public function getDatePurchased()
    {
        return $this->_DatePurchased;
    }

    /**
     * Sets column date_decommissioned
     *
     * @param string $data
     * @return Messerve_Model_Vehicle
     */
    public function setDateDecommissioned($data)
    {
        $this->_DateDecommissioned = $data;
        return $this;
    }

    /**
     * Gets column date_decommissioned
     *
     * @return string
     */
    public function getDateDecommissioned()
    {
        return $this->_DateDecommissioned;
    }

    /**
     * Sets column employee_id
     *
     * @param int $data
     * @return Messerve_Model_Vehicle
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
     * Sets column employee
     *
     * @param string $data
     * @return Messerve_Model_Vehicle
     */
    public function setEmployee($data)
    {
        $this->_Employee = $data;
        return $this;
    }

    /**
     * Gets column employee
     *
     * @return string
     */
    public function getEmployee()
    {
        return $this->_Employee;
    }

    /**
     * Sets column group
     *
     * @param string $data
     * @return Messerve_Model_Vehicle
     */
    public function setGroup($data)
    {
        $this->_Group = $data;
        return $this;
    }

    /**
     * Gets column group
     *
     * @return string
     */
    public function getGroup()
    {
        return $this->_Group;
    }

    /**
     * Sets column client
     *
     * @param string $data
     * @return Messerve_Model_Vehicle
     */
    public function setClient($data)
    {
        $this->_Client = $data;
        return $this;
    }

    /**
     * Gets column client
     *
     * @return string
     */
    public function getClient()
    {
        return $this->_Client;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_Vehicle
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_Vehicle());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_Vehicle::delete
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
