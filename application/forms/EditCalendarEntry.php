<?php

/**
 * Form definition for table client.
 *
 * @package Messerve
 * @author Slide Gurtiza
 * @version $Id$
 *
 */
class Messerve_Form_EditCalendarEntry extends Zend_Form
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
            $this->createElement('hidden', 'calendar_id')
        		->removeDecorator('label')
        		->removeDecorator('DtDd')                
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
        		$this->createElement('select', 'type')
        		->setLabel('Type')
        		->setMultioptions(array('special'=>'Special holiday','legal'=>'Legal holiday'))
        		->setRequired(true)
        );
                
        $this->addElement(
        		$this->createElement('text', 'date')
        		->setLabel('Date')
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