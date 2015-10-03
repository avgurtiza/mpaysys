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
class Messerve_Model_Payroll extends Messerve_Model_ModelAbstract
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
    protected $_EmployeeId;

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
    protected $_DateStart;

    /**
     * Database var type date
     *
     * @var string
     */
    protected $_DateEnd;

    /**
     * Database var type text
     *
     * @var string
     */
    protected $_Notes;

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
     * Database var type float
     *
     * @var float
     */
    protected $_NetPay;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'employee_id'=>'EmployeeId',
            'group_id'=>'GroupId',
            'rate_id'=>'RateId',
            'date_start'=>'DateStart',
            'date_end'=>'DateEnd',
            'notes'=>'Notes',
            'ecola'=>'Ecola',
            'sss'=>'Sss',
            'philhealth'=>'Philhealth',
            'hdmf'=>'Hdmf',
            'cash_bond'=>'CashBond',
            'misc_deduction'=>'MiscDeduction',
            'misc_addition'=>'MiscAddition',
            'gross_pay'=>'GrossPay',
            'net_pay'=>'NetPay',
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
     * @return Messerve_Model_Payroll
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
     * Sets column employee_id
     *
     * @param int $data
     * @return Messerve_Model_Payroll
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
     * @return Messerve_Model_Payroll
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
     * @return Messerve_Model_Payroll
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
     * Sets column date_start
     *
     * @param string $data
     * @return Messerve_Model_Payroll
     */
    public function setDateStart($data)
    {
        $this->_DateStart = $data;
        return $this;
    }

    /**
     * Gets column date_start
     *
     * @return string
     */
    public function getDateStart()
    {
        return $this->_DateStart;
    }

    /**
     * Sets column date_end
     *
     * @param string $data
     * @return Messerve_Model_Payroll
     */
    public function setDateEnd($data)
    {
        $this->_DateEnd = $data;
        return $this;
    }

    /**
     * Gets column date_end
     *
     * @return string
     */
    public function getDateEnd()
    {
        return $this->_DateEnd;
    }

    /**
     * Sets column notes
     *
     * @param string $data
     * @return Messerve_Model_Payroll
     */
    public function setNotes($data)
    {
        $this->_Notes = $data;
        return $this;
    }

    /**
     * Gets column notes
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->_Notes;
    }

    /**
     * Sets column ecola
     *
     * @param float $data
     * @return Messerve_Model_Payroll
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
     * Sets column sss
     *
     * @param float $data
     * @return Messerve_Model_Payroll
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
     * @return Messerve_Model_Payroll
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
     * @return Messerve_Model_Payroll
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
     * @return Messerve_Model_Payroll
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
     * Sets column misc_deduction
     *
     * @param float $data
     * @return Messerve_Model_Payroll
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
     * @return Messerve_Model_Payroll
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
     * @return Messerve_Model_Payroll
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
     * Sets column net_pay
     *
     * @param float $data
     * @return Messerve_Model_Payroll
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
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_Payroll
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_Payroll());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_Payroll::delete
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
