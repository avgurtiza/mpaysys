<?php

/**
 * Form definition for table payroll.
 *
 * @package Messerve
 * @author Zodeken
 * @version $Id$
 *
 */
class Messerve_Form_EditPayroll extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');

        $this->addElement(
            $this->createElement('hidden', 'id')
                
        );

        $this->addElement(
            $this->createElement('text', 'employee_id')
                ->setLabel('Employee Id')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Int())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'group_id')
                ->setLabel('Group Id')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Int())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'rate_id')
                ->setLabel('Rate Id')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Int())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'date_start')
                ->setLabel('Date Start')
                ->setValue(date("Y-m-d"))
                ->setRequired(true)
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'date_end')
                ->setLabel('Date End')
                ->setValue(date("Y-m-d"))
                ->setRequired(true)
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('textarea', 'notes')
                ->setLabel('Notes')
                ->setRequired(true)
                ->addFilter(new Zend_Filter_StringTrim())
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