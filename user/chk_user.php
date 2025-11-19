<?php
include 'config.php';

$username = $_GET["username"];


$sql = "SELECT * FROM users WHERE username = '{$username}' ";
$result = mysqli_query($con, $sql) or die("Query Failed");

if(mysqli_num_rows($result) > 0){

 while($row = mysqli_fetch_assoc($result)){
 
  echo "already";

  }
}else{
 echo "available";
}

?>