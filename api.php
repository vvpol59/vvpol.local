<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
set_time_limit(600);
ini_set('max_execution_time', 600);



$db = new SQLite3("data/working_time_log.db");
$response = array(
    'jsonrpc' => '2.0',
    'result' => array(),
    'id' => 'id',
    'debug' => array(),
    'src' => 'gateway'
);





class msgException extends Exception {

}

function getVar(&$var, $default = '') {
    return isset($var) ? $var : $default;
}


/**
 * Удаленное выполнение метода
 *-----------------------------
 * @param string $class
 * @param string $method
 * @param string|array $params
 * @return mixed
 * @throws msgException
 */
function callRemoteMethod($class, $method, $params){
// Проверка доступа к метод
    if (call_user_func_array(array($class, 'isRemotable'), array($method))) {
        if (empty($params)) {
            $params = array();
        } elseif (!is_array($params)) {
            $params = array($params);
        }
        return call_user_func_array(array($class, $method), $params);
    } else {
        throw new msgException('Метод ' . $class . '.' . $method . ' недоступен для удаленного выполнения.');
    }
}

/**
 * Удалённое выполнение функции
 * @param $fun
 * @param $params
 * @return mixed
 */
function callRemoteFunction($fun, $params){
    return call_user_func_array($fun, $params);
}

function select($sql){
    global $db;
    $res = $db->query($sql);
    if ($res === false){
        $response['error'] = $db->lastErrorMsg();
        return [$db->lastErrorMsg(), []];
    }
    $data = [];
    while (($row = $res->fetchArray(SQLITE3_ASSOC))) {
        array_push($data, $row);
    }
    return [null, $data];
}

/**
 * Список действий
 * @return array
 */
function actionList(){
    global $db, $response;
    [$error, $data] = select('select id, name, max_time from working_name');
    if ($error){
        $response['error'] = $error;
        return [];
    }
    return $data;
}

/**
 * Добавить действие
 * @param $name
 * @param $maxTime
 * @return int
 */
function actionAdd($name, $maxTime){
    //return [1,2,3];
    global $db, $response;
    $name = $db->escapeString($name);
    $sql = "insert into working_name (name, max_time) values('$name',10)";
    $response['debug'] = $sql;
        $db->exec($sql);
    return $db->lastInsertRowID();
}

/**
 * Все действия за период
 * @param $begDate
 * @param $endDate
 * @return array
 */
function actionLog($begDate, $endDate){
    global $db, $response;
    [$error, $data] = select('select * from working_log left join working_name dict on name.id = dict.id');
    return $data;

    //$sql = 'select * from working_log left join working_name dict on name.id = dict.id';
    //$res = $db->query($sql);
    /*
    if ($res === false){
        $response['error'] = $db->lastErrorMsg();
        return [];
    }
    $data = [];

    while (($row = $res->fetchArray(SQLITE3_ASSOC))) {
        array_push($data, $row);
    }
    return $data;
    */
}

/**
 * Обработка изменения действия
 */
function changeAction($curIdAction, $newIdAction){
    global $db, $response;
    // Время останова на текущей
    // поиск незакрытой акции
    [$error, $data] = select('select id from working_log where stop_work is null');
    if ($error){
        return [];
    }
    /*
    $sql = 'select id from working_log where stop_work is null';
    $res = $db->query($sql);
    if ($res === false){
        $response['error'] = $db->lastErrorMsg();
        return [];
    }
    */
    $open_actions = '';
    for ($i = 0; $i < count($data); $i++){
        $open_actions .= (string)$data[$i]['id'] . ',';
    }
    /*
    while (($item = $res->fetchArray(SQLITE3_ASSOC))) {
        $open_actions .= (string)$item['id'] . ',';
        //array_push($open_actions, $item);
    }
*/
    $timestamp = time();
    if ($open_actions){
        $actions = trim($open_actions, ',');
        $sql = "update working_log set stop_work=$timestamp where id in($actions)";
        $res = $db->exec($sql);
        if ($res === false){
            $response['error'] = $db->lastErrorMsg();
            return [];
        }
    }
/*
    if ($curIdAction){ // переключение акции
        $sql = 'select id from working_log where name_id=' . $curIdAction . ' order by id desc limit 1';
        $res = $db->query($sql);
        if ($res === false){
            $response['error'] = $db->lastErrorMsg();
            return [];
        }
        $idLog = $res->fetchArray(SQLITE3_ASSOC); //['id'];
        $sql = "update working_log set stop_work=$timestamp where id=$idLog";
        $db->exec($sql);
    }
    */
    // Время старта для новой
    $sql = "insert into working_log (name_id, begin_work) values($newIdAction, $timestamp)";
    $res = $db->exec($sql);
    if ($res === false){
        $response['error'] = $db->lastErrorMsg();
        return [];
    }
    return 'OK';

}

//====================================================================
try {
    $auth = explode('~', (isset($_COOKIE['auth']) ? $_COOKIE['auth'] : '~') . '~');
    $post = $body = file_get_contents('php://input');
    //self::$response['re'] = $a;
    //self::$response['srv'] = $_SERVER;
    //$response['debug'] = $post;
    $data = json_decode($post, true);
    if (getVar($data['jsonrpc'], '') != '2.0') {
        throw new msgException('Неверный протокол [' . $post . ']');
    }
    $method = getVar($data['method'], '');
    if (!$method) {
        throw new msgException('Не указан метод');
    }
    $classMethod = explode('.', $method);
    $res = call_user_func_array($method, $data['params']);
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
} finally {
    $response['result'] = isset($res) ? $res : '';
    echo json_encode($response);
}
