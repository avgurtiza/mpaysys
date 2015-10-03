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
class Messerve_Model_UserGroup extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_UserId;

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
            'user_id'=>'UserId',
            'group_id'=>'GroupId',
        ));

        $this->setParentList(array(
        ));

        $this->setDependentList(array(
        ));
    }

    /**
     * Sets column user_id
     *
     * @param int $data
     * @return Messerve_Model_UserGroup
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
     * Sets column group_id
     *
     * @param int $data
     * @return Messerve_Model_UserGroup
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
     * @return Messerve_Model_Mapper_UserGroup
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_UserGroup());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_UserGroup::delete
     * @return int|boolean Number of rows deleted or boolean if doing soft delete
     */
    public function deleteRowByPrimaryKey()
    {
        $primary_key = array();
        if (! $this->getUserId()) {
            throw new Exception('Primary Key UserId does not contain a value');
        } else {
            $primary_key['user_id'] = $this->getUserId();
        }

        if (! $this->getGroupId()) {
            throw new Exception('Primary Key GroupId does not contain a value');
        } else {
            $primary_key['group_id'] = $this->getGroupId();
        }

        return $this->getMapper()->getDbTable()->delete('user_id = '
                    . $this->getMapper()->getDbTable()->getAdapter()->quote($primary_key['user_id'])
                    . ' AND group_id = '
                    . $this->getMapper()->getDbTable()->getAdapter()->quote($primary_key['group_id']));
    }
}
