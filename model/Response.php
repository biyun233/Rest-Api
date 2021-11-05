<?php
  class Response {
    private $_ok;
    private $_httpStatusCode;
    private $_msg;
    private $_data;
    private $_toCache = false; // cache the response to get rid of loading many times from the server
    private $_responseData = array();

    public function __construct($ok, $httpStatusCode, $msg, $data = null) {
      $this->setOk($ok);
      $this->setHttpStatusCode($httpStatusCode);
      $this->setMsg($msg);
      if($data !== null) {
        $this->setData($data);
      }
      $this->send();
      exit;
    }

    public function setOk($ok) {
      $this->_ok = $ok;
    }

    public function setHttpStatusCode($httpStatusCode) {
      $this->_httpStatusCode = $httpStatusCode;
    }

    public function setMsg($msg) {
      $this->_msg = $msg;
    }

    public function setData($data) {
      $this->_data = $data;
    }

    public function toCache($toCache) {
      $this->_toCache = $toCache;
    }

    public function send() {
      header('Content-type: application/json;charset=utf-8');

      if($this->_toCache) {
        header('Cache-control: max-age=60'); // can cache the response for a maximum of 60 seconds
      } else {
        header('Cache-control: no-cache, no-store');
      }

      if(($this->_ok !== false && $this->_ok !== true) || !is_numeric($this->_httpStatusCode)) {
        http_response_code(500);
        $this->_responseData['code'] = 500;
        $this->_responseData['ok'] = false;
        $this->_responseData['msg'] = 'Response creation error';
      } else {
        http_response_code($this->_httpStatusCode);
        $this->_responseData['code'] = $this->_httpStatusCode;
        $this->_responseData['ok'] = $this->_ok;
        $this->_responseData['msg'] = $this->_msg;
        $this->_responseData['data'] = $this->_data;
      }

      echo json_encode($this->_responseData);
    }
  }
?>