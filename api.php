<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);



$db = new SQLite3("data/working_time_log.db");
$response = array(
    'jsonrpc' => '2.0',
    'result' => array(),
    'id' => 'id',
    'debug' => array(),
    'src' => 'gateway'
);





class msgException extends Exception
{

}

function getVar(&$var, $default = '')
{
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
function callRemoteMethod($class, $method, $params)
{
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
function callRemoteFunction($fun, $params)
{
    return call_user_func_array($fun, $params);
}


/**
 * Список действий
 * @return array
 */
function actionList(){
    global $db, $response;
    $sql = 'select id, name, max_time from working_name';
    // В цикле выведем все полученные данные
    $res = $db->query($sql);
    if ($res === false){
        $response['error'] = $db->lastErrorMsg();
        return [];
    }
    $data = [];

    while (($row = $res->fetchArray(SQLITE3_ASSOC))) {
        //var_dump($row);
    //}sqlite_fetch_array($res)) {
        array_push($data, $row);
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
    $sql = 'select * from working_log left join working_name dict on name.id = dict.id';
    $res = $db->query($sql);
    if ($res === false){
        $response['error'] = $db->lastErrorMsg();
        return [];
    }
    $data = [];

    while (($row = $res->fetchArray(SQLITE3_ASSOC))) {
        array_push($data, $row);
    }
    return $data;
}

function changeAction($curIdAction, $newIdAction){
    global $db, $response;
    // Время останова на текущей
    $sql = 'select id from working_log where name_id = ' . $curIdAction . 'order by id desc limit 1';
    $res = $db->query($sql);
    if ($res === false){
        $response['error'] = $db->lastErrorMsg();
        return [];
    }
    $idLog = $res->fetchArray(SQLITE3_ASSOC)['id'];
    $sql = "update working_log set stop_work=date('Ymd') where id=$idLog";
    $db->exec($sql);
    // Время старта на новой
    $sql = "insert into work_log (name_id, begin_work) values($newIdAction, date('Ymd'))";
    $db->exec($sql);
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
    //if (count($classMethod) != 2) {
    //    throw new msgException('Неверно передан метод');
    //}
    //$res = call_user_func_array($method, $data['params']);

    $res = call_user_func_array($method, $data['params']);
    // $res = callRemoteMethod($classMethod[0], $classMethod[1], $data['params']);
    //echo json_encode($response);
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    //	echo json_encode(self::$response);
} finally {
    //if (isset($res['debug'])) {
    //    $response['debug'] = $res['debug'];
    //}
    //if ($res['result'])) {
    $response['result'] = isset($res) ? $res : '';
    //}
    echo json_encode($response);
}
// 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
