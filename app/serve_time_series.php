<?PHP

/*

Copyright (c) 2018 Dr. Tilman Wolff-Siemssen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

function err_name_not_found(){
	header("HTTP/1.0 404 Not Found");
	header('Content-Type: application/json');
	echo '{"success": false, "error_message": "the requested time series does not exist"}';
	exit();
}

function get_time_series_names($db){
	//Get relevant time series defs
	$sql="SELECT DISTINCT name FROM ts_def ORDER BY name asc";
	$results = $db->query($sql);
	$ts_names=array();
	while ($row = $results->fetchArray(SQLITE3_ASSOC)) { 
			array_push($ts_names, $row['name']);
	}
	return $ts_names;
}

function serve_time_series_names(){			
	$db=new SQLite3("./db/data.sqlite", SQLITE3_OPEN_READONLY);
	$db->busyTimeout(3000);
	$ts_name='';
	
	$ts_names=get_time_series_names($db);
	unset($db);

	$format=null;
	if(isset($_GET['FORMAT'])) $format=$_GET['FORMAT'];
	if(isset($_GET['format'])) $format=$_GET['format'];
                
	if ($format == null or $format == 'json' or $format == 'JSON') {
		header('Content-Type: application/json');				
		echo json_encode($ts_names,JSON_PRETTY_PRINT);	
	} else {
		header('Content-Type: text/csv');
		echo "Name\r\n" . implode("\r\n", $ts_names);
	}
}


function get_time_series_definitions($db, $ts_name){
	//Get relevant time series defs
	$sql="SELECT name, tag, meta FROM ts_def";
	if(null!=$ts_name){
			if(''!=trim($ts_name)) $sql .= " WHERE name='" . $ts_name . "'";
	}
	$sql .= " ORDER BY name asc, cast(tag as INTEGER) * case LOWER(SUBSTR(tag, -1)) WHEN 'y' THEN 360 WHEN 'm' THEN 30 WHEN 'w' THEN 7 ELSE 1 END asc, tag asc";
	$results = $db->query($sql);
	$ts_defs=array();
	while ($row = $results->fetchArray(SQLITE3_ASSOC)) { 
			array_push($ts_defs, $row);
	}
	return $ts_defs;
}


function serve_time_series_definitions(){
				
	$db=new SQLite3("./db/data.sqlite", SQLITE3_OPEN_READONLY);
	$db->busyTimeout(3000);
	$ts_name='';
	
	$ts_defs=get_time_series_definitions($db, $ts_name);
	unset($db);

	$format=null;
	if(isset($_GET['FORMAT'])) $format=$_GET['FORMAT'];
	if(isset($_GET['format'])) $format=$_GET['format'];
                
	if ($format == null or $format == 'json' or $format == 'JSON') {
		header('Content-Type: application/json');				
		echo json_encode($ts_defs,JSON_PRETTY_PRINT);	
	} else {
		header('Content-Type: text/csv');
		echo array_to_csv($ts_defs);
	}
}

function get_time_series_dates($db,$fromdate,$todate, $asof){
	
	$sql="SELECT DISTINCT dt FROM ts_data
		WHERE dt >= $fromdate
		AND   dt <  $todate
		AND   updated < $asof
		ORDER BY dt asc
	";
		
	$results = $db->query($sql);
		
	$ts_dates =array();
	if(!$results) return $ts_dates;

	while ($row = $results->fetchArray(SQLITE3_ASSOC)) { 
		array_push($ts_dates, $row['dt']);
	} 
	return $ts_dates; 
}

function get_time_series_data($db, $ts_name, $fromdate, $todate, $asof){
	
	$sql="SELECT 	tag,
			dt,
			updated,
			value
		FROM 	ts_data inner join ts_def on ts_data.ts_id=ts_def.ts_id
		WHERE	name= 		'$ts_name'
		AND 	dt >= 		$fromdate
		AND 	dt <  		$todate
		AND	updated < 	$asof
		ORDER BY
			dt asc,
			cast(tag as INTEGER) * case LOWER(SUBSTR(tag, -1)) WHEN 'y' THEN 360 WHEN 'm' THEN 30 WHEN 'w' THEN 7 ELSE 1 END asc,
			updated desc
	";

	$results = $db->query($sql);
		
	$ts_data =array();
	if(!$results) return $ts_data;

	$dt=null;
	$tag=null;
	while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
		if($row['dt']!=$dt || $row['tag']!= $tag){
			//most recent update
			array_push($ts_data, $row);
			$dt=$row['dt'];
			$tag=$row['tag'];
		} //throw away data if not most recent update
	} 
	return $ts_data; 
}

function serve_time_series($ts_name){
	
	header('Content-Type: text/csv');
                
	/* parse request to extract from, to and asof dates. 
	   For dates from and to accepts both unix timestamp and YYYY-mm-dd date formats, 
	   for asof accepts unix timestamp and YYYY-mm-dd HH:MM:SS formats, 
	   with hours/minutes/seconds optional
	*/ 
			
	$fromdate = null;
	$todate = null;
	$asof= null;

	//read GET params

	if(isset($_GET['FROM'])) $fromdate=$_GET['FROM'];
	if(isset($_GET['TO'])) $todate=$_GET['TO'];
	if(isset($_GET['ASOF'])) $asof=$_GET['ASOF'];

	//also accept lowercase for convenience

	if(isset($_GET['from'])) $fromdate=$_GET['from'];
	if(isset($_GET['to'])) $todate=$_GET['to'];
	if(isset($_GET['asof'])) $asof=$_GET['asof'];

	if($ts_name==null && isset($_GET['name'])) $ts_name=$_GET['name'];
	if($ts_name==null && isset($_GET['NAME'])) $ts_name=$_GET['NAME'];
	//if no name is set, return array of names
	if($ts_name==null){
		serve_time_series_names();
		exit();
	}
	

	if ($fromdate!= null){
		$fromdate=(strlen((int)$fromdate) == strlen($fromdate))? 
				$fromdate: strtotime($fromdate);
	}
	if ($todate!= null){
		$todate = (strlen((int)$todate) == strlen($todate))? 
				$todate : strtotime($todate.'+1 day');
				/* if todate is timestamp, prepare a sql query that includes the date,
				if it is string, transform to timestamp to time 00:00:00 of the following day,
				and prepare the sql query up to, but excluding, this time*/ 		 
	}
	if ($asof != null){
		if (strlen((int)$asof) != strlen($asof)) {
			if (strpos($asof,"T")) {
				$asof = strtotime($asof);
			} else {
				$asof = strtotime($asof.'+1 day');
			}
		} else {
			$asof = $asof;
		}				
	}

	if($fromdate==null) $fromdate=PHP_INT_MIN;
	if($todate==null) $todate=PHP_INT_MAX;
	if($asof==null) $asof=PHP_INT_MAX;

	// connect to database and work out queries
	
	$db=new SQLite3("./db/data.sqlite", SQLITE3_OPEN_READONLY);
	$db->busyTimeout(3000);
	
	$db->exec("BEGIN TRANSACTION");
		   					
	$tags = get_tags($db, $ts_name);  // get tags from db
	if (!count($tags)) err_name_not_found(); //exit if no tags of that name were found

	$dates = get_time_series_dates($db, $fromdate, $todate, $asof);   // get dates from db
	$results = get_time_series_data($db, $ts_name, $fromdate, $todate, $asof);  // get time series data from db
	
	$db->exec("ROLLBACK TRANSACTION");
			
	unset($db);  // unset connection to the database

	$results=pivot($results, $tags, $dates); //pivotise results

	//write out results	
	header('Content-Type: text/csv');
	echo $ts_name.";".implode(";", $tags)."\r\n";
	$ndates=count($dates);
	for ($idate=0;$idate<$ndates;$idate++){
		echo date('Y-m-d',$dates[$idate]).";".implode(";", $results[$idate])."\r\n";
	}
}

