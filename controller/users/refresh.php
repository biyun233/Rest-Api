<?php

require_once '../../model/Response.php';
require_once '../conn.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
  new Response(false, 405, 'Request method not allowed');
}

if(!array_key_exists('session_id', $_GET)) {
  new Response(false, 500, 'session_id is missing');
}

$session_id = $_GET['session_id'];

if($session_id === '' || !is_numeric($session_id)) {
  new Response(false, 400, 'Session Id cannot be blank and must be numeric');
}

if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
  new Response(false, 400, 'Content type header not set to JSON');
}

$rowPOSTData = file_get_contents('php://input');

if(!$jsonData =json_decode($rowPOSTData)) {
  new Response(false, 400, 'Request body is not valid JSON');
}

if(!isset($jsonData->refresh_token) || strlen($jsonData->refresh_token) < 1) {
  new Response(false, 400, 'Refresh token cannot be blank');
}

if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1) {
  new Response(false, 401, 'Access token is missing from the header');
}

$accesstoken = $_SERVER['HTTP_AUTHORIZATION'];

try {

  $refreshtoken = $jsonData->refresh_token;

  $query = $db->prepare('select sessions.id as sessionid, sessions.userId as userid, accessToken, refreshToken, useractive, loginattempt, accessExpiry, refreshExpiry from sessions, users where users.id = sessions.userId and sessions.id = :sessionid and sessions.accessToken = :accesstoken and sessions.refreshToken = :refreshtoken');
  $query->bindParam(':sessionid', $session_id, PDO::PARAM_INT);
  $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
  $query->bindParam(':refreshtoken', $refreshtoken, PDO::PARAM_STR);
  $query->execute();

  $rowCount = $query->rowCount();
  if($rowCount === 0) {
    new Response(false, 401, 'Access token or refresh token is incorrect for session id');
  }

  $row = $query->fetch(PDO::FETCH_ASSOC);
  $returned_sessionid = $row['sessionid'];
  $returned_userid = $row['userid'];
  $returned_accesstoken = $row['accessToken'];
  $returned_refreshtoken = $row['refreshToken'];
  $returned_useractive = $row['useractive'];
  $returned_loginattempt = $row['loginattempt'];
  $returned_accessexpiry = $row['accessExpiry'];
  $returned_refreshexpiry = $row['refreshExpiry'];

  if($returned_useractive !== 'Y') {
    new Response(false, 401, 'User account not active');
  }

  if($returned_loginattempt >= 3) {
    new Response(false, 401, 'User account is currently locked out');
  }

  if(strtotime($returned_refreshexpiry) < time()) {
    new Response(false, 401, 'Refresh token has expired - please log in again');
  }

  $accesstoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
  $refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());

  $access_token_expiry_seconds = 1200;
  $refresh_token_expiry_seconds = 1209600;

  $query = $db->prepare('update sessions set accessToken = :accesstoken, accessExpiry = date_add(NOW(), INTERVAL :accesstokenexpiryseconds SECOND), refreshToken = :refreshtoken, refreshExpiry = date_add(NOW(), INTERVAL :refreshtokenexpiryseconds SECOND) where id = :sessionid and userid = :userid and accessToken = :returnedaccesstoken and refreshToken = :returnedrefreshtoken');
  
  $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
  $query->bindParam(':accesstokenexpiryseconds', $access_token_expiry_seconds, PDO::PARAM_INT);
  $query->bindParam(':refreshtoken', $refreshtoken, PDO::PARAM_STR);
  $query->bindParam(':refreshtokenexpiryseconds', $refresh_token_expiry_seconds, PDO::PARAM_INT);
  $query->bindParam(':sessionid', $returned_sessionid, PDO::PARAM_INT);
  $query->bindParam(':userid', $returned_userid, PDO::PARAM_INT);
  $query->bindParam(':returnedaccesstoken', $returned_accesstoken, PDO::PARAM_STR);
  $query->bindParam(':returnedrefreshtoken', $returned_refreshtoken, PDO::PARAM_STR);
  $query->execute();

  $rowCount = $query->rowCount();
  if($rowCount === 0) {
    new Response(false, 401, 'Access token could not be refreshed');
  }

  $returnData = array();
  $returnData['session_id'] = $returned_sessionid;
  $returnData['access_token'] = $accesstoken;
  $returnData['access_token_expires_in'] = $access_token_expiry_seconds;
  $returnData['refresh_token'] = $refreshtoken;
  $returnData['refresh_token_expires_in'] = $refresh_token_expiry_seconds;

  new Response(true, 200, 'Token refreshed', $returnData);

} catch(PDOException $ex) {
  new Response(false, 500, 'There was an issue refreshing access token - please try again');
}