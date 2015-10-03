<?php

/**
 * Application_Form_Users form file.
 */
class Messerve_Form_EditPassword extends Zend_Form
{

    public function __construct($options = null)
    {
        parent::__construct($options);
        
        $this->setName('frmPassword');
        $this->setMethod('post');
        
        $id = new Zend_Form_Element_Hidden('id');
        $id->setRequired(true);
        $id->addValidator(new Zend_Validate_NotEmpty());
        $id->addValidator(new Zend_Validate_Int());
        $this->addElement($id);
        
        $password = new Zend_Form_Element_Password('password');
        $password->setLabel('New password');
        $password->setAttrib('maxlength', 32);
        $password->setRequired(true);
        $password->addValidator(new Zend_Validate_NotEmpty());
        $password->addValidator('StringLength', false, array(4,15));
        $password->addErrorMessage('Please choose a password between 4-15 characters');
        $this->addElement($password);
        
        $verify_password = new Zend_Form_Element_Password('verify_password');
        $verify_password->setLabel('Verify password');
        $verify_password->setAttrib('maxlength', 32);
        $verify_password->setRequired(true);
        $verify_password->addValidator('Identical', false, array('token' => 'password'));
        $verify_password->addErrorMessage('The passwords do not match');
        $this->addElement($verify_password);
        
        $submit = new Zend_Form_Element_Submit('bt_submit');
        $submit->setLabel('Save');
        $this->addElement($submit);
    }


}
