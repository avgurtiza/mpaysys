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
class Messerve_Model_Sss extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Id;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_Min;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_Max;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_Employer;

    /**
     * Database var type decimal(11,2)
     *
     * @var float
     */
    protected $_Employee;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'min'=>'Min',
            'max'=>'Max',
            'employer'=>'Employer',
            'employee'=>'Employee',
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
     * @return Messerve_Model_Sss
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
     * Sets column min
     *
     * @param float $data
     * @return Messerve_Model_Sss
     */
    public function setMin($data)
    {
        $this->_Min = $data;
        return $this;
    }

    /**
     * Gets column min
     *
     * @return float
     */
    public function getMin()
    {
        return $this->_Min;
    }

    /**
     * Sets column max
     *
     * @param float $data
     * @return Messerve_Model_Sss
     */
    public function setMax($data)
    {
        $this->_Max = $data;
        return $this;
    }

    /**
     * Gets column max
     *
     * @return float
     */
    public function getMax()
    {
        return $this->_Max;
    }

    /**
     * Sets column employer
     *
     * @param float $data
     * @return Messerve_Model_Sss
     */
    public function setEmployer($data)
    {
        $this->_Employer = $data;
        return $this;
    }

    /**
     * Gets column employer
     *
     * @return float
     */
    public function getEmployer()
    {
        return $this->_Employer;
    }

    /**
     * Sets column employee
     *
     * @param float $data
     * @return Messerve_Model_Sss
     */
    public function setEmployee($data)
    {
        $this->_Employee = $data;
        return $this;
    }

    /**
     * Gets column employee
     *
     * @return float
     */
    public function getEmployee()
    {
        return $this->_Employee;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_Sss
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_Sss());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_Sss::delete
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
