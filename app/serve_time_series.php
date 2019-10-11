<!--
Copyright (c) 2018 Dr. Tilman Wolff-Siemssen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
-->

<?PHP

function get_time_series_definitions($db, $ts_name){
        //Get relevant time series defs
        $sql="SELECT * FROM ts_def";
        if(null!=$ts_name){
                if(''!=trim($ts_name)) $sql .= " WHERE name='" . $ts_name . "'";
        }
        $sql .= " ORDER BY name asc, tag asc";
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
                
	if (!isset($_GET['FORMAT']) or $_GET['FORMAT'] == 'json' or $_GET['FORMAT'] == 'JSON') {
		header('Content-Type: application/json');			
		echo json_encode($ts_defs,JSON_PRETTY_PRINT);	
	} else {
		header('Content-Type: text/csv');	
		echo array_to_csv($ts_defs);
	}

}

function serve_time_series($ts_name){

	header('Content-Type: text/csv');
                
	/* extract from, to and asof dates. 
	   For dates from and to accepts both unix timestamp and YYYY-mm-dd date formats, 
	   for asof accepts unix timestamp and YYYY-mm-ddTHH:MM:SS.SSS formats, 
	   with hours/minutes/seconds optional
	*/ 
			
	$fromdate = null;
	$todate = null;
	$asof= null;

	//read GET params

	if(isset($_GET['FROM'])) $fromdate=$_GET['FROM'];
	if(isset($_GET['TO'])) $todate=$_GET['TO'];
	if(isset($_GET['ASOF'])) $asof=$_GET['ASOF'];
	if($ts_name==null && isset($_GET['NAME'])) $ts_name=$_GET['NAME'];

	//also accept lowercase for convenience

	if(isset($_GET['from'])) $fromdate=$_GET['from'];
	if(isset($_GET['to'])) $todate=$_GET['to'];
	if(isset($_GET['asof'])) $asof=$_GET['asof'];
	if($ts_name==null && isset($_GET['name'])) $ts_name=$_GET['name'];

	if ($fromdate!= null){
		$fromdate=(strlen((int)$fromdate) == strlen($fromdate))? 
				$fromdate: strtotime($fromdate);
	}
	if ($todate!= null){
		$todate=(strlen((int)$todate) == strlen($todate))? 
				$todate: strtotime($todate);
	}

	if ($asof != null){
		$asof=(strlen((int)$asof) == strlen($asof))? 
				$asof: strtotime($asof);
	}

     
	//open database and get relevant time series data   
	     
        $db=new SQLite3("./db/data.sqlite", SQLITE3_OPEN_READONLY);
        
        $sql="SELECT name, tag, dt, updated, value from ts_data inner join ts_def on ts_data.ts_id=ts_def.ts_id";
        if($ts_name != null || $fromdate != null || $todate != null || $asof != null ) {
			$sql .= " WHERE ";
		}
        if(null!=$ts_name){
                if(''!=trim($ts_name)) $sql .= " name='" . $ts_name. "'";
        } 
                           
	// modification of sql query to allow for selection of dates and asof:
        if ($fromdate != null){
			null!=$ts_name ? $sql .= " AND " : $sql .= "";
			$sql .= " dt >= '".$fromdate."'";
		}
		if ($todate != null){
			(null!=$ts_name || null!=$fromdate) ? $sql .= " AND " : $sql .= "";
			$sql .= " dt <='".$todate."'";
		}
        if ($asof != null) {
			(null!=$ts_name|| null!= $fromdate || null!= $todate) ? $sql .= " AND " : $sql .= "";
			$sql .= " (updated <= '".$asof."')" ;
	} 
	$sql .= " ORDER BY name asc, dt asc, tag asc, updated desc";   

        $results = $db->query($sql);
               
        $ts_defs_refined =array();
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) { 
                array_push($ts_defs_refined, $row);
        }       
					
	$tags = get_tags($db);  // get tags via sql
			
	unset($db);  // at the end close the connection to the database
	
	// PIVOTISE RESULTS AND OUTPUT AS CSV       

	$pivot = pivot($ts_defs_refined,$tags);		
	// print output
	echo $pivot;        
}

// functions called by in serve_time_series($refined=null, $ts_name)


function pivot($input,$tags) {
								
		$output = '';
		$name='';  	
		$date = null;
		$date_string = '';	
		$tags_values_array=array(); // initiate a tags-values array 
		
		foreach($input as $row) {			
			if ($row['name'] != $name) { // at new name				
				$name = $row['name'];
				$head = $name ;
				foreach ($tags as $tag) {
					$head .= ';'.$tag ;
				}
				
				$output .= $head."\n";  // add to output a line containing the name and the list of tags
				
				$tags_values_array=array(); // re-initiate the tags-values array
				$date_string = ''; // reset date_string by new name
				$date = null; // reset date by new name
			} else if ($date != $row['dt']) {  // at new date				
				if ($date != null) { // if a previous date has already been worked out
					// prepare a corresponding string to be added to output
					foreach ($tags as $def_tag) {
						foreach ($tags_values_array as $tag =>$tag_value){
							$tag == $def_tag ? $date_string .= $tag_value : $date_string .= '';								
						}
						$date_string .= ';'; /* at the end of the loop, 
											no matter whether a value for this tag has been found or not, 
											create a csv entry*/
					}									
					$output .= $date_string ."\n";// add to output a line with previous date and corresponding tag values				
				}
				
				// (re)initialise fields for new date				
				$date = $row['dt'];  
				$date_string = date('Y-m-d',$date).';';  // at new date initiate a new date_string 
				$tags_values_array=array(); // at new date re-initiate the tags-values array 
			}
									
				 
			// look for all entries corresponding to the same date
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
		return $output;		
}

function get_tags ($db) {		
	$tags_defs=array();
	$sql="SELECT tag FROM ts_def where not tag = 'tag'";       
        $sql .= " ORDER BY tag asc;";
        $results = $db->query($sql);
		
        while ($row = $results->fetchArray(SQLITE3_ASSOC)) { 
                array_push($tags_defs, $row['tag']);
        }
        $db = null;
        usort($tags_defs,'tag_compare'); 
        return $tags_defs;
}

// tag_compare allows to correctly order outputs, otherwise number 11 comes before number 2 etc...
function tag_compare ($x,$y) {
		if (substr($x,-1) ==  substr($y,-1)) {
			if ((int)substr($x,0,-1) < (int)substr($y,0,-1)) return -1;
			else if ((int)substr($x,0,-1) > (int)substr($y,0,-1)) return 1;
			else return 0;
		} 
		else if (substr($x,-1) == 'M' && substr($y,-1) == 'Y') return -1;
		else if (substr($x,-1) == 'Y' && substr($y,-1) == 'M') return 1;
}

// simple array to csv conversion, without pivoting, useful for outputting  /def as csv:
function array_to_csv($input) {
		$keystring='';
		foreach($input[0] as $key => $key_value) {
			$keystring .= $key.";";
		}
		$output = substr($keystring,0,-1)."\n";
		foreach($input as $row) {
			$valuestring='';	
			foreach($row as $key => $key_value){
				$valuestring .= $key_value.";";
			}
			$output .= substr($valuestring,0,-1)."\n";
		}	
		return $output;							
}

