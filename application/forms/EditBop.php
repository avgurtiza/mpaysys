<?php

/**
 * Form definition for table client.
 *
 * @package Messerve
 * @author Slide Gurtiza
 * @version $Id$
 *
 */
class Messerve_Form_EditBop extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');

        $this->addElement(
            $this->createElement('hidden', 'id')
        		->removeDecorator('label')
        		->removeDecorator('DtDd')                
        );

        $this->addElement(
            $this->createElement('text', 'name')
                ->setLabel('Name')
                ->setAttrib("maxlength", 128)
                ->setRequired(true)
                ->addValidator(new Zend_Validate_StringLength(array("max" => 128)))
                ->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
        		$this->createElement('text', 'motorcycle')
        		->setLabel('Motorcycle cost (MC)')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
        		$this->createElement('text', 'motorcycle_deduction')
        		->setLabel('MC deduction per cutoff')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
        		$this->createElement('text', 'insurance')
        		->setLabel('Insurance')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
        		$this->createElement('text', 'insurance_deduction')
        		->setLabel('Insurance deduction per cutoff')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
        		$this->createElement('text', 'maintenance_1')
        		->setLabel('Maint/cutoff  - Year 1')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );        

        $this->addElement(
        		$this->createElement('text', 'maintenance_2')
        		->setLabel('Maint/cutoff - Year 2')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
        		$this->createElement('text', 'maintenance_3')
        		->setLabel('Maint/cutoff - Year 3')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
        		$this->createElement('text', 'maintenance_4')
        		->setLabel('Maintenance - Year 4')
        		->setRequired(true)
        		->addValidator(new Zend_Validate_Float())
        		->addFilter(new Zend_Filter_StringTrim())
        );
        
        $this->addElement(
            $this->createElement('button', 'submit')
                ->setLabel('Save')
                ->setAttrib('type', 'submit')
        );
        
        parent::init();
    }
}