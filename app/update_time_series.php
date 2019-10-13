<?PHP

/*
Copyright (c) 2018 Dr. Tilman Wolff-Siemssen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

function update_time_series_definitions($ts_name){
        //Update time series defs
	header('Content-Type: application/json'); //return failure or success in JSON format
	
	//create database and table if not existing
	$db=new SQLite3("./db/data.sqlite");

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

	//get uploaded content
	$upfile=isset($_FILES['file']) ? $_FILES['file'] : null;
	if($upfile && $upfile['size']){
		//file has positive size, get its contents as array
		$lines=file($upfile['tmp_name']);
	}else{
		//throw error end exit
		echo '{"success": false}';
		exit();
	}

	$header=array_shift($lines);
	$header=explode(";",trim($header));

	//todo: validate header, must contain all valid fields

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


	$db->exec('COMMIT;');
	unset($db);

	echo '{"success": true}';

}

function update_time_series($ts_name){
        //Update time series data
	header('Content-Type: application/json'); //return failure or success in JSON format

	//create database and table if not existing
	$db=new SQLite3("./db/data.sqlite");

	$db->exec('BEGIN;');

	$sql="CREATE TABLE IF NOT EXISTS ts_data(
		ts_id INTEGER NOT NULL,
		dt INTEGER NOT NULL,
		updated INTEGER DEFAULT ((julianday('now') -2440587.5)*86400.0),
		value REAL NOT NULL,
		PRIMARY KEY (ts_id, dt, updated))";

	$db->exec($sql);

	//get uploaded content
	$upfile=isset($_FILES['file']) ? $_FILES['file'] : null;
	if($upfile && $upfile['size']){
		//file has positive size, get its contents as array
		$lines=file($upfile['tmp_name']);
	}else{
		//throw error and exit
		echo '{"success": false, "reason": "no file uploaded"}';
		exit();
	}

	$header=array_shift($lines);
	$header=explode(";",trim($header));

	//todo: validate header, must contain all valid fields

	function retrieve_number($str){
		$in=$str;
		if (1==substr_count($in,',')) $in=str_replace(',','.',$in);
		if(is_numeric($in)) return floatval($in);
		return null;
	}
	
	//update only the requsted name if given in url path or GET parameter
	if($ts_name==null && isset($_GET['name'])) $ts_name=$_GET['name'];
	//otherwise, retrieve name from first field in uploaded file header
	if($ts_name==null) $ts_name=$header[0];

	//now start updating

	$tag=null;
	$dt=null;
	$value=null;
	$success=0;
	$failure=0;

	$sql="INSERT INTO ts_data(ts_id, dt, value) VALUES((select ts_id from ts_def where name=? and tag=?),strftime('%s',?),?)";
	$prepared=$db->prepare($sql);
	$prepared->bindParam(1, $ts_name);
	$prepared->bindParam(2, $tag);
	$prepared->bindParam(3, $dt);
	$prepared->bindParam(4, $value);

	foreach($lines as $line) {
		$line=explode(";",trim($line));
		if(sizeof($line)!=sizeof($header)) continue;
		$dt=$line[0];
		if (strtotime($dt)==false) continue; //ensure date string is valid
		
		
		for($i=1; $i<sizeof($header); $i++){ //handle all tags
		        $value=retrieve_number($line[$i]);
		        if ($value==null) continue;
			$tag=$header[$i];
			if($prepared->execute()){
				$success++;
			}else{
				$failure++;
			}
		}
	}


	$db->exec('COMMIT;');
		
	echo '{"success": ' . (($success>0)? 'true' : 'false') . ', "num_success": ' . $success . ', "num_failure": ' . $failure . '}';

	//todo: return success message if at least one entry was valid and updated, return number of success and failure

	unset($db);      
}
