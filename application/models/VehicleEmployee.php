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
class Messerve_Model_VehicleEmployee extends Messerve_Model_ModelAbstract
{

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
    protected $_VehicleId;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_GroupId;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'employee_id'=>'EmployeeId',
            'vehicle_id'=>'VehicleId',
            'group_id'=>'GroupId',
        ));

        $this->setParentList(array(
        ));

        $this->setDependentList(array(
        ));
    }

    /**
     * Sets column employee_id
     *
     * @param int $data
     * @return Messerve_Model_VehicleEmployee
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
     * Sets column vehicle_id
     *
     * @param int $data
     * @return Messerve_Model_VehicleEmployee
     */
    public function setVehicleId($data)
    {
        $this->_VehicleId = $data;
        return $this;
    }

    /**
     * Gets column vehicle_id
     *
     * @return int
     */
    public function getVehicleId()
    {
        return $this->_VehicleId;
    }

    /**
     * Sets column group_id
     *
     * @param int $data
     * @return Messerve_Model_VehicleEmployee
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
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_VehicleEmployee
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_VehicleEmployee());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_VehicleEmployee::delete
     * @return int|boolean Number of rows deleted or boolean if doing soft delete
     */
    public function deleteRowByPrimaryKey()
    {
        $primary_key = array();
        if (! $this->getEmployeeId()) {
            throw new Exception('Primary Key EmployeeId does not contain a value');
        } else {
            $primary_key['employee_id'] = $this->getEmployeeId();
        }

        if (! $this->getVehicleId()) {
            throw new Exception('Primary Key VehicleId does not contain a value');
        } else {
            $primary_key['vehicle_id'] = $this->getVehicleId();
        }

        return $this->getMapper()->getDbTable()->delete('employee_id = '
                    . $this->getMapper()->getDbTable()->getAdapter()->quote($primary_key['employee_id'])
                    . ' AND vehicle_id = '
                    . $this->getMapper()->getDbTable()->getAdapter()->quote($primary_key['vehicle_id']));
    }
}
