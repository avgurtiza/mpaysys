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
class Messerve_Model_Client extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Id;

    /**
     * Database var type varchar(128)
     *
     * @var string
     */
    protected $_Name;

    /**
     * Database var type text
     *
     * @var string
     */
    protected $_Notes;

    /**
     * Database var type tinyint(4)
     *
     * @var int
     */
    protected $_NoVat;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'name'=>'Name',
            'notes'=>'Notes',
            'no_vat'=>'NoVat',
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
     * @return Messerve_Model_Client
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
     * Sets column name
     *
     * @param string $data
     * @return Messerve_Model_Client
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
     * Sets column notes
     *
     * @param string $data
     * @return Messerve_Model_Client
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
     * Sets column no_vat
     *
     * @param int $data
     * @return Messerve_Model_Client
     */
    public function setNoVat($data)
    {
        $this->_NoVat = $data;
        return $this;
    }

    /**
     * Gets column no_vat
     *
     * @return int
     */
    public function getNoVat()
    {
        return $this->_NoVat;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_Client
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_Client());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_Client::delete
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
