<?php

require '../../model/Response.php';
require_once '../../model/Task.php';
require_once '../conn.php';

if(!array_key_exists("taskid", $_GET)) {
  new Response(false, 400, 'Task Id is demanded');
} else {
  $taskid = $_GET['taskid'];

  if($taskid == '' || !is_numeric($taskid)) {
    new Response(false, 400, 'Task Id cannot be blank or must be numeric');
  }

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

    $title_updated = false;
    $description_updated = false;
    $deadline_updated = false;
    $completed_updated = false;

    $queryFields = "";
    if(isset($jsonData->title)) {
      $title_updated = true;
      $queryFields .= "title = :title, ";
    }

    if(isset($jsonData->description)) {
      $description_updated = true;
      $queryFields .= "description = :description, ";
    }

    if(isset($jsonData->deadline)) {
      $deadline_updated = true;
      $queryFields .= "deadline = STR_TO_DATE(:deadline, '%d/%m/%Y %H:%i'), ";
    }

    if(isset($jsonData->completed)) {
      $completed_updated = true;
      $queryFields .= "completed = :completed, ";
    }

    $queryFields = rtrim($queryFields, ", "); // Strip whitespace (or other characters) from the end of a string

    if($title_updated === false && $description_updated === false && $deadline_updated === false && $completed_updated === false) {
      new Response(false, 400, 'No task fields provided');
    }

    $query = $db->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tasks where id = :taskid');
    $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
    $query->execute();

    $rowCount = $query->rowCount();
    if($rowCount === 0) {
      new Response(false, 404, 'No task found to update');
    }

    while($row = $query->fetch(PDO::FETCH_ASSOC)) {
      $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
    }

    $queryString = "update tasks set " . $queryFields . " where id = :taskid";
    $query = $db->prepare($queryString);

    if($title_updated === true) {
      $task->setTitle($jsonData->title);
      $up_title = $task->getTitle();
      $query->bindParam(':title', $up_title, PDO::PARAM_STR);
    }

    if($description_updated === true) {
      $task->setDescription($jsonData->description);
      $up_description = $task->getDescription();
      $query->bindParam(':description', $up_description, PDO::PARAM_STR);
    }

    if($deadline_updated === true) {
      $task->setDeadline($jsonData->deadline);
      $up_deadline = $task->getDeadline();
      $query->bindParam(':deadline', $up_deadline, PDO::PARAM_STR);
    }

    if($completed_updated === true) {
      $task->setCompleted($jsonData->completed);
      $up_completed = $task->getCompleted();
      $query->bindParam(':completed', $up_completed, PDO::PARAM_STR);
    }

    $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
    $query->execute();

    $rowCount = $query->rowCount();
    if($rowCount === 0) {
      new Response(false, 404, 'Task not updated');
    }

    $query = $db->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tasks where id = :taskid');
    $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
    $query->execute();

    $rowCount = $query->rowCount();
    if($rowCount === 0) {
      new Response(false, 404, 'No task found after update');
    }

    $taskArray = array();
    while($row = $query->fetch(PDO::FETCH_ASSOC)) {
      $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
      $taskArray[] = $task->returnTaskAsArray();
    }

    $returnData = array();
    $returnData['rows_returned'] = $rowCount;
    $returnData['tasks'] = $taskArray;
    new Response(true, 200, 'Task updated', $returnData);

  } catch(TaskException $ex) {
    new Response(false, 500, $ex->getMessage());
  } catch(PDOException $ex) {
    error_log('Database query error - ' . $ex, 0);
    new Response(false, 500, 'Failed to update tasks - check your data for error');
  }
}