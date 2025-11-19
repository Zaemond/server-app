<?php

include 'config.php';



    $id = "";
    $name = "";
    $dur = "";
    $price = "";

    
    
    $id = $_POST["id"];
    $name = $_POST["name"];
    $duration = $_POST["duration"];
    $price = $_POST["price"];
    




    
    //This command will updated your data
    $update = "UPDATE purchase SET name='$name', duration='$duration', price='$price' WHERE id='$id'";

if ($mysql->query($update) === TRUE) {
  echo "done";
} else {
  echo "Something Error Contact Admin\n" . $mysql->error;
}


    
?>