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
class Messerve_Model_EmployeeRateSchedule extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Id;

    /**
     * Database var type date
     *
     * @var string
     */
    protected $_DateActive;

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
     * Database var type int(11)
     *
     * @var int
     */
    protected $_ClientRateId;

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
            'date_active'=>'DateActive',
            'group_id'=>'GroupId',
            'rate_id'=>'RateId',
            'client_rate_id'=>'ClientRateId',
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
     * @return Messerve_Model_EmployeeRateSchedule
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
     * Sets column date_active
     *
     * @param string $data
     * @return Messerve_Model_EmployeeRateSchedule
     */
    public function setDateActive($data)
    {
        $this->_DateActive = $data;
        return $this;
    }

    /**
     * Gets column date_active
     *
     * @return string
     */
    public function getDateActive()
    {
        return $this->_DateActive;
    }

    /**
     * Sets column group_id
     *
     * @param int $data
     * @return Messerve_Model_EmployeeRateSchedule
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
     * @return Messerve_Model_EmployeeRateSchedule
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
     * @return Messerve_Model_EmployeeRateSchedule
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
     * Sets column notes
     *
     * @param string $data
     * @return Messerve_Model_EmployeeRateSchedule
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
     * @return Messerve_Model_Mapper_EmployeeRateSchedule
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_EmployeeRateSchedule());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_EmployeeRateSchedule::delete
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
