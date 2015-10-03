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
class Messerve_Model_DeductionAttendance extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_DeductionScheduleId;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_AttendanceId;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_Amount;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'deduction_schedule_id'=>'DeductionScheduleId',
            'attendance_id'=>'AttendanceId',
            'amount'=>'Amount',
        ));

        $this->setParentList(array(
        ));

        $this->setDependentList(array(
        ));
    }

    /**
     * Sets column deduction_schedule_id
     *
     * @param int $data
     * @return Messerve_Model_DeductionAttendance
     */
    public function setDeductionScheduleId($data)
    {
        $this->_DeductionScheduleId = $data;
        return $this;
    }

    /**
     * Gets column deduction_schedule_id
     *
     * @return int
     */
    public function getDeductionScheduleId()
    {
        return $this->_DeductionScheduleId;
    }

    /**
     * Sets column attendance_id
     *
     * @param int $data
     * @return Messerve_Model_DeductionAttendance
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
     * Sets column amount
     *
     * @param float $data
     * @return Messerve_Model_DeductionAttendance
     */
    public function setAmount($data)
    {
        $this->_Amount = $data;
        return $this;
    }

    /**
     * Gets column amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->_Amount;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_DeductionAttendance
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_DeductionAttendance());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_DeductionAttendance::delete
     * @return int|boolean Number of rows deleted or boolean if doing soft delete
     */
    public function deleteRowByPrimaryKey()
    {
        $primary_key = array();
        if (! $this->getDeductionScheduleId()) {
            throw new Exception('Primary Key DeductionScheduleId does not contain a value');
        } else {
            $primary_key['deduction_schedule_id'] = $this->getDeductionScheduleId();
        }

        if (! $this->getAttendanceId()) {
            throw new Exception('Primary Key AttendanceId does not contain a value');
        } else {
            $primary_key['attendance_id'] = $this->getAttendanceId();
        }

        return $this->getMapper()->getDbTable()->delete('deduction_schedule_id = '
                    . $this->getMapper()->getDbTable()->getAdapter()->quote($primary_key['deduction_schedule_id'])
                    . ' AND attendance_id = '
                    . $this->getMapper()->getDbTable()->getAdapter()->quote($primary_key['attendance_id']));
    }
}
