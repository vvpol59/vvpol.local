<html>
<body>
create database working_time_log<br><br>
<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


 $db = new SQLite3("working_time_log.db");
  
 $db->exec("CREATE TABLE working_name(id INTEGER PRIMARY KEY, name TEXT, max_time INT)");
 $db->exec("CREATE TABLE working_log(id INTEGER PRIMARY KEY, begin_work integer, stop_work integer, name_id integer)");
 

 /*
 $result = $db->query('select * from cars');


while ($row = $result->fetchArray()) {
    echo "{$row['id']} {$row['name']} {$row['price']} <br>";
}
*/ 
$db->close();
echo "ok"

?>
</body>
</html>