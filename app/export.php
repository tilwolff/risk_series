<?PHP

/*

Copyright (c) 2018 Dr. Tilman Wolff-Siemssen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

$db=new SQLite3("../db/data.sqlite", SQLITE3_OPEN_READONLY);

$sql="SELECT 	name,tag,dt,updated,value
	FROM 	ts_data inner join ts_def on ts_data.ts_id=ts_def.ts_id
	ORDER BY
		dt asc,
		cast(tag as INTEGER) * case LOWER(SUBSTR(tag, -1)) WHEN 'y' THEN 360 WHEN 'm' THEN 30 WHEN 'w' THEN 7 ELSE 1 END asc,
		updated desc
";

$results = $db->query($sql);

echo "NAME;TAG;UPDATED;VALUE";
while ($row = $results->fetchArray(SQLITE3_ASSOC)) {	
	echo "\r\n" . $row['name'] . ";" . $row['tag'] . ";" . $row['dt'] . ";" . $row['updated'] . ";" . $row['value'];
} 
