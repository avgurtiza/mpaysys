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
 * Data Mapper implementation for Messerve_Model_DeductionAttendance
 *
 * @package Messerve_Model
 * @subpackage Mapper
 * @author Slide Gurtiza
 */
class Messerve_Model_Mapper_DeductionAttendance extends Messerve_Model_Mapper_MapperAbstract
{
    /**
     * Returns an array, keys are the field names.
     *
     * @param Messerve_Model_DeductionAttendance $model
     * @return array
     */
    public function toArray($model)
    {
        if (! $model instanceof Messerve_Model_DeductionAttendance) {
            throw new Exception('Unable to create array: invalid model passed to mapper');
        }

        $result = array(
            'deduction_schedule_id' => $model->getDeductionScheduleId(),
            'attendance_id' => $model->getAttendanceId(),
            'amount' => $model->getAmount(),
        );

        return $result;
    }

    /**
     * Returns the DbTable class associated with this mapper
     *
     * @return Messerve_Model_DbTable_DeductionAttendance
     */
    public function getDbTable()
    {
        if ($this->_dbTable === null) {
            $this->setDbTable('Messerve_Model_DbTable_DeductionAttendance');
        }

        return $this->_dbTable;
    }

    /**
     * Deletes the current model
     *
     * @param Messerve_Model_DeductionAttendance $model The model to delete
     * @see Messerve_Model_DbTable_TableAbstract::delete()
     * @return int
     */
    public function delete($model)
    {
        if (! $model instanceof Messerve_Model_DeductionAttendance) {
            throw new Exception('Unable to delete: invalid model passed to mapper');
        }

        $this->getDbTable()->getAdapter()->beginTransaction();
        try {
            $where = array();
        
            $pk_val = $model->getDeductionScheduleId();
            if ($pk_val === null) {
                throw new Exception('The value for DeductionScheduleId cannot be null');
            } else {
                $where[] = $this->getDbTable()->getAdapter()->quoteInto('deduction_schedule_id = ?', $pk_val);
            }

            $pk_val = $model->getAttendanceId();
            if ($pk_val === null) {
                throw new Exception('The value for AttendanceId cannot be null');
            } else {
                $where[] = $this->getDbTable()->getAdapter()->quoteInto('attendance_id = ?', $pk_val);
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
     * @param Messerve_Model_DeductionAttendance $model
     * @param boolean $ignoreEmptyValues Should empty values saved
     * @param boolean $recursive Should the object graph be walked for all related elements
     * @param boolean $useTransaction Flag to indicate if save should be done inside a database transaction
     * @return boolean If the save action was successful
     */
    public function save(Messerve_Model_DeductionAttendance $model,
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

        $pk_val = $model->getDeductionScheduleId();
        if ($pk_val === null) {
            return false;
        } else {
            $primary_key['deduction_schedule_id'] = $pk_val;
        }

        $pk_val = $model->getAttendanceId();
        if ($pk_val === null) {
            return false;
        } else {
            $primary_key['attendance_id'] = $pk_val;
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
                                 'deduction_schedule_id = ?' => $primary_key['deduction_schedule_id'],
                                 'attendance_id = ?' => $primary_key['attendance_id']
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
     * @param Messerve_Model_DeductionAttendance|null $model
     * @return Messerve_Model_DeductionAttendance|null The object provided or null if not found
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
     * @param Messerve_Model_DeductionAttendance|null $entry The object to load the data into, or null to have one created
     * @return Messerve_Model_DeductionAttendance The model with the data provided
     */
    public function loadModel($data, $entry)
    {
        if ($entry === null) {
            $entry = new Messerve_Model_DeductionAttendance();
        }

        if (is_array($data)) {
            $entry->setDeductionScheduleId($data['deduction_schedule_id'])
                  ->setAttendanceId($data['attendance_id'])
                  ->setAmount($data['amount']);
        } elseif ($data instanceof Zend_Db_Table_Row_Abstract || $data instanceof stdClass) {
            $entry->setDeductionScheduleId($data->deduction_schedule_id)
                  ->setAttendanceId($data->attendance_id)
                  ->setAmount($data->amount);
        }

        $entry->setMapper($this);

        return $entry;
    }
}
