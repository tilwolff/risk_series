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
        $db=new SQLite3("./data/data.sqlite", SQLITE3_OPEN_READONLY);
        $ts_defs=get_time_series_definitions($db, $ts_name);
        echo json_encode($ts_defs);
        $db=null;
}

function serve_time_series($refined, $ts_name){
        $db=new SQLite3("./data/data.sqlite", SQLITE3_OPEN_READONLY);

        $ts_defs=get_time_series_definitions($db, $ts_name);
        
        //get relevant time series data
        $sql="SELECT name, tag, dt, updated, value from ts_data inner join ts_def on ts_data.ts_id=ts_def.ts_id";
        if(null!=$ts_name){
                if(''!=trim($ts_name)) $sql .= " WHERE name='" . $ts_name. "'";
        }
        $sql .= " ORDER BY name asc, tag asc, dt asc, updated desc";

        $results = $db->query($sql);
        
	// TO BE IMPLEMENTED: PIVOTISE RESULTS AND OUTPUT AS CSV
        
        
        $db=null;
}

?>
