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
class Messerve_Model_GroupCalendar extends Messerve_Model_ModelAbstract
{

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
    protected $_CalendarId;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'group_id'=>'GroupId',
            'calendar_id'=>'CalendarId',
        ));

        $this->setParentList(array(
        ));

        $this->setDependentList(array(
        ));
    }

    /**
     * Sets column group_id
     *
     * @param int $data
     * @return Messerve_Model_GroupCalendar
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
     * Sets column calendar_id
     *
     * @param int $data
     * @return Messerve_Model_GroupCalendar
     */
    public function setCalendarId($data)
    {
        $this->_CalendarId = $data;
        return $this;
    }

    /**
     * Gets column calendar_id
     *
     * @return int
     */
    public function getCalendarId()
    {
        return $this->_CalendarId;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_GroupCalendar
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_GroupCalendar());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_GroupCalendar::delete
     * @return int|boolean Number of rows deleted or boolean if doing soft delete
     */
    public function deleteRowByPrimaryKey()
    {
        $primary_key = array();
        if (! $this->getGroupId()) {
            throw new Exception('Primary Key GroupId does not contain a value');
        } else {
            $primary_key['group_id'] = $this->getGroupId();
        }

        if (! $this->getCalendarId()) {
            throw new Exception('Primary Key CalendarId does not contain a value');
        } else {
            $primary_key['calendar_id'] = $this->getCalendarId();
        }

        return $this->getMapper()->getDbTable()->delete('group_id = '
                    . $this->getMapper()->getDbTable()->getAdapter()->quote($primary_key['group_id'])
                    . ' AND calendar_id = '
                    . $this->getMapper()->getDbTable()->getAdapter()->quote($primary_key['calendar_id']));
    }
}
