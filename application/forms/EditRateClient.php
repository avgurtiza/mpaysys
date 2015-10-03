<?php

/**
 * Form definition for table rate.
 *
 * @package Messerve
 * @author Zodeken
 * @version $Id$
 *
 */
class Messerve_Form_EditRateClient extends Zend_Form
{
    public function init()
    {
        $this->setMethod('post');
        $this->setOptions(array('class'=>'bigform'));
        
        $this->addElement(
            $this->createElement('hidden', 'id')
                
        );
        
        $this->addElement(
        		$this->createElement('text', 'name')
        		->setLabel('Name')
        		->setAttrib("maxlength", 64)
        		->setRequired(true)
        		->addValidator(new Zend_Validate_StringLength(array("max" => 128)))
        		->addFilter(new Zend_Filter_StringTrim())
        );        

        $this->addElement(
            $this->createElement('text', 'reg')
                ->setLabel('Regular hours')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'reg_nd')
                ->setLabel('Regular hours - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'reg_ot')
                ->setLabel('Regular OT')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'reg_nd_ot')
                ->setLabel('Regular OT - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );


        $this->addElement(
            $this->createElement('text', 'spec')
                ->setLabel('Special holiday')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'spec_nd')
                ->setLabel('Special holiday - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'spec_ot')
                ->setLabel('Special holiday OT')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'spec_nd_ot')
                ->setLabel('Special holiday OT - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'legal')
                ->setLabel('Legal holiday')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'legal_nd')
                ->setLabel('Legal holiday - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'legal_ot')
                ->setLabel('Legal holiday OT')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'legal_nd_ot')
                ->setLabel('Legal holiday OT - night')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        $this->addElement(
            $this->createElement('text', 'legal_unattend')
                ->setLabel('Unworked legal holiday')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_Float())
                ->addFilter(new Zend_Filter_StringTrim())
        );

        
        $this->addDisplayGroup(array(
        			'reg', 'reg_ot', 'reg_nd', 'reg_nd_ot'
        			, 'spec', 'spec_ot', 'spec_nd', 'spec_nd_ot'
        			, 'legal', 'legal_ot', 'legal_nd', 'legal_nd_ot', 'legal_unattend'
        		) , 'hourly', array('legend'=>'Per hour values'));
        
                
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