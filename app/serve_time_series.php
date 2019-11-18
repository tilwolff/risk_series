<?PHP

/*

Copyright (c) 2018 Dr. Tilman Wolff-Siemssen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

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

function get_time_series_dates($db,$fromdate=null,$todate=null){
	
	$sql="SELECT DISTINCT dt from ts_data ";
		
	if($fromdate != null || $todate != null ) {
		$sql .= " WHERE ";
	}
	if ($fromdate != null){
		null!=$ts_name ? $sql .= " AND " : $sql .= "";
		$sql .= " dt >=  '".$fromdate."' ";
	}
	if ($todate != null){
		(null!=$ts_name || null!=$fromdate) ? $sql .= " AND " : $sql .= "";
		$sql .= " dt < '".$todate."' ";
	}
	
	$sql .= " ORDER BY dt asc"; 
	
	$results = $db->query($sql);
		
	$ts_dates =array();
	while ($row = $results->fetchArray(SQLITE3_ASSOC)) { 
		
			array_push($ts_dates, $row);
	} 
	return $ts_dates; 
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
 
	// formulate sql query
	
	$sql="SELECT name, tag, dt, updated, value from ts_data inner join ts_def on ts_data.ts_id=ts_def.ts_id";
	if($ts_name != null || $fromdate != null || $todate != null || $asof != null ) {
		$sql .= " WHERE ";
	}
	if(null!=$ts_name){
			if(''!=trim($ts_name)) $sql .= " name='" . $ts_name. "'";
	} 
                          
	// modify sql query to allow for selection of dates and asof:
        if ($fromdate != null){
		null!=$ts_name ? $sql .= " AND " : $sql .= "";
		$sql .= " dt >= '".$fromdate."'";
	}
	if ($todate != null){
		(null!=$ts_name || null!=$fromdate) ? $sql .= " AND " : $sql .= "";
		$sql .= " dt < '".$todate."'";
	} 
	if ($asof != null) {
		(null!=$ts_name|| null!= $fromdate || null!= $todate) ? $sql .= " AND " : $sql .= "";
		$sql .= " (updated < '".$asof."')" ;
	} 
	$sql .= " ORDER BY name asc, dt asc, tag asc, updated desc"; 
	  
	
	//call function opening database, working query, and close connection to database 
	
	serve_times_series_query_db($sql,$ts_name,$fromdate,$todate,$asof);  
	
}

function serve_times_series_query_db($sql,$ts_name,$fromdate,$todate,$asof){
	
	// connect to database and work out queries
	
	$db=new SQLite3("./db/data.sqlite", SQLITE3_OPEN_READONLY);
	
	$db->query("BEGIN TRAN");
		   					
	$tags = get_tags($db,$ts_name);  // get tags via sql
	
	$dates = get_time_series_dates($db,$fromdate,$todate);   // get dates
	
	$results = $db->query($sql);  // get time series
	
	$db->query("ROLLBACK TRAN");
			
	unset($db);  // unset connection to the database
		
	$ts_defs_refined =array();
	while ($row = $results->fetchArray(SQLITE3_ASSOC)) { 
			array_push($ts_defs_refined, $row);
	}  

	// pivotise results and output as csv   
	  	
	if (!empty($ts_defs_refined)) { 		
		$pivot = pivot_name($ts_defs_refined,$ts_name,$tags,$dates);				
		echo $pivot;   
	} else {
		echo '';  // output empty string if queried range of dates contains no data
	}    
}

// functions called by in serve_time_series($ts_name)

function pivot_name($input,$name,$tags,$dates) {
						
	$output = '';
	$date_tags_nullstring = '';			
	$head = $name ;
	foreach ($tags as $tag) {
		$head .= ';'.$tag ;
		$date_tags_nullstring .= ';'; 
	}
	$date_tags_nullstring=substr($date_tags_nullstring,0.-1);				
	$output .= $head."\n";  // add to output a line containing the name and the list of tags
			
	foreach ($dates as $date) {	
		
		$date_string = date('Y-m-d',$date['dt']).';';
		$date_found = False;
		$tags_values_array=array(); // at each date re-initialize tags_values_array
		
		foreach ($input as $row) {					
			if ($date['dt'] == $row['dt']) {						
				$date_found = True;						
				foreach ($tags as $tag) {
					if ($row['tag'] == $tag) {
						// keep just the first not-null result: it corresponds to asof value
						if ($tags_values_array[$tag] != null) {
							continue;
						} else {	
							$tags_values_array = array_merge($tags_values_array,array($tag => $row['value']));									
						}	
					} 
				}					
			} 							
		}
		if ($date_found == True) {
			$date_string .= substr(tags_values_string($tags,$tags_values_array),0,-1);	
			// take out from input all entries corresponding to the already worked-out date 
			//(to check whether this operation in the end reduces computation time)
			foreach ($input as $entry) {
				if ($entry['dt'] == $date['dt']) {
					array_shift($input);
				} else {
					break;
				}
			}	
		}
		else {
			$date_string .= $date_tags_nullstring;
		}						
		$output .= $date_string."\n";
		array_shift($dates);						
	}
	return $output; 	
}

function get_tags ($db,$ts_name) {			
    $tags_name=array();
    $tags_defs=get_time_series_definitions($db, $ts_name);    
    foreach ($tags_defs as $tag) {
		array_push($tags_name, $tag['tag']);
	}	
	return $tags_name;
}

// loop over all tag names to get their value (for a given date) and returns a csv string
function tags_values_string($tags,$tags_values_array) {
		$string = '';
		foreach ($tags as $def_tag) {
			foreach ($tags_values_array as $tag =>$tag_value){
				$tag == $def_tag ? $string .= $tag_value : $string .= '';								
			}
			$string .= ';'; /* at the end of the loop, 
								no matter whether a value for this tag has been found or not, 
								create a csv entry*/
		}
		return $string;	
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

