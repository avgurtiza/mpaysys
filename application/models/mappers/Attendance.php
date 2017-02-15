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
 * Data Mapper implementation for Messerve_Model_Attendance
 *
 * @package Messerve_Model
 * @subpackage Mapper
 * @author Slide Gurtiza
 */
class Messerve_Model_Mapper_Attendance extends Messerve_Model_Mapper_MapperAbstract
{
    /**
     * Returns an array, keys are the field names.
     *
     * @param Messerve_Model_Attendance $model
     * @return array
     */
    public function toArray($model)
    {
        if (!$model instanceof Messerve_Model_Attendance) {
            throw new Exception('Unable to create array: invalid model passed to mapper');
        }

        $result = array(
            'id' => $model->getId(),
            'type' => $model->getType(),
            'start_1' => $model->getStart1(),
            'end_1' => $model->getEnd1(),
            'start_2' => $model->getStart2(),
            'end_2' => $model->getEnd2(),
            'start_3' => $model->getStart3(),
            'end_3' => $model->getEnd3(),
            'today' => $model->getToday(),
            'today_nd' => $model->getTodayNd(),
            'today_ot' => $model->getTodayOt(),
            'today_nd_ot' => $model->getTodayNdOt(),
            'tomorrow' => $model->getTomorrow(),
            'tomorrow_nd' => $model->getTomorrowNd(),
            'tomorrow_ot' => $model->getTomorrowOt(),
            'tomorrow_nd_ot' => $model->getTomorrowNdOt(),
            'ot_approved' => $model->getOtApproved(),
            'ot_approved_hours' => $model->getOtApprovedHours(),
            'ot_actual_hours' => $model->getOtActualHours(),
            'extended_shift' => $model->getExtendedShift(),
            'employee_id' => $model->getEmployeeId(),
            'employee_number' => $model->getEmployeeNumber(),
            'group_id' => $model->getGroupId(),
            'datetime_start' => $model->getDatetimeStart(),
            'datetime_end' => $model->getDatetimeEnd(),
            'total_hours' => $model->getTotalHours(),
            'reg' => $model->getReg(),
            'reg_nd' => $model->getRegNd(),
            'reg_ot' => $model->getRegOt(),
            'reg_nd_ot' => $model->getRegNdOt(),
            'sun' => $model->getSun(),
            'sun_nd' => $model->getSunNd(),
            'sun_ot' => $model->getSunOt(),
            'sun_nd_ot' => $model->getSunNdOt(),
            'spec' => $model->getSpec(),
            'spec_nd' => $model->getSpecNd(),
            'spec_ot' => $model->getSpecOt(),
            'spec_nd_ot' => $model->getSpecNdOt(),
            'legal' => $model->getLegal(),
            'legal_nd' => $model->getLegalNd(),
            'legal_ot' => $model->getLegalOt(),
            'legal_nd_ot' => $model->getLegalNdOt(),
            'legal_unattend' => $model->getLegalUnattend(),
            'rest' => $model->getRest(),
            'rest_nd' => $model->getRestNd(),
            'rest_ot' => $model->getRestOt(),
            'rest_nd_ot' => $model->getRestNdOt(),
            'fuel_overage' => $model->getFuelOverage(),
            'fuel_hours' => $model->getFuelHours(),
            'fuel_alloted' => $model->getFuelAlloted(),
            'fuel_consumed' => $model->getFuelConsumed(),
            'fuel_cost' => $model->getFuelCost(),
            'today_rate_id' => $model->getTodayRateId(),
            'today_rate_data' => $model->getTodayRateData(),
            'tomorrow_rate_id' => $model->getTomorrowRateId(),
            'tomorrow_rate_data' => $model->getTomorrowRateData(),
            'approved_extended_shift' => $model->getApprovedExtendedShift(),

        );

        return $result;
    }

    /**
     * Returns the DbTable class associated with this mapper
     *
     * @return Messerve_Model_DbTable_Attendance
     */
    public function getDbTable()
    {
        if ($this->_dbTable === null) {
            $this->setDbTable('Messerve_Model_DbTable_Attendance');
        }

        return $this->_dbTable;
    }

