<?php

include 'config.php';

//This will contain a array of your data from database
$result_array = array();

//This will select the specified columns-rows from the database
$sql = "SELECT * FROM users ";
    
    /* If there are results from database push to result array */
    $result = $mysql->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            array_push($result_array, $row);
        }
    }
    
    /*This will send a JSON encded array to client */
    $all= json_encode($result_array);
    $time= round(microtime(true) * 1000);
    
    $sql = "SELECT * from users WHERE active ='yes' AND  end_date < $time";

$result = mysqli_query($con,$sql);
$response = array();

while($row = mysqli_fetch_array($result)){
    array_push($response,array("id"=>$row[0], "username"=>$row[1], "uid"=>$row[2], "active"=>$row[3], "duration"=>$row[4], "start_date"=>$row[5], "end_date"=>$row[6], "key_created_by"=>$row[7]));
}

$exp= json_encode(array($response));
 $d = array( "all"=> "$all",  "exp"=> "$exp",  );
     echo json_encode($d);
    
    $mysql->close();
    
    
?>