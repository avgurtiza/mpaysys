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
class Messerve_Model_CalendarEntry extends Messerve_Model_ModelAbstract
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
    protected $_CalendarId;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Name;

    /**
     * Database var type enum('special','legal')
     *
     * @var string
     */
    protected $_Type;

    /**
     * Database var type char(5)
     *
     * @var string
     */
    protected $_Date;

    /**
     * Database var type char(4)
     *
     * @var string
     */
    protected $_Year;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'calendar_id'=>'CalendarId',
            'name'=>'Name',
            'type'=>'Type',
            'date'=>'Date',
            'year'=>'Year',
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
     * @return Messerve_Model_CalendarEntry
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
     * Sets column calendar_id
     *
     * @param int $data
     * @return Messerve_Model_CalendarEntry
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
     * Sets column name
     *
     * @param string $data
     * @return Messerve_Model_CalendarEntry
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
     * Sets column type
     *
     * @param string $data
     * @return Messerve_Model_CalendarEntry
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
     * Sets column date
     *
     * @param string $data
     * @return Messerve_Model_CalendarEntry
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
     * Sets column year
     *
     * @param string $data
     * @return Messerve_Model_CalendarEntry
     */
    public function setYear($data)
    {
        $this->_Year = $data;
        return $this;
    }

    /**
     * Gets column year
     *
     * @return string
     */
    public function getYear()
    {
        return $this->_Year;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_CalendarEntry
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_CalendarEntry());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_CalendarEntry::delete
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
