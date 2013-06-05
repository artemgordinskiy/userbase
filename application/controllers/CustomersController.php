<?php

class CustomersController extends Zend_Controller_Action
{

    public function init()
    {
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $request = $this->getRequest();
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()){
            $this->_redirect('/auth/login/from/customers');
        }
    }

    public function indexAction()
    {
        $customers = new Application_Model_DbTable_Customers();
        $pageNum = (int)$this->_getParam('p', 1);
        $currentSort = $this->_getParam('o', 'default');
        $filterById = $this->_getParam('filterByID', false);
        $expiration = $this->_getParam('expiration', false);
        $resultSet = $customers->fetchAllCustomers($pageNum, $currentSort, 10, $filterById, $expiration);
        $this->view->customers = $resultSet;

        // Пришлось еще раз дергать ДБ, чтобы получить полный список групп для фильтра.
        $groups = new Application_Model_DbTable_Groups();
        $groupsResultSet = $groups->getAllGroupsWithMembers();
        $this->view->groups = $groupsResultSet;

        $this->view->orderLinks = $this->getOrderLinks($currentSort);
    }

    public function addAction()
    {
        $id = (int)$this->_getParam('id', 0);
        $groups = new Application_Model_DbTable_Groups();
        $groupsArr = $groups->getGroupsForTheForm();
        $form = new Application_Form_Customer(array('customerID' => $id, 'groups' => $groupsArr));
        $form->submit->setLabel('Сохранить');
        $this->view->form = $form;

        // Если форма была отправлена...
        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            // Если форма прошла проверки...
            if ($form->isValid($formData)) {
                $upload = new Zend_File_Transfer_Adapter_Http();
                $userpicExt = null;

                if($upload->isUploaded()) {
                    $userpicName = $upload -> getFileName('userpic');
                    $userpicExt = pathinfo($userpicName, PATHINFO_EXTENSION);
                }

                $group_id = (int)$form->getValue('group_id');
                $group_id = $group_id !== 0 ? $group_id : null;
                $acc_exp_date = $form->getValue('acc_exp_date');
                $pass = $form->getValue('password');
                $login = $form->getValue('login');
                $email = $form->getValue('email');
                $customers = new Application_Model_DbTable_Customers();
                $newID = $customers->addCustomer($group_id, $acc_exp_date, $pass, $login, $email, $userpicExt);

                if($upload->isUploaded()) {
                    $upload->addFilter('Rename', array('target' => PUBLIC_PATH . '/images/uploads/' . $newID . '.' . $userpicExt, 'overwrite' => true));
                    $upload->receive();
                }
                $this->_helper->flashMessenger->addMessage('Клиент был успешно добавлен в базу');
                $this->_helper->redirector('index');
            } else {
                $form->populate($formData);
            }
        }
    }

    public function editAction()
    {
        $id = (int)$this->_getParam('id', 0);
        $groups = new Application_Model_DbTable_Groups();
        $groupsArr = $groups->getGroupsForTheForm();
        $form = new Application_Form_Customer(array('customerID' => $id, 'groups' => $groupsArr));

        $form->submit->setLabel('Сохранить');
        $this->view->form = $form;

        if($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            if ($form->isValid($formData)) {
                $userpicExt = null;
                $id = (int)$form->getValue('id');
                $upload = new Zend_File_Transfer_Adapter_Http();

                if($upload->isUploaded()) {
                    // Удаляем предыдущий юзерпик
                    array_map('unlink', glob(PUBLIC_PATH . '/images/uploads/' . $id . '.*'));
                    $userpicName = $upload -> getFileName('userpic');
                    $userpicExt = pathinfo($userpicName, PATHINFO_EXTENSION);
                    $upload->addFilter('Rename', array('target' => PUBLIC_PATH . '/images/uploads/' . $id . '.' . $userpicExt, 'overwrite' => true));
                    $upload->receive();
                }

                $group_id = (int)$form->getValue('group_id');
                $group_id = $group_id !== 0 ? $group_id : null;
                $acc_exp_date = $form->getValue('acc_exp_date');
                $pass = $form->getValue('password');
                $login = $form->getValue('login');
                $email = $form->getValue('email');

                $customers = new Application_Model_DbTable_Customers();
                $customers->editCustomer($id, $group_id, $acc_exp_date, $pass, $login, $email, $userpicExt);
                $this->_helper->flashMessenger->addMessage('Информация клиента сохранена');
                $this->_helper->redirector('index');
            } else {
                $form->populate($formData);

            }
        } else {
            if($id > 0) {
                $customers = new Application_Model_DbTable_Customers();
                $form->populate($customers->getCustomer($id));
            }
        }
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $del = $this->getRequest()->getPost('del');
            if($del === 'Да') {
                $id = $this->getRequest()->getPost('id');
                $customers = new Application_Model_DbTable_Customers();
                $customers->deleteCustomer($id);
                $this->_helper->flashMessenger->addMessage('Клиент успешно удален');
            }
            $this->_helper->redirector('index');
        } else {
            $id = $this->_getParam('id', 0);
            $customers = new Application_Model_DbTable_Customers();
            $this->view->customer = $customers->getCustomer($id);
        }
    }

    private function getOrderLinks($currentSortTerm) {
        // Хоть и не лучшее решение, но вряд ли хуже нагромождения "if"-ов
        $orderLinksArr = array(
            'id_a' => array(
                'id' => array('id_d', 'icon-sort-up'),
                'login' => array('lg_a', 'icon-sort'),
                'email' => array('em_a', 'icon-sort'),
                'group' => array('gr_a', 'icon-sort'),
                'exp_date' => array('exp_a', 'icon-sort')
            ),
            'id_d' => array(
                'id' => array('id_a', 'icon-sort-down'),
                'login' => array('lg_a', 'icon-sort'),
                'email' => array('em_a', 'icon-sort'),
                'group' => array('gr_a', 'icon-sort'),
                'exp_date' => array('exp_a', 'icon-sort')
            ),
            'lg_a' => array(
                'id' => array('id_a', 'icon-sort'),
                'login' => array('lg_d', 'icon-sort-up'),
                'email' => array('em_a', 'icon-sort'),
                'group' => array('gr_a', 'icon-sort'),
                'exp_date' => array('exp_a', 'icon-sort')
            ),
            'lg_d' => array(
                'id' => array('id_a', 'icon-sort'),
                'login' => array('lg_a', 'icon-sort-down'),
                'email' => array('em_a', 'icon-sort'),
                'group' => array('gr_a', 'icon-sort'),
                'exp_date' => array('exp_a', 'icon-sort')
            ),
            'em_a' => array(
                'id' => array('id_a', 'icon-sort'),
                'login' => array('lg_a', 'icon-sort'),
                'email' => array('em_d', 'icon-sort-up'),
                'group' => array('gr_a', 'icon-sort'),
                'exp_date' => array('exp_a', 'icon-sort')
            ),
            'em_d' => array(
                'id' => array('id_a', 'icon-sort'),
                'login' => array('lg_a', 'icon-sort'),
                'email' => array('em_a', 'icon-sort-down'),
                'group' => array('gr_a', 'icon-sort'),
                'exp_date' => array('exp_a', 'icon-sort')
            ),
            'gr_a' => array(
                'id' => array('id_a', 'icon-sort'),
                'login' => array('lg_a', 'icon-sort'),
                'email' => array('em_a', 'icon-sort'),
                'group' => array('gr_d', 'icon-sort-up'),
                'exp_date' => array('exp_a', 'icon-sort')
            ),
            'gr_d' => array(
                'id' => array('id_a', 'icon-sort'),
                'login' => array('lg_a', 'icon-sort'),
                'email' => array('em_a', 'icon-sort'),
                'group' => array('gr_a', 'icon-sort-down'),
                'exp_date' => array('exp_a', 'icon-sort')
            ),
            'exp_a' => array(
                'id' => array('id_a', 'icon-sort'),
                'login' => array('lg_a', 'icon-sort'),
                'email' => array('em_a', 'icon-sort'),
                'group' => array('gr_a', 'icon-sort'),
                'exp_date' => array('exp_d', 'icon-sort-up')
            ),
            'exp_d' => array(
                'id' => array('id_a', 'icon-sort'),
                'login' => array('lg_a', 'icon-sort'),
                'email' => array('em_a', 'icon-sort'),
                'group' => array('gr_a', 'icon-sort'),
                'exp_date' => array('exp_a', 'icon-sort-down')
            ),
            'default' => array(
                'id' => array('id_a', 'icon-sort'),
                'login' => array('lg_a', 'icon-sort'),
                'email' => array('em_a', 'icon-sort'),
                'group' => array('gr_a', 'icon-sort'),
                'exp_date' => array('exp_a', 'icon-sort')
            )
        );

        if(!array_key_exists($currentSortTerm, $orderLinksArr)) {
            return $orderLinksArr['default'];
        }

        return $orderLinksArr[$currentSortTerm];
    }

}