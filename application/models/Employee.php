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
class Messerve_Model_Employee extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Id;

    /**
     * Database var type enum('','rider','serviceman')
     *
     * @var string
     */
    protected $_Type;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_EmployeeNumber;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Firstname;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Lastname;

    /**
     * Database var type varchar(16)
     *
     * @var string
     */
    protected $_Middleinitial;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_AccountNumber;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Tin;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Sss;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Hdmf;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Philhealth;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_GroupId;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_RateId;

    /**
     * Database var type date
     *
     * @var string
     */
    protected $_Dateemployed;

    /**
     * Database var type date
     *
     * @var string
     */
    protected $_BikeRehabEnd;

    /**
     * Database var type date
     *
     * @var string
     */
    protected $_BikeInsuranceRegEnd;

    /**
     * Name search
     * Database var type varchar(256)
     *
     * @var string
     */
    protected $_Search;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_BopId;

    /**
     * Database var type date
     *
     * @var string
     */
    protected $_BopStart;
    protected $_BopCurrentRiderStart;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_BopStartingbalance;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_BopCurrentbalance;

    /**
     * Database var type bigint(32)
     *
     * @var int
     */
    protected $_Gascard;

    /**
     * Database var type bigint(32)
     *
     * @var int
     */
    protected $_Gascard2;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'type'=>'Type',
            'employee_number'=>'EmployeeNumber',
            'firstname'=>'Firstname',
            'lastname'=>'Lastname',
            'middleinitial'=>'Middleinitial',
            'account_number'=>'AccountNumber',
            'tin'=>'Tin',
            'sss'=>'Sss',
            'hdmf'=>'Hdmf',
            'philhealth'=>'Philhealth',
            'group_id'=>'GroupId',
            'rate_id'=>'RateId',
            'dateemployed'=>'Dateemployed',
            'bike_rehab_end'=>'BikeRehabEnd',
            'bike_insurance_reg_end'=>'BikeInsuranceRegEnd',
            'search'=>'Search',
            'bop_id'=>'BopId',
            'bop_start'=>'BopStart',
            'bop_startingbalance'=>'BopStartingbalance',
            'bop_currentbalance'=>'BopCurrentbalance',
            'gascard'=>'Gascard',
            'gascard2'=>'Gascard2',
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
     * @return Messerve_Model_Employee
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
     * Sets column type
     *
     * @param string $data
     * @return Messerve_Model_Employee
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
     * Sets column employee_number
     *
     * @param int $data
     * @return Messerve_Model_Employee
     */
    public function setEmployeeNumber($data)
    {
        $this->_EmployeeNumber = $data;
        return $this;
    }

    /**
     * Gets column employee_number
     *
     * @return int
     */
    public function getEmployeeNumber()
    {
        return $this->_EmployeeNumber;
    }

    /**
     * Sets column firstname
     *
     * @param string $data
     * @return Messerve_Model_Employee
     */
    public function setFirstname($data)
    {
        $this->_Firstname = $data;
        return $this;
    }

    /**
     * Gets column firstname
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->_Firstname;
    }

    /**
     * Sets column lastname
     *
     * @param string $data
     * @return Messerve_Model_Employee
     */
    public function setLastname($data)
    {
        $this->_Lastname = $data;
        return $this;
    }

    /**
     * Gets column lastname
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->_Lastname;
    }

    /**
     * Sets column middleinitial
     *
     * @param string $data
     * @return Messerve_Model_Employee
     */
    public function setMiddleinitial($data)
    {
        $this->_Middleinitial = $data;
        return $this;
    }

    /**
     * Gets column middleinitial
     *
     * @return string
     */
    public function getMiddleinitial()
    {
        return $this->_Middleinitial;
    }

    /**
     * Sets column account_number
     *
     * @param string $data
     * @return Messerve_Model_Employee
     */
    public function setAccountNumber($data)
    {
        $this->_AccountNumber = $data;
        return $this;
    }

    /**
     * Gets column account_number
     *
     * @return string
     */
    public function getAccountNumber()
    {
        return $this->_AccountNumber;
    }

    /**
     * Sets column tin
     *
     * @param string $data
     * @return Messerve_Model_Employee
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
     * Sets column sss
     *
     * @param string $data
     * @return Messerve_Model_Employee
     */
    public function setSss($data)
    {
        $this->_Sss = $data;
        return $this;
    }

    /**
     * Gets column sss
     *
     * @return string
     */
    public function getSss()
    {
        return $this->_Sss;
    }

    /**
     * Sets column hdmf
     *
     * @param string $data
     * @return Messerve_Model_Employee
     */
    public function setHdmf($data)
    {
        $this->_Hdmf = $data;
        return $this;
    }

    /**
     * Gets column hdmf
     *
     * @return string
     */
    public function getHdmf()
    {
        return $this->_Hdmf;
    }

    /**
     * Sets column philhealth
     *
     * @param string $data
     * @return Messerve_Model_Employee
     */
    public function setPhilhealth($data)
    {
        $this->_Philhealth = $data;
        return $this;
    }

    /**
     * Gets column philhealth
     *
     * @return string
     */
    public function getPhilhealth()
    {
        return $this->_Philhealth;
    }

    /**
     * Sets column group_id
     *
     * @param int $data
     * @return Messerve_Model_Employee
     */
    public function setGroupId($data)
    {
        $this->_GroupId = $data;
        return $this;
    }

    /**
     * Gets column group_id
     *
     * @return int
     */
    public function getGroupId()
    {
        return $this->_GroupId;
    }

    /**
     * Sets column rate_id
     *
     * @param int $data
     * @return Messerve_Model_Employee
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
     * Sets column dateemployed
     *
     * @param string $data
     * @return Messerve_Model_Employee
     */
    public function setDateemployed($data)
    {
        $this->_Dateemployed = $data;
        return $this;
    }

    /**
     * Gets column dateemployed
     *
     * @return string
     */
    public function getDateemployed()
    {
        return $this->_Dateemployed;
    }

    /**
     * Sets column bike_rehab_end
     *
     * @param string $data
     * @return Messerve_Model_Employee
     */
    public function setBikeRehabEnd($data)
    {
        $this->_BikeRehabEnd = $data;
        return $this;
    }

    /**
     * Gets column bike_rehab_end
     *
     * @return string
     */
    public function getBikeRehabEnd()
    {
        return $this->_BikeRehabEnd;
    }

    /**
     * Sets column bike_insurance_reg_end
     *
     * @param string $data
     * @return Messerve_Model_Employee
     */
    public function setBikeInsuranceRegEnd($data)
    {
        $this->_BikeInsuranceRegEnd = $data;
        return $this;
    }

    /**
     * Gets column bike_insurance_reg_end
     *
     * @return string
     */
    public function getBikeInsuranceRegEnd()
    {
        return $this->_BikeInsuranceRegEnd;
    }

    /**
     * Sets column search
     *
     * @param string $data
     * @return Messerve_Model_Employee
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
     * Sets column bop_id
     *
     * @param int $data
     * @return Messerve_Model_Employee
     */
    public function setBopId($data)
    {
        $this->_BopId = $data;
        return $this;
    }

    /**
     * Gets column bop_id
     *
     * @return int
     */
    public function getBopId()
    {
        return $this->_BopId;
    }

    /**
     * Sets column bop_start
     *
     * @param string $data
     * @return Messerve_Model_Employee
     */
    public function setBopStart($data)
    {
        $this->_BopStart = $data;
        return $this;
    }

    /**
     * Gets column bop_start
     *
     * @return string
     */
    public function getBopStart()
    {
        return $this->_BopStart;
    }

    /**
     * Sets column bop_start
     *
     * @param string $data
     * @return Messerve_Model_Employee
     */
    public function setBopCurrentRiderStart($data)
    {
        $this->_BopCurrentRiderStart = $data;
        return $this;
    }

    /**
     * Gets column bop_start
     *
     * @return string
     */
    public function getBopCurrentRiderStart()
    {
        return $this->_BopCurrentRiderStart;
    }

    /**
     * Sets column bop_startingbalance
     *
     * @param float $data
     * @return Messerve_Model_Employee
     */
    public function setBopStartingbalance($data)
    {
        $this->_BopStartingbalance = $data;
        return $this;
    }

    /**
     * Gets column bop_startingbalance
     *
     * @return float
     */
    public function getBopStartingbalance()
    {
        return $this->_BopStartingbalance;
    }

    /**
     * Sets column bop_currentbalance
     *
     * @param float $data
     * @return Messerve_Model_Employee
     */
    public function setBopCurrentbalance($data)
    {
        $this->_BopCurrentbalance = $data;
        return $this;
    }

    /**
     * Gets column bop_currentbalance
     *
     * @return float
     */
    public function getBopCurrentbalance()
    {
        return $this->_BopCurrentbalance;
    }

    /**
     * Sets column gascard
     *
     * @param int $data
     * @return Messerve_Model_Employee
     */
    public function setGascard($data)
    {
        $this->_Gascard = $data;
        return $this;
    }

    /**
     * Gets column gascard
     *
     * @return int
     */
    public function getGascard()
    {
        return $this->_Gascard;
    }

    /**
     * Sets column gascard2
     *
     * @param int $data
     * @return Messerve_Model_Employee
     */
    public function setGascard2($data)
    {
        $this->_Gascard2 = $data;
        return $this;
    }

    /**
     * Gets column gascard2
     *
     * @return int
     */
    public function getGascard2()
    {
        return $this->_Gascard2;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_Employee
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_Employee());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_Employee::delete
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