    /**
     * Deletes the current model
     *
     * @param Messerve_Model_Attendance $model The model to delete
     * @see Messerve_Model_DbTable_TableAbstract::delete()
     * @return int
     */
    public function delete($model)
    {
        if (!$model instanceof Messerve_Model_Attendance) {
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
     * @param Messerve_Model_Attendance $model
     * @param boolean $ignoreEmptyValues Should empty values saved
     * @param boolean $recursive Should the object graph be walked for all related elements
     * @param boolean $useTransaction Flag to indicate if save should be done inside a database transaction
     * @return boolean If the save action was successful
     */
    public function save(Messerve_Model_Attendance $model,
                         $ignoreEmptyValues = true, $recursive = false, $useTransaction = true
    )
    {
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
     * @param Messerve_Model_Attendance|null $model
     * @return Messerve_Model_Attendance|null The object provided or null if not found
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
     * @param Messerve_Model_Attendance|null $entry The object to load the data into, or null to have one created
     * @return Messerve_Model_Attendance The model with the data provided
     */
    public function loadModel($data, $entry)
    {
        if ($entry === null) {
            $entry = new Messerve_Model_Attendance();
        }

        if (is_array($data)) {
            $entry->setId($data['id'])
                ->setType($data['type'])
                ->setStart1($data['start_1'])
                ->setEnd1($data['end_1'])
                ->setStart2($data['start_2'])
                ->setEnd2($data['end_2'])
                ->setStart3($data['start_3'])
                ->setEnd3($data['end_3'])
                ->setToday($data['today'])
                ->setTodayNd($data['today_nd'])
                ->setTodayOt($data['today_ot'])
                ->setTodayNdOt($data['today_nd_ot'])
                ->setTomorrow($data['tomorrow'])
                ->setTomorrowNd($data['tomorrow_nd'])
                ->setTomorrowOt($data['tomorrow_ot'])
                ->setTomorrowNdOt($data['tomorrow_nd_ot'])
                ->setOtApproved($data['ot_approved'])
                ->setOtApprovedHours($data['ot_approved_hours'])
                ->setOtActualHours($data['ot_actual_hours'])
                ->setExtendedShift($data['extended_shift'])
                ->setEmployeeId($data['employee_id'])
                ->setEmployeeNumber($data['employee_number'])
                ->setGroupId($data['group_id'])
                ->setDatetimeStart($data['datetime_start'])
                ->setDatetimeEnd($data['datetime_end'])
                ->setTotalHours($data['total_hours'])
                ->setReg($data['reg'])
                ->setRegNd($data['reg_nd'])
                ->setRegOt($data['reg_ot'])
                ->setRegNdOt($data['reg_nd_ot'])
                ->setSun($data['sun'])
                ->setSunNd($data['sun_nd'])
                ->setSunOt($data['sun_ot'])
                ->setSunNdOt($data['sun_nd_ot'])
                ->setSpec($data['spec'])
                ->setSpecNd($data['spec_nd'])
                ->setSpecOt($data['spec_ot'])
                ->setSpecNdOt($data['spec_nd_ot'])
                ->setLegal($data['legal'])
                ->setLegalNd($data['legal_nd'])
                ->setLegalOt($data['legal_ot'])
                ->setLegalNdOt($data['legal_nd_ot'])
                ->setLegalUnattend($data['legal_unattend'])
                ->setRest($data['rest'])
                ->setRestNd($data['rest_nd'])
                ->setRestOt($data['rest_ot'])
                ->setRestNdOt($data['rest_nd_ot'])
                ->setFuelOverage($data['fuel_overage'])
                ->setFuelHours($data['fuel_hours'])
                ->setFuelAlloted($data['fuel_alloted'])
                ->setFuelConsumed($data['fuel_consumed'])
                ->setFuelCost($data['fuel_cost'])
                ->setTodayRateId($data['today_rate_id'])
                ->setTodayRateData($data['today_rate_data'])
                ->setTomorrowRateId($data['tomorrow_rate_id'])
                ->setTomorrowRateData($data['tomorrow_rate_data'])
                ->setApprovedExtendedShift($data['approved_extended_shift']);

        } elseif ($data instanceof Zend_Db_Table_Row_Abstract || $data instanceof stdClass) {
            $entry->setId($data->id)
                ->setType($data->type)
                ->setStart1($data->start_1)
                ->setEnd1($data->end_1)
                ->setStart2($data->start_2)
                ->setEnd2($data->end_2)
                ->setStart3($data->start_3)
                ->setEnd3($data->end_3)
                ->setToday($data->today)
                ->setTodayNd($data->today_nd)
                ->setTodayOt($data->today_ot)
                ->setTodayNdOt($data->today_nd_ot)
                ->setTomorrow($data->tomorrow)
                ->setTomorrowNd($data->tomorrow_nd)
                ->setTomorrowOt($data->tomorrow_ot)
                ->setTomorrowNdOt($data->tomorrow_nd_ot)
                ->setOtApproved($data->ot_approved)
                ->setOtApprovedHours($data->ot_approved_hours)
                ->setOtActualHours($data->ot_actual_hours)
                ->setExtendedShift($data->extended_shift)
                ->setEmployeeId($data->employee_id)
                ->setEmployeeNumber($data->employee_number)
                ->setGroupId($data->group_id)
                ->setDatetimeStart($data->datetime_start)
                ->setDatetimeEnd($data->datetime_end)
                ->setTotalHours($data->total_hours)
                ->setReg($data->reg)
                ->setRegNd($data->reg_nd)
                ->setRegOt($data->reg_ot)
                ->setRegNdOt($data->reg_nd_ot)
                ->setSun($data->sun)
                ->setSunNd($data->sun_nd)
                ->setSunOt($data->sun_ot)
                ->setSunNdOt($data->sun_nd_ot)
                ->setSpec($data->spec)
                ->setSpecNd($data->spec_nd)
                ->setSpecOt($data->spec_ot)
                ->setSpecNdOt($data->spec_nd_ot)
                ->setLegal($data->legal)
                ->setLegalNd($data->legal_nd)
                ->setLegalOt($data->legal_ot)
                ->setLegalNdOt($data->legal_nd_ot)
                ->setLegalUnattend($data->legal_unattend)
                ->setRest($data->rest)
                ->setRestNd($data->rest_nd)
                ->setRestOt($data->rest_ot)
                ->setRestNdOt($data->rest_nd_ot)
                ->setFuelOverage($data->fuel_overage)
                ->setFuelHours($data->fuel_hours)
                ->setFuelAlloted($data->fuel_alloted)
                ->setFuelConsumed($data->fuel_consumed)
                ->setFuelCost($data->fuel_cost)
                ->setTodayRateId($data->today_rate_id)
                ->setTodayRateData($data->today_rate_data)
                ->setTomorrowRateId($data->tomorrow_rate_id)
                ->setTomorrowRateData($data->tomorrow_rate_data)
                ->setApprovedExtendedShift($data->approved_extended_shift);
        }

        $entry->setMapper($this);

        return $entry;
    }
}
