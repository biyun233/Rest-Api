<?php

require_once '../../model/Response.php';
require_once '../conn.php';

if($_SERVER['REQUEST_METHOD'] !== 'GET') {
  new Response(false, 405, 'Request method not allowed');
}

if(!array_key_exists('session_id', $_GET)) {
  new Response(false, 500, 'session_id is missing');
}

$session_id = $_GET['session_id'];

if($session_id === '' || !is_numeric($session_id)) {
  new Response(false, 400, 'Session Id cannot be blank and must be numeric');
}

if(!isset($_SERVER['HTTP_AUTHORIZATION']) || strlen($_SERVER['HTTP_AUTHORIZATION']) < 1) {
  new Response(false, 401, 'Access token is missing from the header');
}

$accesstoken = $_SERVER['HTTP_AUTHORIZATION'];


try {

  $query = $db->prepare('delete from sessions where id = :session_id and accessToken = :accesstoken');
  $query->bindParam(':session_id', $session_id, PDO::PARAM_INT);
  $query->bindParam(':accesstoken', $accesstoken, PDO::PARAM_STR);
  $query->execute();

  $rowCount = $query->rowCount();
  if($rowCount === 0) {
    new Response(false, 400, 'Failed to log out of this session using access token provided');
  }

  $returnData = array();
  $returnData['session_id'] = intval($session_id);

  new Response(true, 200, 'Logged out', $returnData);

} catch(PDOException $ex) {
  new Response(false, 500, 'There was an issue logging out - please try again');
}