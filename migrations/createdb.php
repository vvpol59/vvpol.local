<html>
<body>
create database working_time_log<br><br>
<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);


function execSql($tableName, $sql){
    try{
        echo '<p>Создание таблицы '.$tableName;
        global $db;
        $res = $db->exec($sql);
        if ($res === false){
            echo $db->lastErrorMsg().'</p>';
        }
        echo ' Ok!</p>';
    } catch (Exception $e) {
        echo $db->lastErrorMsg().'</p>';
    }
}
$db = new SQLite3("../data/working_time_log.db");
$db->enableExceptions(true);

$sql = "CREATE TABLE working_name(id INTEGER PRIMARY KEY, name TEXT, max_time INT, hidden INT)";
execSql('working_name', $sql);
$sql = "CREATE TABLE working_log(id INTEGER PRIMARY KEY, date INTEGER, begin_work integer, stop_work integer, name_id integer)";
execSql('working_log', $sql);
$sql = 'CREATE INDEX date_index ON working_log (date)';
execSql('date_index', $sql);
$db->close();

/*
 * date - YYYYMMDD
 * begin_work, stop_work - номер секунды в date
 */


?>
</body>
</html>