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
 * Table definition for user_group
 *
 * @package Messerve_Model
 * @subpackage DbTable
 * @author Slide Gurtiza
 */
class Messerve_Model_DbTable_UserGroup extends Messerve_Model_DbTable_TableAbstract
{
    /**
     * $_name - name of database table
     *
     * @var string
     */
    protected $_name = 'user_group';

    /**
     * $_id - this is the primary key name
     *
     * @var array
     */
    protected $_id = array('user_id', 'group_id');

    protected $_sequence = false;

    
    



}
