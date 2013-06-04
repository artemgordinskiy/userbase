<?php

class AuthController extends Zend_Controller_Action
{

    public function init()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()){
          $this->_redirect('/customers/index');
        }
    }

    public function indexAction()
    {
        $back = $this->_getParam('from', 'customers');

        $loginForm = new Application_Form_Auth();

        if($this->getRequest()->isPost()) {
            if ($loginForm->isValid($_POST)) {
                $login = $loginForm->getValue('username');
                $password = $loginForm->getValue('password');

                $autorization  = new Application_Model_DbTable_Auth($login, $password);

                $result = $autorization->authorize();

                if ($result) {
                    $this->_redirect($back);
                } else {
                    echo '<div class="alert alert-error" style="width: 500px; margin: 0 auto; text-align:center;">Неправильный логин или пароль</div>';
                }
            }
        }

        $this->view->loginForm = $loginForm;
    }

}



