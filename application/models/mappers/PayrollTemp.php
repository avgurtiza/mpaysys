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
 * Data Mapper implementation for Messerve_Model_PayrollTemp
 *
 * @package Messerve_Model
 * @subpackage Mapper
 * @author Slide Gurtiza
 */
class Messerve_Model_Mapper_PayrollTemp extends Messerve_Model_Mapper_MapperAbstract
{
    /**
     * Returns an array, keys are the field names.
     *
     * @param Messerve_Model_PayrollTemp $model
     * @return array
     */
    public function toArray($model)
    {
        if (!$model instanceof Messerve_Model_PayrollTemp) {
            throw new Exception('Unable to create array: invalid model passed to mapper');
        }

        $result = array(
            'id' => $model->getId(),
            'group_id' => $model->getGroupId(),
            'employee_id' => $model->getEmployeeId(),
            'employee_number' => $model->getEmployeeNumber(),
            'period_covered' => $model->getPeriodCovered(),
            'firstname' => $model->getFirstname(),
            'middlename' => $model->getMiddlename(),
            'lastname' => $model->getLastname(),
            'client_name' => $model->getClientName(),
            'group_name' => $model->getGroupName(),
            'account_number' => $model->getAccountNumber(),
            'net_pay' => $model->getNetPay(),
            'ecola' => $model->getEcola(),
            'incentives' => $model->getIncentives(),
            'sss' => $model->getSss(),
            'philhealth' => $model->getPhilhealth(),
            'hdmf' => $model->getHdmf(),
            'cash_bond' => $model->getCashBond(),
            'insurance' => $model->getInsurance(),
            'communication' => $model->getCommunication(),
            'misc_deduction' => $model->getMiscDeduction(),
            'misc_addition' => $model->getMiscAddition(),
            'paternity' => $model->getPaternity(),
            'gross_pay' => $model->getGrossPay(),
            'deduction_data' => $model->getDeductionData(),
            'is_reliever' => $model->getIsReliever(),
            'sss_loan' => $model->getSssLoan(),
            'hdmf_loan' => $model->getHdmfLoan(),
            'hdmf_calamity_loan' => $model->getHdmfCalamityLoan(),
            'accident' => $model->getAccident(),
            'uniform' => $model->getUniform(),
            'adjustment' => $model->getAdjustment(),
            'miscellaneous' => $model->getMiscellaneous(),
            'fuel_overage' => $model->getFuelOverage(),
            'fuel_addition' => $model->getFuelAddition(),
            'fuel_deduction' => $model->getFuelDeduction(),
            'fuel_allotment' => $model->getFuelAllotment(),
            'fuel_usage' => $model->getFuelUsage(),
            'fuel_hours' => $model->getFuelHours(),
            'fuel_price' => $model->getFuelPrice(),
            'thirteenth_month' => $model->getThirteenthMonth(),
            'bop_motorcycle' => $model->getBopMotorcycle(),
            'bop_insurance' => $model->getBopInsurance(),
            'bop_maintenance' => $model->getBopMaintenance(),
            'lost_card' => $model->getLostCard(),
            'food' => $model->getFood(),
            'basic_pay' => $model->getBasicPay(),
            'philhealth_basic' => $model->getPhilhealthBasic(),
            'payroll_meta' => $model->getPayrollMeta(),
            'rate_id' => $model->getRateId(),
            'updated_at' => $model->getUpdatedAt(),
        );

        return $result;
    }

    /**
     * Returns the DbTable class associated with this mapper
     *
     * @return Messerve_Model_DbTable_PayrollTemp
     */
    public function getDbTable()
    {
        if ($this->_dbTable === null) {
            $this->setDbTable('Messerve_Model_DbTable_PayrollTemp');
        }

        return $this->_dbTable;
    }

