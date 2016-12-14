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
class Messerve_Model_PayrollTemp extends Messerve_Model_ModelAbstract
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
    protected $_GroupId;

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
     * Database var type date
     *
     * @var string
     */
    protected $_PeriodCovered;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Firstname;

    /**
     * Database var type varchar(32)
     *
     * @var string
     */
    protected $_Middlename;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Lastname;

    /**
     * Database var type varchar(32)
     *
     * @var string
     */
    protected $_ClientName;

    /**
     * Database var type varchar(32)
     *
     * @var string
     */
    protected $_GroupName;

    /**
     * Database var type varchar(32)
     *
     * @var string
     */
    protected $_AccountNumber;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_NetPay;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Ecola;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Incentives;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Sss;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Philhealth;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Hdmf;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_CashBond;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Insurance;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Communication;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_MiscDeduction;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_MiscAddition;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_GrossPay;

    /**
     * Database var type text
     *
     * @var string
     */
    protected $_DeductionData;

    /**
     * Database var type enum('yes','no')
     *
     * @var string
     */
    protected $_IsReliever;

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
    protected $_Adjustment;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Miscellaneous;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_FuelOverage;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_FuelAddition;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_FuelDeduction;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_FuelAllotment;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_FuelUsage;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_FuelHours;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_FuelPrice;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_ThirteenthMonth;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_BopMotorcycle;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_BopInsurance;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_BopMaintenance;
    protected $_LostCard;
    protected $_Food;
    protected $_Paternity;
    protected $_BasicPay;
    protected $_PayrollMeta;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'group_id'=>'GroupId',
            'employee_id'=>'EmployeeId',
            'employee_number'=>'EmployeeNumber',
            'period_covered'=>'PeriodCovered',
            'firstname'=>'Firstname',
            'middlename'=>'Middlename',
            'lastname'=>'Lastname',
            'client_name'=>'ClientName',
            'group_name'=>'GroupName',
            'account_number'=>'AccountNumber',
            'net_pay'=>'NetPay',
            'ecola'=>'Ecola',
            'incentives'=>'Incentives',
            'sss'=>'Sss',
            'philhealth'=>'Philhealth',
            'hdmf'=>'Hdmf',
            'cash_bond'=>'CashBond',
            'insurance'=>'Insurance',
            'communication'=>'Communication',
            'misc_deduction'=>'MiscDeduction',
            'misc_addition'=>'MiscAddition',
            'gross_pay'=>'GrossPay',
            'deduction_data'=>'DeductionData',
            'is_reliever'=>'IsReliever',
            'sss_loan'=>'SssLoan',
            'hdmf_loan'=>'HdmfLoan',
            'accident'=>'Accident',
            'uniform'=>'Uniform',
            'adjustment'=>'Adjustment',
            'miscellaneous'=>'Miscellaneous',
            'fuel_overage'=>'FuelOverage',
            'fuel_addition'=>'FuelAddition',
            'fuel_deduction'=>'FuelDeduction',
            'fuel_allotment'=>'FuelAllotment',
            'fuel_usage'=>'FuelUsage',
            'fuel_hours'=>'FuelHours',
            'fuel_price'=>'FuelPrice',
            'thirteenth_month'=>'ThirteenthMonth',
            'bop_motorcycle'=>'BopMotorcycle',
            'bop_insurance'=>'BopInsurance',
            'bop_maintenance'=>'BopMaintenance',
            'lost_card'=>'LostCard',
            'food'=>'Food',
            'paternity'=>'Paternity',
            'basic_pay'=>'BasicPay',
            'payroll_meta'=>'PayrollMeta',
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
     * @return Messerve_Model_PayrollTemp
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
     * Sets column group_id
     *
     * @param int $data
     * @return Messerve_Model_PayrollTemp
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
     * Sets column employee_id
     *
     * @param int $data
     * @return Messerve_Model_PayrollTemp
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
     * @return Messerve_Model_PayrollTemp
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
     * Sets column period_covered
     *
     * @param string $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setPeriodCovered($data)
    {
        $this->_PeriodCovered = $data;
        return $this;
    }

    /**
     * Gets column period_covered
     *
     * @return string
     */
    public function getPeriodCovered()
    {
        return $this->_PeriodCovered;
    }

    /**
     * Sets column firstname
     *
     * @param string $data
     * @return Messerve_Model_PayrollTemp
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
     * Sets column middlename
     *
     * @param string $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setMiddlename($data)
    {
        $this->_Middlename = $data;
        return $this;
    }

    /**
     * Gets column middlename
     *
     * @return string
     */
    public function getMiddlename()
    {
        return $this->_Middlename;
    }

    /**
     * Sets column lastname
     *
     * @param string $data
     * @return Messerve_Model_PayrollTemp
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
     * Sets column client_name
     *
     * @param string $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setClientName($data)
    {
        $this->_ClientName = $data;
        return $this;
    }

    /**
     * Gets column client_name
     *
     * @return string
     */
    public function getClientName()
    {
        return $this->_ClientName;
    }

    /**
     * Sets column group_name
     *
     * @param string $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setGroupName($data)
    {
        $this->_GroupName = $data;
        return $this;
    }

    /**
     * Gets column group_name
     *
     * @return string
     */
    public function getGroupName()
    {
        return $this->_GroupName;
    }

    /**
     * Sets column account_number
     *
     * @param string $data
     * @return Messerve_Model_PayrollTemp
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
     * Sets column net_pay
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setNetPay($data)
    {
        $this->_NetPay = $data;
        return $this;
    }

    /**
     * Gets column net_pay
     *
     * @return float
     */
    public function getNetPay()
    {
        return $this->_NetPay;
    }

    /**
     * Sets column ecola
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setEcola($data)
    {
        $this->_Ecola = $data;
        return $this;
    }

    /**
     * Gets column ecola
     *
     * @return float
     */
    public function getEcola()
    {
        return $this->_Ecola;
    }

    /**
     * Sets column incentives
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
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
     * Sets column sss
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setSss($data)
    {
        $this->_Sss = $data;
        return $this;
    }

    /**
     * Gets column sss
     *
     * @return float
     */
    public function getSss()
    {
        return $this->_Sss;
    }

    /**
     * Sets column philhealth
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setPhilhealth($data)
    {
        $this->_Philhealth = $data;
        return $this;
    }

    /**
     * Gets column philhealth
     *
     * @return float
     */
    public function getPhilhealth()
    {
        return $this->_Philhealth;
    }

    /**
     * Sets column hdmf
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setHdmf($data)
    {
        $this->_Hdmf = $data;
        return $this;
    }

    /**
     * Gets column hdmf
     *
     * @return float
     */
    public function getHdmf()
    {
        return $this->_Hdmf;
    }

    /**
     * Sets column cash_bond
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setCashBond($data)
    {
        $this->_CashBond = $data;
        return $this;
    }

    /**
     * Gets column cash_bond
     *
     * @return float
     */
    public function getCashBond()
    {
        return $this->_CashBond;
    }

    /**
     * Sets column insurance
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setInsurance($data)
    {
        $this->_Insurance = $data;
        return $this;
    }

    /**
     * Gets column insurance
     *
     * @return float
     */
    public function getInsurance()
    {
        return $this->_Insurance;
    }

    /**
     * Sets column communication
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
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
     * Sets column misc_deduction
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setMiscDeduction($data)
    {
        $this->_MiscDeduction = $data;
        return $this;
    }

    /**
     * Gets column misc_deduction
     *
     * @return float
     */
    public function getMiscDeduction()
    {
        return $this->_MiscDeduction;
    }

    /**
     * Sets column misc_addition
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setMiscAddition($data)
    {
        $this->_MiscAddition = $data;
        return $this;
    }

    /**
     * Gets column misc_addition
     *
     * @return float
     */
    public function getMiscAddition()
    {
        return $this->_MiscAddition;
    }

    /**
     * Sets column gross_pay
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setGrossPay($data)
    {
        $this->_GrossPay = $data;
        return $this;
    }

    /**
     * Gets column gross_pay
     *
     * @return float
     */
    public function getGrossPay()
    {
        return $this->_GrossPay;
    }

    /**
     * Sets column deduction_data
     *
     * @param string $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setDeductionData($data)
    {
        $this->_DeductionData = $data;
        return $this;
    }

    /**
     * Gets column deduction_data
     *
     * @return string
     */
    public function getDeductionData()
    {
        return $this->_DeductionData;
    }

    /**
     * Sets column is_reliever
     *
     * @param string $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setIsReliever($data)
    {
        $this->_IsReliever = $data;
        return $this;
    }

    /**
     * Gets column is_reliever
     *
     * @return string
     */
    public function getIsReliever()
    {
        return $this->_IsReliever;
    }

    /**
     * Sets column sss_loan
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
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
     * @return Messerve_Model_PayrollTemp
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
     * Sets column accident
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
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
     * @return Messerve_Model_PayrollTemp
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
     * Sets column adjustment
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setAdjustment($data)
    {
        $this->_Adjustment = $data;
        return $this;
    }

    /**
     * Gets column adjustment
     *
     * @return float
     */
    public function getAdjustment()
    {
        return $this->_Adjustment;
    }

    /**
     * Sets column miscellaneous
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setMiscellaneous($data)
    {
        $this->_Miscellaneous = $data;
        return $this;
    }

    /**
     * Gets column miscellaneous
     *
     * @return float
     */
    public function getMiscellaneous()
    {
        return $this->_Miscellaneous;
    }

    /**
     * Sets column fuel_overage
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
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
     * Sets column fuel_addition
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setFuelAddition($data)
    {
        $this->_FuelAddition = $data;
        return $this;
    }

    /**
     * Gets column fuel_addition
     *
     * @return float
     */
    public function getFuelAddition()
    {
        return $this->_FuelAddition;
    }

    /**
     * Sets column fuel_deduction
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setFuelDeduction($data)
    {
        $this->_FuelDeduction = $data;
        return $this;
    }

    /**
     * Gets column fuel_deduction
     *
     * @return float
     */
    public function getFuelDeduction()
    {
        return $this->_FuelDeduction;
    }

    /**
     * Sets column fuel_allotment
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setFuelAllotment($data)
    {
        $this->_FuelAllotment = $data;
        return $this;
    }

    /**
     * Gets column fuel_allotment
     *
     * @return float
     */
    public function getFuelAllotment()
    {
        return $this->_FuelAllotment;
    }

    /**
     * Sets column fuel_usage
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setFuelUsage($data)
    {
        $this->_FuelUsage = $data;
        return $this;
    }

    /**
     * Gets column fuel_usage
     *
     * @return float
     */
    public function getFuelUsage()
    {
        return $this->_FuelUsage;
    }

    /**
     * Sets column fuel_hours
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
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
     * Sets column fuel_price
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setFuelPrice($data)
    {
        $this->_FuelPrice = $data;
        return $this;
    }

    /**
     * Gets column fuel_price
     *
     * @return float
     */
    public function getFuelPrice()
    {
        return $this->_FuelPrice;
    }

    /**
     * Sets column thirteenth_month
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setThirteenthMonth($data)
    {
        $this->_ThirteenthMonth = $data;
        return $this;
    }

    /**
     * Gets column thirteenth_month
     *
     * @return float
     */
    public function getThirteenthMonth()
    {
        return $this->_ThirteenthMonth;
    }

    /**
     * Sets column bop_motorcycle
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setBopMotorcycle($data)
    {
        $this->_BopMotorcycle = $data;
        return $this;
    }

    /**
     * Gets column bop_motorcycle
     *
     * @return float
     */
    public function getBopMotorcycle()
    {
        return $this->_BopMotorcycle;
    }

    /**
     * Sets column bop_insurance
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setBopInsurance($data)
    {
        $this->_BopInsurance = $data;
        return $this;
    }

    /**
     * Gets column bop_insurance
     *
     * @return float
     */
    public function getBopInsurance()
    {
        return $this->_BopInsurance;
    }

    /**
     * Sets column bop_maintenance
     *
     * @param float $data
     * @return Messerve_Model_PayrollTemp
     */
    public function setBopMaintenance($data)
    {
        $this->_BopMaintenance = $data;
        return $this;
    }

    /**
     * Gets column bop_maintenance
     *
     * @return float
     */
    public function getBopMaintenance()
    {
        return $this->_BopMaintenance;
    }


    public function setLostCard($data)
    {
        $this->_LostCard = $data;
        return $this;
    }

    public function getLostCard()
    {
        return $this->_LostCard;
    }

    public function setFood($data)
    {
        $this->_Food = $data;
        return $this;
    }

    public function getFood()
    {
        return $this->_Food;
    }
    public function setPaternity($data)
    {
        $this->_Paternity = $data;
        return $this;
    }

    public function getPaternity()
    {
        return $this->_Paternity;
    }

    public function setBasicPay($data)
    {
        $this->_BasicPay = $data;
        return $this;
    }

    public function getBasicPay()
    {
        return $this->_BasicPay;
    }

    public function setPayrollMeta($data)
    {
        $this->_PayrollMeta = $data;
        return $this;
    }

    public function getPayrollMeta()
    {
        return $this->_PayrollMeta;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_PayrollTemp
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_PayrollTemp());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_PayrollTemp::delete
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
