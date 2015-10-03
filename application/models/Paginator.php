<?php

/**
 *
 * @package Messerve_Model
 * @subpackage Paginator
 * @author Slide Gurtiza
 * @copyright Slide Gurtiza
 * @license All rights reserved
 */

/**
 * Paginator class that extends Zend_Paginator_Adapter_DbSelect to return an
 * object instead of an array.
 *
 * @package Messerve_Model
 * @subpackage Paginator
 * @author Slide Gurtiza
 */
class Messerve_Model_Paginator extends Zend_Paginator_Adapter_DbSelect
{
    /**
     * Object mapper
     *
     * @var Messerve_Model_MapperAbstract
     */
    protected $_mapper = null;

    /**
     * Constructor.
     *
     * @param Zend_Db_Select $select The select query
     * @param Messerve_Model_MapperAbstract $mapper The mapper associated with the object type
     */
    public function __construct(Zend_Db_Select $select, Messerve_Model_Mapper_MapperAbstract $mapper)
    {
        $this->_mapper = $mapper;
        parent::__construct($select);
    }

    /**
     * Returns an array of items as objects for a page.
     *
     * @param  integer $offset Page offset
     * @param  integer $itemCountPerPage Number of items per page
     * @return array An array of Messerve_ModelAbstract objects
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $items = parent::getItems($offset, $itemCountPerPage);
        $objects = array();

        foreach ($items as $item) {
            $objects[] = $this->_mapper->loadModel($item, null);
        }

        return $objects;
    }
}
