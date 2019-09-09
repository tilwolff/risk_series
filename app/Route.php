<!--
Copyright (c) 2018 SteamPixel

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
-->


<?PHP

class Route{

  private static $routes = Array();
  private static $pathNotFound = null;
  private static $methodNotAllowed = null;

  public static function add($expression, $function, $method = 'get'){
    array_push(self::$routes,Array(
      'expression' => $expression,
      'function' => $function,
      'method' => $method
    ));
  }

  public static function pathNotFound($function){
    self::$pathNotFound = $function;
  }

  public static function methodNotAllowed($function){
    self::$methodNotAllowed = $function;
  }

  public static function run($basepath = '/'){

    // Parse current url
    $parsed_url = parse_url($_SERVER['REQUEST_URI']);//Parse Uri

    if(isset($parsed_url['path'])){
      $path = $parsed_url['path'];
    }else{
      $path = '/';
    }

    // Get current request method
    $method = $_SERVER['REQUEST_METHOD'];

    $path_match_found = false;

    $route_match_found = false;

    foreach(self::$routes as $route){

      // If the method matches check the path

      // Add basepath to matching string
      if($basepath!=''&&$basepath!='/'){
        $route['expression'] = '('.$basepath.')'.$route['expression'];
      }

      // Add 'find string start' automatically
      $route['expression'] = '^'.$route['expression'];

      // Add 'find string end' automatically
      $route['expression'] = $route['expression'].'$';

      // echo $route['expression'].'<br/>';

      // Check path match	
      if(preg_match('#'.$route['expression'].'#',$path,$matches)){

        $path_match_found = true;

        // Check method match
        if(strtolower($method) == strtolower($route['method'])){

          array_shift($matches);// Always remove first element. This contains the whole string

          if($basepath!=''&&$basepath!='/'){
            array_shift($matches);// Remove basepath
          }

          call_user_func_array($route['function'], $matches);

          $route_match_found = true;

          // Do not check other routes
          break;
        }
      }
    }

    // No matching route was found
    if(!$route_match_found){

      // But a matching path exists
      if($path_match_found){
        header("HTTP/1.0 405 Method Not Allowed");
        if(self::$methodNotAllowed){
          call_user_func_array(self::$methodNotAllowed, Array($path,$method));
        }
      }else{
        header("HTTP/1.0 404 Not Found");
        if(self::$pathNotFound){
          call_user_func_array(self::$pathNotFound, Array($path));
        }
      }

    }

  }

}
