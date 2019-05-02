<?php

/**
 * Form definition for table employee.
 *
 * @package Messerve
 * @author Zodeken
 * @version $Id$
 *
 */
class Messerve_Form_EditEmployee extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');

        $this->addElement(
            $this->createElement('hidden', 'id')
                
        );
        
        $this->addElement(
        		$this->createElement('select', 'type')
        		->setLabel('Employee type')
        		->setRequired(true)
        		->addFilter(new Zend_Filter_StringTrim())
        		->setMultioptions(
        			array(
        				''=>''
        				,'rider'=>'Rider'
        				, 'serviceman'=>'Serviceman'
        			)
        		)
        );
        

        $this->addElement(
            $this->createElement('text', 'firstname')
                ->setLabel('First name')
                ->setAttrib("maxlength", 64)
                ->setRequired(true)
                ->addValidator(new Zend_Validate_StringLength(array("max" => 64)))
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'lastname')
                ->setLabel('Last name')
                ->setAttrib("maxlength", 64)
                ->setRequired(true)
                ->addValidator(new Zend_Validate_StringLength(array("max" => 64)))
                ->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
        		$this->createElement('text', 'middleinitial')
        		->setLabel('Middle initial')
        		->setAttrib("maxlength", 16)
        		->setRequired(true)
        		->addValidator(new Zend_Validate_StringLength(array("max" => 16)))
        		->addFilter(new Zend_Filter_StringTrim())
        );
        

        $this->addElement(
        		$this->createElement('text', 'account_number')
        		->setLabel('Account number')
        		->setAttrib("maxlength", 64)
        		->setRequired(true)
        		->addValidator(new Zend_Validate_StringLength(array("max" => 64)))
        		->addFilter(new Zend_Filter_StringTrim())
        );

        /*
        $this->addElement(
        		$this->createElement('text', 'employee_number')
        		->setLabel('Employee number')
        		->setAttrib("maxlength", 64)
        		//->setRequired(true)
        		//->addValidator(new Zend_Validate_StringLength(array("max" => 64)))
        		->addFilter(new Zend_Filter_StringTrim())
        );
        */
        
        $this->addElement(
        		$this->createElement('text', 'tin')
        		->setLabel('TIN')
        		->setAttrib("maxlength", 64)
        		->setRequired(true)
        		->addValidator(new Zend_Validate_StringLength(array("max" => 64)))
        		->addFilter(new Zend_Filter_StringTrim())
        );
                
        $this->addElement(
        		$this->createElement('text', 'sss')
        		->setLabel('SSS Number')
        		->setAttrib("maxlength", 64)
        		->setRequired(true)
        		->addValidator(new Zend_Validate_StringLength(array("max" => 64)))
        		->addFilter(new Zend_Filter_StringTrim())
        );        
        
        $this->addElement(
        		$this->createElement('text', 'hdmf')
        		->setLabel('HDMF Number')
        		->setAttrib("maxlength", 64)
        		//->setRequired(true)
        		->addValidator(new Zend_Validate_StringLength(array("max" => 64)))
        		->addFilter(new Zend_Filter_StringTrim())
        );        

        $this->addElement(
        		$this->createElement('text', 'philhealth')
        		->setLabel('Philhealth Number')
        		->setAttrib("maxlength", 64)
        		->setRequired(true)
        		->addValidator(new Zend_Validate_StringLength(array("max" => 64)))
        		->addFilter(new Zend_Filter_StringTrim())
        );
                

        $this->addElement(
            $this->createElement('select', 'group_id')
                ->setLabel('Group')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Int())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        /*
        $this->addElement(
        		$this->createElement('select', 'rate_id')
        		->setLabel('Rate')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Int())
        		->addFilter(new Zend_Filter_StringTrim())
        );
        */
        
        $this->addElement(
            $this->createElement('text', 'dateemployed')
                ->setLabel('Date employed')
                ->setValue(date("Y-m-d"))
                ->setRequired(true)
                ->addFilter(new Zend_Filter_StringTrim())
        		->setOptions(array('class'=>'datepicker'))
        );
        
        $this->addElement(
        		$this->createElement('text', 'gascard')
        		->setLabel('Gas card - Petron')
        );

        $this->addElement(
        		$this->createElement('text', 'gascard2')
        		->setLabel('Gas card - Caltex')
        );

        $this->addElement(
            $this->createElement('text', 'gascard3')
                ->setLabel('Gas card - Oil Empire')
        );

        $this->addElement(
        		$this->createElement('select', 'bop_id')
        		->setLabel('Bike ownership program')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Int())
        		->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'bop_start')
                ->setLabel('Bike purchase date')
                ->setOptions(array('class'=>'datepicker'))
        );

        $this->addElement(
        		$this->createElement('text', 'bop_current_rider_start')
        		->setLabel('BOP start date')
        		->setOptions(array('class'=>'datepicker'))
        );
        
        $this->addElement(
        		$this->createElement('text', 'bop_startingbalance')
        		->setLabel('BOP starting balance')
        );
        
        $this->addElement(
        		$this->createElement('text', 'bop_currentbalance')
        		->setLabel('BOP current balance')
        		->setDescription('Read-only')
        		->setOptions(array('readonly'=>'readonly'))
        );
        
/*
        $this->addElement(
            $this->createElement('text', 'bike_rehab_end')
                ->setLabel('Bike rehab end date')
                ->setValue(date("Y-m-d"))
                ->setRequired(true)
                ->addFilter(new Zend_Filter_StringTrim())
        		->setOptions(array('class'=>'datepicker'))
        );

        $this->addElement(
            $this->createElement('text', 'bike_insurance_reg_end')
                ->setLabel('Bike insurance/reg end date')
                ->setValue(date("Y-m-d"))
                ->setRequired(true)
                ->addFilter(new Zend_Filter_StringTrim())
        		->setOptions(array('class'=>'datepicker'))
        );
*/
        $this->addElement(
            $this->createElement('button', 'submit')
                ->setLabel('Save')
                ->setAttrib('type', 'submit')
        );
        
        $this->addElement(
        		$this->createElement('button', 'cancel')
        		->setLabel('Cancel')
        		->setAttrib('type', 'button')
        		->setAttrib('class', 'cancel')
        );

        parent::init();
    }
}