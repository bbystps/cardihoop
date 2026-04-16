<?php
session_start();
$connect = new mysqli("localhost", "root", "Password123!", "cardihoop");

if ($connect->connect_error) {
  die("Connection Failed: " . $connect->connect_error);
}

$username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
$password = isset($_POST["password"]) ? $_POST["password"] : "";
$confirm_password = isset($_POST["confirm_password"]) ? $_POST["confirm_password"] : "";

if (!empty($username) && !empty($password) && !empty($confirm_password)) {

  if ($password != $confirm_password) {
    $myArr = new stdClass();
    $myArr->status = "PASSWORD MISMATCH";
    $myJSON = json_encode($myArr);
    echo $myJSON;
    exit;
  }

  $username_safe = $connect->real_escape_string($username);

  $sql = "SELECT * FROM accounts WHERE username='$username_safe'";
  $result = $connect->query($sql);

  if ($result->num_rows > 0) {
    $myArr = new stdClass();
    $myArr->status = "USER EXISTS";
    $myJSON = json_encode($myArr);
    echo $myJSON;
  } else {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $insert_sql = "INSERT INTO accounts (username, password) VALUES ('$username_safe', '$hashed_password')";

    if ($connect->query($insert_sql) === TRUE) {
      $myArr = new stdClass();
      $myArr->status = "SUCCESS";
      $myJSON = json_encode($myArr);
      echo $myJSON;
    } else {
      $myArr = new stdClass();
      $myArr->status = "FAIL";
      $myJSON = json_encode($myArr);
      echo $myJSON;
    }
  }
} else {
  $myArr = new stdClass();
  $myArr->status = "EMPTY FIELD";
  $myJSON = json_encode($myArr);
  echo $myJSON;
}
