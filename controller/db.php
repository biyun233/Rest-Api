<?php
  class DB {

    private static $dbConnection;

    public static function connectDB() {
      if(self::$dbConnection === null) {
        self::$dbConnection = new PDO('mysql:host=192.168.10.121;dbname=task_db;charset=utf8', 'root', 'Password@1!');
        // when catch exceptions we can roll back if something is not right
        self::$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // emulate prepared statement allows assign the data to the sql rather than hard code the sql itself
        self::$dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      }
      return self::$dbConnection;
    }

    // public static function connectDB() {
    //   if(self::$dbConnection === null) {
    //     self::$dbConnection = new PDO('mysql:host=127.0.0.1:3306;dbname=task_db;charset=utf8', 'root', '123456');
    //     // when catch exceptions we can roll back if something is not right
    //     self::$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //     // emulate prepared statement allows assign the data to the sql rather than hard code the sql itself
    //     self::$dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    //   }
    //   return self::$dbConnection;
    // }

  }
?>