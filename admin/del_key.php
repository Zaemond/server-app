<?php

include 'config.php';


$id = $_POST['id'];


// sql to delete a record
$sql = "DELETE FROM purchase WHERE id= '$id'";

if ($conn->query($sql) === TRUE) {
  echo "done";
} else {
  echo "error" . $conn->error;
}



$conn->close();

?>

