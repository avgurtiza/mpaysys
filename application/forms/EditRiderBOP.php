<?php

/**
 * Form definition for table employee.
 *
 * @package Messerve
 * @author Zodeken
 * @version $Id$
 *
 */
class Messerve_Form_EditRiderBOP extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');

        $this->addElement(
            $this->createElement('hidden', 'id')
                
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