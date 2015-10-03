<?php

/**
 * Application Model DbTables
 *
 * @package Messerve_Model
 * @subpackage DbTable
 * @author Slide Gurtiza
 * @copyright Slide Gurtiza
 * @license All rights reserved
 */

/**
 * Table definition for employee
 *
 * @package Messerve_Model
 * @subpackage DbTable
 * @author Slide Gurtiza
 */
class Messerve_Model_DbTable_Employee extends Messerve_Model_DbTable_TableAbstract
{
    /**
     * $_name - name of database table
     *
     * @var string
     */
    protected $_name = 'employee';

    /**
     * $_id - this is the primary key name
     *
     * @var int
     */
    protected $_id = 'id';

    protected $_sequence = true;

    
    



}
