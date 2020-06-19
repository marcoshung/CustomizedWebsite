<?php
    start();
    function start(){
        session_start();
        require_once"login.php";
        $conn = new mysqli($hn, $un, $pw, $db);
        authenticate_login($conn);
        $conn->close();
    }
    function authenticate_login($conn){
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_USER'])){
            $username = mysql_entities_fix_string($conn,$_SERVER['PHP_AUTH_USER']);
            $password = mysql_entities_fix_string($conn,$_SERVER['PHP_AUTH_PW']);
            if(isset($username)){
                $_SESSION['username'] = $username;
                $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
            }
            $query = "use dream_users";
            $result = $conn->query($query);
            $query = "SELECT * FROM users WHERE username='$username'";
            $result = $conn->query($query);
            if (!$result) die(handle_error());
            elseif ($result->num_rows) {
                $row = $result->fetch_array(MYSQLI_NUM);
                $result->close();
                $salt1 = "1nsd^1)93*";
                $salt2 = "&4NNWK0!";
                $hashed = hash('ripemd128', "$salt1$password$salt2");
                if($hashed == $row[1]){
                    make_admin_page($conn);
                }else die(login_failed());
            }else die(login_failed());
            
        }else{
            header('WWW-Authenticate: Basic realm="Restricted Section“');
            header('HTTP/1.0 401 Unauthorized');
            die ("Please enter your username and password");
        }
    }
    
    function login_failed(){
        $message = 'Invalid Username or Password';
        echo "<SCRIPT>
            alert('$message')
            window.location.replace('client_page.php');
        </SCRIPT>";
    }
    
    
    function handle_error(){
        echo "Ooopppps! Something went wrong. Refresh and try again :)";
    }

    
    function make_admin_page($conn){
    echo <<<_END
                <html><head><title> Ari's Dream - Admin Page</title><h1>Ari's Dream - Admin Page</h1></head><body>
                
                <form method="post" action="admin_page.php" enctype="multipart/form-data">

                    Select Image: <input type="file" name="filename[]" size="10"multiple> <br></br>

                   <textarea name="usertext" rows="5" cols="40"> </textarea><br></br>
                    <input type="submit" value="Upload">
                </form>
        
        <form method = "post" action = "client_page.php" enctype="multipart/form-data">
                  
              <input type = "submit" name = "guest" value = "Go Back To Guest Page">
          </form>
_END;
        $image_dir = "uploads/";
        if(!is_dir($image_dir)){
            mkdir($image_dir, 0777, true);
        }
        $text = get_post($conn, 'usertext');
        if ($_FILES){
            $num_files = count($_FILES['filename']['name']);

            //gets all file names
            for($i = 0; $i < $num_files; $i++){
                $file_name = htmlentities($_FILES['filename']['name'][$i]);
                move_uploaded_file($_FILES['filename']['tmp_name'][$i], $image_dir.$file_name);
                if($_FILES['filename']['type'][$i] != "image/jpeg"){
                       echo $file_name." is the wrong File type. Only accepts .jpg files!";
                }else{
                    if($i != $num_files-1){
                        insert_into_database($conn, $file_name,"");
                    }else{
                        $content_file_name = $file_name.".txt";
                        $content_file = create_content_file($content_file_name,$text);
                        insert_into_database($conn,$file_name,$content_file_name);
                    }
                }
            }
            echo "Files Successfully Uploaded!";
        }
}
    
   function mysql_entities_fix_string($connection, $string) {
        return htmlentities(mysql_fix_string($connection, $string));
        
    }
    function mysql_fix_string($connection, $string) {
        if (get_magic_quotes_gpc()) $string = stripslashes($string);
            return $connection->real_escape_string($string);
    }
    function get_post($conn, $var){
        $result = $_POST[$var];
        if (get_magic_quotes_gpc())
            $result = stripslashes($result);
        return $conn->real_escape_string($result);

    }
    
    function parse_file_info($file_name){
        if (file_exists($file_name)){
            $input = fopen($file_name, "r") or die("Failed to create file");
            $content = fread($input,20);
            $content = strtolower(preg_replace("[^A-Za-z0-9.]", "", $content));
            fclose($input);
        }
        return $content;
    }

    function insert_into_database($conn,$image_name, $text){
        $query = $conn->prepare('INSERT INTO content VALUES(?,?)');
        $query->bind_param('ss', $image_name, $text);
        $query->execute();
    }
    
    function create_content_file($file_name,$text){
        $target_dir = "uploads/";
        $content_file = fopen($target_dir.$file_name, "w");
        fwrite($content_file, $text);
        fclose($content_file);
    }
