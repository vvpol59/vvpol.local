<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
set_time_limit(600);
ini_set('max_execution_time', 600);

date_default_timezone_set('Europe/Moscow');

$cur_str = date("d-m-Y H:i:s");
$cur_ts = strtotime($cur_str);
$cd_str = date("d-m-Y H:i:s", $cur_ts);





$db = new SQLite3("data/working_time_log.db");
$db->enableExceptions(true);
$response = array(
    'jsonrpc' => '2.0',
    'result' => array(),
    'id' => 'id',
    'debug' => array(),
    'src' => 'gateway'
);

function secondsToTime($inputSeconds) {

    $secondsInAMinute = 60;
    $secondsInAnHour  = 60 * $secondsInAMinute;
    $secondsInADay    = 24 * $secondsInAnHour;

    // extract days
    $days = floor($inputSeconds / $secondsInADay);

    // extract hours
    $hourSeconds = $inputSeconds % $secondsInADay;
    $hours = floor($hourSeconds / $secondsInAnHour);

    // extract minutes
    $minuteSeconds = $hourSeconds % $secondsInAnHour;
    $minutes = floor($minuteSeconds / $secondsInAMinute);

    // extract the remaining seconds
    $remainingSeconds = $minuteSeconds % $secondsInAMinute;
    $seconds = ceil($remainingSeconds);

    // return the final array
    $obj = array(
        'd' => (int) $days,
        'h' => (int) $hours,
        'm' => (int) $minutes,
        's' => (int) $seconds,
    );
    return $obj;
}







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

/**
 * Выполнение select запроса
 * @param $sql
 * @return array
 */
function select($sql){
    try {
        global $db, $response;
        $response['debug'][] = 0;
        $res = $db->query($sql);
        $response['debug'][] = 1;
        if ($res === false){
            $response['error'] = $db->lastErrorMsg();
            return [$db->lastErrorMsg(), []];
        }
        $data = [];
        while (($row = $res->fetchArray(SQLITE3_ASSOC))) {
            array_push($data, $row);
        }
        return [null, $data];
    } catch (Exception $e) {
        $response['debug'][] = 2;
        $response['debug'][] = $e->getMessage();
        return [$e->getMessage(), []];
    }
}

//================= Функции логера ====================

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
 * @param $begDate   DD-MM-YYYY
 * @param $endDate   DD-MM-YYYY
 * @return array
 */
function actionGroupLog($begDate, $endDate){
    global $db, $response;
    try {
        $beg = strtotime("$begDate 00:00:00");
        $response['debug'][] = "$begDate 00:00:00";
        $response['debug'][] = $beg;
        $response['debug'][] = date('d-m-Y H:i:s', $beg);
        if (!$endDate) {
            $end = strtotime("$begDate 23:59:59");
            $response['debug'][] = "$begDate 23:59:59";
        } else {
            $end = strtotime("$endDate 23:59:59");
            $response['debug'][] = "$endDate 23:59:59";
        };
        $response['debug'][] = $end;
        $response['debug'][] = date('d-m-Y H:i:s', $end);

        $subSQL = "select sum(stop_work - begin_work) as sum from working_log l where n.id=name_id and begin_work BETWEEN $beg and $end";
        $sql = "select n.id, n.name, ($subSQL) as sum from working_name n";
        $response['debug'][] = $sql;
        [$error, $data] = select($sql);
        if ($error){
            $response['error'] = $error;
            return [];
        }
        return $data;
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        return [];
    }


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
    // strtotime('22-09-2008 00:01:00'); для диапазона стартовой даты

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

//================= функции експлорера базы =========================
/**
 * Выполнение select запроса
 * @param $sql
 * @return array|mixed
 */
function remoteSelect($sql){
    global $response;
    try {
        [$error, $data] = select($sql);

        if ($error){
            $response['error'] = $error;
            return [];
        }
        return $data;
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        return [];
    }
}

/**
 * выполнение eval
 * @param $codeStr
 * @return mixed|string
 */
function remoteEval($codeStr){
    global $response;
    $res = '';
    try {
        $_eval = "\$res = $codeStr;";
        $response['debug'][] = $_eval;
        eval($_eval);
        return $res;
    } catch (Exception $e){
        $response['error'] = $e->getMessage();
        return $e->getMessage();
    }
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
