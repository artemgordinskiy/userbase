<?php

class AuthController extends Zend_Controller_Action
{

    public function init()
    {
        // $this->_helper->layout()->setLayout('auth');
    }

    public function indexAction()
    {
        $db = $this->_getParam('db');
        $back = $this->_getParam('from', 'customers');

        $loginForm = new Application_Form_Auth();

        if($this->getRequest()->isPost()) {
            if ($loginForm->isValid($_POST)) {
                $adapter = new Zend_Auth_Adapter_DbTable(
                    $db,
                    'admins',
                    'username',
                    'password'
                    );

                $adapter->setIdentity($loginForm->getValue('username'));
                $adapter->setCredential($loginForm->getValue('password'));

                $auth   = Zend_Auth::getInstance();
                $result = $auth->authenticate($adapter);

                if ($result->isValid()) {
                    $this->_redirect($back);
                } else {
                    echo '<div class="alert alert-error" style="width: 500px; margin: 0 auto; text-align:center;">Неправильный логин или пароль</div>';
                }
            }
        }

        $this->view->loginForm = $loginForm;
    }

}



