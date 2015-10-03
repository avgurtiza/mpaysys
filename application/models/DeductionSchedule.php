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
class Messerve_Model_DeductionSchedule extends Messerve_Model_ModelAbstract
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
     * Database var type enum('sss_loan','hdmf_loan','fuel_overage','maintenance_deduct','accident','uniform','lost_card','food','communication','adjustment','misc')
     *
     * @var string
     */
    protected $_Type;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_Amount;

    /**
     * Database var type double
     *
     * @var float
     */
    protected $_Deduction;

    /**
     * Database var type enum('1','2','3')
     *
     * @var string
     */
    protected $_Cutoff;

    /**
     * Database var type timestamp
     *
     * @var string
     */
    protected $_DateAdded;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_UserId;

    /**
     * Database var type text
     *
     * @var string
     */
    protected $_Notes;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'employee_id'=>'EmployeeId',
            'type'=>'Type',
            'amount'=>'Amount',
            'deduction'=>'Deduction',
            'cutoff'=>'Cutoff',
            'date_added'=>'DateAdded',
            'user_id'=>'UserId',
            'notes'=>'Notes',
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
     * @return Messerve_Model_DeductionSchedule
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
     * @return Messerve_Model_DeductionSchedule
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
     * Sets column type
     *
     * @param string $data
     * @return Messerve_Model_DeductionSchedule
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
     * Sets column amount
     *
     * @param float $data
     * @return Messerve_Model_DeductionSchedule
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
     * Sets column deduction
     *
     * @param float $data
     * @return Messerve_Model_DeductionSchedule
     */
    public function setDeduction($data)
    {
        $this->_Deduction = $data;
        return $this;
    }

    /**
     * Gets column deduction
     *
     * @return float
     */
    public function getDeduction()
    {
        return $this->_Deduction;
    }

    /**
     * Sets column cutoff
     *
     * @param string $data
     * @return Messerve_Model_DeductionSchedule
     */
    public function setCutoff($data)
    {
        $this->_Cutoff = $data;
        return $this;
    }

    /**
     * Gets column cutoff
     *
     * @return string
     */
    public function getCutoff()
    {
        return $this->_Cutoff;
    }

    /**
     * Sets column date_added
     *
     * @param string $data
     * @return Messerve_Model_DeductionSchedule
     */
    public function setDateAdded($data)
    {
        $this->_DateAdded = $data;
        return $this;
    }

    /**
     * Gets column date_added
     *
     * @return string
     */
    public function getDateAdded()
    {
        return $this->_DateAdded;
    }

    /**
     * Sets column user_id
     *
     * @param int $data
     * @return Messerve_Model_DeductionSchedule
     */
    public function setUserId($data)
    {
        $this->_UserId = $data;
        return $this;
    }

    /**
     * Gets column user_id
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->_UserId;
    }

    /**
     * Sets column notes
     *
     * @param string $data
     * @return Messerve_Model_DeductionSchedule
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
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_DeductionSchedule
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_DeductionSchedule());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_DeductionSchedule::delete
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
