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
    public function fetchAllGroups($page = 1, $orderBy = 'id', $orderDirection = 'ASC', $resultCount = 10) {
        $query = $this->select();
        $query->order($orderBy . ' ' . $orderDirection);

        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbTableSelect($query));
        $paginator->setItemCountPerPage((int)$resultCount);
        $paginator->setCurrentPageNumber((int)$page);
        return $paginator;
    }

    /**
     * Запрос данных группы из БД
     * @param  [INT] $id    ID группы;
     * @return [ARR]        Массив данных группы, в случае успешного запроса. Иначе — Exception;
     */
    public function getGroup($id) {
        $id = (int)$id;
        $row = $this->fetchRow('id=' . $id);
        // Если запрос прошел удачно, в $row будет либо массив, либо объект
        if(is_array($row) || is_object($row)) {
            return $row->toArray();
        } else {
            throw new Exception('Группы с ID #' . $id . ' не существует.');
        }
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
        $result = $this->insert($data);
        $result = (int)$result;
        if(is_int($result)) {
            return true;
        } else {
            throw new Exception('Не получилось добавить группу.' . $result);
        }
    }

    /**
     * Редактирует информацию группы по ID
     * @param   [INT]   $id        ID группы
     * @param   [STR]   $login     Название группы, до 64 символов;
     * @return  [BOOL]             True, при успехе; Иначе — Exception
     */
    public function editGroup($id, $name) {
        $data = array(
            'name' => $name,
        );
        // функция update() возвращает количество затронутых рядов. Сохраняем его для проверки.
        $result = $this->update($data, 'id=' . (int)$id);
        $result = (int)$result;
        if($result === 1) {
            return true;
        } else {
            throw new Exception('Не получилось изменить информацию группы №' . $id . '. Вероятнее всего, ее не существует.');
        }
    }

    /**
     * Удаляет группы по ID
     * @param  [INT]    $id    ID группы;
     * @return [BOOL]          True, при успехе; Иначе — Exception
     */
    public function deleteGroup($id) {
        // функция delete() возвращает количество затронутых рядов. Сохраняем его для проверки.
        $rowsAffected = $this->delete('id=' . $id);
        if($rowsAffected === 1) {
            return true;
        } else {
            throw new Exception('Не получилось удалить группу №' . $id . '. Вероятнее всего, ее не существует.');
        }
    }
}