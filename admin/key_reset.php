<?php

include 'config.php';



    
    
    $username = $_POST["username"];
    $uid = 'r';
   

    
    //This command will updated your data
    $update = "UPDATE users SET uid='$uid' WHERE username='$username'";

if ($mysql->query($update) === TRUE) {
  echo "done";
} else {
  echo "Something Error Contact Admin\n" . $mysql->error;
}


    
?>