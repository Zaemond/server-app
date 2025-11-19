<?php
include 'config.php';


$data = array();
$username = $_GET['username'];
$uid = $_GET['uid'];

// $username ='vivek';
// $uid = 'SKQ1.210216.001';

$sql = "SELECT * FROM users WHERE username = '$username' ";
$result = mysqli_query($con, $sql) or die("Query Failed");

if(mysqli_num_rows($result) > 0){
    
$qry=mysqli_query($con, "SELECT * FROM users WHERE username = '$username' ");

while ($result=mysqli_fetch_array($qry))
{

//  while($row = mysqli_fetch_assoc($result)){

        $id        =$result["id"];
        $username  =$result["username"];
        $data_uid  =$result["uid"];
        $active    =$result["active"];
        $duration  =$result["duration"];;
        $start_date=$result["start_date"];
        $end_date  =$result["end_date"];
    


 }




  

 $time= round(microtime(true) * 1000);
 
 
//  logic start
                
                 
                         if ($active == yes) {
                             
                             if ($uid == $data_uid) {
                             
                                if ($end_date > $time) {
                                    $left=$end_date-$time;
                             
                                    $d = array( "start_date"=> "$start_date",  "end_date"=> "$end_date",   "time"=> "$time", "msg"=> "Welcome", "status"=> "done", "left"=>"$left",);

                                    echo json_encode($d);
                             
                                 }
                                 else
                                 {
                                      echo'';
                                      $d = array( "start_date"=> "$start_date",  "end_date"=> "$end_date",   "time"=> "$time", "msg"=> "Key Expired", "status"=> "error",);

                                        echo json_encode($d);
                                 }
                             
                             }
                             else
                             {
                                  
                                  $d = array( "start_date"=> "$start_date",  "end_date"=> "$end_date",   "time"=> "$time", "msg"=> "Invalid Login & Device Id Not Match", "status"=> "error",);

                                echo json_encode($d);
                             }
                             
                             
                         }  else {
                             
                              $d = array( "start_date"=> "$start_date",  "end_date"=> "$end_date",   "time"=> "$time", "msg"=> "Invalid Login", "status"=> "error",);

                              echo json_encode($d);
                         }
                 
                
         
         

}
  
  
else{
    
     $d = array( "start_date"=> "$start_date",  "end_date"=> "$end_date",   "time"=> "$time", "msg"=> "Details Not Match", "status"=> "error",);
     echo json_encode($d);
}
 
 



    $mysql->close();
 
 ?>