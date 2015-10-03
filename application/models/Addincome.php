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
class Messerve_Model_Addincome extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Id;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_AttendanceId;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_ThirteenthMonthPay;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Gasoline;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_MaintenanceIncome;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_BikeOwnership;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Incentives;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Paternity;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_MiscIncome;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'attendance_id'=>'AttendanceId',
            'thirteenth_month_pay'=>'ThirteenthMonthPay',
            'gasoline'=>'Gasoline',
            'maintenance_income'=>'MaintenanceIncome',
            'bike_ownership'=>'BikeOwnership',
            'incentives'=>'Incentives',
            'paternity'=>'Paternity',
            'misc_income'=>'MiscIncome',
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
     * @return Messerve_Model_Addincome
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
     * Sets column attendance_id
     *
     * @param int $data
     * @return Messerve_Model_Addincome
     */
    public function setAttendanceId($data)
    {
        $this->_AttendanceId = $data;
        return $this;
    }

    /**
     * Gets column attendance_id
     *
     * @return int
     */
    public function getAttendanceId()
    {
        return $this->_AttendanceId;
    }

    /**
     * Sets column thirteenth_month_pay
     *
     * @param float $data
     * @return Messerve_Model_Addincome
     */
    public function setThirteenthMonthPay($data)
    {
        $this->_ThirteenthMonthPay = $data;
        return $this;
    }

    /**
     * Gets column thirteenth_month_pay
     *
     * @return float
     */
    public function getThirteenthMonthPay()
    {
        return $this->_ThirteenthMonthPay;
    }

    /**
     * Sets column gasoline
     *
     * @param float $data
     * @return Messerve_Model_Addincome
     */
    public function setGasoline($data)
    {
        $this->_Gasoline = $data;
        return $this;
    }

    /**
     * Gets column gasoline
     *
     * @return float
     */
    public function getGasoline()
    {
        return $this->_Gasoline;
    }

    /**
     * Sets column maintenance_income
     *
     * @param float $data
     * @return Messerve_Model_Addincome
     */
    public function setMaintenanceIncome($data)
    {
        $this->_MaintenanceIncome = $data;
        return $this;
    }

    /**
     * Gets column maintenance_income
     *
     * @return float
     */
    public function getMaintenanceIncome()
    {
        return $this->_MaintenanceIncome;
    }

    /**
     * Sets column bike_ownership
     *
     * @param float $data
     * @return Messerve_Model_Addincome
     */
    public function setBikeOwnership($data)
    {
        $this->_BikeOwnership = $data;
        return $this;
    }

    /**
     * Gets column bike_ownership
     *
     * @return float
     */
    public function getBikeOwnership()
    {
        return $this->_BikeOwnership;
    }

    /**
     * Sets column incentives
     *
     * @param float $data
     * @return Messerve_Model_Addincome
     */
    public function setIncentives($data)
    {
        $this->_Incentives = $data;
        return $this;
    }

    /**
     * Gets column incentives
     *
     * @return float
     */
    public function getIncentives()
    {
        return $this->_Incentives;
    }

    /**
     * Sets column paternity
     *
     * @param float $data
     * @return Messerve_Model_Addincome
     */
    public function setPaternity($data)
    {
        $this->_Paternity = $data;
        return $this;
    }

    /**
     * Gets column paternity
     *
     * @return float
     */
    public function getPaternity()
    {
        return $this->_Paternity;
    }

    /**
     * Sets column misc_income
     *
     * @param float $data
     * @return Messerve_Model_Addincome
     */
    public function setMiscIncome($data)
    {
        $this->_MiscIncome = $data;
        return $this;
    }

    /**
     * Gets column misc_income
     *
     * @return float
     */
    public function getMiscIncome()
    {
        return $this->_MiscIncome;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_Addincome
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_Addincome());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_Addincome::delete
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
