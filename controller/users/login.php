<?php

require_once '../../model/Response.php';
require_once '../conn.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
  new Response(false, 405, 'Request method not allowed');
}

// for security
sleep(1);

if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
  new Response(false, 400, 'Content type header not set to JSON');
}

$rowPOSTData = file_get_contents('php://input');

if(!$jsonData =json_decode($rowPOSTData)) {
  new Response(false, 400, 'Request body is not valid JSON');
}

if(!isset($jsonData->username) || !isset($jsonData->password)) {
  new Response(false, 400, 'username and password are demanded');
}

if(strlen($jsonData->username) < 1 || strlen($jsonData->username) > 30
|| strlen($jsonData->password) < 1 || strlen($jsonData->password) > 30) {
  new Response(false, 400, 'username and password cannot be blank or greater than 30 characters');
}

try {

  $username = trim($jsonData->username);
  $password = $jsonData->password;

  $query = $db->prepare('select id, fullname, username, password, useractive, loginattempt from users where username = :username');
  $query->bindParam(':username', $username, PDO::PARAM_STR);
  $query->execute();

  $rowCount = $query->rowCount();
  if($rowCount === 0) {
    new Response(false, 401, 'Username or password is incorrect');
  }

  $row = $query->fetch(PDO::FETCH_ASSOC);

  $returned_id = $row['id'];
  $returned_fullname = $row['fullname'];
  $returned_username = $row['username'];
  $returned_password = $row['password'];
  $returned_useractive = $row['useractive'];
  $returned_loginattempt = $row['loginattempt'];

  if($returned_useractive !== 'Y') {
    new Response(false, 401, 'User account not active');
  }

  if($returned_loginattempt >= 3) {
    new Response(false, 401, 'User account is currently locked out');
  }

  if(!password_verify($password, $returned_password)) {
    $query = $db->prepare('update users set loginattempt = loginattempt + 1 where id = :id');
    $query->bindParam(':id', $returned_id, PDO::PARAM_INT);
    $query->execute();
    new Response(false, 401, 'Username or password is incorrect');
  }

  $accesstoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
  $refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());

  $access_token_expiry_seconds = 1200;
  $refresh_token_expiry_seconds = 1209600;

} catch(PDOException $ex) {
  new Response(false, 500, 'There was an issue logging in');
}

try {

  // allow database to roll back if catch errors
  $db->beginTransaction();

  $query = $db->prepare('update users set loginattempt = 0 where id = :id');
  $query->bindParam(':id', $returned_id, PDO::PARAM_INT);
  $query->execute();

  $query = $db->prepare('insert into sessions (userId, accessToken, accessExpiry, refreshToken, refreshExpiry) values (:userid, :accesstoken, date_add(NOW(), INTERVAL :accesstokenexpiryseconds SECOND), :refreshtoken, date_add(NOW(), INTERVAL :refreshtokenexpiryseconds SECOND))');
  $query->bindParam(':userid', $returned_id, PDO::PARAM_INT);
  $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
  $query->bindParam(':accesstokenexpiryseconds', $access_token_expiry_seconds, PDO::PARAM_INT);
  $query->bindParam(':refreshtoken', $refreshtoken, PDO::PARAM_STR);
  $query->bindParam(':refreshtokenexpiryseconds', $refresh_token_expiry_seconds, PDO::PARAM_INT);
  $query->execute();

  $lastSessionId = $db->lastInsertId();

  // save data to database with transaction
  $db->commit();

  $returnData = array();
  $returnData['session_id'] = intval($lastSessionId);
  $returnData['access_token'] = $accesstoken;
  $returnData['access_token_expires_in'] = $access_token_expiry_seconds;
  $returnData['refresh_token'] = $refreshtoken;
  $returnData['refresh_token_expires_in'] = $refresh_token_expiry_seconds;

  new Response(true, 201, 'Log in', $returnData);

} catch(PDOException $ex) {
  $db->rollBack();
  new Response(false, 500, 'There was an issue logging in - please try again');
}