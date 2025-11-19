<?php

include 'config.php';


    $id = $_POST["id"];
    $uid = $_POST["uid"];
    



    
    //This command will updated your data
    $update = "UPDATE users SET uid='$uid' WHERE id='$id'";

if ($mysql->query($update) === TRUE) {
  echo "done";
} else {
  echo "Something Error Contact Admin\n" . $mysql->error;
}


    
?>