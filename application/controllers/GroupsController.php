<?php

class GroupsController extends Zend_Controller_Action
{

    public function init()
    {
        $request = $this->getRequest();
        $auth = Zend_Auth::getInstance();
        if (!$auth->hasIdentity()){
            $this->_redirect('/auth/login/from/groups');
        }
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
    }

    public function indexAction()
    {
        $this->view->messages = $this->_helper->flashMessenger->getMessages();
        $currentSort = $this->_getParam('o', 'default');
        $groups = new Application_Model_DbTable_Groups();
        $resultSet = $groups->fetchAllGroups($this->_getParam('p', 1), $currentSort);
        $this->view->groups = $resultSet;
        $this->view->orderLinks = $this->getOrderLinks($currentSort);
    }

    private function getOrderLinks($currentSortTerm) {
        // Не лучшее решение, конечно, но лучше, чем нагромождение "if"-ов
        $orderLinksArr = array(
            'id_a' => array(
                'id' => array('id_d', 'icon-sort-up'),
                'name' => array('nm_a', 'icon-sort'),
                'memberCount' => array('mc_a', 'icon-sort')
            ),
            'id_d' => array(
                'id' => array('id_a', 'icon-sort-down'),
                'name' => array('nm_a', 'icon-sort'),
                'memberCount' => array('mc_a', 'icon-sort')
            ),
            'nm_a' => array(
                'id' => array('id_d', 'icon-sort'),
                'name' => array('nm_d', 'icon-sort-up'),
                'memberCount' => array('mc_a', 'icon-sort')
            ),
            'nm_d' => array(
                'id' => array('id_a', 'icon-sort'),
                'name' => array('nm_a', 'icon-sort-down'),
                'memberCount' => array('mc_a', 'icon-sort')
            ),
            'mc_a' => array(
                'id' => array('id_a', 'icon-sort'),
                'name' => array('nm_a', 'icon-sort'),
                'memberCount' => array('mc_d', 'icon-sort-up')
            ),
            'mc_d' => array(
                'id' => array('id_a', 'icon-sort'),
                'name' => array('nm_a', 'icon-sort'),
                'memberCount' => array('mc_a', 'icon-sort-down')
            ),
            'default' => array(
                'id' => array('id_a', 'icon-sort'),
                'name' => array('nm_a', 'icon-sort'),
                'memberCount' => array('mc_a', 'icon-sort')
            )
        );

        if(!array_key_exists($currentSortTerm, $orderLinksArr)) {
            return $orderLinksArr['default'];
        }

        return $orderLinksArr[$currentSortTerm];
    }

    public function addAction()
    {
        $form = new Application_Form_Group();
        $form->submit->setLabel('Сохранить');
        $this->view->form = $form;

        // Если форма была отправлена...
        if ($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            // Если форма прошла проверки...
            if ($form->isValid($formData)) {
                $name = $form->getValue('name');
                $groups = new Application_Model_DbTable_Groups();
                $groups->addGroup($name);
                $this->_helper->flashMessenger->addMessage('Группа успешно добавлена');
                $this->_helper->redirector('index');
            } else {
                $form->populate($formData);
            }
        }
    }

    public function editAction()
    {
        $id = (int)$this->_getParam('id', 0);
        $form = new Application_Form_Group(array('groupID' => $id));
        $form->submit->setLabel('Сохранить');
        $this->view->form = $form;

        if($this->getRequest()->isPost()) {
            $formData = $this->getRequest()->getPost();
            if ($form->isValid($formData)) {
                $id = (int)$form->getValue('id');
                $id = $id !== 0 ? $id : null;
                $name = $form->getValue('name');
                $groups = new Application_Model_DbTable_Groups();
                $groups->editGroup($id, $name);
                $this->_helper->flashMessenger->addMessage('Информация группы сохранена');
                $this->_helper->redirector('index');
            } else {
                $form->populate($formData);

            }
        } else {
            if($id > 0) {
                $groups = new Application_Model_DbTable_Groups();
                $form->populate($groups->getGroup($id));
            }
        }
    }

    public function deleteAction()
    {
        $this->view->notEmpty = null;
        if ($this->getRequest()->isPost()) {
            $del = $this->getRequest()->getPost('del');
            if($del === 'Да') {
                $id = (int)$this->getRequest()->getPost('id');
                $groups = new Application_Model_DbTable_Groups();
                $memberCount = $groups->getMemberCount($id);

                if($memberCount > 0) {
                    $customers = new Application_Model_DbTable_Customers;
                    $customers ->deleteEverybodyInAGroup($id);
                }

                $groups->deleteGroup($id);

            }
            $this->_helper->flashMessenger->addMessage('Группа была успешно удалена');
            $this->_helper->redirector('index');
        } else {
            $id = $this->_getParam('id', 0);
            $groups = new Application_Model_DbTable_Groups();
            $this->view->group = $groups->getGroup($id);
            $memberCount = $groups->getMemberCount($id);
            $memberCount = (int)$memberCount;
            $nounForm = $groups->getNumWord($memberCount, array('клиент', 'клиента', 'клиентов'));
            if($memberCount > 0) {
                $this->view->memberCount = $memberCount;
                $this->view->nounForm = $nounForm;
                $this->view->notEmpty = true;
            }
        }
    }


}







