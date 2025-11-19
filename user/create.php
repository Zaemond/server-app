
<?php

include 'config.php';



$username = "";
$dur = "";




//This function (empty()) will check that if the data inserted are empty or not
if(empty($_POST["username"]) || 
 empty($_POST["dur"])){

echo "Fill Details\n";

} else {


$username = $_POST["username"];
$dur = $_POST["dur"];


$check1 = $mysql->query("SELECT 1 FROM users WHERE username = '$username' LIMIT 1");

//Here all the rows are checked and if no match is found than data is submitted
if($check1->fetch_row()){
echo "already"; } else  
{
//Insert data into the table, users is the table name
$sql = "INSERT INTO users (username, duration, key_created_by)
VALUES ( '$username',  '$dur', 'user')";

if ($mysql->query($sql) === TRUE) {
  echo "done";
} else {
  echo "Error: " . $sql . "<br>" . $mysql->error;
}
}
}
?>