    /**
     * Deletes the current model
     *
     * @param Messerve_Model_PayrollTemp $model The model to delete
     * @see Messerve_Model_DbTable_TableAbstract::delete()
     * @return int
     */
    public function delete($model)
    {
        if (!$model instanceof Messerve_Model_PayrollTemp) {
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
     * @param Messerve_Model_PayrollTemp $model
     * @param boolean $ignoreEmptyValues Should empty values saved
     * @param boolean $recursive Should the object graph be walked for all related elements
     * @param boolean $useTransaction Flag to indicate if save should be done inside a database transaction
     * @return boolean If the save action was successful
     */
    public function save(Messerve_Model_PayrollTemp $model,
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
     * @param Messerve_Model_PayrollTemp|null $model
     * @return Messerve_Model_PayrollTemp|null The object provided or null if not found
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
     * @param Messerve_Model_PayrollTemp|null $entry The object to load the data into, or null to have one created
     * @return Messerve_Model_PayrollTemp The model with the data provided
     */
    public function loadModel($data, $entry)
    {
        if ($entry === null) {
            $entry = new Messerve_Model_PayrollTemp();
        }

        if (is_array($data)) {
            $entry->setId($data['id'])
                ->setGroupId($data['group_id'])
                ->setEmployeeId($data['employee_id'])
                ->setEmployeeNumber($data['employee_number'])
                ->setPeriodCovered($data['period_covered'])
                ->setFirstname($data['firstname'])
                ->setMiddlename($data['middlename'])
                ->setLastname($data['lastname'])
                ->setClientName($data['client_name'])
                ->setGroupName($data['group_name'])
                ->setAccountNumber($data['account_number'])
                ->setNetPay($data['net_pay'])
                ->setEcola($data['ecola'])
                ->setIncentives($data['incentives'])
                ->setSss($data['sss'])
                ->setPhilhealth($data['philhealth'])
                ->setHdmf($data['hdmf'])
                ->setCashBond($data['cash_bond'])
                ->setInsurance($data['insurance'])
                ->setCommunication($data['communication'])
                ->setMiscDeduction($data['misc_deduction'])
                ->setMiscAddition($data['misc_addition'])
                ->setGrossPay($data['gross_pay'])
                ->setDeductionData($data['deduction_data'])
                ->setIsReliever($data['is_reliever'])
                ->setSssLoan($data['sss_loan'])
                ->setHdmfLoan($data['hdmf_loan'])
                ->setHdmfCalamityLoan($data['hdmf_calamity_loan'])
                ->setAccident($data['accident'])
                ->setUniform($data['uniform'])
                ->setAdjustment($data['adjustment'])
                ->setMiscellaneous($data['miscellaneous'])
                ->setFuelOverage($data['fuel_overage'])
                ->setFuelAddition($data['fuel_addition'])
                ->setFuelDeduction($data['fuel_deduction'])
                ->setFuelAllotment($data['fuel_allotment'])
                ->setFuelUsage($data['fuel_usage'])
                ->setFuelHours($data['fuel_hours'])
                ->setFuelPrice($data['fuel_price'])
                ->setThirteenthMonth($data['thirteenth_month'])
                ->setBopMotorcycle($data['bop_motorcycle'])
                ->setBopInsurance($data['bop_insurance'])
                ->setBopMaintenance($data['bop_maintenance'])
                ->setPaternity($data['paternity'])
                ->setLostCard($data['lost_card'])
                ->setFood($data['food'])
                ->setBasicPay($data['basic_pay'])
                ->setPhilhealthbasic($data['philhealth_basic'])
                ->setPayrollMeta($data['payroll_meta'])
                ->setRateId($data['rate_id'])
                ->setUpdatedAt($data['updated_at'])
            ;
        } elseif ($data instanceof Zend_Db_Table_Row_Abstract || $data instanceof stdClass) {
            $entry->setId($data->id)
                ->setGroupId($data->group_id)
                ->setEmployeeId($data->employee_id)
                ->setEmployeeNumber($data->employee_number)
                ->setPeriodCovered($data->period_covered)
                ->setFirstname($data->firstname)
                ->setMiddlename($data->middlename)
                ->setLastname($data->lastname)
                ->setClientName($data->client_name)
                ->setGroupName($data->group_name)
                ->setAccountNumber($data->account_number)
                ->setNetPay($data->net_pay)
                ->setEcola($data->ecola)
                ->setIncentives($data->incentives)
                ->setSss($data->sss)
                ->setPhilhealth($data->philhealth)
                ->setHdmf($data->hdmf)
                ->setCashBond($data->cash_bond)
                ->setInsurance($data->insurance)
                ->setCommunication($data->communication)
                ->setMiscDeduction($data->misc_deduction)
                ->setMiscAddition($data->misc_addition)
                ->setGrossPay($data->gross_pay)
                ->setDeductionData($data->deduction_data)
                ->setIsReliever($data->is_reliever)
                ->setSssLoan($data->sss_loan)
                ->setHdmfLoan($data->hdmf_loan)
                ->setHdmfCalamityLoan($data->hdmf_calamity_loan)
                ->setAccident($data->accident)
                ->setUniform($data->uniform)
                ->setAdjustment($data->adjustment)
                ->setMiscellaneous($data->miscellaneous)
                ->setFuelOverage($data->fuel_overage)
                ->setFuelAddition($data->fuel_addition)
                ->setFuelDeduction($data->fuel_deduction)
                ->setFuelAllotment($data->fuel_allotment)
                ->setFuelUsage($data->fuel_usage)
                ->setFuelHours($data->fuel_hours)
                ->setFuelPrice($data->fuel_price)
                ->setThirteenthMonth($data->thirteenth_month)
                ->setBopMotorcycle($data->bop_motorcycle)
                ->setBopInsurance($data->bop_insurance)
                ->setBopMaintenance($data->bop_maintenance)
                ->setPaternity($data->paternity)
                ->setLostCard($data->lost_card)
                ->setFood($data->food)
                ->setBasicPay($data->basic_pay)
                ->setPhilhealthbasic($data->philhealth_basic)
                ->setPayrollMeta($data->payroll_meta)
                ->setRateId($data->rate_id)
                ->setUpdatedAt($data->updated_at)
            ;
        }

        $entry->setMapper($this);

        return $entry;
    }
}
