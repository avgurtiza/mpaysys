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
 * Data Mapper implementation for Messerve_Model_RateBak
 *
 * @package Messerve_Model
 * @subpackage Mapper
 * @author Slide Gurtiza
 */
class Messerve_Model_Mapper_RateBak extends Messerve_Model_Mapper_MapperAbstract
{
    /**
     * Returns an array, keys are the field names.
     *
     * @param Messerve_Model_RateBak $model
     * @return array
     */
    public function toArray($model)
    {
        if (! $model instanceof Messerve_Model_RateBak) {
            throw new Exception('Unable to create array: invalid model passed to mapper');
        }

        $result = array(
            'id' => $model->getId(),
            'name' => $model->getName(),
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
            'thirteenth_month_pay' => $model->getThirteenthMonthPay(),
            'incentive_5_day_leave' => $model->getIncentive5DayLeave(),
            'ecola' => $model->getEcola(),
            'sss_employee' => $model->getSssEmployee(),
            'sss_employer' => $model->getSssEmployer(),
            'philhealth_employee' => $model->getPhilhealthEmployee(),
            'philhealth_employer' => $model->getPhilhealthEmployer(),
            'ec' => $model->getEc(),
            'hdmf_employee' => $model->getHdmfEmployee(),
            'hdmf_employer' => $model->getHdmfEmployer(),
            'admin_fee' => $model->getAdminFee(),
            'fuel_allocation' => $model->getFuelAllocation(),
            'bike_rehab' => $model->getBikeRehab(),
            'bike_insurance_reg' => $model->getBikeInsuranceReg(),
            'bike_rental' => $model->getBikeRental(),
            'cash_bond' => $model->getCashBond(),
        );

        return $result;
    }

    /**
     * Returns the DbTable class associated with this mapper
     *
     * @return Messerve_Model_DbTable_RateBak
     */
    public function getDbTable()
    {
        if ($this->_dbTable === null) {
            $this->setDbTable('Messerve_Model_DbTable_RateBak');
        }

        return $this->_dbTable;
    }

    /**
     * Deletes the current model
     *
     * @param Messerve_Model_RateBak $model The model to delete
     * @see Messerve_Model_DbTable_TableAbstract::delete()
     * @return int
     */
    public function delete($model)
    {
        if (! $model instanceof Messerve_Model_RateBak) {
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
     * @param Messerve_Model_RateBak $model
     * @param boolean $ignoreEmptyValues Should empty values saved
     * @param boolean $recursive Should the object graph be walked for all related elements
     * @param boolean $useTransaction Flag to indicate if save should be done inside a database transaction
     * @return boolean If the save action was successful
     */
    public function save(Messerve_Model_RateBak $model,
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
     * @param Messerve_Model_RateBak|null $model
     * @return Messerve_Model_RateBak|null The object provided or null if not found
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
     * @param Messerve_Model_RateBak|null $entry The object to load the data into, or null to have one created
     * @return Messerve_Model_RateBak The model with the data provided
     */
    public function loadModel($data, $entry)
    {
        if ($entry === null) {
            $entry = new Messerve_Model_RateBak();
        }

        if (is_array($data)) {
            $entry->setId($data['id'])
                  ->setName($data['name'])
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
                  ->setThirteenthMonthPay($data['thirteenth_month_pay'])
                  ->setIncentive5DayLeave($data['incentive_5_day_leave'])
                  ->setEcola($data['ecola'])
                  ->setSssEmployee($data['sss_employee'])
                  ->setSssEmployer($data['sss_employer'])
                  ->setPhilhealthEmployee($data['philhealth_employee'])
                  ->setPhilhealthEmployer($data['philhealth_employer'])
                  ->setEc($data['ec'])
                  ->setHdmfEmployee($data['hdmf_employee'])
                  ->setHdmfEmployer($data['hdmf_employer'])
                  ->setAdminFee($data['admin_fee'])
                  ->setFuelAllocation($data['fuel_allocation'])
                  ->setBikeRehab($data['bike_rehab'])
                  ->setBikeInsuranceReg($data['bike_insurance_reg'])
                  ->setBikeRental($data['bike_rental'])
                  ->setCashBond($data['cash_bond']);
        } elseif ($data instanceof Zend_Db_Table_Row_Abstract || $data instanceof stdClass) {
            $entry->setId($data->id)
                  ->setName($data->name)
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
                  ->setThirteenthMonthPay($data->thirteenth_month_pay)
                  ->setIncentive5DayLeave($data->incentive_5_day_leave)
                  ->setEcola($data->ecola)
                  ->setSssEmployee($data->sss_employee)
                  ->setSssEmployer($data->sss_employer)
                  ->setPhilhealthEmployee($data->philhealth_employee)
                  ->setPhilhealthEmployer($data->philhealth_employer)
                  ->setEc($data->ec)
                  ->setHdmfEmployee($data->hdmf_employee)
                  ->setHdmfEmployer($data->hdmf_employer)
                  ->setAdminFee($data->admin_fee)
                  ->setFuelAllocation($data->fuel_allocation)
                  ->setBikeRehab($data->bike_rehab)
                  ->setBikeInsuranceReg($data->bike_insurance_reg)
                  ->setBikeRental($data->bike_rental)
                  ->setCashBond($data->cash_bond);
        }

        $entry->setMapper($this);

        return $entry;
    }
}
