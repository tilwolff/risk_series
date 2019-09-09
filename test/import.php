<?php

$db=new SQLite3("../data/data.sqlite");

$db->exec('BEGIN;');

$sql="CREATE TABLE IF NOT EXISTS ts_def(
        ts_id INTEGER NOT NULL PRIMARY KEY,
        name TEXT NOT NULL,
        tag TEXT,
        default_risk_type TEXT,
        log_shift REAL,
        csv_alias TEXT,
        CONSTRAINT unique_series_def UNIQUE (name, tag))";

$db->exec($sql);

function retrieve_number($str){
        $in=$str;
        if (1==substr_count($in,',')) $in=str_replace(',','.',$in);
        if(is_numeric($in)) return floatval($in);
        return null;
}

$lines=file("ts_definitions.csv");

$sql="INSERT OR IGNORE INTO ts_def(name, tag) VALUES(?,?)";
$prepared=$db->prepare($sql);
$prepared->bindParam(1, $name);
$prepared->bindParam(2, $tag);

foreach($lines as $line) {
        $line=explode(";",trim($line));
        $name=$line[0];
        $tag=(""==$line[1]) ? null : $line[1];
        $prepared->execute();
}

$sql="UPDATE ts_def
        SET default_risk_type=?, 
        log_shift=?, 
        csv_alias=?
        WHERE 
        name=? and tag=?";
$prepared=$db->prepare($sql);
$prepared->bindParam(1, $default_risk_type);
$prepared->bindParam(2, $log_shift);
$prepared->bindParam(3, $csv_alias);
$prepared->bindParam(4, $name);
$prepared->bindParam(5, $tag);

foreach($lines as $line) {
        $line=explode(";",trim($line));
        $name=$line[0];
        $tag=(""==$line[1]) ? null : $line[1];
        $default_risk_type=(""==$line[2]) ? null : $line[2];
        $log_shift=floatval($line[3]);
        $csv_alias=$line[4];
        $prepared->execute();
}


$sql="CREATE TABLE IF NOT EXISTS ts_data(
        ts_id INTEGER NOT NULL,
        dt INTEGER NOT NULL,
        updated INTEGER DEFAULT ((julianday('now') -2440587.5)*86400.0),
        value REAL NOT NULL,
        PRIMARY KEY (ts_id, dt, updated))";

$db->exec($sql);

$lines=file("./buba.csv");

$sql="INSERT INTO ts_data(ts_id, dt, value) VALUES(?,strftime('%s',?),?)";
$prepared=$db->prepare($sql);
$prepared->bindParam(1, $ts_id);
$prepared->bindParam(2, $dt);
$prepared->bindParam(3, $value);

$header=array_shift($lines);
$header=explode(";",trim($header));


for($i=1; $i<sizeof($header); $i++){
        $ts_id=$db->querySingle("SELECT ts_id FROM ts_def WHERE csv_alias='" . $header[$i] . "'", false);
        $header[$i]=(false==$ts_id) ? '' : $ts_id;
}

foreach($lines as $line) {
        $line=explode(";",trim($line));
        if(sizeof($line)!=sizeof($header)) continue;
        $dt=$line[0];
        if (strtotime($dt)==false) continue;
        
        
        for($i=1; $i<sizeof($header); $i++){
                if(''==$header[$i]) continue;
                $ts_id=$header[$i];
                $value=retrieve_number($line[$i]);
                if ($value!=null) $prepared->execute();
        }
}


$db->exec('COMMIT;');

$db=null;

?>