// andrea modified to return data in form of array for displaying in the browser with AngularJs

include('token.php');

function serve_time_series_definitions_data(){

  	$headers = apache_request_headers();  // can be set in .htaccess
	if(isset($headers['Authorization'])){
		
		if (get_token() === $headers['Authorization']) {

			$db=new SQLite3("./db/data.sqlite", SQLITE3_OPEN_READONLY);
			$db->busyTimeout(3000);
			$ts_name='';
			
			$ts_defs=get_time_series_definitions($db, $ts_name);
			unset($db);
	
			header('Content-Type: application/json');
			$records = array('records' => $ts_defs);
			echo json_encode($records);
	
		} else {
			header('Content-Type: application/json');
			$failure = ['failure' => 'Request not authorized'];
			$resp = ['resp' => $failure];
			$records = ['records' => $resp];
			echo json_encode($records);
		}
	} else {
		header('Content-Type: application/json');
		$failure = ['failure' => 'Authentication required'];
		$resp = ['resp' => $failure];
		$records = ['records' => $resp];
		echo json_encode($records);
	}	
}

function serve_time_series_names_data(){	
	
  	$headers = apache_request_headers();
	if(isset($headers['Authorization'])){
		
		if (get_token() === $headers['Authorization']) {

			$db=new SQLite3("./db/data.sqlite", SQLITE3_OPEN_READONLY);
			$db->busyTimeout(3000);
			$ts_name='';
			
			$ts_names=get_time_series_names($db);
			unset($db);

			$output = array('Name');
			$output = array_merge($output,$ts_names);
			$records = array('records' => $output);

			header('Content-Type: application/json');
			echo json_encode($records);
		} else {
			header('Content-Type: application/json');
			$failure = ['failure' => 'Request not authorized'];
			$resp = ['resp' => $failure];
			$records = ['records' => $resp];
			echo json_encode($records);
		}
	} else {
		header('Content-Type: application/json');
		$failure = ['failure' => 'Authentication required'];
		$resp = ['resp' => $failure];
		$records = ['records' => $resp];
		echo json_encode($records);
	}
}

