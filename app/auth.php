<?PHP
    
    $token = file_get_contents('./app/.token');
    if($token){
        $headers = apache_request_headers();
        $pass=isset($headers['Authorization']);
        if ($pass) $pass=(trim($token) === trim($headers['Authorization']));
        if(!$pass){
		header("HTTP/1.0 401 Unauthorized");
		echo '{"success": false, "error_message": "Error: authentication failure."}';
                exit();
        }
    }
?>
