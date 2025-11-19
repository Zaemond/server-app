<?php

include 'config.php';




$name = $_POST["name"];
$duration=$_POST["duration"];
$price = $_POST["price"];


$check1 = $mysql->query("SELECT 1 FROM purchase WHERE name = '$name' LIMIT 1");

//Here all the rows are checked and if no match is found than data is submitted
if($check1->fetch_row()){
echo "already"; } else  
{
//Insert data into the table, users is the table name
$sql = "INSERT INTO purchase (name, duration, price)
VALUES ( '$name', '$duration', '$price')";

if ($mysql->query($sql) === TRUE) {
  echo "done";
} else {
  echo "Error: " . $sql . "<br>" . $mysql->error;
}

}
?>

