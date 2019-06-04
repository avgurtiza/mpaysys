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
class Messerve_Model_Fuelpurchase extends Messerve_Model_ModelAbstract
{

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_Id;

    /**
     * Database var type int(11)
     *
     * @var int
     */
    protected $_EmployeeId;

    /**
     * Database var type datetime
     *
     * @var string
     */
    protected $_InvoiceDate;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_ProductQuantity;

    /**
     * Database var type varchar(128)
     *
     * @var string
     */
    protected $_InvoiceNumber;

    /**
     * Database var type varchar(128)
     *
     * @var string
     */
    protected $_StationName;

    /**
     * Database var type varchar(128)
     *
     * @var string
     */
    protected $_Product;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_FuelCost;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Fueloverage;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Actualoverage;

    protected $_GascardType;


    /**
     * Sets up column and relationship lists
     */
    public function __construct()
    {
        parent::init();
        $this->setColumnsList(array(
            'id'=>'Id',
            'employee_id'=>'EmployeeId',
            'invoice_date'=>'InvoiceDate',
            'product_quantity'=>'ProductQuantity',
            'invoice_number'=>'InvoiceNumber',
            'station_name'=>'StationName',
            'product'=>'Product',
            'fuel_cost'=>'FuelCost',
            'fueloverage'=>'Fueloverage',
            'actualoverage'=>'Actualoverage',
            'gascard_type'=>'GascardType'
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
     * @return Messerve_Model_Fuelpurchase
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
     * Sets column employee_id
     *
     * @param int $data
     * @return Messerve_Model_Fuelpurchase
     */
    public function setEmployeeId($data)
    {
        $this->_EmployeeId = $data;
        return $this;
    }

    /**
     * Gets column employee_id
     *
     * @return int
     */
    public function getEmployeeId()
    {
        return $this->_EmployeeId;
    }

    /**
     * Sets column invoice_date. Stored in ISO 8601 format.
     *
     * @param string|Zend_Date $date
     * @return Messerve_Model_Fuelpurchase
     */
    public function setInvoiceDate($data)
    {
        if (! empty($data)) {
            if (! $data instanceof Zend_Date) {
                $zdate = new Zend_Date();
            }

            $data = $zdate->toString($data,'YYYY-MM-ddTHH:mm:ss.S');
        }

        $this->_InvoiceDate = $data;
        return $this;
    }

    /**
     * Gets column invoice_date
     *
     * @param boolean $returnZendDate
     * @return Zend_Date|null|string Zend_Date representation of this datetime if enabled, or ISO 8601 string if not
     */
    public function getInvoiceDate($returnZendDate = false)
    {
        if ($returnZendDate) {
            if ($this->_InvoiceDate === null) {
                return null;
            }

            return new Zend_Date($this->_InvoiceDate, Zend_Date::ISO_8601);
        }

        return $this->_InvoiceDate;
    }

    /**
     * Sets column product_quantity
     *
     * @param float $data
     * @return Messerve_Model_Fuelpurchase
     */
    public function setProductQuantity($data)
    {
        $this->_ProductQuantity = $data;
        return $this;
    }

    /**
     * Gets column product_quantity
     *
     * @return float
     */
    public function getProductQuantity()
    {
        return $this->_ProductQuantity;
    }

    /**
     * Sets column invoice_number
     *
     * @param string $data
     * @return Messerve_Model_Fuelpurchase
     */
    public function setInvoiceNumber($data)
    {
        $this->_InvoiceNumber = $data;
        return $this;
    }

    /**
     * Gets column invoice_number
     *
     * @return string
     */
    public function getInvoiceNumber()
    {
        return $this->_InvoiceNumber;
    }

    /**
     * Sets column station_name
     *
     * @param string $data
     * @return Messerve_Model_Fuelpurchase
     */
    public function setStationName($data)
    {
        $this->_StationName = $data;
        return $this;
    }

    /**
     * Gets column station_name
     *
     * @return string
     */
    public function getStationName()
    {
        return $this->_StationName;
    }

    /**
     * Sets column product
     *
     * @param string $data
     * @return Messerve_Model_Fuelpurchase
     */
    public function setProduct($data)
    {
        $this->_Product = $data;
        return $this;
    }

    /**
     * Gets column product
     *
     * @return string
     */
    public function getProduct()
    {
        return $this->_Product;
    }

    /**
     * Sets column fuel_cost
     *
     * @param float $data
     * @return Messerve_Model_Fuelpurchase
     */
    public function setFuelCost($data)
    {
        $this->_FuelCost = $data;
        return $this;
    }

    /**
     * Gets column fuel_cost
     *
     * @return float
     */
    public function getFuelCost()
    {
        return $this->_FuelCost;
    }

    /**
     * Sets column fueloverage
     *
     * @param float $data
     * @return Messerve_Model_Fuelpurchase
     */
    public function setFueloverage($data)
    {
        $this->_Fueloverage = $data;
        return $this;
    }

    /**
     * Gets column fueloverage
     *
     * @return float
     */
    public function getFueloverage()
    {
        return $this->_Fueloverage;
    }

    /**
     * Sets column actualoverage
     *
     * @param float $data
     * @return Messerve_Model_Fuelpurchase
     */
    public function setActualoverage($data)
    {
        $this->_Actualoverage = $data;
        return $this;
    }

    /**
     * Gets column actualoverage
     *
     * @return float
     */
    public function getActualoverage()
    {
        return $this->_Actualoverage;
    }

    public function setGascardType($data)
    {
        $this->_GascardType = $data;
        return $this;
    }

    public function getGascardType()
    {
        return $this->_GascardType;
    }
    /**
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_Fuelpurchase
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_Fuelpurchase());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_Fuelpurchase::delete
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
