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
class Messerve_Model_Attendance extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Id;

    /**
     * Database var type enum('regular','rest','special')
     *
     * @var string
     */
    protected $_Type;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Start1;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_End1;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Start2;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_End2;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Start3;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_End3;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_Today;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_TodayNd;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_TodayOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_TodayNdOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_Tomorrow;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_TomorrowNd;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_TomorrowOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_TomorrowNdOt;

    /**
     * Database var type enum('yes','no')
     *
     * @var string
     */
    protected $_OtApproved;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_OtApprovedHours;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_OtActualHours;

    /**
     * Database var type enum('yes','no')
     *
     * @var string
     */
    protected $_ExtendedShift;

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
    protected $_EmployeeNumber;

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
    protected $_DatetimeStart;

    /**
     * Database var type datetime
     *
     * @var string
     */
    protected $_DatetimeEnd;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_TotalHours;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_Reg;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_RegNd;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_RegOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_RegNdOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_Sun;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_SunNd;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_SunOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_SunNdOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_Spec;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_SpecNd;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_SpecOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_SpecNdOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_Legal;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_LegalNd;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_LegalOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_LegalNdOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_LegalUnattend;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_Rest;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_RestNd;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_RestOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_RestNdOt;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_FuelOverage;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_FuelHours;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_FuelAlloted;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_FuelConsumed;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_FuelCost;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_TodayRateId;

    /**
     * Database var type text
     *
     * @var string
     */
    protected $_TodayRateData;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_TomorrowRateId;

    /**
     * Database var type text
     *
     * @var string
     */
    protected $_TomorrowRateData;
    protected $_ApprovedExtendedShift;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'type'=>'Type',
            'start_1'=>'Start1',
            'end_1'=>'End1',
            'start_2'=>'Start2',
            'end_2'=>'End2',
            'start_3'=>'Start3',
            'end_3'=>'End3',
            'today'=>'Today',
            'today_nd'=>'TodayNd',
            'today_ot'=>'TodayOt',
            'today_nd_ot'=>'TodayNdOt',
            'tomorrow'=>'Tomorrow',
            'tomorrow_nd'=>'TomorrowNd',
            'tomorrow_ot'=>'TomorrowOt',
            'tomorrow_nd_ot'=>'TomorrowNdOt',
            'ot_approved'=>'OtApproved',
            'ot_approved_hours'=>'OtApprovedHours',
            'ot_actual_hours'=>'OtActualHours',
            'extended_shift'=>'ExtendedShift',
            'employee_id'=>'EmployeeId',
            'employee_number'=>'EmployeeNumber',
            'group_id'=>'GroupId',
            'datetime_start'=>'DatetimeStart',
            'datetime_end'=>'DatetimeEnd',
            'total_hours'=>'TotalHours',
            'reg'=>'Reg',
            'reg_nd'=>'RegNd',
            'reg_ot'=>'RegOt',
            'reg_nd_ot'=>'RegNdOt',
            'sun'=>'Sun',
            'sun_nd'=>'SunNd',
            'sun_ot'=>'SunOt',
            'sun_nd_ot'=>'SunNdOt',
            'spec'=>'Spec',
            'spec_nd'=>'SpecNd',
            'spec_ot'=>'SpecOt',
            'spec_nd_ot'=>'SpecNdOt',
            'legal'=>'Legal',
            'legal_nd'=>'LegalNd',
            'legal_ot'=>'LegalOt',
            'legal_nd_ot'=>'LegalNdOt',
            'legal_unattend'=>'LegalUnattend',
            'rest'=>'Rest',
            'rest_nd'=>'RestNd',
            'rest_ot'=>'RestOt',
            'rest_nd_ot'=>'RestNdOt',
            'fuel_overage'=>'FuelOverage',
            'fuel_hours'=>'FuelHours',
            'fuel_alloted'=>'FuelAlloted',
            'fuel_consumed'=>'FuelConsumed',
            'fuel_cost'=>'FuelCost',
            'today_rate_id'=>'TodayRateId',
            'today_rate_data'=>'TodayRateData',
            'tomorrow_rate_id'=>'TomorrowRateId',
            'tomorrow_rate_data'=>'TomorrowRateData',
            'approved_extended_shift'=>'ApprovedExtendedShift',

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
     * @return Messerve_Model_Attendance
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
     * @return Messerve_Model_Attendance
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
     * Sets column start_1
     *
     * @param int $data
     * @return Messerve_Model_Attendance
     */
    public function setStart1($data)
    {
        $this->_Start1 = $data;
        return $this;
    }

    /**
     * Gets column start_1
     *
     * @return int
     */
    public function getStart1()
    {
        return $this->_Start1;
    }

    /**
     * Sets column end_1
     *
     * @param int $data
     * @return Messerve_Model_Attendance
     */
    public function setEnd1($data)
    {
        $this->_End1 = $data;
        return $this;
    }

    /**
     * Gets column end_1
     *
     * @return int
     */
    public function getEnd1()
    {
        return $this->_End1;
    }

    /**
     * Sets column start_2
     *
     * @param int $data
     * @return Messerve_Model_Attendance
     */
    public function setStart2($data)
    {
        $this->_Start2 = $data;
        return $this;
    }

    /**
     * Gets column start_2
     *
     * @return int
     */
    public function getStart2()
    {
        return $this->_Start2;
    }

    /**
     * Sets column end_2
     *
     * @param int $data
     * @return Messerve_Model_Attendance
     */
    public function setEnd2($data)
    {
        $this->_End2 = $data;
        return $this;
    }

    /**
     * Gets column end_2
     *
     * @return int
     */
    public function getEnd2()
    {
        return $this->_End2;
    }

    /**
     * Sets column start_3
     *
     * @param int $data
     * @return Messerve_Model_Attendance
     */
    public function setStart3($data)
    {
        $this->_Start3 = $data;
        return $this;
    }

    /**
     * Gets column start_3
     *
     * @return int
     */
    public function getStart3()
    {
        return $this->_Start3;
    }

    /**
     * Sets column end_3
     *
     * @param int $data
     * @return Messerve_Model_Attendance
     */
    public function setEnd3($data)
    {
        $this->_End3 = $data;
        return $this;
    }

    /**
     * Gets column end_3
     *
     * @return int
     */
    public function getEnd3()
    {
        return $this->_End3;
    }

    /**
     * Sets column today
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setToday($data)
    {
        $this->_Today = $data;
        return $this;
    }

    /**
     * Gets column today
     *
     * @return float
     */
    public function getToday()
    {
        return $this->_Today;
    }

    /**
     * Sets column today_nd
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setTodayNd($data)
    {
        $this->_TodayNd = $data;
        return $this;
    }

    /**
     * Gets column today_nd
     *
     * @return float
     */
    public function getTodayNd()
    {
        return $this->_TodayNd;
    }

    /**
     * Sets column today_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setTodayOt($data)
    {
        $this->_TodayOt = $data;
        return $this;
    }

    /**
     * Gets column today_ot
     *
     * @return float
     */
    public function getTodayOt()
    {
        return $this->_TodayOt;
    }

    /**
     * Sets column today_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setTodayNdOt($data)
    {
        $this->_TodayNdOt = $data;
        return $this;
    }

    /**
     * Gets column today_nd_ot
     *
     * @return float
     */
    public function getTodayNdOt()
    {
        return $this->_TodayNdOt;
    }

    /**
     * Sets column tomorrow
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setTomorrow($data)
    {
        $this->_Tomorrow = $data;
        return $this;
    }

    /**
     * Gets column tomorrow
     *
     * @return float
     */
    public function getTomorrow()
    {
        return $this->_Tomorrow;
    }

    /**
     * Sets column tomorrow_nd
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setTomorrowNd($data)
    {
        $this->_TomorrowNd = $data;
        return $this;
    }

    /**
     * Gets column tomorrow_nd
     *
     * @return float
     */
    public function getTomorrowNd()
    {
        return $this->_TomorrowNd;
    }

    /**
     * Sets column tomorrow_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setTomorrowOt($data)
    {
        $this->_TomorrowOt = $data;
        return $this;
    }

    /**
     * Gets column tomorrow_ot
     *
     * @return float
     */
    public function getTomorrowOt()
    {
        return $this->_TomorrowOt;
    }

    /**
     * Sets column tomorrow_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setTomorrowNdOt($data)
    {
        $this->_TomorrowNdOt = $data;
        return $this;
    }

    /**
     * Gets column tomorrow_nd_ot
     *
     * @return float
     */
    public function getTomorrowNdOt()
    {
        return $this->_TomorrowNdOt;
    }

    /**
     * Sets column ot_approved
     *
     * @param string $data
     * @return Messerve_Model_Attendance
     */
    public function setOtApproved($data)
    {
        $this->_OtApproved = $data;
        return $this;
    }

    /**
     * Gets column ot_approved
     *
     * @return string
     */
    public function getOtApproved()
    {
        return $this->_OtApproved;
    }

    /**
     * Sets column ot_approved_hours
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setOtApprovedHours($data)
    {
        $this->_OtApprovedHours = $data;
        return $this;
    }

    /**
     * Gets column ot_approved_hours
     *
     * @return float
     */
    public function getOtApprovedHours()
    {
        return $this->_OtApprovedHours;
    }

    /**
     * Sets column ot_actual_hours
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setOtActualHours($data)
    {
        $this->_OtActualHours = $data;
        return $this;
    }

    /**
     * Gets column ot_actual_hours
     *
     * @return float
     */
    public function getOtActualHours()
    {
        return $this->_OtActualHours;
    }

    /**
     * Sets column extended_shift
     *
     * @param string $data
     * @return Messerve_Model_Attendance
     */
    public function setExtendedShift($data)
    {
        $this->_ExtendedShift = $data;
        return $this;
    }

    /**
     * Gets column extended_shift
     *
     * @return string
     */
    public function getExtendedShift()
    {
        return $this->_ExtendedShift;
    }

    /**
     * Sets column employee_id
     *
     * @param int $data
     * @return Messerve_Model_Attendance
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
     * Sets column employee_number
     *
     * @param int $data
     * @return Messerve_Model_Attendance
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
     * Sets column group_id
     *
     * @param int $data
     * @return Messerve_Model_Attendance
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
     * Sets column datetime_start. Stored in ISO 8601 format.
     *
     * @param string|Zend_Date $date
     * @return Messerve_Model_Attendance
     */
    public function setDatetimeStart($data)
    {
        if (! empty($data)) {
            if (! $data instanceof Zend_Date) {
                $zdate = new Zend_Date();
            }

            $data = $zdate->toString($data,'YYYY-MM-ddTHH:mm:ss.S');
        }

        $this->_DatetimeStart = $data;
        return $this;
    }

    /**
     * Gets column datetime_start
     *
     * @param boolean $returnZendDate
     * @return Zend_Date|null|string Zend_Date representation of this datetime if enabled, or ISO 8601 string if not
     */
    public function getDatetimeStart($returnZendDate = false)
    {
        if ($returnZendDate) {
            if ($this->_DatetimeStart === null) {
                return null;
            }

            return new Zend_Date($this->_DatetimeStart, Zend_Date::ISO_8601);
        }

        return $this->_DatetimeStart;
    }

    /**
     * Sets column datetime_end. Stored in ISO 8601 format.
     *
     * @param string|Zend_Date $date
     * @return Messerve_Model_Attendance
     */
    public function setDatetimeEnd($data)
    {
        if (! empty($data)) {
            if (! $data instanceof Zend_Date) {
                $zdate = new Zend_Date();
            }

            $data = $zdate->toString($data,'YYYY-MM-ddTHH:mm:ss.S');
        }

        $this->_DatetimeEnd = $data;
        return $this;
    }

    /**
     * Gets column datetime_end
     *
     * @param boolean $returnZendDate
     * @return Zend_Date|null|string Zend_Date representation of this datetime if enabled, or ISO 8601 string if not
     */
    public function getDatetimeEnd($returnZendDate = false)
    {
        if ($returnZendDate) {
            if ($this->_DatetimeEnd === null) {
                return null;
            }

            return new Zend_Date($this->_DatetimeEnd, Zend_Date::ISO_8601);
        }

        return $this->_DatetimeEnd;
    }

    /**
     * Sets column total_hours
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setTotalHours($data)
    {
        $this->_TotalHours = $data;
        return $this;
    }

    /**
     * Gets column total_hours
     *
     * @return float
     */
    public function getTotalHours()
    {
        return $this->_TotalHours;
    }

    /**
     * Sets column reg
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setReg($data)
    {
        $this->_Reg = $data;
        return $this;
    }

    /**
     * Gets column reg
     *
     * @return float
     */
    public function getReg()
    {
        return $this->_Reg;
    }

    /**
     * Sets column reg_nd
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setRegNd($data)
    {
        $this->_RegNd = $data;
        return $this;
    }

    /**
     * Gets column reg_nd
     *
     * @return float
     */
    public function getRegNd()
    {
        return $this->_RegNd;
    }

    /**
     * Sets column reg_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setRegOt($data)
    {
        $this->_RegOt = $data;
        return $this;
    }

    /**
     * Gets column reg_ot
     *
     * @return float
     */
    public function getRegOt()
    {
        return $this->_RegOt;
    }

    /**
     * Sets column reg_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setRegNdOt($data)
    {
        $this->_RegNdOt = $data;
        return $this;
    }

    /**
     * Gets column reg_nd_ot
     *
     * @return float
     */
    public function getRegNdOt()
    {
        return $this->_RegNdOt;
    }

    /**
     * Sets column sun
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setSun($data)
    {
        $this->_Sun = $data;
        return $this;
    }

    /**
     * Gets column sun
     *
     * @return float
     */
    public function getSun()
    {
        return $this->_Sun;
    }

    /**
     * Sets column sun_nd
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setSunNd($data)
    {
        $this->_SunNd = $data;
        return $this;
    }

    /**
     * Gets column sun_nd
     *
     * @return float
     */
    public function getSunNd()
    {
        return $this->_SunNd;
    }

    /**
     * Sets column sun_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setSunOt($data)
    {
        $this->_SunOt = $data;
        return $this;
    }

    /**
     * Gets column sun_ot
     *
     * @return float
     */
    public function getSunOt()
    {
        return $this->_SunOt;
    }

    /**
     * Sets column sun_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setSunNdOt($data)
    {
        $this->_SunNdOt = $data;
        return $this;
    }

    /**
     * Gets column sun_nd_ot
     *
     * @return float
     */
    public function getSunNdOt()
    {
        return $this->_SunNdOt;
    }

    /**
     * Sets column spec
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setSpec($data)
    {
        $this->_Spec = $data;
        return $this;
    }

    /**
     * Gets column spec
     *
     * @return float
     */
    public function getSpec()
    {
        return $this->_Spec;
    }

    /**
     * Sets column spec_nd
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setSpecNd($data)
    {
        $this->_SpecNd = $data;
        return $this;
    }

    /**
     * Gets column spec_nd
     *
     * @return float
     */
    public function getSpecNd()
    {
        return $this->_SpecNd;
    }

    /**
     * Sets column spec_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setSpecOt($data)
    {
        $this->_SpecOt = $data;
        return $this;
    }

    /**
     * Gets column spec_ot
     *
     * @return float
     */
    public function getSpecOt()
    {
        return $this->_SpecOt;
    }

    /**
     * Sets column spec_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setSpecNdOt($data)
    {
        $this->_SpecNdOt = $data;
        return $this;
    }

    /**
     * Gets column spec_nd_ot
     *
     * @return float
     */
    public function getSpecNdOt()
    {
        return $this->_SpecNdOt;
    }

    /**
     * Sets column legal
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setLegal($data)
    {
        $this->_Legal = $data;
        return $this;
    }

    /**
     * Gets column legal
     *
     * @return float
     */
    public function getLegal()
    {
        return $this->_Legal;
    }

    /**
     * Sets column legal_nd
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setLegalNd($data)
    {
        $this->_LegalNd = $data;
        return $this;
    }

    /**
     * Gets column legal_nd
     *
     * @return float
     */
    public function getLegalNd()
    {
        return $this->_LegalNd;
    }

    /**
     * Sets column legal_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setLegalOt($data)
    {
        $this->_LegalOt = $data;
        return $this;
    }

    /**
     * Gets column legal_ot
     *
     * @return float
     */
    public function getLegalOt()
    {
        return $this->_LegalOt;
    }

    /**
     * Sets column legal_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setLegalNdOt($data)
    {
        $this->_LegalNdOt = $data;
        return $this;
    }

    /**
     * Gets column legal_nd_ot
     *
     * @return float
     */
    public function getLegalNdOt()
    {
        return $this->_LegalNdOt;
    }

    /**
     * Sets column legal_unattend
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setLegalUnattend($data)
    {
        $this->_LegalUnattend = $data;
        return $this;
    }

    /**
     * Gets column legal_unattend
     *
     * @return float
     */
    public function getLegalUnattend()
    {
        return $this->_LegalUnattend;
    }

    /**
     * Sets column rest
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setRest($data)
    {
        $this->_Rest = $data;
        return $this;
    }

    /**
     * Gets column rest
     *
     * @return float
     */
    public function getRest()
    {
        return $this->_Rest;
    }

    /**
     * Sets column rest_nd
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setRestNd($data)
    {
        $this->_RestNd = $data;
        return $this;
    }

    /**
     * Gets column rest_nd
     *
     * @return float
     */
    public function getRestNd()
    {
        return $this->_RestNd;
    }

    /**
     * Sets column rest_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setRestOt($data)
    {
        $this->_RestOt = $data;
        return $this;
    }

    /**
     * Gets column rest_ot
     *
     * @return float
     */
    public function getRestOt()
    {
        return $this->_RestOt;
    }

    /**
     * Sets column rest_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setRestNdOt($data)
    {
        $this->_RestNdOt = $data;
        return $this;
    }

    /**
     * Gets column rest_nd_ot
     *
     * @return float
     */
    public function getRestNdOt()
    {
        return $this->_RestNdOt;
    }

    /**
     * Sets column fuel_overage
     *
     * @param float $data
     * @return Messerve_Model_Attendance
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
     * Sets column fuel_hours
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setFuelHours($data)
    {
        $this->_FuelHours = $data;
        return $this;
    }

    /**
     * Gets column fuel_hours
     *
     * @return float
     */
    public function getFuelHours()
    {
        return $this->_FuelHours;
    }

    /**
     * Sets column fuel_alloted
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setFuelAlloted($data)
    {
        $this->_FuelAlloted = $data;
        return $this;
    }

    /**
     * Gets column fuel_alloted
     *
     * @return float
     */
    public function getFuelAlloted()
    {
        return $this->_FuelAlloted;
    }

    /**
     * Sets column fuel_consumed
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setFuelConsumed($data)
    {
        $this->_FuelConsumed = $data;
        return $this;
    }

    /**
     * Gets column fuel_consumed
     *
     * @return float
     */
    public function getFuelConsumed()
    {
        return $this->_FuelConsumed;
    }

    /**
     * Sets column fuel_cost
     *
     * @param float $data
     * @return Messerve_Model_Attendance
     */
    public function setFuelCost($data)
    {
        $this->_FuelCost = $data;
        return $this;
    }

    /**
     * Gets column fuel_cost
     *
     * @return float
     */
    public function getFuelCost()
    {
        return $this->_FuelCost;
    }

    /**
     * Sets column today_rate_id
     *
     * @param int $data
     * @return Messerve_Model_Attendance
     */
    public function setTodayRateId($data)
    {
        $this->_TodayRateId = $data;
        return $this;
    }

    /**
     * Gets column today_rate_id
     *
     * @return int
     */
    public function getTodayRateId()
    {
        return $this->_TodayRateId;
    }

    /**
     * Sets column today_rate_data
     *
     * @param string $data
     * @return Messerve_Model_Attendance
     */
    public function setTodayRateData($data)
    {
        $this->_TodayRateData = $data;
        return $this;
    }

    /**
     * Gets column today_rate_data
     *
     * @return string
     */
    public function getTodayRateData()
    {
        return $this->_TodayRateData;
    }

    /**
     * Sets column tomorrow_rate_id
     *
     * @param int $data
     * @return Messerve_Model_Attendance
     */
    public function setTomorrowRateId($data)
    {
        $this->_TomorrowRateId = $data;
        return $this;
    }

    /**
     * Gets column tomorrow_rate_id
     *
     * @return int
     */
    public function getTomorrowRateId()
    {
        return $this->_TomorrowRateId;
    }

    /**
     * Sets column tomorrow_rate_data
     *
     * @param string $data
     * @return Messerve_Model_Attendance
     */
    public function setTomorrowRateData($data)
    {
        $this->_TomorrowRateData = $data;
        return $this;
    }




    /**
     * Gets column tomorrow_rate_data
     *
     * @return string
     */
    public function getTomorrowRateData()
    {
        return $this->_TomorrowRateData;
    }


    public function getApprovedExtendedShift()
    {
        return $this->_ApprovedExtendedShift;
    }

    public function setApprovedExtendedShift($data)
    {
        $this->_ApprovedExtendedShift = $data;
        return $this;
    }
    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_Attendance
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_Attendance());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_Attendance::delete
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
