<?php
    start();

function start(){
    session_start();
    if(!isset($_SESSION['begin'])){
        session_regenerate_id();
        $_SESSION['begin'] = 1;
    }
    if (isset($_SESSION['ip']) && $_SESSION['ip'] != $_SERVER['REMOTE_ADDR']){
        different_user();
    }else{
        $username = $_SESSION['username'];
        if(isset($username)){
            echo "Welcome ".$username."!";
        }else{
            echo "Login in to upload images and text";
        }
        require_once"login.php";
        $conn = new mysqli($hn, $un, $pw, $db);
        create_user_database($conn);
        create_content_database($conn);
        make_home_page($conn);
        $conn->close();
        $_SESSION = array();
        setcookie(session_name(), '', time() - 2592000 , '/');
        session_destroy();
    }
}

function create_user_database($conn){
    if($conn->connect_error) die(handle_error());
    $query = "USE dream_users";
    $result = $conn->query($query);
    if(!($result)) die(handle_error());
    $query = "CREATE TABLE users(username VARCHAR(128) NOT NULL UNIQUE, password VARCHAR(128) NOT NULL)";
    $result = $conn->query($query);
}

function make_home_page($conn){
    echo <<<_END
                <html><head><title> Ari's Dream </title><h1>Ari's Dream</h1></head><body>
    
    <form method = "post" action = "signup.php" enctype="multipart/form-data">
            
        <input type = "submit" name = "signup" value = "Sign Up">
    </form>

    
    <form method = "post" action = "admin_page.php" enctype="multipart/form-data">
            
        <input type = "submit" name = "admin" value = "Login">
    </form>
    
_END;
    $target_dir = "uploads/";
    if(!is_dir($target_dir)){
        mkdir($target_dir, 0777, true);
    }
    $all_files = scandir($target_dir);
    $num_files = count($all_files);
    
    $image_files = array();
    $content_files = array();
    for($i = 0; $i < $num_files; $i++){
        $path = $target_dir.$all_files[$i];
        if(!is_dir($path)){
            if(ends_with($path, ".jpg") || ends_with($path, ".jpeg")){
                array_push($image_files, $all_files[$i]);
            }else if(ends_with($path,".txt")){
                array_push($content_files, $all_files[$i]);
            }
        }
    }
    echo count($all_files);
    for($i = 0; $i < count($image_files); $i++){
        $name = $image_files[$i];
        echo '<img src="'.$target_dir.$name.'">';
        $query = "use dream_users";
        $result = $conn->query($query);
        $query = "SELECT * FROM content WHERE image_name='$name'";
        $result = $conn->query($query);
        if (!$result) die(handle_error());
        elseif ($result->num_rows) {
            $row = $result->fetch_array(MYSQLI_NUM);
            $content_file_name = $row[1];
            for($j = 0; $j < count($content_files); $j++){
                $path = $target_dir.$content_files[$j];
                if(!is_dir($path) && $content_files[$j] == $content_file_name){
                    $file = fopen($content_file_name, "r");
                    $text = file_get_contents($target_dir.$content_file_name);
                    echo $text;
                    fclose($file);
                }
            }
        }
         
    }
}
    
    function handle_error(){
         echo "Ooopppps! Something went wrong. Refresh and try again :)";
     }
    
    function get_post($conn, $var){
        $result = $_POST[$var];
         if (get_magic_quotes_gpc())
             $result = stripslashes($result);
         return $conn->real_escape_string($result);
    }
    
    function create_content_database($conn){
        if($conn->connect_error) die(handle_error());
        $query = "USE dream_users";
        $result = $conn->query($query);
        if(!($result)) die(handle_error());
        $query = "CREATE TABLE content(image_name VARCHAR(128) NOT NULL UNIQUE, text VARCHAR(128) NOT NULL UNIQUE)";
        $result = $conn->query($query);
    }
    function different_user(){
        $_SESSION = array();
        setcookie(session_name(), '', time() - 2592000 , '/');
        session_destroy();
        echo "Oops! Sorry about that! There was a technical error. Please login again!";
    }
    
    function ends_with($str, $lastString) {
        $str = strtolower($str);
       $count = strlen($lastString);
       if ($count == 0) {
          return true;
       }
       return (substr($str, -$count) === $lastString);
    }
