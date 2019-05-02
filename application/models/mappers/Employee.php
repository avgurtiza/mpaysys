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
 * Data Mapper implementation for Messerve_Model_Employee
 *
 * @package Messerve_Model
 * @subpackage Mapper
 * @author Slide Gurtiza
 */
class Messerve_Model_Mapper_Employee extends Messerve_Model_Mapper_MapperAbstract
{
    /**
     * Returns an array, keys are the field names.
     *
     * @param Messerve_Model_Employee $model
     * @return array
     */
    public function toArray($model)
    {
        if (! $model instanceof Messerve_Model_Employee) {
            throw new Exception('Unable to create array: invalid model passed to mapper');
        }

        $result = array(
            'id' => $model->getId(),
            'type' => $model->getType(),
            'employee_number' => $model->getEmployeeNumber(),
            'firstname' => $model->getFirstname(),
            'lastname' => $model->getLastname(),
            'middleinitial' => $model->getMiddleinitial(),
            'account_number' => $model->getAccountNumber(),
            'tin' => $model->getTin(),
            'sss' => $model->getSss(),
            'hdmf' => $model->getHdmf(),
            'philhealth' => $model->getPhilhealth(),
            'group_id' => $model->getGroupId(),
            'rate_id' => $model->getRateId(),
            'dateemployed' => $model->getDateemployed(),
            'bike_rehab_end' => $model->getBikeRehabEnd(),
            'bike_insurance_reg_end' => $model->getBikeInsuranceRegEnd(),
            'search' => $model->getSearch(),
            'bop_id' => $model->getBopId(),
            'bop_start' => $model->getBopStart(),
            'bop_current_rider_start' => $model->getBopCurrentRiderStart(),
            'bop_startingbalance' => $model->getBopStartingbalance(),
            'bop_currentbalance' => $model->getBopCurrentbalance(),
            'gascard' => $model->getGascard(),
            'gascard2' => $model->getGascard2(),
            'gascard3' => $model->getGascard3(),
        );

        return $result;
    }

    /**
     * Returns the DbTable class associated with this mapper
     *
     * @return Messerve_Model_DbTable_Employee
     */
    public function getDbTable()
    {
        if ($this->_dbTable === null) {
            $this->setDbTable('Messerve_Model_DbTable_Employee');
        }

        return $this->_dbTable;
    }

    /**
     * Deletes the current model
     *
     * @param Messerve_Model_Employee $model The model to delete
     * @see Messerve_Model_DbTable_TableAbstract::delete()
     * @return int
     */
    public function delete($model)
    {
        if (! $model instanceof Messerve_Model_Employee) {
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
     * @param Messerve_Model_Employee $model
     * @param boolean $ignoreEmptyValues Should empty values saved
     * @param boolean $recursive Should the object graph be walked for all related elements
     * @param boolean $useTransaction Flag to indicate if save should be done inside a database transaction
     * @return boolean If the save action was successful
     */
    public function save(Messerve_Model_Employee $model,
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
     * @param Messerve_Model_Employee|null $model
     * @return Messerve_Model_Employee|null The object provided or null if not found
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
     * @param Messerve_Model_Employee|null $entry The object to load the data into, or null to have one created
     * @return Messerve_Model_Employee The model with the data provided
     */
    public function loadModel($data, $entry)
    {
        if ($entry === null) {
            $entry = new Messerve_Model_Employee();
        }

        if (is_array($data)) {
            $entry->setId($data['id'])
                  ->setType($data['type'])
                  ->setEmployeeNumber($data['employee_number'])
                  ->setFirstname($data['firstname'])
                  ->setLastname($data['lastname'])
                  ->setMiddleinitial($data['middleinitial'])
                  ->setAccountNumber($data['account_number'])
                  ->setTin($data['tin'])
                  ->setSss($data['sss'])
                  ->setHdmf($data['hdmf'])
                  ->setPhilhealth($data['philhealth'])
                  ->setGroupId($data['group_id'])
                  ->setRateId($data['rate_id'])
                  ->setDateemployed($data['dateemployed'])
                  ->setBikeRehabEnd($data['bike_rehab_end'])
                  ->setBikeInsuranceRegEnd($data['bike_insurance_reg_end'])
                  ->setSearch($data['search'])
                  ->setBopId($data['bop_id'])
                  ->setBopStart($data['bop_start'])
                  ->setBopCurrentRiderStart($data['bop_current_rider_start'])
                  ->setBopStartingbalance($data['bop_startingbalance'])
                  ->setBopCurrentbalance($data['bop_currentbalance'])
                  ->setGascard($data['gascard'])
                  ->setGascard2($data['gascard2'])
                  ->setGascard3($data['gascard3']);
        } elseif ($data instanceof Zend_Db_Table_Row_Abstract || $data instanceof stdClass) {
            $entry->setId($data->id)
                  ->setType($data->type)
                  ->setEmployeeNumber($data->employee_number)
                  ->setFirstname($data->firstname)
                  ->setLastname($data->lastname)
                  ->setMiddleinitial($data->middleinitial)
                  ->setAccountNumber($data->account_number)
                  ->setTin($data->tin)
                  ->setSss($data->sss)
                  ->setHdmf($data->hdmf)
                  ->setPhilhealth($data->philhealth)
                  ->setGroupId($data->group_id)
                  ->setRateId($data->rate_id)
                  ->setDateemployed($data->dateemployed)
                  ->setBikeRehabEnd($data->bike_rehab_end)
                  ->setBikeInsuranceRegEnd($data->bike_insurance_reg_end)
                  ->setSearch($data->search)
                  ->setBopId($data->bop_id)
                  ->setBopStart($data->bop_start)
                  ->setBopCurrentRiderStart($data->bop_current_rider_start)
                  ->setBopStartingbalance($data->bop_startingbalance)
                  ->setBopCurrentbalance($data->bop_currentbalance)
                  ->setGascard($data->gascard)
                  ->setGascard2($data->gascard2)
                  ->setGascard3($data->gascard3);
        }

        $entry->setMapper($this);

        return $entry;
    }
}
