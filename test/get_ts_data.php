<?php
        
$db=new SQLite3("./data/data.sqlite");

$results = $db->query("SELECT ts_id, date(dt, 'unixepoch') as dt, datetime(updated, 'unixepoch') as updated, value FROM ts_data order by ts_id, dt,updated");

$arr=array();
while ($row = $results->fetchArray(SQLITE3_ASSOC)) { 
        array_push($arr, $row);
}

echo json_encode($arr);

$db=null;

?>
