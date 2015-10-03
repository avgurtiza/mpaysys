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
 * Table definition for vehicle_employee
 *
 * @package Messerve_Model
 * @subpackage DbTable
 * @author Slide Gurtiza
 */
class Messerve_Model_DbTable_VehicleEmployee extends Messerve_Model_DbTable_TableAbstract
{
    /**
     * $_name - name of database table
     *
     * @var string
     */
    protected $_name = 'vehicle_employee';

    /**
     * $_id - this is the primary key name
     *
     * @var array
     */
    protected $_id = array('employee_id', 'vehicle_id');

    protected $_sequence = false;

    
    



}
