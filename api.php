<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 0);
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
        $res = $db->query($sql);
        if ($res === false){
            $response['error'] = $db->lastErrorMsg();
            return [true, []];
        }
        $data = [];
        while (($row = $res->fetchArray(SQLITE3_ASSOC))) {
            array_push($data, $row);
        }
        return [false, $data];
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        return [true, []];
    }
}

function execSql($sql){
    try{
        global $db, $response;
        $res = $db->exec($sql);
        if ($res === false){
            $response['error'] = $db->lastErrorMsg();
            return [true, ''];
        }
        return [false, ''];
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        return [true, ''];
    }
}

//================= Функции логера ====================

/**
 * Список действий
 * @return array
 */
/*
function actionList(){
    global $db, $response;
    [$error, $data] = select('select id, name, max_time from working_name');
    if ($error){
        $response['error'] = $error;
        return [];
    }
    return $data;
}
*/

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
    $sql = "insert into working_name (name, max_time, hidden) values('$name',$maxTime, 0)";
    [$is_error, $res] = execSql($sql);
    if ($is_error){
        $response['debug'][] = $sql;
        return '';
    }
    return $db->lastInsertRowID();
}

/**
 * Все действия за период
 * @param $begDate   DD-MM-YYYY
 * @param $endDate   DD-MM-YYYY
 * @return array
 */
function actionGroupLog($begDate, $endDate){
    global $response;
    try {
        $_ = explode('-', $begDate);
        $beg = (integer)($_[2] . $_[1] . $_[0]);
        if (!$endDate) {
            $end = $beg;
        } else {
            $_ = explode('-', $endDate);
            $end = (integer)($_[2] . $_[1] . $_[0]);
        };
        $subSQL = "select sum(stop_work - begin_work) as sum from working_log l where n.id=name_id and date BETWEEN $beg and $end";
        $sql = "select n.id, n.name, max_time, ($subSQL) as sum from working_name n";
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
 * Обработка изменения действия
 * @param $curIdAction    on tableworking_name
 * @param $newIdAction    on tableworking_name
 * @return array     (Длительность закрытой акции (сек.), timestsmp последнего использования)
 */
function changeAction($curIdAction, $newIdAction){
    global $response;
    // todo  Если пересеклись сутки - закрытие оформить как две записи
    try {
        $time = time() - strtotime('today');  // Текущая секунда суток
        //$timestamp = time();
        $duration = 0;
        // Выборка текущей акции - последняя с $curIdAction
        if ($curIdAction){
            $sql = "select id, begin_work, stop_work from working_log where name_id=$curIdAction order by id desc limit 1";
            [$is_error, $data] = select($sql);
            $response['debug'][] = $sql;
            $response['debug'][] = $data;
            if ($is_error){
                $response['debug'][] = $sql;
                return array('duration' => 0, 'last' => $time);
            }
            // Время останова на текущей
            $id = $data[0]['id'];
            $sql = "update working_log set stop_work=$time where id=$id";
            [$is_error, $res] = execSql($sql);
            if ($is_error){
                $response['debug'][] = $sql;
                return array('duration' => 0, 'last' => $time);
            }

        } else {  // Нет текущего действия (сразу после загрузки, например)
            $data[0]['begin_work'] = $time;
        }
        $response['debug'][] = $data;
        // подсчёт длительности
        if (count($data) == 0) {
            $response['error'] = 'not open action';
            $duration = 0;
        } else {
            $duration = $time - $data[0]['begin_work'];
        }
        // Добавление записи на новой
        // Время старта для новой
        if ($newIdAction){
            $sql = "insert into working_log (name_id, begin_work) values($newIdAction, $time)";
            [$is_error, $res] = execSql($sql);
            if ($is_error){
                $response['debug'][] = $sql;
                return array('duration' => 0, 'last' => $time);
            }
        }
        return array('duration' => $duration, 'last' => $time);
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        return array('duration' => 0, 'last' => $time);
    }

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
