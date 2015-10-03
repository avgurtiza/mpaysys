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
class Messerve_Model_RateClient extends Messerve_Model_ModelAbstract
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
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Reg;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_RegOt;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_RegNd;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_RegNdOt;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Spec;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_SpecOt;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_SpecNd;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_SpecNdOt;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_Legal;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_LegalOt;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_LegalNd;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_LegalNdOt;

    /**
     * Database var type decimal(10,2)
     *
     * @var float
     */
    protected $_LegalUnattend;



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
            'reg_ot'=>'RegOt',
            'reg_nd'=>'RegNd',
            'reg_nd_ot'=>'RegNdOt',
            'spec'=>'Spec',
            'spec_ot'=>'SpecOt',
            'spec_nd'=>'SpecNd',
            'spec_nd_ot'=>'SpecNdOt',
            'legal'=>'Legal',
            'legal_ot'=>'LegalOt',
            'legal_nd'=>'LegalNd',
            'legal_nd_ot'=>'LegalNdOt',
            'legal_unattend'=>'LegalUnattend',
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
     * @return Messerve_Model_RateClient
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
     * @return Messerve_Model_RateClient
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
     * @return Messerve_Model_RateClient
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
     * Sets column reg_ot
     *
     * @param float $data
     * @return Messerve_Model_RateClient
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
     * Sets column reg_nd
     *
     * @param float $data
     * @return Messerve_Model_RateClient
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
     * Sets column reg_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_RateClient
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
     * Sets column spec
     *
     * @param float $data
     * @return Messerve_Model_RateClient
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
     * Sets column spec_ot
     *
     * @param float $data
     * @return Messerve_Model_RateClient
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
     * Sets column spec_nd
     *
     * @param float $data
     * @return Messerve_Model_RateClient
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
     * Sets column spec_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_RateClient
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
     * @return Messerve_Model_RateClient
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
     * Sets column legal_ot
     *
     * @param float $data
     * @return Messerve_Model_RateClient
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
     * Sets column legal_nd
     *
     * @param float $data
     * @return Messerve_Model_RateClient
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
     * Sets column legal_nd_ot
     *
     * @param float $data
     * @return Messerve_Model_RateClient
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
     * @return Messerve_Model_RateClient
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
     * Returns the mapper class for this model
     *
     * @return Messerve_Model_Mapper_RateClient
     */
    public function getMapper()
    {
        if ($this->_mapper === null) {
            $this->setMapper(new Messerve_Model_Mapper_RateClient());
        }

        return $this->_mapper;
    }

    /**
     * Deletes current row by deleting the row that matches the primary key
     *
	 * @see Messerve_Model_Mapper_RateClient::delete
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
