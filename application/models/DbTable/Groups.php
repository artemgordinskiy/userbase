<?php

class Application_Model_DbTable_Groups extends Zend_Db_Table_Abstract
{

    protected $_name = 'groups';

    /**
     * Выдает все группы; Постранично, и с сортировкой;
     * @param  [INT]   $page             Страница, для которой нужно выдать результаты
     * @param  [STR]   $orderBy          Имя столбца, по которому будем сортировать
     * @param  [STR]   $orderDirection   Направление сортировки
     * @param  [INT]   $resultCount      Количество результатов
     * @return [ARR]                     Массив с результатами
     */
    public function fetchAllGroups($page = 1, $orderTerm = 'id_a', $resultCount = 10) {
        $orderTerms = array(
            'id_a' => 'id ASC',
            'id_d' => 'id DESC',
            'nm_a' => 'name ASC',
            'nm_d' => 'name DESC',
            'mc_a' => 'memberCount ASC',
            'mc_d' => 'memberCount DESC'
        );

        $page = (int)$page;
        $resultCount = (int)$resultCount;

        $query = $this->select();
        $query->setIntegrityCheck(false)
              ->from('groups', array('groups.id', 'groups.name', 'COUNT(customers.id) as memberCount'))
              ->joinLeft('customers', 'groups.id = customers.group_id', null)
              ->group('groups.name');

        if(array_key_exists($orderTerm, $orderTerms)) {
            $query->order($orderTerms[$orderTerm]);
        } else {
            $query->order('id ASC');
        }

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($query));
        $paginator->setItemCountPerPage($resultCount);
        $paginator->setCurrentPageNumber($page);
        return $paginator;
    }

    /**
     * Запрос данных группы из БД
     * @param  [INT] $id    ID группы;
     * @return [ARR]        Массив данных группы, в случае успешного запроса. Иначе — Exception;
     */
    public function getGroup($id) {
        if(!$this->isRealID($id)) {
            throw new Exception('Ошибка в Groups.php/getMemberCount(). Неверный/несуществующий ID группы: ' . $id);
        }

        $row = $this->fetchRow('id=' . $id);
        $row = $row->toArray();
        // Если запрос прошел удачно, в $row будет либо массив данных группы
        if(!is_array($row)) {
            throw new Exception('Группы с ID #' . $id . ' не существует.');
        }

        return $row;

    }

    /**
     * Добавляет новую группу
     * @param   [STR]   $login        Название группы, до 64 символов;
     * @return  [BOOL]  $name         True, если запрос прошел удачно, иначе — Exception;
     */
    public function addGroup($name) {
        $data = array(
            'name' => $name,
        );
        // Insert возвращает Primary Key новой записи, если запрос прошел удачно;
        // «parameter binding» производится «под капотом»
        $result = $this->insert($data);
        $result = (int)$result;

        if($result <= 0) {
            throw new Exception('Не получилось добавить группу.' . $result);
        }

        return true;

    }

    /**
     * Редактирует информацию группы по ID
     * @param   [INT]   $id        ID группы
     * @param   [STR]   $login     Название группы, до 64 символов;
     * @return  [BOOL]             True, при успехе; Иначе — Exception
     */
    public function editGroup($id, $name) {

        if(!$this->isRealID($id)) {
            throw new Exception('Ошибка в Groups.php/editGroup(). Неверный/несуществующий ID группы: ' . print_r($id));
        }

        $data = array(
            'name' => $name,
        );
        // функция update() возвращает количество затронутых рядов. Сохраняем его для проверки.
        // «parameter binding» производится «под капотом»
        $result = $this->update($data, 'id=' . $id);
        $result = (int)$result;

        if($result !== 1 && $result !== 0) {
            throw new Exception('Ошибка в Groups.php/editGroup(); $id = ' . $id . '; $result = ' . $result);
        }

        return true;

    }

    /**
     * Удаляет группу по ID
     * @param  [INT]    $id    ID группы;
     * @return [BOOL]          True, при успехе; Иначе — Exception
     */
    public function deleteGroup($id) {

        if(!$this->isRealID($id)) {
            throw new Exception('Ошибка в Groups.php/deleteGroup(). Неверный/несуществующий ID группы: ' . print_r($id));
        }

        // функция delete() возвращает количество затронутых рядов. Сохраняем его для проверки.
        $rowsAffected = $this->delete('id=' . $id);
        $rowsAffected = (int)$rowsAffected;

        if($rowsAffected !== 1) {
            throw new Exception('Произошла ошибка при удалении группы №' . $id . '. $rowsAffected = ' . $rowsAffected);
        }

        return true;
    }

    /**
     * Выбирает из БД только непустые группы
     * @return [ARR]    Массив групп
     */
    public function getAllGroupsWithMembers() {
        $query = $this->select();
        $query->setIntegrityCheck(false)
              ->from('groups', array('groups.id', 'groups.name'))
              ->joinRight('customers', 'groups.id = customers.group_id', null)
              ->group('groups.name')
              ->order('name ASC');

        if(is_array($query) || is_object($query)) {
            return $this->_fetch($query);
        } else {
            throw new Exception('Ошибка при работе с DB, в функции getAllGroupsWithMembers()');
        }
    }

    /**
     * Выдает количество клиентов в группе по ID
     * @param  [INT]    $groupID   ID группы
     * @return [ARR]               Массив с результатом
     */
    public function getMemberCount($groupID) {
        $groupID = (int)$groupID;

        if(!$this->isRealID($groupID)) {
            throw new Exception('Ошибка в Groups.php/getMemberCount(). Неверный/несуществующий ID группы: ' . print_r($groupID));
        }

        try {
            $query = $this->select();
            $query->setIntegrityCheck(false)
                  ->from('groups', array('COUNT(customers.id) as memberCount'))
                  ->joinLeft('customers', 'groups.id = customers.group_id', null)
                  ->where('groups.id = ?', $groupID);

            $memberCount = $this->_fetch($query);
        } catch(PDOException $e) {
            throw new Exception('Ошибка в Groups.php/getMemberCount(): ' . $e->getMessage());
        } catch(Exception $e) {
            throw new Exception('Ошибка в Groups.php/getMemberCount(): ' . $e->getMessage());
        }

        if(!is_array($memberCount)) {
            throw new Exception('Ошибка в Groups.php/getMemberCount(). Неожиданный тип $result. Содержимое: ' . print_r($result));
        }

        return $memberCount[0]['memberCount'];

    }

    /**
     * Производит проверку данного ID группы, сверяя его с БД.
     * @param  [INT]  $id   ID группы
     * @return [BOOL]       Результат проверки.
     */
    public function isRealID($id) {
        $id = (int)$id;

        if($id <= 0) {
            return false;
        }

        try {
            $query = $this->select();
            $query->from('groups', array('id'))->order('id');
            $rowSet = $this->_fetch($query);
            $IDs = array();
        } catch(PDOException $e) {
            throw new Exception('Ошибка в Groups.php/isRealID(): ' . $e->getMessage());
        } catch(Exception $e) {
            throw new Exception('Ошибка в Groups.php/isRealID(): ' . $e->getMessage());
        }

        if(!is_array($rowSet)) {
            throw new Exception('Ошибка в Groups.php/isRealID(). Неправильный тип переменной. Содержимое: ' . print_r($rowSet));
        }

        foreach ($rowSet as $group => $_id) {
            array_push($IDs, $_id['id']);
        }

        if(!in_array($id, $IDs)){
            return false;
        }

        return true;
    }

    /**
     * Выбирает правильное склонение существительного, в зависимости от числительного
     * @param  [INT]    $inputNumber    Число, с которым используется существительное
     * @param  [ARR]    $wordWorms      Формы существительного,
     * в таком виде: array('клиент', 'клиента', 'клиентов')
     *
     * @return [STR]    Правильная форма существительного
     */
    public function getNumWord($inputNumber, $wordForms) {
        $inputNumber = (int)$inputNumber;
        $intRemainder = null;
        $outputForm = null;

        $intRemainder = $inputNumber % 100;
        if ($intRemainder >= 11 && $intRemainder <= 19) {
            $outputForm = $wordForms[2];
        } else {
            $i = $intRemainder % 10;
            switch ($i) {
            case (1):
                $outputForm = $wordForms[0];
                break;
            case (2):
            case (3):
            case (4):
                $outputForm = $wordForms[1];
                break;
            default:
                $outputForm = $wordForms[2];
            }
        }

        return $outputForm;

    }

    /**
     * Создает ассоциативный массив с данными группы, пригодный для использования в выпадающих списках форм
     * @return [ARR]    Массив с данными, вида ['id' => 'name']
     */
    public function getGroupsForTheForm() {

        try {
            $groups = $this->fetchAll();
        } catch(PDOException $e) {
            throw new Exception('Ошибка в Groups.php/getGroupsForTheForm(): ' . $e->getMessage());
        } catch(Exception $e) {
            throw new Exception('Ошибка в Groups.php/getGroupsForTheForm(): ' . $e->getMessage());
        }

        $resultSet = array();
        foreach ($groups as $row => $group) {
            $resultSet[$group['id']] = $group['name'];
        }

        if(!is_array($resultSet) || count($resultSet) <= 1) {
            throw new Exception('Ошибка в Groups.php/getGroupsForTheForm(). Получен пустой, или неправильный массив: ' . var_dump($resultSet));
        }

        return $resultSet;

    }

}