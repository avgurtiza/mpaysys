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
class Messerve_Model_Deductions extends Messerve_Model_ModelAbstract
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
     * Database var type float
     *
     * @var float
     */
    protected $_SssLoan;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_HdmfLoan;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_FuelOverage;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_MaintenanceDeduct;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Accident;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Uniform;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_LostCard;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Food;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Communication;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'attendance_id'=>'AttendanceId',
            'sss_loan'=>'SssLoan',
            'hdmf_loan'=>'HdmfLoan',
            'fuel_overage'=>'FuelOverage',
            'maintenance_deduct'=>'MaintenanceDeduct',
            'accident'=>'Accident',
            'uniform'=>'Uniform',
            'lost_card'=>'LostCard',
            'food'=>'Food',
            'communication'=>'Communication',
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
     * @return Messerve_Model_Deductions
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
     * @return Messerve_Model_Deductions
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
     * Sets column sss_loan
     *
     * @param float $data
     * @return Messerve_Model_Deductions
     */
    public function setSssLoan($data)
    {
        $this->_SssLoan = $data;
        return $this;
    }

    /**
     * Gets column sss_loan
     *
     * @return float
     */
    public function getSssLoan()
    {
        return $this->_SssLoan;
    }

    /**
     * Sets column hdmf_loan
     *
     * @param float $data
     * @return Messerve_Model_Deductions
     */
    public function setHdmfLoan($data)
    {
        $this->_HdmfLoan = $data;
        return $this;
    }

    /**
     * Gets column hdmf_loan
     *
     * @return float
     */
    public function getHdmfLoan()
    {
        return $this->_HdmfLoan;
    }

    /**
     * Sets column fuel_overage
     *
     * @param float $data
     * @return Messerve_Model_Deductions
     */
    public function setFuelOverage($data)
    {
        $this->_FuelOverage = $data;
        return $this;
    }

    /**
     * Gets column fuel_overage
     *
     * @return float
     */
    public function getFuelOverage()
    {
        return $this->_FuelOverage;
    }

    /**
     * Sets column maintenance_deduct
     *
     * @param float $data
     * @return Messerve_Model_Deductions
     */
    public function setMaintenanceDeduct($data)
    {
        $this->_MaintenanceDeduct = $data;
        return $this;
    }

    /**
     * Gets column maintenance_deduct
     *
     * @return float
     */
    public function getMaintenanceDeduct()
    {
        return $this->_MaintenanceDeduct;
    }

    /**
     * Sets column accident
     *
     * @param float $data
     * @return Messerve_Model_Deductions
     */
    public function setAccident($data)
    {
        $this->_Accident = $data;
        return $this;
    }

    /**
     * Gets column accident
     *
     * @return float
     */
    public function getAccident()
    {
        return $this->_Accident;
    }

    /**
     * Sets column uniform
     *
     * @param float $data
     * @return Messerve_Model_Deductions
     */
    public function setUniform($data)
    {
        $this->_Uniform = $data;
        return $this;
    }

    /**
     * Gets column uniform
     *
     * @return float
     */
    public function getUniform()
    {
        return $this->_Uniform;
    }

    /**
     * Sets column lost_card
     *
     * @param float $data
     * @return Messerve_Model_Deductions
     */
    public function setLostCard($data)
    {
        $this->_LostCard = $data;
        return $this;
    }

    /**
     * Gets column lost_card
     *
     * @return float
     */
    public function getLostCard()
    {
        return $this->_LostCard;
    }

    /**
     * Sets column food
     *
     * @param float $data
     * @return Messerve_Model_Deductions
     */
    public function setFood($data)
    {
        $this->_Food = $data;
        return $this;
    }

    /**
     * Gets column food
     *
     * @return float
     */
    public function getFood()
    {
        return $this->_Food;
    }

    /**
     * Sets column communication
     *
     * @param float $data
     * @return Messerve_Model_Deductions
     */
    public function setCommunication($data)
    {
        $this->_Communication = $data;
        return $this;
    }

    /**
     * Gets column communication
     *
     * @return float
     */
    public function getCommunication()
    {
        return $this->_Communication;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_Deductions
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_Deductions());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_Deductions::delete
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
