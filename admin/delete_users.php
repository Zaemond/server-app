<?php

include 'config.php';



$username = $_POST['username'];


// sql to delete a record
$sql = "DELETE FROM users WHERE username= '$username'";

if ($conn->query($sql) === TRUE) {
  echo "done";
} else {
  echo "error" . $conn->error;
}



?>