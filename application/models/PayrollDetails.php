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
class Messerve_Model_PayrollDetails extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_PayrollId;

    /**
     * Database var type varchar(32)
     *
     * @var string
     */
    protected $_Name;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Value;

    /**
     * Database var type enum('standard','custom')
     *
     * @var string
     */
    protected $_Type;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'payroll_id'=>'PayrollId',
            'name'=>'Name',
            'value'=>'Value',
            'type'=>'Type',
        ));

        $this->setParentList(array(
        ));

        $this->setDependentList(array(
        ));
    }

    /**
     * Sets column payroll_id
     *
     * @param int $data
     * @return Messerve_Model_PayrollDetails
     */
    public function setPayrollId($data)
    {
        $this->_PayrollId = $data;
        return $this;
    }

    /**
     * Gets column payroll_id
     *
     * @return int
     */
    public function getPayrollId()
    {
        return $this->_PayrollId;
    }

    /**
     * Sets column name
     *
     * @param string $data
     * @return Messerve_Model_PayrollDetails
     */
    public function setName($data)
    {
        $this->_Name = $data;
        return $this;
    }

    /**
     * Gets column name
     *
     * @return string
     */
    public function getName()
    {
        return $this->_Name;
    }

    /**
     * Sets column value
     *
     * @param float $data
     * @return Messerve_Model_PayrollDetails
     */
    public function setValue($data)
    {
        $this->_Value = $data;
        return $this;
    }

    /**
     * Gets column value
     *
     * @return float
     */
    public function getValue()
    {
        return $this->_Value;
    }

    /**
     * Sets column type
     *
     * @param string $data
     * @return Messerve_Model_PayrollDetails
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
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_PayrollDetails
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_PayrollDetails());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_PayrollDetails::delete
     * @return int|boolean Number of rows deleted or boolean if doing soft delete
     */
    public function deleteRowByPrimaryKey()
    {
        $primary_key = array();
        if (! $this->getPayrollId()) {
            throw new Exception('Primary Key PayrollId does not contain a value');
        } else {
            $primary_key['payroll_id'] = $this->getPayrollId();
        }

        if (! $this->getName()) {
            throw new Exception('Primary Key Name does not contain a value');
        } else {
            $primary_key['name'] = $this->getName();
        }

        return $this->getMapper()->getDbTable()->delete('payroll_id = '
                    . $this->getMapper()->getDbTable()->getAdapter()->quote($primary_key['payroll_id'])
                    . ' AND name = '
                    . $this->getMapper()->getDbTable()->getAdapter()->quote($primary_key['name']));
    }
}
