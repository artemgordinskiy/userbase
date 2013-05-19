<?php

class Application_Model_DbTable_Customers extends Zend_Db_Table_Abstract
{

    protected $_name = 'customers';

    /**
     * Выдает всех пользователей; Постранично, и с сортировкой;
     * @param  [INT]   $page             Страница, для которой нужно выдать результаты
     * @param  [STR]   $orderBy          Имя столбца, по которому будем сортировать
     * @param  [STR]   $orderDirection   Направление сортировки
     * @param  [INT]   $resultCount      Количество результатов
     * @return [ARR]                     Массив с результатами
     */
    public function fetchAllCustomers($page = 1, $orderBy = 'id', $orderDirection = 'ASC', $resultCount = 10, $filterById = false, $filterByDate = false) {
        $query = $this->select();
        $query->setIntegrityCheck(false)
              ->from('customers', array('customers.group_id', 'customers.id', 'login', 'password', 'email', 'userpic_ext', 'acc_exp_date'));

        if($filterById !== false) {
            $query->where('customers.group_id = ' . $filterById);
        }

        if($filterByDate !== false) {
            if($filterByDate === 'expired') {
                $query->where('customers.acc_exp_date < NOW()');
            } else {
                $query->where('customers.acc_exp_date > NOW()');
            }
        }

        $query->join('groups', 'customers.group_id = groups.id', 'name as group_name')
              ->order($orderBy . ' ' . $orderDirection);
        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($query));
        $paginator->setItemCountPerPage((int)$resultCount);
        $paginator->setCurrentPageNumber((int)$page);
        return $paginator;
    }

    /**
     * Запрос данных пользователя из БД
     * @param  [INT]    $id    ID пользователя;
     * @return [ARR]           Массив данных пользователя, в случае успешного запроса. Иначе — Exception;
     */
    public function getCustomer($id) {
        $id = (int)$id;
        $row = $this->fetchRow('id=' . $id);
        // Если запрос прошел удачно, в $row будет либо массив, либо объект
        if(is_array($row) || is_object($row)) {
            return $row->toArray();
        } else {
            throw new Exception('Клиента номер ' . $id . ' не существует.');
        }
    }

    /**
     * Добавление нового пользователя
     * @param  [INT]   $group_id      ID группы, к которой принадлежит пользователь
     * @param  [STR]   $acc_exp_date Дата и время просрочки аккаунта в формате '9999-12-31 23:59:59'
     * @param  [STR]   $pass         Пароль, до 64 символов;
     * @param  [STR]   $login        Логин, до 64 символов;
     * @param  [STR]   $email        Электропочта, до 64 символов;
     * @return [BOOL]                True, если запрос прошел удачно, иначе создает Exception;
     */
    public function addCustomer($group_id, $acc_exp_date, $pass, $login, $email, $userpicExt) {
        $data = array(
            'group_id' => $group_id,
            'acc_exp_date' => $acc_exp_date,
            'password' => $pass,
            'login' => $login,
            'email' => $email,
            'userpic_ext' => $userpicExt
        );
        // Insert возвращает Primary Key новой записи, если запрос прошел удачно;
        $result = (int)$this->insert($data);
        if(is_int($result)) {
            return $result;
        } else {
            throw new Exception('Не получилось добавить клиента.');
        }
    }

    /**
     * Редактирует информацию пользователя по ID
     * @param [INT] $id           ID пользователя
     * @param [INT] $group_id      ID группы, к которой принадлежит пользователь
     * @param [STR] $acc_exp_date Дата и время просрочки аккаунта в формате '9999-12-31 23:59:59'
     * @param [STR] $pass         Пароль, до 64 символов;
     * @param [STR] $login        Логин, до 64 символов;
     * @param [STR] $email        Электропочта, до 64 символов;
     * @return[BOOL]              True, при успехе; Иначе — Exception
     */
    public function editCustomer($id, $group_id, $acc_exp_date, $pass, $login, $email, $userpicExt) {
        $data = array(
            'group_id' => $group_id,
            'acc_exp_date' => $acc_exp_date,
            'password' => $pass,
            'login' => $login,
            'email' => $email,
            'userpic_ext' => $userpicExt
        );

        // функция update() возвращает количество затронутых рядов, сохраним его для проверки.
        $rowsAffected = (int)$this->update($data, 'id=' . $id);
        if($rowsAffected = 1) {
            return true;
        } else {
            throw new Exception('Не получилось изменить информацию клиента №' . $id . '. Вероятнее всего, его не существует.');
        }
    }

    /**
     * Удаляет пользователя по ID
     * @param  [INT]   $id    ID пользователя;
     * @return [BOOL]         True, при успехе; Иначе — Exception
     */
    public function deleteCustomer($id) {
        // метод delete() возвращает количество затронутых рядов, сохраним его для проверки.
        $rowsAffected = (int)$this->delete('id=' . $id);
        if($rowsAffected = 1) {
            // Удаляем юзерпик
            array_map('unlink', glob(PUBLIC_PATH . '/images/uploads/' . $id . '.*'));
            return true;
        } else {
            throw new Exception('Не получилось удалить клиента №' . $id . '. Вероятнее всего, его не существует.');
        }
    }

}