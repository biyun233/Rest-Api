<?php

require_once '../../model/Response.php';
require_once '../conn.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
  new Response(false, 405, 'Request method not allowed');
}

if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
  new Response(false, 400, 'Content type header not set to JSON');
}

$rowPOSTData = file_get_contents('php://input');

if(!$jsonData =json_decode($rowPOSTData)) {
  new Response(false, 400, 'Request body is not valid JSON');
}

if(!isset($jsonData->fullname) || !isset($jsonData->username) || !isset($jsonData->password)) {
  new Response(false, 400, 'fullname, username and password are demanded');
}

if(strlen($jsonData->fullname) < 1 || strlen($jsonData->fullname) > 30 
|| strlen($jsonData->username) < 1 || strlen($jsonData->username) > 30
|| strlen($jsonData->password) < 1 || strlen($jsonData->password) > 30) {
  new Response(false, 400, 'fullname, username and password cannot be blank or greater than 30 characters');
}

$fullname = trim($jsonData->fullname);
$username = trim($jsonData->username);
$password = $jsonData->password;

try {

  $query = $db->prepare('select id from users where username = :username');
  $query->bindParam(':username', $username, PDO::PARAM_STR);
  $query->execute();

  $rowCount = $query->rowCount();

  if($rowCount !== 0) {
    new Response(false, 409, 'User already exists');
  }

  $hashed_password = password_hash($password, PASSWORD_DEFAULT);
  $query = $db->prepare('insert into users (fullname, username, password) values (:fullname, :username, :password)');
  $query->bindParam(':fullname', $fullname, PDO::PARAM_STR);
  $query->bindParam(':username', $username, PDO::PARAM_STR);
  $query->bindParam(':password', $hashed_password, PDO::PARAM_STR);
  $query->execute();

  $rowCount = $query->rowCount();
  if($rowCount === 0) {
    new Response(false, 500, 'There was an issue creating a user account - please try again');
  }

  $lastUserID = $db->lastInsertId();

  $returnData = array();
  $returnData['user_id'] = $lastUserID;
  $returnData['fullname'] = $fullname;
  $returnData['username'] = $username;

  new Response(true, 201, 'User created successfully', $returnData);
 
} catch(PDOException $ex) {
  error_log("Database query error - " . $ex, 0);
  new Response(false, 500, 'There was an issue creating a user account - please try again');
}