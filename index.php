<?PHP

/*
Copyright (c) 2018 Dr. Tilman Wolff-Siemssen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

// Include router class
include('./app/route.php');

// Add base route (startpage)
Route::add('/',function(){		
		include('./app/index.html');
});


//
// Routes for reading data
//

// Time series definitions
Route::add('/def',function(){
        include 'app/serve_time_series.php';
        serve_time_series_definitions();
});

// time series data, all names, restricted by from, to and asof
Route::add('/data',function(){
        include 'app/serve_time_series.php';
        serve_time_series(null);
}, 'get');


// time series data, restrict to single name, from, to and asof, name 
Route::add('/data/([^/]{1,})',function($name){
        include 'app/serve_time_series.php';
        serve_time_series($name);
}, 'get');


//
// Routes for updating data
//

// Time series definitions
Route::add('/def',function(){
        include 'app/update_time_series.php';
        update_time_series_definitions(null);
}, 'post');

// Time series definitions ,restrict to name
Route::add('/def/([^/]{1,})',function($ts_name){
        include 'app/update_time_series.php';
        update_time_series_definitions($ts_name);
}, 'post');


// Update time series for single name with csv 
Route::add('/data',function(){
        include 'app/update_time_series.php';
        update_time_series(null);
}, 'post');

// Update time series for single name with csv 
Route::add('/data/([^/]{1,})',function($ts_name){
        include 'app/update_time_series.php';
        update_time_series($ts_name);
}, 'post');

//
// Routes for error handling
//

// 404 not found route
Route::pathNotFound(function($path){
  echo 'risk_series: error 404<br/>';
  echo 'The requested path "'.$path.'" was not found!';
});

// 405 method not allowed route
Route::methodNotAllowed(function($path, $method){
  echo 'risk_series: error 405<br/>';
  echo 'The requested path "'.$path.'" exists. But the request method "'.$method.'" is not allowed on this path!';
});

// Run the Router with the given Basepath
$basepath=pathinfo($_SERVER['SCRIPT_NAME'])['dirname'];
Route::run($basepath);

?>
