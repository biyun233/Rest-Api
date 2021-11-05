<?php

require_once '../model/Response.php';
require_once '../model/Task.php';
require_once 'conn.php';


if(array_key_exists("taskid", $_GET)) {
  $taskid = $_GET['taskid'];

  if($taskid == '' || !is_numeric($taskid)) {
    new Response(false, 400, 'Task Id cannot be blank or must be numeric');
  }

  if($_SERVER['REQUEST_METHOD'] !== 'GET') {
    new Response(false, 405, 'Request method not allowed');
  }

  try {

    $query = $db->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tasks where id = :taskid');
    $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
    $query->execute();

    $rowCount = $query->rowCount();
    if($rowCount === 0) {
      new Response(false, 404, 'Task not found');
    }

    while($row = $query->fetch(PDO::FETCH_ASSOC)) {
      $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
      $taskArray[] = $task->returnTaskAsArray();
    }

    $returnData = array();
    $returnData['rows_returned'] = $rowCount;
    $returnData['tasks'] = $taskArray;
    new Response(true, 200, '', $returnData);

  } catch(TaskException $ex) {
    // catch error when data fails (defined in task model) 
    new Response(false, 500, $ex->getMessage());
    
  } catch(PDOException $ex) {
    // catching database errors
    error_log("Database query error - " . $ex, 0);
    new Response(false, 500, 'Failed to get task');
  }

}