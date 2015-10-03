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
class Messerve_Model_Bop extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Id;

    /**
     * Database var type varchar(256)
     *
     * @var string
     */
    protected $_Name;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Motorcycle;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_MotorcycleDeduction;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Insurance;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_InsuranceDeduction;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Maintenance1;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Maintenance2;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Maintenance3;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Maintenance4;



    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'name'=>'Name',
            'motorcycle'=>'Motorcycle',
            'motorcycle_deduction'=>'MotorcycleDeduction',
            'insurance'=>'Insurance',
            'insurance_deduction'=>'InsuranceDeduction',
            'maintenance_1'=>'Maintenance1',
            'maintenance_2'=>'Maintenance2',
            'maintenance_3'=>'Maintenance3',
            'maintenance_4'=>'Maintenance4',
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
     * @return Messerve_Model_Bop
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
     * @return Messerve_Model_Bop
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
     * Sets column motorcycle
     *
     * @param float $data
     * @return Messerve_Model_Bop
     */
    public function setMotorcycle($data)
    {
        $this->_Motorcycle = $data;
        return $this;
    }

    /**
     * Gets column motorcycle
     *
     * @return float
     */
    public function getMotorcycle()
    {
        return $this->_Motorcycle;
    }

    /**
     * Sets column motorcycle_deduction
     *
     * @param float $data
     * @return Messerve_Model_Bop
     */
    public function setMotorcycleDeduction($data)
    {
        $this->_MotorcycleDeduction = $data;
        return $this;
    }

    /**
     * Gets column motorcycle_deduction
     *
     * @return float
     */
    public function getMotorcycleDeduction()
    {
        return $this->_MotorcycleDeduction;
    }

    /**
     * Sets column insurance
     *
     * @param float $data
     * @return Messerve_Model_Bop
     */
    public function setInsurance($data)
    {
        $this->_Insurance = $data;
        return $this;
    }

    /**
     * Gets column insurance
     *
     * @return float
     */
    public function getInsurance()
    {
        return $this->_Insurance;
    }

    /**
     * Sets column insurance_deduction
     *
     * @param float $data
     * @return Messerve_Model_Bop
     */
    public function setInsuranceDeduction($data)
    {
        $this->_InsuranceDeduction = $data;
        return $this;
    }

    /**
     * Gets column insurance_deduction
     *
     * @return float
     */
    public function getInsuranceDeduction()
    {
        return $this->_InsuranceDeduction;
    }

    /**
     * Sets column maintenance_1
     *
     * @param float $data
     * @return Messerve_Model_Bop
     */
    public function setMaintenance1($data)
    {
        $this->_Maintenance1 = $data;
        return $this;
    }

    /**
     * Gets column maintenance_1
     *
     * @return float
     */
    public function getMaintenance1()
    {
        return $this->_Maintenance1;
    }

    /**
     * Sets column maintenance_2
     *
     * @param float $data
     * @return Messerve_Model_Bop
     */
    public function setMaintenance2($data)
    {
        $this->_Maintenance2 = $data;
        return $this;
    }

    /**
     * Gets column maintenance_2
     *
     * @return float
     */
    public function getMaintenance2()
    {
        return $this->_Maintenance2;
    }

    /**
     * Sets column maintenance_3
     *
     * @param float $data
     * @return Messerve_Model_Bop
     */
    public function setMaintenance3($data)
    {
        $this->_Maintenance3 = $data;
        return $this;
    }

    /**
     * Gets column maintenance_3
     *
     * @return float
     */
    public function getMaintenance3()
    {
        return $this->_Maintenance3;
    }

    /**
     * Sets column maintenance_4
     *
     * @param float $data
     * @return Messerve_Model_Bop
     */
    public function setMaintenance4($data)
    {
        $this->_Maintenance4 = $data;
        return $this;
    }

    /**
     * Gets column maintenance_4
     *
     * @return float
     */
    public function getMaintenance4()
    {
        return $this->_Maintenance4;
    }

    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_Bop
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_Bop());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_Bop::delete
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
