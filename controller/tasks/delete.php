<?php

require '../../model/Response.php';
require_once '../conn.php';

if(array_key_exists("taskid", $_GET)) {
  $taskid = $_GET['taskid'];

  if($taskid == '' || !is_numeric($taskid)) {
    new Response(false, 400, 'Task Id cannot be blank or must be numeric');
  }

  if($_SERVER['REQUEST_METHOD'] !== 'GET') {
    new Response(false, 405, 'Request method not allowed');
  }

  try {

    $query = $db->prepare('delete from tasks where id = :taskid');
    $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
    $query->execute();

    $rowCount = $query->rowCount();
    if($rowCount === 0) {
      new Response(false, 404, 'Task not found');
    }

    new Response(true, 200, 'Task deleted');
  } catch(PDOException $ex) {
    error_log("Database query error - " . $ex, 0);
    new Response(false, 500, 'Failed to get task');
  }

}