<?php

/**
 * Application Model Mappers
 *
 * @package Messerve_Model
 * @subpackage Mapper
 * @author Slide Gurtiza
 * @copyright Slide Gurtiza
 * @license All rights reserved
 */

/**
 * Data Mapper implementation for Messerve_Model_GroupCalendar
 *
 * @package Messerve_Model
 * @subpackage Mapper
 * @author Slide Gurtiza
 */
class Messerve_Model_Mapper_GroupCalendar extends Messerve_Model_Mapper_MapperAbstract
{
    /**
     * Returns an array, keys are the field names.
     *
     * @param Messerve_Model_GroupCalendar $model
     * @return array
     */
    public function toArray($model)
    {
        if (! $model instanceof Messerve_Model_GroupCalendar) {
            throw new Exception('Unable to create array: invalid model passed to mapper');
        }

        $result = array(
            'group_id' => $model->getGroupId(),
            'calendar_id' => $model->getCalendarId(),
        );

        return $result;
    }

    /**
     * Returns the DbTable class associated with this mapper
     *
     * @return Messerve_Model_DbTable_GroupCalendar
     */
    public function getDbTable()
    {
        if ($this->_dbTable === null) {
            $this->setDbTable('Messerve_Model_DbTable_GroupCalendar');
        }

        return $this->_dbTable;
    }

    /**
     * Deletes the current model
     *
     * @param Messerve_Model_GroupCalendar $model The model to delete
     * @see Messerve_Model_DbTable_TableAbstract::delete()
     * @return int
     */
    public function delete($model)
    {
        if (! $model instanceof Messerve_Model_GroupCalendar) {
            throw new Exception('Unable to delete: invalid model passed to mapper');
        }

        $this->getDbTable()->getAdapter()->beginTransaction();
        try {
            $where = array();
        
            $pk_val = $model->getGroupId();
            if ($pk_val === null) {
                throw new Exception('The value for GroupId cannot be null');
            } else {
                $where[] = $this->getDbTable()->getAdapter()->quoteInto('group_id = ?', $pk_val);
            }

            $pk_val = $model->getCalendarId();
            if ($pk_val === null) {
                throw new Exception('The value for CalendarId cannot be null');
            } else {
                $where[] = $this->getDbTable()->getAdapter()->quoteInto('calendar_id = ?', $pk_val);
            }
            $result = $this->getDbTable()->delete($where);

            $this->getDbTable()->getAdapter()->commit();
        } catch (Exception $e) {
            $this->getDbTable()->getAdapter()->rollback();
            $result = false;
        }

        return $result;
    }

    /**
     * Saves current row, and optionally dependent rows
     *
     * @param Messerve_Model_GroupCalendar $model
     * @param boolean $ignoreEmptyValues Should empty values saved
     * @param boolean $recursive Should the object graph be walked for all related elements
     * @param boolean $useTransaction Flag to indicate if save should be done inside a database transaction
     * @return boolean If the save action was successful
     */
    public function save(Messerve_Model_GroupCalendar $model,
        $ignoreEmptyValues = true, $recursive = false, $useTransaction = true
    ) {
        $data = $model->toArray();
        if ($ignoreEmptyValues) {
            foreach ($data as $key => $value) {
                if ($value === null or $value === '') {
                    unset($data[$key]);
                }
            }
        }

        $primary_key = array();

        $pk_val = $model->getGroupId();
        if ($pk_val === null) {
            return false;
        } else {
            $primary_key['group_id'] = $pk_val;
        }

        $pk_val = $model->getCalendarId();
        if ($pk_val === null) {
            return false;
        } else {
            $primary_key['calendar_id'] = $pk_val;
        }

        $exists = $this->find($primary_key, null);
        $success = true;

        if ($useTransaction) {
            $this->getDbTable()->getAdapter()->beginTransaction();
        }

        try {
            // Check for current existence to know if needs to be inserted
            if ($exists === null) {
                $this->getDbTable()->insert($data);
            } else {
                $this->getDbTable()
                     ->update($data,
                              array(
                                 'group_id = ?' => $primary_key['group_id'],
                                 'calendar_id = ?' => $primary_key['calendar_id']
                              )
                );
            }

            if ($useTransaction && $success) {
                $this->getDbTable()->getAdapter()->commit();
            } elseif ($useTransaction) {
                $this->getDbTable()->getAdapter()->rollback();
            }

        } catch (Exception $e) {
            if ($useTransaction) {
                $this->getDbTable()->getAdapter()->rollback();
            }

            $success = false;
        }

        return $success;
    }

    /**
     * Finds row by primary key
     *
     * @param array $primary_key
     * @param Messerve_Model_GroupCalendar|null $model
     * @return Messerve_Model_GroupCalendar|null The object provided or null if not found
     */
    public function find($primary_key, $model)
    {
        $result = $this->getRowset($primary_key);

        if (is_null($result)) {
            return null;
        }

        $row = $result->current();

        $model = $this->loadModel($row, $model);

        return $model;
    }

    /**
     * Loads the model specific data into the model object
     *
     * @param Zend_Db_Table_Row_Abstract|array $data The data as returned from a Zend_Db query
     * @param Messerve_Model_GroupCalendar|null $entry The object to load the data into, or null to have one created
     * @return Messerve_Model_GroupCalendar The model with the data provided
     */
    public function loadModel($data, $entry)
    {
        if ($entry === null) {
            $entry = new Messerve_Model_GroupCalendar();
        }

        if (is_array($data)) {
            $entry->setGroupId($data['group_id'])
                  ->setCalendarId($data['calendar_id']);
        } elseif ($data instanceof Zend_Db_Table_Row_Abstract || $data instanceof stdClass) {
            $entry->setGroupId($data->group_id)
                  ->setCalendarId($data->calendar_id);
        }

        $entry->setMapper($this);

        return $entry;
    }
}
