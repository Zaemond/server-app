<?php
include 'config.php';


$data = array();
$username = $_GET[ 'username' ];
$uid = $_GET[ 'uid' ];
$r = 'r';

// $username ='vivek';
// $uid = '6464';

$sql = "SELECT * FROM users WHERE username = '$username' ";
$result = mysqli_query( $con, $sql )or die( "Query Failed" );

if ( mysqli_num_rows( $result ) > 0 ) {

  $qry = mysqli_query( $con, "SELECT * FROM users WHERE username = '$username' " );

  while ( $result = mysqli_fetch_array( $qry ) ) {

    //  while($row = mysqli_fetch_assoc($result)){

    $id = $result[ "id" ];
    $username = $result[ "username" ];
    $data_uid = $result[ "uid" ];
    $active = $result[ "active" ];
    $duration = $result[ "duration" ];;
    $start_date = $result[ "start_date" ];
    $end_date = $result[ "end_date" ];


  }


  $time = round( microtime( true ) * 1000 );

  //  logic start
  if ( $active == no ) {

    $en = $time + $duration;

    $update = "UPDATE users SET  active = 'yes' , uid = '$uid' , start_date = '$time', end_date = '$en'  WHERE username='$username'";

    if ( $mysql->query( $update ) === TRUE ) {

      echo 'done1';

    } else {
      echo "error";
    }

  } else {

    if ( $active == yes ) {

      if ( $uid == $data_uid ) {

        if ( $end_date >= $time ) {

          echo 'done';

        } else {
          echo 'Key Expired';
        }

      } else {
        if ( $data_uid == $r ) {

          if ( $end_date >= $time ) {

            $en = $time + $duration;

            $update = "UPDATE users SET   uid = '$uid'   WHERE username='$username'";

            if ( $mysql->query( $update ) === TRUE ) {

              echo 'done2';

            } else {
              echo "error";
            }

          } else {
            echo 'Key Expired';
          }

        } else {
          echo 'Device Id Not Match';
        }

      }


    }

  }


} else {
  echo "Details Not Match";
}


?>
