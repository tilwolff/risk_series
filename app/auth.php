<?PHP
    
    $token = file_get_contents('./app/.token');
    if($token){
        $pass=isset($_SERVER['HTTP_AUTHORIZATION']);
        if ($pass) $pass=(trim($token) === trim($_SERVER['HTTP_AUTHORIZATION']));
        if(!$pass){
		header("HTTP/1.0 401 Unauthorized");
		echo '{"success": false, "error_message": "Error: authentication failure."}';
                exit();
        }
    }
?>
