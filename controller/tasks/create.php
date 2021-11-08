<?php

require '../../model/Response.php';
require_once '../../model/Task.php';
require_once '../conn.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
  new Response(false, 405, 'Request method not allowed');
} 

try {

  if($_SERVER['CONTENT_TYPE'] !== 'application/json') {
    new Response(false, 400, 'Content type header is not set to JSON');
  }

  $rowPOSTData = file_get_contents('php://input');
  $jsonData = json_decode($rowPOSTData);
  if(!$jsonData) {
    new Response(false, 400, 'Request body is not valid JSON');
  }

  if(!isset($jsonData->title) || !isset($jsonData->completed)) {
    new Response(false, 400, 'Title field and completed field are mandatory');
  }

  $newTask = new Task(null, $jsonData->title, (isset($jsonData->description) ? $jsonData->description : null), (isset($jsonData->deadline) ? $jsonData->deadline : null), $jsonData->completed);

  $title = $newTask->getTitle();
  $description = $newTask->getDescription();
  $deadline = $newTask->getDeadline();
  $completed = $newTask->getCompleted();

  $query = $db->prepare('insert into tasks (title, description, deadline, completed) values (:title, :description, STR_TO_DATE(:deadline, \'%d/%m/%Y %H:%i\'), :completed)');
  $query->bindParam(':title', $title, PDO::PARAM_STR);
  $query->bindParam(':description', $description, PDO::PARAM_STR);
  $query->bindParam(':deadline', $deadline, PDO::PARAM_STR);
  $query->bindParam(':completed', $completed, PDO::PARAM_STR);
  $query->execute();

  $rowCount = $query->rowCount();
  if($rowCount === 0) {
    new Response(false, 500, 'Failed to create task');
  }

  $lastTaskID = $db->lastInsertId();

  $query = $db->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tasks where id = :taskid');
  $query->bindParam(':taskid', $lastTaskID, PDO::PARAM_INT);
  $query->execute();

  $rowCount = $query->rowCount();
  if($rowCount === 0) {
    new Response(false, 404, 'Failed to retrive task task');
  }

} catch(TaskException $ex) {
  new Response(false, 500, $ex->getMessage());
} catch(PDOException $ex) {
  error_log('Database query error - ' . $ex, 0);
  new Response(false, 500, 'Failed to insert tasks into database - check submitted data for error');
}