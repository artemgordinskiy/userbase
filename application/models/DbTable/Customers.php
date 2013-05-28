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
        $tableColumns = $this->info(Zend_Db_Table_Abstract::COLS);
        array_push($tableColumns, 'group_name');

        $page = (int)$page;
        $resultCount = (int)$resultCount;
        $filterById = (int)$filterById;

        $query = $this->select();
        $query->setIntegrityCheck(false)
              ->from('customers', array('customers.group_id', 'customers.id', 'login', 'email', 'userpic_ext', 'acc_exp_date'))
              ->joinLeft('groups', 'customers.group_id = groups.id', 'name as group_name');

        if($filterById > 0) {
            $query->where('customers.group_id = ?', $filterById);
        }

        if($filterByDate !== false) {
            if($filterByDate === 'expired') {
                $query->where('customers.acc_exp_date < NOW()');
            } else {
                $query->where('customers.acc_exp_date > NOW()');
            }
        }

        if(in_array($orderBy, $tableColumns)) {
            $order = $orderBy . ' ';
            $order .= $orderDirection === 'ASC' ? 'ASC' : 'DESC';
            $query->order($order);
        };

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
        $select = $this->select();

        if($id <= 0) {
            throw new Exception('Указан несуществующий ID группы: ' . $id . '. Customers.php/getCustomer()');
        }

        $select->where('id = ?', (int)$id);
        $row = $this->_fetch($select);
        $row = $row[0];
        // Если запрос прошел удачно, в $row будет либо массив, либо объект
        if(is_array($row) || is_object($row)) {
            return $row;
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
    public function addCustomer($group_id = null, $acc_exp_date = null, $pass = null, $login, $email, $userpicExt = null) {
        $group_id = (int)$group_id;

        $data = array(
            'group_id' => $group_id,
            'acc_exp_date' => $acc_exp_date,
            'login' => $login,
            'email' => $email
        );

        if(strlen($pass) > 0) {
            $generatedSalt = $this->generatePassSalt(50);
            $data['password'] = md5(USER_STATIC_PASS_SALT . $pass . $generatedSalt);
            $data['pass_salt'] = $generatedSalt;
        }

        if(strlen($userpicExt) > 0) {
            $data['userpic_ext'] = $userpicExt;
        }

        // insert() возвращает Primary Key новой записи, если запрос прошел удачно;
        // «parameter binding» производится автоматически, по кажому столбцу.
        $result = $this->insert($data);
        $result = (int)$result;

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
        $id = (int)$id;
        $group_id = (int)$group_id;

        $data = array(
            'acc_exp_date' => $acc_exp_date,
            'login' => $login,
            'email' => $email,
        );

        if($id > 0) {
            $data['group_id'] = $group_id;
        }

        if(strlen($pass) > 0) {
            $generatedSalt = $this->generatePassSalt(50);
            $data['password'] = md5(USER_STATIC_PASS_SALT . $pass . $generatedSalt);
            $data['pass_salt'] = $generatedSalt;
        }

        if(strlen($userpicExt) > 0) {
            $data['userpic_ext'] = $userpicExt;
        }

        // update() возвращает количество затронутых рядов, сохраним его для проверки.
        // «parameter binding» производится автоматически, по кажому столбцу.
        $rowsAffected = (int)$this->update($data, 'id=' . (int)$id);
        if($rowsAffected >= 0) {
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
        // (int) переваривает что угодно, даже буквы (в «0»). Так что, с безопасностью должно быть всё ок.
        $id = (int)$id;

        if($id <= 0) {
            throw new Exception('Указан несуществующий ID группы: ' . $id . '. Customers.php/getMemberCount()');
        }

        // метод delete() возвращает количество затронутых рядов, сохраним его для проверки.
        $rowsAffected = (int)$this->delete('id = ' . $id);
        if($rowsAffected === 1) {
            // Удаляем юзерпик
            array_map('unlink', glob(PUBLIC_PATH . '/images/uploads/' . $id . '.*'));
            return true;
        } else {
            throw new Exception('Не получилось удалить клиента №' . $id . '. Вероятнее всего, его не существует.');
        }
    }

    /**
     * Генерирует строку из случайных символов, чтобы использовать
     * в качестве «соли» при хешировании.
     * @param  integer   $length    Необходимая длина
     * @return string               Строка указанной длины
     */
    public function generatePassSalt($length) {
        $characters = '!@#$%^&*()_+0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

}