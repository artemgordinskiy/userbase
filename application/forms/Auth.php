<?php

class Application_Form_Auth extends Zend_Form
{

    public function init()
    {
        $this->setName('auth')
             ->setMethod('post')
             ->setAttribs(array('class'=>'form-horizontal login-form'));
        $username = new Zend_Form_Element_Text('username');
        $username->setLabel('Логин')
                 ->setRequired(true)
                 ->addFilter('StripTags')
                 ->addFilter('StringTrim')
                 ->addValidator('NotEmpty')
                 ->addValidator('StringLength', false, array(5, 50));

        $password = new Zend_Form_Element_Password('password');
        $password->setLabel('Пароль')
                 ->setRequired(true)
                 ->addFilter('StripTags')
                 ->addFilter('StringTrim')
                 ->addValidator('NotEmpty')
                 ->addValidator('StringLength', false, array(5, 50));

        $submit = new Zend_Form_Element_Submit('submit');
        $submit->setLabel('Войти')
               ->setAttribs(array('class'=>'btn'));
        $this->addElements(array($username, $password, $submit));

    }
}

