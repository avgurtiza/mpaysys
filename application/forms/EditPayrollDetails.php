<?php

/**
 * Form definition for table payroll_details.
 *
 * @package Messerve
 * @author Zodeken
 * @version $Id$
 *
 */
class Messerve_Form_EditPayrollDetails extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');

        $this->addElement(
            $this->createElement('hidden', 'payroll_id')
                
        );

        $this->addElement(
            $this->createElement('hidden', 'name')
                
        );

        $this->addElement(
            $this->createElement('text', 'value')
                ->setLabel('Value')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('radio', 'type')
                ->setLabel('Type')
                ->setMultiOptions(array('standard' => 'standard','custom' => 'custom'))
                ->setSeparator(" ")
                ->setRequired(true)
                ->setValue("standard")
                ->addValidator(new Zend_Validate_InArray(array('haystack' => array('standard' => 'standard','custom' => 'custom'))))
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