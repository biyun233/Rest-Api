<?php
require_once 'db.php';
require_once '../model/Response.php';

try {
  $db = DB::connectDB();
} catch (PDOException $ex) {
  // 0: 发送到PHP的系统日志
  error_log("Connection error - " . $ex, 0);
  new Response(false, 500, 'Database Connection error');
}