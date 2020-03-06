<?PHP
    function get_token() {
        $token = 'abc123';  // value for simple testing
        return $token;
    }

    // the following will be useful in the future
    function create_token() {
        $token = bin2hex(random_bytes(64));
        // $token = bin2hex(openssl_random_pseudo_bytes(64));  // for php versions older than 7
        return $token;
    }

?>
