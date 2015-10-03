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
class Messerve_Model_BopAttendance extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_BopId;

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
    protected $_MotorcycleDeduction;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_InsuranceDeduction;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_MaintenanceAddition;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_PreviousMonthHours;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'bop_id'=>'BopId',
            'attendance_id'=>'AttendanceId',
            'motorcycle_deduction'=>'MotorcycleDeduction',
            'insurance_deduction'=>'InsuranceDeduction',
            'maintenance_addition'=>'MaintenanceAddition',
            'previous_month_hours'=>'PreviousMonthHours',
        ));

        $this->setParentList(array(
        ));

        $this->setDependentList(array(
        ));
    }

    /**
     * Sets column bop_id
     *
     * @param int $data
     * @return Messerve_Model_BopAttendance
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
     * Sets column attendance_id
     *
     * @param int $data
     * @return Messerve_Model_BopAttendance
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
     * Sets column motorcycle_deduction
     *
     * @param float $data
     * @return Messerve_Model_BopAttendance
     */
    public function setMotorcycleDeduction($data)
    {
        $this->_MotorcycleDeduction = $data;
        return $this;
    }

    /**
     * Gets column motorcycle_deduction
     *
     * @return float
     */
    public function getMotorcycleDeduction()
    {
        return $this->_MotorcycleDeduction;
    }

    /**
     * Sets column insurance_deduction
     *
     * @param float $data
     * @return Messerve_Model_BopAttendance
     */
    public function setInsuranceDeduction($data)
    {
        $this->_InsuranceDeduction = $data;
        return $this;
    }

    /**
     * Gets column insurance_deduction
     *
     * @return float
     */
    public function getInsuranceDeduction()
    {
        return $this->_InsuranceDeduction;
    }

    /**
     * Sets column maintenance_addition
     *
     * @param float $data
     * @return Messerve_Model_BopAttendance
     */
    public function setMaintenanceAddition($data)
    {
        $this->_MaintenanceAddition = $data;
        return $this;
    }

    /**
     * Gets column maintenance_addition
     *
     * @return float
     */
    public function getMaintenanceAddition()
    {
        return $this->_MaintenanceAddition;
    }

    /**
     * Sets column previous_month_hours
     *
     * @param float $data
     * @return Messerve_Model_BopAttendance
     */
    public function setPreviousMonthHours($data)
    {
        $this->_PreviousMonthHours = $data;
        return $this;
    }

    /**
     * Gets column previous_month_hours
     *
     * @return float
     */
    public function getPreviousMonthHours()
    {
        return $this->_PreviousMonthHours;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_BopAttendance
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_BopAttendance());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_BopAttendance::delete
     * @return int|boolean Number of rows deleted or boolean if doing soft delete
     */
    public function deleteRowByPrimaryKey()
    {
        $primary_key = array();
        if (! $this->getBopId()) {
            throw new Exception('Primary Key BopId does not contain a value');
        } else {
            $primary_key['bop_id'] = $this->getBopId();
        }

        if (! $this->getAttendanceId()) {
            throw new Exception('Primary Key AttendanceId does not contain a value');
        } else {
            $primary_key['attendance_id'] = $this->getAttendanceId();
        }

        return $this->getMapper()->getDbTable()->delete('bop_id = '
                    . $this->getMapper()->getDbTable()->getAdapter()->quote($primary_key['bop_id'])
                    . ' AND attendance_id = '
                    . $this->getMapper()->getDbTable()->getAdapter()->quote($primary_key['attendance_id']));
    }
}
