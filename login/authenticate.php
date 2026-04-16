<?php
session_start();
$connect = new mysqli("localhost", "root", "Password123!", "cardihoop");

if ($connect->connect_error) {
  die("Connection Failed: " . $connect->connect_error);
}

$username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
$password_input = isset($_POST["password"]) ? $_POST["password"] : "";

if (!empty($username) && !empty($password_input)) {

  $username_safe = $connect->real_escape_string($username);
  $sql = "SELECT * FROM accounts WHERE username='$username_safe'";
  $result = $connect->query($sql);

  if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
      $password = $row["password"];

      if (password_verify($password_input, $password)) {
        session_regenerate_id();
        $_SESSION['loggedin'] = TRUE;
        $_SESSION['username'] = $username;

        $myArr = new stdClass();
        $myArr->login = "SUCCESS";
        $myJSON = json_encode($myArr);
        echo $myJSON;
      } else {
        $myArr = new stdClass();
        $myArr->login = "FAIL";
        $myJSON = json_encode($myArr);
        echo $myJSON;
      }
    }
  } else {
    $myArr = new stdClass();
    $myArr->login = "NO USER";
    $myJSON = json_encode($myArr);
    echo $myJSON;
  }
} else {
  $myArr = new stdClass();
  $myArr->login = "EMPTY FIELD";
  $myJSON = json_encode($myArr);
  echo $myJSON;
}
