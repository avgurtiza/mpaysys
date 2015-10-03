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
 * Data Mapper implementation for Messerve_Model_Vehicle
 *
 * @package Messerve_Model
 * @subpackage Mapper
 * @author Slide Gurtiza
 */
class Messerve_Model_Mapper_Vehicle extends Messerve_Model_Mapper_MapperAbstract
{
    /**
     * Returns an array, keys are the field names.
     *
     * @param Messerve_Model_Vehicle $model
     * @return array
     */
    public function toArray($model)
    {
        if (! $model instanceof Messerve_Model_Vehicle) {
            throw new Exception('Unable to create array: invalid model passed to mapper');
        }

        $result = array(
            'id' => $model->getId(),
            'year' => $model->getYear(),
            'makemodel' => $model->getMakemodel(),
            'plate_no' => $model->getPlateNo(),
            'engine_no' => $model->getEngineNo(),
            'date_purchased' => $model->getDatePurchased(),
            'date_decommissioned' => $model->getDateDecommissioned(),
            'employee_id' => $model->getEmployeeId(),
            'employee' => $model->getEmployee(),
            'group' => $model->getGroup(),
            'client' => $model->getClient(),
        );

        return $result;
    }

    /**
     * Returns the DbTable class associated with this mapper
     *
     * @return Messerve_Model_DbTable_Vehicle
     */
    public function getDbTable()
    {
        if ($this->_dbTable === null) {
            $this->setDbTable('Messerve_Model_DbTable_Vehicle');
        }

        return $this->_dbTable;
    }

    /**
     * Deletes the current model
     *
     * @param Messerve_Model_Vehicle $model The model to delete
     * @see Messerve_Model_DbTable_TableAbstract::delete()
     * @return int
     */
    public function delete($model)
    {
        if (! $model instanceof Messerve_Model_Vehicle) {
            throw new Exception('Unable to delete: invalid model passed to mapper');
        }

        $this->getDbTable()->getAdapter()->beginTransaction();
        try {
            $where = $this->getDbTable()->getAdapter()->quoteInto('id = ?', $model->getId());
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
     * @param Messerve_Model_Vehicle $model
     * @param boolean $ignoreEmptyValues Should empty values saved
     * @param boolean $recursive Should the object graph be walked for all related elements
     * @param boolean $useTransaction Flag to indicate if save should be done inside a database transaction
     * @return boolean If the save action was successful
     */
    public function save(Messerve_Model_Vehicle $model,
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

        $primary_key = $model->getId();
        $success = true;

        if ($useTransaction) {
            $this->getDbTable()->getAdapter()->beginTransaction();
        }

        unset($data['id']);

        try {
            if ($primary_key === null) {
                $primary_key = $this->getDbTable()->insert($data);
                if ($primary_key) {
                    $model->setId($primary_key);
                } else {
                    $success = false;
                }
            } else {
                $this->getDbTable()
                     ->update($data,
                              array(
                                 'id = ?' => $primary_key
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
     * @param int $primary_key
     * @param Messerve_Model_Vehicle|null $model
     * @return Messerve_Model_Vehicle|null The object provided or null if not found
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
     * @param Messerve_Model_Vehicle|null $entry The object to load the data into, or null to have one created
     * @return Messerve_Model_Vehicle The model with the data provided
     */
    public function loadModel($data, $entry)
    {
        if ($entry === null) {
            $entry = new Messerve_Model_Vehicle();
        }

        if (is_array($data)) {
            $entry->setId($data['id'])
                  ->setYear($data['year'])
                  ->setMakemodel($data['makemodel'])
                  ->setPlateNo($data['plate_no'])
                  ->setEngineNo($data['engine_no'])
                  ->setDatePurchased($data['date_purchased'])
                  ->setDateDecommissioned($data['date_decommissioned'])
                  ->setEmployeeId($data['employee_id'])
                  ->setEmployee($data['employee'])
                  ->setGroup($data['group'])
                  ->setClient($data['client']);
        } elseif ($data instanceof Zend_Db_Table_Row_Abstract || $data instanceof stdClass) {
            $entry->setId($data->id)
                  ->setYear($data->year)
                  ->setMakemodel($data->makemodel)
                  ->setPlateNo($data->plate_no)
                  ->setEngineNo($data->engine_no)
                  ->setDatePurchased($data->date_purchased)
                  ->setDateDecommissioned($data->date_decommissioned)
                  ->setEmployeeId($data->employee_id)
                  ->setEmployee($data->employee)
                  ->setGroup($data->group)
                  ->setClient($data->client);
        }

        $entry->setMapper($this);

        return $entry;
    }
}
