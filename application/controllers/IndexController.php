<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
          $request = $this->getRequest();
          $auth = Zend_Auth::getInstance();
          if (!$auth->hasIdentity()){
            $this->_redirect('/auth');
          } else {
            $this->_redirect('/customers');
          }

    }

    public function indexAction()
    {

    }

}















