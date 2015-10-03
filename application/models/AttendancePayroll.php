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
class Messerve_Model_AttendancePayroll extends Messerve_Model_ModelAbstract
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
    protected $_Employee;

    /**
     * Database var type date
     *
     * @var string
     */
    protected $_Date;

    /**
     * Database var type date
     *
     * @var string
     */
    protected $_PeriodStart;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_AttendanceId;

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
    protected $_ClientRateId;

    /**
     * Database var type enum('Regular','Rest','Special','Legal','Legal unattended')
     *
     * @var string
     */
    protected $_HolidayType;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_RegHours;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_RegPay;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_OtHours;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_OtPay;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_NdHours;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_NdPay;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_NdOtHours;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_NdOtPay;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_EmployeeId;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_GroupId;

    /**
     * Database var type datetime
     *
     * @var string
     */
    protected $_DateProcessed;

    /**
     * Database var type text
     *
     * @var string
     */
    protected $_RateData;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'employee'=>'Employee',
            'date'=>'Date',
            'period_start'=>'PeriodStart',
            'attendance_id'=>'AttendanceId',
            'rate_id'=>'RateId',
            'client_rate_id'=>'ClientRateId',
            'holiday_type'=>'HolidayType',
            'reg_hours'=>'RegHours',
            'reg_pay'=>'RegPay',
            'ot_hours'=>'OtHours',
            'ot_pay'=>'OtPay',
            'nd_hours'=>'NdHours',
            'nd_pay'=>'NdPay',
            'nd_ot_hours'=>'NdOtHours',
            'nd_ot_pay'=>'NdOtPay',
            'employee_id'=>'EmployeeId',
            'group_id'=>'GroupId',
            'date_processed'=>'DateProcessed',
            'rate_data'=>'RateData',
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
     * @return Messerve_Model_AttendancePayroll
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
     * Sets column employee
     *
     * @param string $data
     * @return Messerve_Model_AttendancePayroll
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
     * Sets column date
     *
     * @param string $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setDate($data)
    {
        $this->_Date = $data;
        return $this;
    }

    /**
     * Gets column date
     *
     * @return string
     */
    public function getDate()
    {
        return $this->_Date;
    }

    /**
     * Sets column period_start
     *
     * @param string $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setPeriodStart($data)
    {
        $this->_PeriodStart = $data;
        return $this;
    }

    /**
     * Gets column period_start
     *
     * @return string
     */
    public function getPeriodStart()
    {
        return $this->_PeriodStart;
    }

    /**
     * Sets column attendance_id
     *
     * @param int $data
     * @return Messerve_Model_AttendancePayroll
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
     * Sets column rate_id
     *
     * @param int $data
     * @return Messerve_Model_AttendancePayroll
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
     * Sets column client_rate_id
     *
     * @param int $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setClientRateId($data)
    {
        $this->_ClientRateId = $data;
        return $this;
    }

    /**
     * Gets column client_rate_id
     *
     * @return int
     */
    public function getClientRateId()
    {
        return $this->_ClientRateId;
    }

    /**
     * Sets column holiday_type
     *
     * @param string $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setHolidayType($data)
    {
        $this->_HolidayType = $data;
        return $this;
    }

    /**
     * Gets column holiday_type
     *
     * @return string
     */
    public function getHolidayType()
    {
        return $this->_HolidayType;
    }

    /**
     * Sets column reg_hours
     *
     * @param float $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setRegHours($data)
    {
        $this->_RegHours = $data;
        return $this;
    }

    /**
     * Gets column reg_hours
     *
     * @return float
     */
    public function getRegHours()
    {
        return $this->_RegHours;
    }

    /**
     * Sets column reg_pay
     *
     * @param float $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setRegPay($data)
    {
        $this->_RegPay = $data;
        return $this;
    }

    /**
     * Gets column reg_pay
     *
     * @return float
     */
    public function getRegPay()
    {
        return $this->_RegPay;
    }

    /**
     * Sets column ot_hours
     *
     * @param float $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setOtHours($data)
    {
        $this->_OtHours = $data;
        return $this;
    }

    /**
     * Gets column ot_hours
     *
     * @return float
     */
    public function getOtHours()
    {
        return $this->_OtHours;
    }

    /**
     * Sets column ot_pay
     *
     * @param float $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setOtPay($data)
    {
        $this->_OtPay = $data;
        return $this;
    }

    /**
     * Gets column ot_pay
     *
     * @return float
     */
    public function getOtPay()
    {
        return $this->_OtPay;
    }

    /**
     * Sets column nd_hours
     *
     * @param float $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setNdHours($data)
    {
        $this->_NdHours = $data;
        return $this;
    }

    /**
     * Gets column nd_hours
     *
     * @return float
     */
    public function getNdHours()
    {
        return $this->_NdHours;
    }

    /**
     * Sets column nd_pay
     *
     * @param float $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setNdPay($data)
    {
        $this->_NdPay = $data;
        return $this;
    }

    /**
     * Gets column nd_pay
     *
     * @return float
     */
    public function getNdPay()
    {
        return $this->_NdPay;
    }

    /**
     * Sets column nd_ot_hours
     *
     * @param float $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setNdOtHours($data)
    {
        $this->_NdOtHours = $data;
        return $this;
    }

    /**
     * Gets column nd_ot_hours
     *
     * @return float
     */
    public function getNdOtHours()
    {
        return $this->_NdOtHours;
    }

    /**
     * Sets column nd_ot_pay
     *
     * @param float $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setNdOtPay($data)
    {
        $this->_NdOtPay = $data;
        return $this;
    }

    /**
     * Gets column nd_ot_pay
     *
     * @return float
     */
    public function getNdOtPay()
    {
        return $this->_NdOtPay;
    }

    /**
     * Sets column employee_id
     *
     * @param int $data
     * @return Messerve_Model_AttendancePayroll
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
     * Sets column group_id
     *
     * @param int $data
     * @return Messerve_Model_AttendancePayroll
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
     * Sets column date_processed. Stored in ISO 8601 format.
     *
     * @param string|Zend_Date $date
     * @return Messerve_Model_AttendancePayroll
     */
    public function setDateProcessed($data)
    {
        if (! empty($data)) {
            if (! $data instanceof Zend_Date) {
                $zdate = new Zend_Date();
            }

            $data = $zdate->toString($data,'YYYY-MM-ddTHH:mm:ss.S');
        }

        $this->_DateProcessed = $data;
        return $this;
    }

    /**
     * Gets column date_processed
     *
     * @param boolean $returnZendDate
     * @return Zend_Date|null|string Zend_Date representation of this datetime if enabled, or ISO 8601 string if not
     */
    public function getDateProcessed($returnZendDate = false)
    {
        if ($returnZendDate) {
            if ($this->_DateProcessed === null) {
                return null;
            }

            return new Zend_Date($this->_DateProcessed, Zend_Date::ISO_8601);
        }

        return $this->_DateProcessed;
    }

    /**
     * Sets column rate_data
     *
     * @param string $data
     * @return Messerve_Model_AttendancePayroll
     */
    public function setRateData($data)
    {
        $this->_RateData = $data;
        return $this;
    }

    /**
     * Gets column rate_data
     *
     * @return string
     */
    public function getRateData()
    {
        return $this->_RateData;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_AttendancePayroll
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_AttendancePayroll());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_AttendancePayroll::delete
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