function serve_time_series_data($ts_name){

  	$headers = apache_request_headers();
	if(isset($headers['Authorization'])){
		
		if (get_token() === $headers['Authorization']) {

			// headers for testing on local server
			header("Access-Control-Allow-Origin: *");
			header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
			
			// content-type set to 'json' for angular 
			header('Content-Type: application/json');
						
			/* parse request to extract from, to and asof dates. 
			For dates from and to accepts both unix timestamp and YYYY-mm-dd date formats, 
			for asof accepts unix timestamp and YYYY-mm-dd HH:MM:SS formats, 
			with hours/minutes/seconds optional
			*/ 
					
			$fromdate = null;
			$todate = null;
			$asof= null;

			//read GET params

			if(isset($_GET['FROM'])) $fromdate=$_GET['FROM'];
			if(isset($_GET['TO'])) $todate=$_GET['TO'];
			if(isset($_GET['ASOF'])) $asof=$_GET['ASOF'];

			//also accept lowercase for convenience

			if(isset($_GET['from'])) $fromdate=$_GET['from'];
			if(isset($_GET['to'])) $todate=$_GET['to'];
			if(isset($_GET['asof'])) $asof=$_GET['asof'];

			if($ts_name==null && isset($_GET['name'])) $ts_name=$_GET['name'];
			if($ts_name==null && isset($_GET['NAME'])) $ts_name=$_GET['NAME'];
			//if no name is set, return array of names
			if($ts_name==null){
				serve_time_series_names_data();
				exit();
			}
			
			if ($fromdate!= null){
				$fromdate=(strlen((int)$fromdate) == strlen($fromdate))? 
						$fromdate: strtotime($fromdate);
			}
			if ($todate!= null){
				$todate = (strlen((int)$todate) == strlen($todate))? 
						$todate : strtotime($todate.'+1 day');
						/* if todate is timestamp, prepare a sql query that includes the date,
						if it is string, transform to timestamp to time 00:00:00 of the following day,
						and prepare the sql query up to, but excluding, this time*/ 		 
			}
			if ($asof != null){
				if (strlen((int)$asof) != strlen($asof)) {
					if (strpos($asof,"T")) {
						$asof = strtotime($asof);
					} else {
						$asof = strtotime($asof.'+1 day');
					}
				} else {
					$asof = $asof;
				}				
			}

			if($fromdate==null) $fromdate=PHP_INT_MIN;
			if($todate==null) $todate=PHP_INT_MAX;
			if($asof==null) $asof=PHP_INT_MAX;

			// connect to database and work out queries
			
			$db=new SQLite3("./db/data.sqlite", SQLITE3_OPEN_READONLY);
			$db->busyTimeout(3000);
			
			$db->exec("BEGIN TRANSACTION");
									
			$tags = get_tags($db, $ts_name);  // get tags from db
			if (!count($tags)) err_name_not_found(); //exit if no tags of that name were found

			$dates = get_time_series_dates($db, $fromdate, $todate, $asof);   // get dates from db
			$results = get_time_series_data($db, $ts_name, $fromdate, $todate, $asof);  // get time series data from db
			
			$db->exec("ROLLBACK TRANSACTION");
					
			unset($db);  // unset connection to the database

			$results=pivot($results, $tags, $dates); //pivotise results

			// content-type set to 'json' for angular 
			header('Content-Type: application/json');

			/*
			the following commented lines provide an alternative associative array version
			which attaches an index to array items
			in the attempt to prevent the apparent angularJs tracking-by-index problem in double arrays.
			However, also in this case angularJs correctly tracks items by index and outputs the results as it should, 
			but an error message appears in the console, indicating duplicates in tracking by value. 
			Is the console error message just due to a bug in the
			error-message library of angularJs? 
			*/

			/*
			$head = array("0" => $ts_name);

			$nTags = count($tags);
			for ($i = 1; $i <= $nTags; $i++) {
				$head = array_merge($head, array(strval($i) => $tags[$i-1]));
			}

			$output=array("0" => $head);

			$ndates=count($dates);
			for ($idate=0;$idate<$ndates;$idate++){
				$j = 0;
				$line = array(strval($j) => date('Y-m-d',$dates[$idate]));
				$nResults = count($results[$idate]);
				for ($j = 1; $j <= $nResults; $j++) {
					$line = array_merge($line, array(strval($j) => $results[$idate][$j-1]));
				}
				
				$output = array_merge($output, array(strval($idate) => $line));
			}
			*/

			// non-associative array version (preferred)
			
			$head = array($ts_name);
			$head = array_merge($head, $tags);

			$output = array();
			array_push($output, $head);

			$ndates=count($dates);
			for ($idate=0;$idate<$ndates;$idate++){
				
				$line = array(date('Y-m-d',$dates[$idate]));
				$line = array_merge($line, $results[$idate]);

				array_push($output, $line);
			}

			// output the result
			$records = array('records' => $output);
			echo json_encode($records);
		} else {
			header('Content-Type: application/json');
			$failure = ['failure' => 'Request not authorized'];
			$resp = ['resp' => $failure];
			$records = ['records' => $resp];
			echo json_encode($records);
		}
	} else {
		header('Content-Type: application/json');
		$failure = ['failure' => 'Authentication required'];
		$resp = ['resp' => $failure];
		$records = ['records' => $resp];
		echo json_encode($records);
	}
}  // end of Andrea modification for angularJs

