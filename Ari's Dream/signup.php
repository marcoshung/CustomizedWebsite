<?php
    start();
    
    function start(){
        require_once"login.php";
        $conn = new mysqli($hn, $un, $pw, $db);
        make_page($conn);
        $conn->close();
    }

    function make_page($conn){
        echo <<<_END
                    <html><head><title> Ari's Dream - Sign Up</title><h1>Ari's Dream - Sign Up</h1></head><body>
        
            <form method="post" action= "signup.php" enctype="multipart/form-data">
                    Username: <input type="text" name="username" size="10"> <br></br>
        
                    Password: <input type = "text" name = "password" size = "10" <br></br>

                    <input type="submit" value="Sign Up">
                    </form>
_END;
    
    
        $username = get_post($conn, "username");
        $password = get_post($conn, "password");
        if(isset($username) && notEmpty($username) && isset($password) && notEmpty($password)){
            if(!check_if_username_is_taken($conn, $username)){
                insert_into_database($conn, $username, encrypt_password($password));
                sign_up_complete();
            }else{
                echo "Username taken. Pick another";
            }
        }
    }

    
    function sign_up_complete(){
        $message = 'Successfully Signed Up!';
        echo "<SCRIPT>
            alert('$message')
            window.location.replace('client_page.php');
        </SCRIPT>";
           
    }
    
    function get_post($conn, $var){
        $result = $_POST[$var];
         if (get_magic_quotes_gpc())
             $result = stripslashes($result);
         return $conn->real_escape_string($result);
    }

    function check_if_username_is_taken($conn, $username){
        $query = "use dream_users";
        $result = $conn->query($query);
        $query = "SELECT * FROM users WHERE username='$username'";
        $result = $conn->query($query);
        if (!$result) die(handle_error());
        else{
            if($result->num_rows > 0){
                return true;
            }
            return false;
        }
    }
    
    function handle_error(){
        echo "Ooopppps! Something went wrong. Refresh and try again :)";
    }
    
    function insert_into_database($conn,$username, $password){
        $query = $conn->prepare('INSERT INTO users VALUES(?,?)');
        $query->bind_param('ss', $username, $password);
        $query->execute();
    }
    
    function encrypt_password($password){
        $salt1 = "1nsd^1)93*";
        $salt2 = "&4NNWK0!";
        $hashed = hash('ripemd128', "$salt1$password$salt2");
        return $hashed;
    }
    
    function notEmpty($string){
        return ($string !== '');
    }
