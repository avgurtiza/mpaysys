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
 * Table definition for payroll_details
 *
 * @package Messerve_Model
 * @subpackage DbTable
 * @author Slide Gurtiza
 */
class Messerve_Model_DbTable_PayrollDetails extends Messerve_Model_DbTable_TableAbstract
{
    /**
     * $_name - name of database table
     *
     * @var string
     */
    protected $_name = 'payroll_details';

    /**
     * $_id - this is the primary key name
     *
     * @var array
     */
    protected $_id = array('payroll_id', 'name');

    protected $_sequence = false;

    
    



}
