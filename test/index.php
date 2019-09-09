<!--
Copyright (c) 2018 Dr. Tilman Wolff-Siemssen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
-->

<?PHP


// Include router class
include('app/Route.php');

// Add base route (startpage)
Route::add('/',function(){
  echo 'API SPEC <br/>';
});



//
// Routes for reading data
//

// Time series definitions
Route::add('/def',function(){
        include 'app/serve_time_series.php';
        serve_time_series_definitions();
});

// Raw time series data, all series
Route::add('/raw/{0,1}',function(){
        include 'app/serve_time_series.php';
        serve_time_series(false, null);
});

// Raw time series data, restrict to single name
Route::add('/raw/([^/]{1,})',function($ts_name){
        include 'app/serve_time_series.php';
        serve_time_series(false, $ts_name);
});



//
// Routes for updating data
//

// Time series definitions
Route::add('/def',function(){
        //TO BE IMPLEMENTED: POST NEW TIME SERIES DEFINITION
}, 'post');

// Update time series for single name with csv 
Route::add('/update/([^/]{1,})',function($ts_name){
 	//TO BE IMPLEMENTED: POST NEW CSV DATA
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
Route::run('/');

?>
