<?php

/**
 * Form definition for table client.
 *
 * @package Messerve
 * @author Zodeken
 * @version $Id$
 *
 */
class Messerve_Form_EditClient extends Zend_Form
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
/*
        $this->addElement(
            $this->createElement('textarea', 'notes')
                ->setLabel('Notes')
                // ->setRequired(true)
                ->addFilter(new Zend_Filter_StringTrim())
        		->setOptions(array('rows'=>8))
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