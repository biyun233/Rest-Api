<?php

require '../../model/Response.php';
require_once '../../model/Task.php';
require_once '../conn.php';

if($_SERVER['REQUEST_METHOD'] !== 'GET') {
  new Response(false, 405, 'Request method not allowed');
}

$where = array();
$completed = null;

if(array_key_exists('page', $_GET)) {
    $page = $_GET['page'];
    if($page == '' || !is_numeric($page)) {
      new Response(false, 400, 'Page number cannot be blank and must be numeric');
    }
    $limitPerPage = 5;
} else {
  new Response(false, 400, 'Page number is demanded');
}

if(array_key_exists('completed', $_GET)) {
  $completed = $_GET['completed'];
  if($completed !== 'Y' && $completed !== 'N') {
    new Response(false, 400, 'Completed filter must be Y or N');
  }
  $where[] = 'completed = :completed';
}

try {

  $countSql = 'select count(id) as totalNoOfTasks from tasks';
  if(!empty($where)) {
    $countSql .= " where " . implode(" and ", $where);
  }

  $query = $db->prepare($countSql);
  if($completed) {
    $query->bindParam(':completed', $completed, PDO::PARAM_STR);
  }
 
  $query->execute();

  $row = $query->fetch(PDO::FETCH_ASSOC);

  $tasksCount = intval($row['totalNoOfTasks']);
  $numOfPages = ceil($tasksCount / $limitPerPage);

  if($numOfPages == 0) {
    $numOfPages = 1;
  }

  if($page > $numOfPages || $page <= 0) {
    new Response(false, 404, 'Page not found');
  }

  $offset = $limitPerPage * ($page - 1);

  $sql = 'select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tasks';
  if(!empty($where)) {
    $sql .= ' where ' . implode(' and ', $where);
  }
  $sql .= ' limit :limit offset :offset';
  $query = $db->prepare($sql);

  if($completed) {
    $query->bindParam(':completed', $completed, PDO::PARAM_STR);
  }
  $query->bindParam(':limit', $limitPerPage, PDO::PARAM_INT);
  $query->bindParam(':offset', $offset, PDO::PARAM_INT);
  $query->execute();

  $rowCount = $query->rowCount();
  $taskArray = array();

  while($row = $query->fetch(PDO::FETCH_ASSOC)) {
    $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
    $taskArray[] = $task->returnTaskAsArray();
  }

  $returnData = array();
  $returnData['rows_returned'] = $rowCount;
  $returnData['total_rows'] = $tasksCount;
  $returnData['tasks'] = $taskArray;
  $returnData['total_pages'] = $numOfPages;
  ($page < $numOfPages ? $returnData['has_next_page'] = true : $returnData['has_next_page'] = false);
  ($page > 1 ? $returnData['has_previous_page'] = true : $returnData['has_previous_page'] = false);
  new Response(true, 200, '', $returnData);


} catch(TaskException $ex) {
  new Response(false, 500, $ex->getMessage());
} catch(PDOException $ex) {
  error_log('Database query error - ' . $ex, 0);
  new Response(false, 500, 'Failed to get tasks');
}