// function to transfom an array in html table row entries 
function tr($input) {
	$output = "";
	$nInput = count($input);
	for($i = 0; $i < $nInput; $i++) {
		$output .= "<td>".$input[$i]."</td>";
	}
	return $output;
}

function pivot($input,$tags,$dates) {
	$ntags=count($tags);
	$ndates=count($dates);
	if(0==$ndates) return Array();

	//initialise result
	$res=array_fill(0, $ndates, array_fill(0, $ntags, null));
	if(0==count($input)) return $res; //return null data if no data found

	//loop through input
	$row=array_shift($input);
	for ($idate=0;$idate<$ndates;$idate++){
		for($itag=0;$itag<$ntags;$itag++){
			if($tags[$itag]==$row['tag'] && $dates[$idate]==$row['dt']){
				//record fits tag and date, add it to result and retrieve next row
				$res[$idate][$itag]=$row['value'];
				$row=array_shift($input);
			}		
		}
	}
	return $res;
}

function get_tags($db,$ts_name) {			
	$tags_name=array();
	$tags_defs=get_time_series_definitions($db, $ts_name);    
	foreach ($tags_defs as $tag) {
		array_push($tags_name, $tag['tag']);
	}	
	return $tags_name;
}

// simple array to csv conversion, without pivoting, useful for outputting  /def as csv:
function array_to_csv($input) {
	$keystring='';
	foreach($input[0] as $key => $key_value) {
		$keystring .= $key.";";
	}
	$output = substr($keystring,0,-1)."\r\n";
	foreach($input as $row) {
		$valuestring='';	
		foreach($row as $key => $key_value){
			$valuestring .= $key_value.";";
		}
		$output .= substr($valuestring,0,-1)."\r\n";
	}	
	return $output;							
}

