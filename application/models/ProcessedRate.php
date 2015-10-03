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
class Messerve_Model_ProcessedRate extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Id;

    /**
     * Database var type varchar(64)
     *
     * @var string
     */
    protected $_Name;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Reg;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_RegNd;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_RegOt;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_RegNdOt;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Sun;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_SunNd;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_SunOt;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_SunNdOt;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Spec;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_SpecNd;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_SpecOt;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_SpecNdOt;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Legal;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_LegalNd;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_LegalOt;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_LegalNdOt;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_LegalUnattend;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_RestOt;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_RestNdOt;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_ThirteenthMonthPay;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Incentive5DayLeave;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Ecola;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_SssEmployee;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_SssEmployer;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_PhilhealthEmployee;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_PhilhealthEmployer;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_Ec;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_HdmfEmployee;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_HdmfEmployer;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_AdminFee;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_FuelAllocation;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_BikeRehab;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_BikeInsuranceReg;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_BikeRental;

    /**
     * Database var type float
     *
     * @var float
     */
    protected $_CashBond;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'name'=>'Name',
            'reg'=>'Reg',
            'reg_nd'=>'RegNd',
            'reg_ot'=>'RegOt',
            'reg_nd_ot'=>'RegNdOt',
            'sun'=>'Sun',
            'sun_nd'=>'SunNd',
            'sun_ot'=>'SunOt',
            'sun_nd_ot'=>'SunNdOt',
            'spec'=>'Spec',
            'spec_nd'=>'SpecNd',
            'spec_ot'=>'SpecOt',
            'spec_nd_ot'=>'SpecNdOt',
            'legal'=>'Legal',
            'legal_nd'=>'LegalNd',
            'legal_ot'=>'LegalOt',
            'legal_nd_ot'=>'LegalNdOt',
            'legal_unattend'=>'LegalUnattend',
            'rest_ot'=>'RestOt',
            'rest_nd_ot'=>'RestNdOt',
            'thirteenth_month_pay'=>'ThirteenthMonthPay',
            'incentive_5_day_leave'=>'Incentive5DayLeave',
            'ecola'=>'Ecola',
            'sss_employee'=>'SssEmployee',
            'sss_employer'=>'SssEmployer',
            'philhealth_employee'=>'PhilhealthEmployee',
            'philhealth_employer'=>'PhilhealthEmployer',
            'ec'=>'Ec',
            'hdmf_employee'=>'HdmfEmployee',
            'hdmf_employer'=>'HdmfEmployer',
            'admin_fee'=>'AdminFee',
            'fuel_allocation'=>'FuelAllocation',
            'bike_rehab'=>'BikeRehab',
            'bike_insurance_reg'=>'BikeInsuranceReg',
            'bike_rental'=>'BikeRental',
            'cash_bond'=>'CashBond',
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
     * @return Messerve_Model_ProcessedRate
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
     * @return Messerve_Model_ProcessedRate
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
     * Sets column reg
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setReg($data)
    {
        $this->_Reg = $data;
        return $this;
    }

    /**
     * Gets column reg
     *
     * @return float
     */
    public function getReg()
    {
        return $this->_Reg;
    }

    /**
     * Sets column reg_nd
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setRegNd($data)
    {
        $this->_RegNd = $data;
        return $this;
    }

    /**
     * Gets column reg_nd
     *
     * @return float
     */
    public function getRegNd()
    {
        return $this->_RegNd;
    }

    /**
     * Sets column reg_ot
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setRegOt($data)
    {
        $this->_RegOt = $data;
        return $this;
    }

    /**
     * Gets column reg_ot
     *
     * @return float
     */
    public function getRegOt()
    {
        return $this->_RegOt;
    }

    /**
     * Sets column reg_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setRegNdOt($data)
    {
        $this->_RegNdOt = $data;
        return $this;
    }

    /**
     * Gets column reg_nd_ot
     *
     * @return float
     */
    public function getRegNdOt()
    {
        return $this->_RegNdOt;
    }

    /**
     * Sets column sun
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setSun($data)
    {
        $this->_Sun = $data;
        return $this;
    }

    /**
     * Gets column sun
     *
     * @return float
     */
    public function getSun()
    {
        return $this->_Sun;
    }

    /**
     * Sets column sun_nd
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setSunNd($data)
    {
        $this->_SunNd = $data;
        return $this;
    }

    /**
     * Gets column sun_nd
     *
     * @return float
     */
    public function getSunNd()
    {
        return $this->_SunNd;
    }

    /**
     * Sets column sun_ot
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setSunOt($data)
    {
        $this->_SunOt = $data;
        return $this;
    }

    /**
     * Gets column sun_ot
     *
     * @return float
     */
    public function getSunOt()
    {
        return $this->_SunOt;
    }

    /**
     * Sets column sun_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setSunNdOt($data)
    {
        $this->_SunNdOt = $data;
        return $this;
    }

    /**
     * Gets column sun_nd_ot
     *
     * @return float
     */
    public function getSunNdOt()
    {
        return $this->_SunNdOt;
    }

    /**
     * Sets column spec
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setSpec($data)
    {
        $this->_Spec = $data;
        return $this;
    }

    /**
     * Gets column spec
     *
     * @return float
     */
    public function getSpec()
    {
        return $this->_Spec;
    }

    /**
     * Sets column spec_nd
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setSpecNd($data)
    {
        $this->_SpecNd = $data;
        return $this;
    }

    /**
     * Gets column spec_nd
     *
     * @return float
     */
    public function getSpecNd()
    {
        return $this->_SpecNd;
    }

    /**
     * Sets column spec_ot
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setSpecOt($data)
    {
        $this->_SpecOt = $data;
        return $this;
    }

    /**
     * Gets column spec_ot
     *
     * @return float
     */
    public function getSpecOt()
    {
        return $this->_SpecOt;
    }

    /**
     * Sets column spec_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setSpecNdOt($data)
    {
        $this->_SpecNdOt = $data;
        return $this;
    }

    /**
     * Gets column spec_nd_ot
     *
     * @return float
     */
    public function getSpecNdOt()
    {
        return $this->_SpecNdOt;
    }

    /**
     * Sets column legal
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setLegal($data)
    {
        $this->_Legal = $data;
        return $this;
    }

    /**
     * Gets column legal
     *
     * @return float
     */
    public function getLegal()
    {
        return $this->_Legal;
    }

    /**
     * Sets column legal_nd
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setLegalNd($data)
    {
        $this->_LegalNd = $data;
        return $this;
    }

    /**
     * Gets column legal_nd
     *
     * @return float
     */
    public function getLegalNd()
    {
        return $this->_LegalNd;
    }

    /**
     * Sets column legal_ot
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setLegalOt($data)
    {
        $this->_LegalOt = $data;
        return $this;
    }

    /**
     * Gets column legal_ot
     *
     * @return float
     */
    public function getLegalOt()
    {
        return $this->_LegalOt;
    }

    /**
     * Sets column legal_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setLegalNdOt($data)
    {
        $this->_LegalNdOt = $data;
        return $this;
    }

    /**
     * Gets column legal_nd_ot
     *
     * @return float
     */
    public function getLegalNdOt()
    {
        return $this->_LegalNdOt;
    }

    /**
     * Sets column legal_unattend
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setLegalUnattend($data)
    {
        $this->_LegalUnattend = $data;
        return $this;
    }

    /**
     * Gets column legal_unattend
     *
     * @return float
     */
    public function getLegalUnattend()
    {
        return $this->_LegalUnattend;
    }

    /**
     * Sets column rest_ot
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setRestOt($data)
    {
        $this->_RestOt = $data;
        return $this;
    }

    /**
     * Gets column rest_ot
     *
     * @return float
     */
    public function getRestOt()
    {
        return $this->_RestOt;
    }

    /**
     * Sets column rest_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setRestNdOt($data)
    {
        $this->_RestNdOt = $data;
        return $this;
    }

    /**
     * Gets column rest_nd_ot
     *
     * @return float
     */
    public function getRestNdOt()
    {
        return $this->_RestNdOt;
    }

    /**
     * Sets column thirteenth_month_pay
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setThirteenthMonthPay($data)
    {
        $this->_ThirteenthMonthPay = $data;
        return $this;
    }

    /**
     * Gets column thirteenth_month_pay
     *
     * @return float
     */
    public function getThirteenthMonthPay()
    {
        return $this->_ThirteenthMonthPay;
    }

    /**
     * Sets column incentive_5_day_leave
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setIncentive5DayLeave($data)
    {
        $this->_Incentive5DayLeave = $data;
        return $this;
    }

    /**
     * Gets column incentive_5_day_leave
     *
     * @return float
     */
    public function getIncentive5DayLeave()
    {
        return $this->_Incentive5DayLeave;
    }

    /**
     * Sets column ecola
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setEcola($data)
    {
        $this->_Ecola = $data;
        return $this;
    }

    /**
     * Gets column ecola
     *
     * @return float
     */
    public function getEcola()
    {
        return $this->_Ecola;
    }

    /**
     * Sets column sss_employee
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setSssEmployee($data)
    {
        $this->_SssEmployee = $data;
        return $this;
    }

    /**
     * Gets column sss_employee
     *
     * @return float
     */
    public function getSssEmployee()
    {
        return $this->_SssEmployee;
    }

    /**
     * Sets column sss_employer
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setSssEmployer($data)
    {
        $this->_SssEmployer = $data;
        return $this;
    }

    /**
     * Gets column sss_employer
     *
     * @return float
     */
    public function getSssEmployer()
    {
        return $this->_SssEmployer;
    }

    /**
     * Sets column philhealth_employee
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setPhilhealthEmployee($data)
    {
        $this->_PhilhealthEmployee = $data;
        return $this;
    }

    /**
     * Gets column philhealth_employee
     *
     * @return float
     */
    public function getPhilhealthEmployee()
    {
        return $this->_PhilhealthEmployee;
    }

    /**
     * Sets column philhealth_employer
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setPhilhealthEmployer($data)
    {
        $this->_PhilhealthEmployer = $data;
        return $this;
    }

    /**
     * Gets column philhealth_employer
     *
     * @return float
     */
    public function getPhilhealthEmployer()
    {
        return $this->_PhilhealthEmployer;
    }

    /**
     * Sets column ec
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setEc($data)
    {
        $this->_Ec = $data;
        return $this;
    }

    /**
     * Gets column ec
     *
     * @return float
     */
    public function getEc()
    {
        return $this->_Ec;
    }

    /**
     * Sets column hdmf_employee
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setHdmfEmployee($data)
    {
        $this->_HdmfEmployee = $data;
        return $this;
    }

    /**
     * Gets column hdmf_employee
     *
     * @return float
     */
    public function getHdmfEmployee()
    {
        return $this->_HdmfEmployee;
    }

    /**
     * Sets column hdmf_employer
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setHdmfEmployer($data)
    {
        $this->_HdmfEmployer = $data;
        return $this;
    }

    /**
     * Gets column hdmf_employer
     *
     * @return float
     */
    public function getHdmfEmployer()
    {
        return $this->_HdmfEmployer;
    }

    /**
     * Sets column admin_fee
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setAdminFee($data)
    {
        $this->_AdminFee = $data;
        return $this;
    }

    /**
     * Gets column admin_fee
     *
     * @return float
     */
    public function getAdminFee()
    {
        return $this->_AdminFee;
    }

    /**
     * Sets column fuel_allocation
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setFuelAllocation($data)
    {
        $this->_FuelAllocation = $data;
        return $this;
    }

    /**
     * Gets column fuel_allocation
     *
     * @return float
     */
    public function getFuelAllocation()
    {
        return $this->_FuelAllocation;
    }

    /**
     * Sets column bike_rehab
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setBikeRehab($data)
    {
        $this->_BikeRehab = $data;
        return $this;
    }

    /**
     * Gets column bike_rehab
     *
     * @return float
     */
    public function getBikeRehab()
    {
        return $this->_BikeRehab;
    }

    /**
     * Sets column bike_insurance_reg
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setBikeInsuranceReg($data)
    {
        $this->_BikeInsuranceReg = $data;
        return $this;
    }

    /**
     * Gets column bike_insurance_reg
     *
     * @return float
     */
    public function getBikeInsuranceReg()
    {
        return $this->_BikeInsuranceReg;
    }

    /**
     * Sets column bike_rental
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setBikeRental($data)
    {
        $this->_BikeRental = $data;
        return $this;
    }

    /**
     * Gets column bike_rental
     *
     * @return float
     */
    public function getBikeRental()
    {
        return $this->_BikeRental;
    }

    /**
     * Sets column cash_bond
     *
     * @param float $data
     * @return Messerve_Model_ProcessedRate
     */
    public function setCashBond($data)
    {
        $this->_CashBond = $data;
        return $this;
    }

    /**
     * Gets column cash_bond
     *
     * @return float
     */
    public function getCashBond()
    {
        return $this->_CashBond;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_ProcessedRate
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_ProcessedRate());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_ProcessedRate::delete
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
