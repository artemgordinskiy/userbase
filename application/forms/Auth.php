<?php

class Application_Form_Auth extends Zend_Form
{

    public function init()
    {
        $this->setName('auth')
             ->setMethod('post')
             ->setAttribs(array('class'=>'form-horizontal login-form'));

        $username = new Zend_Form_Element_Text('username');
        $username->setRequired(true)
                 ->addFilter('StripTags')
                 ->addFilter('StringTrim')
                 ->addValidator('NotEmpty')
                 ->addValidator('StringLength', false, array(5, 50))
                 ->removeDecorator('htmlTag')
                 ->removeDecorator('DtDdWrapper')
                 ->removeDecorator('label');

        $password = new Zend_Form_Element_Password('password');
        $password->setRequired(true)
                 ->addFilter('StripTags')
                 ->addFilter('StringTrim')
                 ->addValidator('NotEmpty')
                 ->addValidator('StringLength', false, array(5, 50))
                 ->removeDecorator('htmlTag')
                 ->removeDecorator('DtDdWrapper')
                 ->removeDecorator('label');

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Войти')
               ->setAttribs(array('class'=>'btn'))
               ->removeDecorator('DtDdWrapper');

        $this->setDecorators(array(
            array('ViewScript', array(
                'viewScript' => '_form_login.phtml'
                )
            )
        ));

        $this->addElements(array($username, $password, $submit));

    }
}

